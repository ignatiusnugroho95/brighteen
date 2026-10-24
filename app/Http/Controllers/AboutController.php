<?php

namespace App\Http\Controllers;

use App\Models\ContributorApplication;
use App\Models\Notification;
use App\Models\Page;
use App\Models\User;

require_once __DIR__ . '/../../Models/ContributorApplication.php';
require_once __DIR__ . '/../../Models/Notification.php';
require_once __DIR__ . '/../../Models/Page.php';
require_once __DIR__ . '/../../Models/User.php';

class AboutController
{
    private function checkEditorOrManagementAccess()
    {
        if (!auth()->check()) {
            redirect('login?redirect=about/edit');
        }

        if (!auth()->isEditor() && !auth()->isManagement()) {
            http_response_code(403);
            die(view('errors.403', ['message' => 'Restricted Area: Only Editors and Management can edit the About Us page.']));
        }
    }

    /**
     * Public About Us Page View
     */
    public function show()
    {
        $page = Page::getAbout();

        // Fetch user's contributor application if authenticated
        $myApplication = null;
        if (auth()->check()) {
            $myApplication = ContributorApplication::findLatestByUser(auth()->id());
        }

        // Fetch editorial team & key creators
        $team = [];
        try {
            $stmt = db()->query("
                SELECT u.id, u.name, u.avatar, u.bio, r.name as role_name, r.display_name as role_display_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE r.name IN ('management', 'editor', 'journalist', 'contributor')
                ORDER BY r.id ASC, u.id ASC
                LIMIT 8
            ");
            $team = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $team = [];
        }

        return view('pages.about', [
            'page' => $page,
            'team' => $team,
            'canEdit' => auth()->check() && (auth()->isEditor() || auth()->isManagement()),
            'myApplication' => $myApplication,
            'notice' => $_GET['notice'] ?? null,
            'error' => $_GET['error'] ?? null
        ]);
    }

    /**
     * Handle Contributor Application submission from Registered Readers
     */
    public function applyContributor()
    {
        if (!auth()->check()) {
            redirect('login?redirect=about#get-involved');
        }

        // If user already has writer access (contributor/journalist/editor/management), direct to create
        if (auth()->canCreateArticle()) {
            redirect('articles/create');
        }

        $userId = auth()->id();
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        if (empty($fullName)) {
            $fullName = trim($firstName . ' ' . $lastName);
        }
        $authorName = trim($_POST['author_name'] ?? '');
        $email = trim($_POST['email'] ?? (auth()->user()['email'] ?? ''));
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '');
        $institution = trim($_POST['institution'] ?? '');
        $gradeOrPosition = trim($_POST['grade_or_position'] ?? '');

        if (empty($firstName) || empty($lastName) || empty($birthDate) || empty($institution) || empty($gradeOrPosition)) {
            redirect('about?error=missing_application_fields#get-involved');
        }

        $existing = ContributorApplication::findLatestByUser($userId);
        if ($existing && $existing['status'] === 'pending') {
            ContributorApplication::update($existing['id'], [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => $fullName,
                'author_name' => $authorName ?: null,
                'email' => $email,
                'whatsapp' => $whatsapp ?: null,
                'birth_date' => $birthDate,
                'institution' => $institution,
                'grade_or_position' => $gradeOrPosition,
            ]);
        } else {
            ContributorApplication::create([
                'user_id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => $fullName,
                'author_name' => $authorName ?: null,
                'email' => $email,
                'whatsapp' => $whatsapp ?: null,
                'birth_date' => $birthDate,
                'institution' => $institution,
                'grade_or_position' => $gradeOrPosition,
                'status' => 'pending',
            ]);
        }

        // Notify management team ONLY (journalists & other staff have no business receiving contributor applications)
        try {
            $mgmtUsers = db()->query("
                SELECT u.id 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE r.name = 'management'
            ")->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($mgmtUsers as $m) {
                Notification::send(
                    $m['id'],
                    'application_received',
                    'New Contributor Application',
                    "{$fullName} ({$institution}) applied to become a contributor.",
                    'dashboard/management#readers'
                );
            }
        } catch (\Throwable $e) {}

        redirect('about?notice=contributor_applied#get-involved');
    }

    /**
     * Editor / Management Edit Form
     */
    public function edit()
    {
        $this->checkEditorOrManagementAccess();
        $page = Page::getAbout();

        return view('pages.about_edit', [
            'page' => $page,
            'notice' => $_GET['notice'] ?? null,
            'error' => $_GET['error'] ?? null
        ]);
    }

    /**
     * Save / Update About Us Page
     */
    public function update()
    {
        $this->checkEditorOrManagementAccess();

        $title = trim($_POST['title'] ?? 'About Brighteen');
        $subtitle = trim($_POST['subtitle'] ?? '');
        $heroImage = trim($_POST['hero_image'] ?? '');
        $mission = trim($_POST['mission'] ?? '');
        $manifesto = trim($_POST['manifesto'] ?? '');
        $content = $_POST['content'] ?? '';

        // Process stats
        $stats = [];
        $statVals = $_POST['stat_val'] ?? [];
        $statLabels = $_POST['stat_label'] ?? [];
        $statIcons = $_POST['stat_icon'] ?? [];

        if (is_array($statVals)) {
            for ($i = 0; $i < count($statVals); $i++) {
                $val = trim($statVals[$i] ?? '');
                $lbl = trim($statLabels[$i] ?? '');
                $icn = trim($statIcons[$i] ?? 'verified');
                if (!empty($val) || !empty($lbl)) {
                    $stats[] = [
                        'value' => $val,
                        'label' => $lbl,
                        'icon' => $icn ?: 'verified'
                    ];
                }
            }
        }

        if (empty($stats)) {
            $stats = [
                ["value" => "150K+", "label" => "Monthly Youth Readers", "icon" => "trending_up"],
                ["value" => "2,400+", "label" => "Campus Dispatches", "icon" => "auto_stories"],
                ["value" => "85+", "label" => "Student Reporters", "icon" => "groups"],
                ["value" => "100%", "label" => "Independent & Youth-Led", "icon" => "verified"]
            ];
        }

        Page::updateAbout([
            'title' => $title,
            'subtitle' => $subtitle,
            'hero_image' => $heroImage,
            'mission' => $mission,
            'manifesto' => $manifesto,
            'content' => $content,
            'stats' => $stats
        ], auth()->id());

        redirect('about?notice=about_updated');
    }
}
