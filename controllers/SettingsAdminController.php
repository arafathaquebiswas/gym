<?php

final class SettingsAdminController extends AdminController
{
    /** These match the exact keys already read by the public site (footer, home, contact, meta tags). */
    private const KEYS = [
        'gym_name', 'gym_tagline', 'gym_phone', 'gym_email', 'gym_address',
        'facebook_url', 'instagram_url', 'youtube_url', 'tiktok_url', 'whatsapp_number', 'google_map_embed',
        'business_hours_weekday', 'business_hours_friday',
        'currency_symbol', 'late_fine_amount', 'lost_locker_fine', 'default_trainer_fee',
        'membership_grace_days', 'auto_expire_memberships',
        'shipping_flat_rate', 'free_shipping_min_amount', 'tax_percent', 'delivery_estimate_text',
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_email', 'smtp_from_name',
        // Feature flags — every one of these gates a whole module/section; see core/Feature.php.
        'feature_trainer_module', 'feature_trainer_fee', 'feature_trainer_booking', 'feature_trainer_display',
        'feature_store', 'feature_blog', 'feature_gallery', 'feature_wishlist', 'feature_reviews',
        'feature_coupons', 'feature_guest_checkout', 'feature_preorder', 'feature_offers',
        'feature_contact_form', 'feature_membership_sales',
        'shipping_enabled', 'tax_enabled', 'tax_applies_to_membership', 'tax_applies_to_trainer_fee',
        'free_trial_enabled', 'free_trial_title', 'free_trial_subtitle', 'free_trial_button_text',
        'free_trial_button_link', 'free_trial_start_date', 'free_trial_end_date', 'free_trial_max_registrations',
        'feature_delivery', 'feature_pickup',
        'discount_stacking_enabled',
    ];

    public function index(): void
    {
        $settingModel = new Setting();
        $settings = [];
        foreach (self::KEYS as $key) {
            $settings[$key] = $settingModel->get($key);
        }
        $settings['gym_logo'] = $settingModel->get('gym_logo');
        $settings['free_trial_background_image'] = $settingModel->get('free_trial_background_image');

        $this->adminView('settings/index', [
            'pageTitle' => 'Settings',
            'settings' => $settings,
        ]);
    }

    public function update(): void
    {
        Security::requireCsrf();

        $settingModel = new Setting();
        $pairs = [];
        foreach (self::KEYS as $key) {
            if ($key === 'smtp_pass' && $this->rawInput('smtp_pass') === '') {
                continue; // Leave the stored password untouched if the field was left blank.
            }
            $pairs[$key] = $key === 'smtp_pass' ? $this->rawInput($key) : $this->input($key);
        }

        $logoPath = Upload::handle($_FILES['gym_logo'] ?? [], 'settings');
        if ($logoPath) {
            $pairs['gym_logo'] = $logoPath;
        }

        $freeTrialBgPath = Upload::handle($_FILES['free_trial_background_image'] ?? [], 'settings');
        if ($freeTrialBgPath) {
            $pairs['free_trial_background_image'] = $freeTrialBgPath;
        }

        // The public footer (views/partials/footer.php) reads one combined "business_hours"
        // string — the two-field form above is friendlier to edit, so combine on save.
        $weekday = $pairs['business_hours_weekday'] ?? '';
        $friday = $pairs['business_hours_friday'] ?? '';
        if ($weekday !== '' || $friday !== '') {
            $pairs['business_hours'] = trim("Sat–Thu: $weekday | Fri: $friday", ' |');
        }

        $settingModel->setMany($pairs);
        $this->logActivity('settings_updated', 'Updated gym settings');

        flash('success', 'Settings saved successfully.');
        redirect('admin/settings');
    }

    public function freeTrialRegistrations(): void
    {
        $page = max(1, (int) $this->input('page', '1'));
        $result = (new FreeTrialRegistration())->paginateForAdmin($page);

        $this->adminView('settings/free-trial-registrations', [
            'pageTitle' => 'Free Trial Registrations',
            'registrations' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    public function backup(): void
    {
        $sql = Backup::export();
        $filename = 'powersurge-backup-' . date('Y-m-d-His') . '.sql';

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
    }

    public function restore(): void
    {
        Security::requireCsrf();

        if ($this->input('confirm_phrase') !== 'RESTORE') {
            flash('danger', 'Please type RESTORE exactly to confirm this destructive action.');
            redirect('admin/settings');
        }

        $file = $_FILES['backup_file'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('danger', 'Please choose a valid .sql backup file to restore.');
            redirect('admin/settings');
        }

        $sql = file_get_contents($file['tmp_name']);
        if ($sql === false || trim($sql) === '') {
            flash('danger', 'That backup file could not be read.');
            redirect('admin/settings');
        }

        // Always snapshot the current state immediately before overwriting it.
        $backupDir = BASE_PATH . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        file_put_contents($backupDir . '/pre-restore-' . date('Y-m-d-His') . '.sql', Backup::export());

        try {
            Backup::import($sql);
        } catch (Throwable $e) {
            flash('danger', 'Restore failed: ' . $e->getMessage() . '. A pre-restore backup was saved before this attempt.');
            redirect('admin/settings');
        }

        $this->logActivity('database_restored', 'Restored database from an uploaded backup file');
        flash('success', 'Database restored successfully. A safety backup of the previous state was saved on the server.');
        redirect('admin/settings');
    }
}
