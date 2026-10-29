<?php

namespace App\Http\Controllers;

use App\Models\User;
use PDO;

require_once __DIR__ . '/../../Models/User.php';

class ProfileController
{
    /**
     * Display the editable user profile and account page
     */
    public function show()
    {
        if (!auth()->check()) {
            redirect('login?redirect=profile');
        }

        $userId = auth()->id();
        $user = User::findWithRole($userId);

        if (!$user) {
            redirect('logout');
        }

        // Fetch creator/reader stats for top profile badge display
        $articleStats = db()->query("
            SELECT 
                COUNT(*) as total_articles,
                COALESCE(SUM(views_count), 0) as total_views,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_count,
                SUM(CASE WHEN status = 'pending_review' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count
            FROM articles 
            WHERE user_id = " . (int)$userId
        )->fetch(PDO::FETCH_ASSOC);

        $bookmarksCount = (int)db()->query("SELECT COUNT(*) FROM saved_posts WHERE user_id = " . (int)$userId)->fetchColumn();
        $commentsCount = (int)db()->query("SELECT COUNT(*) FROM comments WHERE user_id = " . (int)$userId)->fetchColumn();

        // Check if user has an application
        $app = db()->query("SELECT * FROM contributor_applications WHERE user_id = " . (int)$userId . " ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $notice = $_GET['notice'] ?? null;
        $error = $_GET['error'] ?? null;

        return view('profile.index', [
            'user' => $user,
            'articleStats' => $articleStats,
            'bookmarksCount' => $bookmarksCount,
            'commentsCount' => $commentsCount,
            'application' => $app,
            'notice' => $notice,
            'error' => $error,
            'defaultAvatar' => url('uploads/avatars/default-avatar.svg')
        ]);
    }

    /**
     * Save and update profile & account details
     */
    public function update()
    {
        if (!auth()->check()) {
            redirect('login?redirect=profile');
        }

        $userId = auth()->id();
        $user = User::findWithRole($userId);

        if (!$user) {
            redirect('logout');
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $authorName = trim($_POST['author_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '');
        
        // Institution resolution (Preset vs Others)
        $institutionChoice = trim($_POST['institution_choice'] ?? '');
        $institutionCustom = trim($_POST['institution_custom'] ?? '');
        if ($institutionChoice === 'Others') {
            $institution = !empty($institutionCustom) ? $institutionCustom : 'Brighton JHS Depok';
        } else {
            $institution = !empty($institutionChoice) ? $institutionChoice : 'Brighton JHS Depok';
        }

        // Grade / Position resolution (Preset vs Others)
        $gradeChoice = trim($_POST['grade_choice'] ?? '');
        $gradeCustom = trim($_POST['grade_custom'] ?? '');
        if ($gradeChoice === 'Others') {
            $gradeOrPosition = !empty($gradeCustom) ? $gradeCustom : 'Student';
        } else {
            $gradeOrPosition = !empty($gradeChoice) ? $gradeChoice : '7A';
        }

        $bio = trim($_POST['bio'] ?? '');
        $removeAvatar = !empty($_POST['remove_avatar']);

        // Validation
        if (empty($firstName) || empty($lastName)) {
            redirect('profile?error=name_required');
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirect('profile?error=invalid_email');
        }

        // Check if email already used by someone else
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            redirect('profile?error=email_taken');
        }

        if (empty($birthDate)) {
            redirect('profile?error=birth_date_required');
        }

        // Compute Full Name
        $fullName = trim($firstName . ' ' . $lastName);

        // Handle Avatar File Upload if provided
        $avatarUrl = null;
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $file = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            
            // Validate MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedTypes)) {
                redirect('profile?error=invalid_image_type');
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                redirect('profile?error=image_too_large');
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $ext = 'jpg';
            }

            $filename = 'avatar_' . $userId . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            
            // Project root is c:\xampp\htdocs\brighteen
            $projectRoot = realpath(__DIR__ . '/../../..');
            $uploadDir = $projectRoot . '/uploads/avatars/';
            $publicUploadDir = $projectRoot . '/public/uploads/avatars/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            if (!is_dir($publicUploadDir)) {
                mkdir($publicUploadDir, 0777, true);
            }

            $destination = $uploadDir . $filename;
            $publicDestination = $publicUploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Ensure mirrored to both root uploads and public uploads
                copy($destination, $publicDestination);
                $avatarUrl = url('uploads/avatars/' . $filename);
            } else {
                redirect('profile?error=upload_failed');
            }
        }

        // Optional Password Update
        $newPassword = $_POST['new_password'] ?? '';
        $currentPassword = $_POST['current_password'] ?? '';
        $passwordSql = "";
        $passwordParams = [];

        if (!empty($newPassword)) {
            if (empty($currentPassword) || !password_verify($currentPassword, $user['password'])) {
                redirect('profile?error=incorrect_current_password');
            }
            if (strlen($newPassword) < 6) {
                redirect('profile?error=password_too_short');
            }
            $passwordSql = ", password = ?";
            $passwordParams[] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        // Prepare User Update Statement
        $avatarSql = "";
        $avatarParams = [];
        if ($avatarUrl !== null) {
            $avatarSql = ", avatar = ?";
            $avatarParams[] = $avatarUrl;
        } elseif ($removeAvatar) {
            // Set explicitly to default template avatar
            $defaultAvatarUrl = url('uploads/avatars/default-avatar.svg');
            $avatarSql = ", avatar = ?";
            $avatarParams[] = $defaultAvatarUrl;
        }

        $sql = "
            UPDATE users SET 
                name = ?,
                first_name = ?,
                last_name = ?,
                author_name = ?,
                email = ?,
                whatsapp = ?,
                birth_date = ?,
                institution = ?,
                grade_or_position = ?,
                bio = ?
                {$avatarSql}
                {$passwordSql},
                updated_at = NOW()
            WHERE id = ?
        ";

        $params = array_merge(
            [
                $fullName,
                $firstName,
                $lastName,
                $authorName ?: null,
                $email,
                $whatsapp ?: null,
                $birthDate,
                $institution,
                $gradeOrPosition,
                $bio ?: null
            ],
            $avatarParams,
            $passwordParams,
            [$userId]
        );

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        // Synchronize with contributor_applications if user has one
        $stmtApp = db()->prepare("
            UPDATE contributor_applications SET 
                first_name = ?,
                last_name = ?,
                full_name = ?,
                author_name = ?,
                email = ?,
                whatsapp = ?,
                birth_date = ?,
                institution = ?,
                grade_or_position = ?
            WHERE user_id = ?
        ");
        $stmtApp->execute([
            $firstName,
            $lastName,
            $fullName,
            $authorName ?: null,
            $email,
            $whatsapp ?: null,
            $birthDate,
            $institution,
            $gradeOrPosition,
            $userId
        ]);

        redirect('profile?notice=saved');
    }
}

