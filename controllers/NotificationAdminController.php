<?php

final class NotificationAdminController extends AdminController
{
    protected string $moduleKey = ''; // All admin staff have access to their own notifications

    public function index(): void
    {
        $userId = (int) Auth::user()['id'];
        $page = max(1, (int) $this->input('page', '1'));
        $result = (new Notification())->paginateForUser($userId, $page);

        $this->adminView('notifications/index', [
            'pageTitle' => 'Notifications History',
            'notifications' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    public function unreadCount(): void
    {
        header('Content-Type: application/json');
        $userId = (int) (Auth::user()['id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['unread_count' => 0]);
            exit;
        }

        $count = (new Notification())->unreadCount($userId);
        echo json_encode(['unread_count' => $count]);
        exit;
    }

    public function latest(): void
    {
        header('Content-Type: application/json');
        $userId = (int) (Auth::user()['id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['unread_count' => 0, 'notifications' => []]);
            exit;
        }

        $notificationModel = new Notification();
        $unreadCount = $notificationModel->unreadCount($userId);
        $items = $notificationModel->latestForUser($userId, 8);
        $soundEnabled = (new Setting())->get("user_notif_sound_{$userId}", '1') !== '0';

        echo json_encode([
            'unread_count' => $unreadCount,
            'sound_enabled' => $soundEnabled,
            'notifications' => array_map(function ($n) {
                return [
                    'id' => (int) $n['id'],
                    'title' => $n['title'],
                    'message' => $n['message'],
                    'link' => $n['link'],
                    'is_read' => (bool) $n['is_read'],
                    'created_at' => format_date($n['created_at']) . ' ' . date('h:i A', strtotime($n['created_at'])),
                ];
            }, $items),
        ]);
        exit;
    }

    public function markAsRead(string $id): void
    {
        Security::requireCsrf();
        $userId = (int) Auth::user()['id'];
        (new Notification())->markAsRead((int) $id, $userId);

        if ($this->input('ajax') === '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        flash('success', 'Notification marked as read.');
        redirect('admin/notifications');
    }

    public function markAllAsRead(): void
    {
        Security::requireCsrf();
        $userId = (int) Auth::user()['id'];
        (new Notification())->markAllAsRead($userId);

        if ($this->input('ajax') === '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        flash('success', 'All notifications marked as read.');
        redirect('admin/notifications');
    }

    public function toggleSound(): void
    {
        Security::requireCsrf();
        $userId = (int) Auth::user()['id'];
        $enabled = $this->input('enabled') === '0' ? '0' : '1';
        (new Setting())->set("user_notif_sound_{$userId}", $enabled);

        if ($this->input('ajax') === '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'sound_enabled' => $enabled === '1']);
            exit;
        }

        flash('success', $enabled === '1' ? 'Notification sound enabled.' : 'Notification sound disabled.');
        redirect('admin/notifications');
    }
}
