<?php

namespace App\Models;

use PDO;

require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/Notification.php';

class Assignment extends Model
{
    protected static $table = 'assignments';

    public static function allDetailed($where = '1=1', $params = [])
    {
        $sql = "
            SELECT 
                a.*,
                c.name as category_name,
                c.slug as category_slug,
                c.color_code as category_color,
                c.icon as category_icon,
                u_j.name as journalist_name,
                u_j.email as journalist_email,
                u_j.avatar as journalist_avatar,
                u_e.name as editor_name,
                u_e.email as editor_email,
                u_e.avatar as editor_avatar,
                art.title as article_title,
                art.slug as article_slug,
                art.status as article_status,
                art.published_at as article_published_at
            FROM assignments a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN users u_j ON a.assigned_to_user_id = u_j.id
            LEFT JOIN users u_e ON a.assigned_by_user_id = u_e.id
            LEFT JOIN articles art ON a.article_id = art.id
            WHERE {$where}
            ORDER BY a.submission_deadline ASC, a.created_at DESC
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $now = time();
        foreach ($rows as &$row) {
            $deadlineTs = strtotime($row['submission_deadline']);
            $isOverdue = ($deadlineTs < $now);

            // Compute visual/operational status
            if ($row['status'] === 'pending' && $isOverdue) {
                $row['effective_status'] = 'expired';
            } elseif ($row['article_status'] === 'published') {
                $row['effective_status'] = 'approved';
            } else {
                $row['effective_status'] = $row['status'];
            }
            $row['is_overdue'] = $isOverdue;
        }

        return $rows;
    }

    public static function findDetailed($id)
    {
        $results = self::allDetailed('a.id = ?', [(int)$id]);
        return !empty($results) ? $results[0] : null;
    }

    public static function getForJournalist($journalistId)
    {
        return self::allDetailed('a.assigned_to_user_id = ?', [(int)$journalistId]);
    }

