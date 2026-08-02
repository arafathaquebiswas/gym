<?php

final class NotificationAdminController extends AdminController
{
    protected string $moduleKey = ''; // Every authenticated admin user owns their own notifications

    /* ──────────────────────────────────────────────────────────────
       Valid sound options served to the browser
    ────────────────────────────────────────────────────────────── */
    private const SOUND_OPTIONS = [
        'classic_ding' => 'Classic Ding',
        'soft_pop'     => 'Soft Pop',
        'bell'         => 'Bell',
        'chime'        => 'Chime',
        'silent'       => 'Silent',
    ];

    /* ──────────────────────────────────────────────────────────────
       GET /admin/notifications  — History page
    ────────────────────────────────────────────────────────────── */
    public function index(): void
    {
        $userId  = (int) Auth::user()['id'];
        $page    = max(1, (int) $this->input('page', '1'));
        $filters = [
            'filter'    => $this->input('filter',    'all'),
            'category'  => $this->input('category',  ''),
            'search'    => $this->input('search',    ''),
            'date_from' => $this->input('date_from', ''),
            'date_to'   => $this->input('date_to',   ''),
        ];

        $result       = (new Notification())->paginateForUser($userId, $page, 25, $filters);
        $settingModel = new Setting();
        $soundChoice  = $settingModel->get("user_notif_sound_choice_{$userId}", 'classic_ding');
        $soundEnabled = $settingModel->get("user_notif_sound_{$userId}", '1') !== '0';
        $desktopPref  = $settingModel->get("user_notif_desktop_{$userId}", '0');

        // Per-category opt-in/out preferences
        $catPrefs = [];
        foreach (array_keys(Notification::CATEGORIES) as $cat) {
            $catPrefs[$cat] = $settingModel->get("user_notif_cat_{$userId}_{$cat}", '1') !== '0';
        }

        $this->adminView('notifications/index', [
            'pageTitle'    => 'Notification Center',
            'notifications'=> $result['items'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'totalPages'   => $result['totalPages'],
            'filters'      => $filters,
            'categories'   => Notification::CATEGORIES,
            'soundEnabled' => $soundEnabled,
            'soundChoice'  => $soundChoice,
            'soundOptions' => self::SOUND_OPTIONS,
            'desktopPref'  => $desktopPref,
            'catPrefs'     => $catPrefs,
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
       GET /admin/notifications/latest  — AJAX polling endpoint
    ────────────────────────────────────────────────────────────── */
    public function latest(): void
    {
        header('Content-Type: application/json');
        $userId = (int) (Auth::user()['id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['unread_count' => 0, 'notifications' => []]);
            exit;
        }

        $model        = new Notification();
        $settingModel = new Setting();
        $unreadCount  = $model->unreadCount($userId);
        $items        = $model->latestForUser($userId, 10);
        $soundEnabled = $settingModel->get("user_notif_sound_{$userId}", '1') !== '0';
        $soundChoice  = $settingModel->get("user_notif_sound_choice_{$userId}", 'classic_ding');
        $desktopPref  = $settingModel->get("user_notif_desktop_{$userId}", '0') === '1';

        echo json_encode([
            'unread_count'  => $unreadCount,
            'sound_enabled' => $soundEnabled,
            'sound_choice'  => $soundChoice,
            'desktop_notif' => $desktopPref,
            'notifications' => array_map(function ($n) {
                $cat = Notification::CATEGORIES[$n['category']] ?? Notification::CATEGORIES['system'];
                return [
                    'id'       => (int) $n['id'],
                    'title'    => $n['title'],
                    'message'  => $n['message'],
                    'link'     => $n['link'],
                    'category' => $n['category'],
                    'icon'     => $cat['icon'],
                    'color'    => $cat['color'],
                    'is_read'  => (bool) $n['is_read'],
                    'created_at' => date('d M, h:i A', strtotime($n['created_at'])),
                ];
            }, $items),
        ]);
        exit;
    }

    /* ──────────────────────────────────────────────────────────────
       GET /admin/notifications/unread-count
    ────────────────────────────────────────────────────────────── */
    public function unreadCount(): void
    {
        header('Content-Type: application/json');
        $userId = (int) (Auth::user()['id'] ?? 0);
        $count  = $userId > 0 ? (new Notification())->unreadCount($userId) : 0;
        echo json_encode(['unread_count' => $count]);
        exit;
    }

    /* ──────────────────────────────────────────────────────────────
       POST /admin/notifications/{id}/read
    ────────────────────────────────────────────────────────────── */
    public function markAsRead(string $id): void
    {
        Security::requireCsrf();
        $userId = (int) Auth::user()['id'];
        (new Notification())->markAsRead((int) $id, $userId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /* ──────────────────────────────────────────────────────────────
       POST /admin/notifications/mark-all-read
    ────────────────────────────────────────────────────────────── */
    public function markAllAsRead(): void
    {
        Security::requireCsrf();
        $userId   = (int) Auth::user()['id'];
        $category = $this->input('category', '');
        (new Notification())->markAllAsRead($userId, $category);

        if ($this->input('ajax') === '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }

        flash('success', 'All notifications marked as read.');
        redirect('admin/notifications' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
    }

    /* ──────────────────────────────────────────────────────────────
       POST /admin/notifications/toggle-sound
    ────────────────────────────────────────────────────────────── */
    public function toggleSound(): void
    {
        Security::requireCsrf();
        $userId  = (int) Auth::user()['id'];
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

    /* ──────────────────────────────────────────────────────────────
       POST /admin/notifications/sound-choice
    ────────────────────────────────────────────────────────────── */
    public function soundChoice(): void
    {
        Security::requireCsrf();
        $userId = (int) Auth::user()['id'];
        $choice = $this->input('choice', 'classic_ding');
        $valid  = ['classic_ding', 'soft_pop', 'bell', 'chime', 'silent'];
        if (!in_array($choice, $valid, true)) $choice = 'classic_ding';
        (new Setting())->set("user_notif_sound_choice_{$userId}", $choice);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'choice' => $choice]);
        exit;
    }

    /* ──────────────────────────────────────────────────────────────
       POST /admin/notifications/desktop-pref
    ────────────────────────────────────────────────────────────── */
    public function desktopPref(): void
    {
        Security::requireCsrf();
        $userId  = (int) Auth::user()['id'];
        $enabled = $this->input('enabled') === '1' ? '1' : '0';
        (new Setting())->set("user_notif_desktop_{$userId}", $enabled);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'desktop_notif' => $enabled === '1']);
        exit;
    }

    /* ──────────────────────────────────────────────────────────────
       POST /admin/notifications/category-pref
    ────────────────────────────────────────────────────────────── */
    public function categoryPref(): void
    {
        Security::requireCsrf();
        $userId  = (int) Auth::user()['id'];
        $cat     = $this->input('category', '');
        $enabled = $this->input('enabled') === '0' ? '0' : '1';

        if (!array_key_exists($cat, Notification::CATEGORIES)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid category']);
            exit;
        }

        (new Setting())->set("user_notif_cat_{$userId}_{$cat}", $enabled);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /* ──────────────────────────────────────────────────────────────
       POST /admin/notifications/cleanup
       Deletes notifications older than 90 days for every user.
       Only super_admin / main_admin can trigger this.
    ────────────────────────────────────────────────────────────── */
    public function cleanup(): void
    {
        Security::requireCsrf();
        if (!Auth::hasRole('main_admin', 'super_admin', 'admin')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
            exit;
        }
        $deleted = Notification::cleanup(90);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'deleted' => $deleted]);
        exit;
    }
}
