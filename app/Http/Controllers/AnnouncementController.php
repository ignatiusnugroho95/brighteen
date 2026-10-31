<?php

namespace App\Http\Controllers;

use App\Models\Announcement;

require_once __DIR__ . '/../../Models/Announcement.php';

class AnnouncementController
{
    private function checkAccess()
    {
        if (!auth()->check()) {
            redirect('login?redirect=dashboard/announcement');
        }

        if (!auth()->isEditor() && !auth()->isManagement()) {
            http_response_code(403);
            die(view('errors.403', ['message' => 'Restricted Area: Editor or Management permissions required to manage announcements.']));
        }
    }

    public function index()
    {
        $this->checkAccess();

        Announcement::ensureTable();
        $announcements = Announcement::allWithCreator();
        $activeAnnouncements = Announcement::getActive();
        $activeCount = count($activeAnnouncements);

        return view('dashboard.announcement', [
            'announcements' => $announcements,
            'activeAnnouncements' => $activeAnnouncements,
            'activeCount' => $activeCount,
            'maxActive' => Announcement::MAX_ACTIVE,
            'maxTextLength' => Announcement::MAX_TEXT_LENGTH,
            'maxLinkUrlLength' => Announcement::MAX_LINK_URL_LENGTH,
            'maxLinkLabelLength' => Announcement::MAX_LINK_LABEL_LENGTH,
            'notice' => $_GET['notice'] ?? null,
            'error' => $_GET['error'] ?? null,
        ]);
    }

    public function store()
    {
        $this->checkAccess();
        Announcement::ensureTable();

        $emoji = trim($_POST['emoji'] ?? '📢');
        if ($emoji === '') {
            $emoji = '📢';
        }
        $emoji = mb_substr($emoji, 0, 8);

        $text = trim($_POST['text'] ?? '');
        if ($text === '') {
            redirect('dashboard/announcement?error=empty_text');
        }
        $text = mb_substr($text, 0, Announcement::MAX_TEXT_LENGTH);

        $linkUrl = trim($_POST['link_url'] ?? '');
        $linkLabel = trim($_POST['link_label'] ?? '');

        if ($linkUrl !== '') {
            $linkUrl = mb_substr($linkUrl, 0, Announcement::MAX_LINK_URL_LENGTH);
            $linkLabel = $linkLabel !== '' ? mb_substr($linkLabel, 0, Announcement::MAX_LINK_LABEL_LENGTH) : 'Open Link';
        } else {
            $linkUrl = null;
            $linkLabel = null;
        }

        $wantsActive = isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1;
        $currentActive = Announcement::activeCount();

        if ($wantsActive === 1 && $currentActive >= Announcement::MAX_ACTIVE) {
            redirect('dashboard/announcement?error=max_active_reached');
        }

        $isActive = ($wantsActive === 1 && $currentActive < Announcement::MAX_ACTIVE) ? 1 : 0;

        Announcement::create([
            'emoji' => $emoji,
            'text' => $text,
            'link_url' => $linkUrl,
            'link_label' => $linkLabel,
            'is_active' => $isActive,
            'sort_order' => $currentActive + 1,
            'created_by' => auth()->id(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        redirect('dashboard/announcement?notice=created');
    }

    public function update($id)
    {
        $this->checkAccess();
        Announcement::ensureTable();

        $id = (int) $id;
        $existing = Announcement::find($id);
        if (!$existing) {
            redirect('dashboard/announcement?error=not_found');
        }

        $emoji = trim($_POST['emoji'] ?? '📢');
        if ($emoji === '') {
            $emoji = '📢';
        }
        $emoji = mb_substr($emoji, 0, 8);

        $text = trim($_POST['text'] ?? '');
        if ($text === '') {
            redirect('dashboard/announcement?error=empty_text');
        }
        $text = mb_substr($text, 0, Announcement::MAX_TEXT_LENGTH);

        $linkUrl = trim($_POST['link_url'] ?? '');
        $linkLabel = trim($_POST['link_label'] ?? '');

        if ($linkUrl !== '') {
            $linkUrl = mb_substr($linkUrl, 0, Announcement::MAX_LINK_URL_LENGTH);
            $linkLabel = $linkLabel !== '' ? mb_substr($linkLabel, 0, Announcement::MAX_LINK_LABEL_LENGTH) : 'Open Link';
        } else {
            $linkUrl = null;
            $linkLabel = null;
        }

        $wantsActive = isset($_POST['is_active']) ? (int) $_POST['is_active'] : (int) $existing['is_active'];
        $otherActiveCount = Announcement::activeCount($id);

        if ($wantsActive === 1 && $otherActiveCount >= Announcement::MAX_ACTIVE) {
            redirect('dashboard/announcement?error=max_active_reached');
        }

        Announcement::update($id, [
            'emoji' => $emoji,
            'text' => $text,
            'link_url' => $linkUrl,
            'link_label' => $linkLabel,
            'is_active' => $wantsActive ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        redirect('dashboard/announcement?notice=updated');
    }

    public function toggle($id)
    {
        $this->checkAccess();
        Announcement::ensureTable();

        $id = (int) $id;
        $existing = Announcement::find($id);
        if (!$existing) {
            redirect('dashboard/announcement?error=not_found');
        }

        $newStatus = ((int) $existing['is_active'] === 1) ? 0 : 1;
        if ($newStatus === 1 && Announcement::activeCount($id) >= Announcement::MAX_ACTIVE) {
            redirect('dashboard/announcement?error=max_active_reached');
        }

        Announcement::update($id, [
            'is_active' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        redirect('dashboard/announcement?notice=' . ($newStatus ? 'activated' : 'deactivated'));
    }

    public function destroy($id)
    {
        $this->checkAccess();
        Announcement::ensureTable();

        $id = (int) $id;
        $existing = Announcement::find($id);
        if (!$existing) {
            redirect('dashboard/announcement?error=not_found');
        }

        Announcement::delete($id);
        redirect('dashboard/announcement?notice=deleted');
    }
}