    public static function getActivePendingForJournalist($journalistId)
    {
        $sql = "
            SELECT 
                a.*,
                c.name as category_name,
                c.color_code as category_color
            FROM assignments a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.assigned_to_user_id = ? 
              AND a.status IN ('pending', 'expired')
            ORDER BY a.submission_deadline ASC
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute([(int)$journalistId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createWithNotification(array $data)
    {
        $assignmentId = self::create($data);

        // Notify assigned journalist immediately
        $journalistId = (int)$data['assigned_to_user_id'];
        $editor = User::find($data['assigned_by_user_id']);
        $editorName = $editor ? $editor['name'] : 'Editor Desk';
        $deadlineFormatted = date('M j, Y \a\t H:i', strtotime($data['submission_deadline']));

        Notification::send(
            $journalistId,
            'assignment_created',
            "New Assignment: {$data['topic']}",
            "Editor {$editorName} has assigned you to cover '{$data['topic']}'. Submission deadline is {$deadlineFormatted}.",
            "dashboard/assignments"
        );

        return $assignmentId;
    }

    public static function markTurnedIn($assignmentId, $articleId)
    {
        $assignment = self::find($assignmentId);
        if (!$assignment) return false;

        self::update($assignmentId, [
            'status' => 'turned_in',
            'article_id' => $articleId,
            'turned_in_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Notify editor that journalist turned in the story
        $editorId = (int)$assignment['assigned_by_user_id'];
        $journalist = User::find($assignment['assigned_to_user_id']);
        $journalistName = $journalist ? $journalist['name'] : 'Journalist';

        Notification::send(
            $editorId,
            'assignment_turned_in',
            "Story Turned In: {$assignment['topic']}",
            "{$journalistName} has submitted the article for '{$assignment['topic']}'! It is now waiting in your Editorial Review Queue.",
            "dashboard/editorial"
        );

        return true;
    }

    public static function checkAndGenerateReminders()
    {
        // 1. Check Deadline Reminders for Pending Assignments (Day of Deadline or Overdue)
        $stmt = db()->query("
            SELECT a.*, u_j.name as journalist_name, u_e.name as editor_name
            FROM assignments a
            LEFT JOIN users u_j ON a.assigned_to_user_id = u_j.id
            LEFT JOIN users u_e ON a.assigned_by_user_id = u_e.id
            WHERE a.status = 'pending' 
              AND DATE(a.submission_deadline) <= CURDATE()
        ");
        $pendingDeadlines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pendingDeadlines as $asg) {
            $linkJ = "dashboard/assignments";
            $linkE = "dashboard/editorial";
            $isToday = (date('Y-m-d', strtotime($asg['submission_deadline'])) === date('Y-m-d'));
            $statusLabel = $isToday ? "due TODAY" : "OVERDUE (Expired)";

            // Reminder for Journalist
            if (!Notification::existsToday($asg['assigned_to_user_id'], 'deadline_reminder_journalist', $linkJ . '#' . $asg['id'])) {
                Notification::send(
                    $asg['assigned_to_user_id'],
                    'deadline_reminder_journalist',
                    "Submission Deadline Reminder: {$asg['topic']}",
                    "Your assignment '{$asg['topic']}' is {$statusLabel}. Please complete your draft and turn it in.",
                    $linkJ . '#' . $asg['id']
                );
            }

            // Reminder for Editor
            if (!Notification::existsToday($asg['assigned_by_user_id'], 'deadline_reminder_editor', $linkE . '#assignment-' . $asg['id'])) {
                Notification::send(
                    $asg['assigned_by_user_id'],
                    'deadline_reminder_editor',
                    "Journalist Deadline Alert: {$asg['topic']}",
                    "Assignment '{$asg['topic']}' assigned to {$asg['journalist_name']} is {$statusLabel} and has not been turned in yet.",
                    $linkE . '#assignment-' . $asg['id']
                );
            }
        }

        // 2. Check Publishing Schedule Reminders for Editor (Day of Publishing)
        $stmt = db()->query("
            SELECT a.*, art.status as article_status
            FROM assignments a
            LEFT JOIN articles art ON a.article_id = art.id
            WHERE DATE(a.publishing_schedule) <= CURDATE()
              AND (a.article_id IS NULL OR art.status != 'published')
        ");
        $pendingPublishes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pendingPublishes as $asg) {
            $linkE = "dashboard/editorial";
            if (!Notification::existsToday($asg['assigned_by_user_id'], 'publishing_reminder_editor', $linkE . '#pub-' . $asg['id'])) {
                $pubDateStr = date('M j, Y', strtotime($asg['publishing_schedule']));
                Notification::send(
                    $asg['assigned_by_user_id'],
                    'publishing_reminder_editor',
                    "Publishing Schedule Alert: {$asg['topic']}",
                    "Story '{$asg['topic']}' was scheduled for publication on {$pubDateStr}, but is still unpublished. Check review status.",
                    $linkE . '#pub-' . $asg['id']
                );
            }
        }

        // 3. Contributor Applications Pending Reminder for Management ONLY
        // Note: Journalists and other roles have no business receiving contributor application reminders.
        $pendingAppsCount = (int)db()->query("SELECT COUNT(*) FROM contributor_applications WHERE status = 'pending'")->fetchColumn();
        if ($pendingAppsCount > 0) {
            $mgmtUsers = db()->query("
                SELECT u.id 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE r.name = 'management'
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($mgmtUsers as $m) {
                $linkM = "dashboard/management#readers";
                if (!Notification::existsToday($m['id'], 'contributor_application_reminder', $linkM)) {
                    Notification::send(
                        $m['id'],
                        'contributor_application_reminder',
                        "Contributor Applications Pending Review ({$pendingAppsCount})",
                        "There are {$pendingAppsCount} pending contributor application(s) awaiting review by Management.",
                        $linkM
                    );
                }
            }
        }
    }
}
