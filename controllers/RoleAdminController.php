<?php

/**
 * Role & Permission Management — deliberately NOT gated through the generic Permission system
 * (no $moduleKey): this is the tool that controls that system, so its own access rules are
 * hard-coded in PHP. Main Admin manages everyone (Super Admins + Staff); Super Admin gets a
 * scoped-down section for Staff only — never another Super Admin, never Main Admin, never
 * module locks. A Super Admin also can't grant a Staff member access to a module they
 * themselves can't reach (see grantableModuleKeys()) — that's the actual anti-escalation
 * guard, not just hiding buttons in the UI.
 */
final class RoleAdminController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (!Auth::hasRole('main_admin', 'super_admin')) {
            http_response_code(403);
            die('403 - Permission Denied');
        }
    }

    public function index(): void
    {
        $userModel = new User();
        $isMainAdmin = Auth::hasRole('main_admin');

        $data = [
            'pageTitle' => 'Role Management',
            'isMainAdmin' => $isMainAdmin,
            'staff' => $userModel->findByRole('staff'),
            'modules' => Modules::ALL,
        ];

        if ($isMainAdmin) {
            $data['mainAdmin'] = $userModel->findByRole('main_admin')[0] ?? null;
            $data['superAdmins'] = $userModel->findByRole('super_admin');
        }

        $this->adminView('roles/index', $data);
    }

    // ---------------------------------------------------------------- Staff (Main Admin + Super Admin)

    public function createStaff(): void
    {
        $this->adminView('roles/form', ['pageTitle' => 'Add Staff', 'targetRole' => 'staff', 'member' => null]);
    }

    public function storeStaff(): void
    {
        $this->storeUser('staff', 'admin/roles/staff/create');
    }

    public function editStaff(string $id): void
    {
        $this->editUser($id, 'staff');
    }

    public function updateStaff(string $id): void
    {
        $this->updateUser($id, 'staff', 'admin/roles/staff/' . $id . '/edit');
    }

    public function toggleStaffStatus(string $id): void
    {
        $this->toggleSuspend($id, 'staff');
    }

    public function deleteStaff(string $id): void
    {
        $this->deleteUser($id, 'staff');
    }

    // ---------------------------------------------------------------- Super Admin (Main Admin only)

    public function createSuperAdmin(): void
    {
        $this->requireMainAdmin();
        $this->adminView('roles/form', ['pageTitle' => 'Add Super Admin', 'targetRole' => 'super_admin', 'member' => null]);
    }

    public function storeSuperAdmin(): void
    {
        $this->requireMainAdmin();
        $this->storeUser('super_admin', 'admin/roles/super-admin/create');
    }

    public function editSuperAdmin(string $id): void
    {
        $this->requireMainAdmin();
        $this->editUser($id, 'super_admin');
    }

    public function updateSuperAdmin(string $id): void
    {
        $this->requireMainAdmin();
        $this->updateUser($id, 'super_admin', 'admin/roles/super-admin/' . $id . '/edit');
    }

    public function toggleSuperAdminStatus(string $id): void
    {
        $this->requireMainAdmin();
        $this->toggleSuspend($id, 'super_admin');
    }

    public function deleteSuperAdmin(string $id): void
    {
        $this->requireMainAdmin();
        $this->deleteUser($id, 'super_admin');
    }

    // ---------------------------------------------------------------- Permissions (either target role)

    public function permissions(string $id): void
    {
        $userModel = new User();
        $target = $userModel->findById((int) $id);
        if (!$target || !in_array($target['role_slug'], ['staff', 'super_admin'], true)) {
            $this->abort404();
        }
        if ($target['role_slug'] === 'super_admin') {
            $this->requireMainAdmin();
        }

        $existing = (new UserPermission())->forUser((int) $id);

        $this->adminView('roles/permissions', [
            'pageTitle' => 'Assign Permissions — ' . $target['name'],
            'target' => $target,
            'modules' => Modules::ALL,
            'actions' => UserPermission::actions(),
            'existing' => $existing,
            'grantableModules' => $this->grantableModuleKeys(),
        ]);
    }

    public function savePermissions(string $id): void
    {
        Security::requireCsrf();

        $userModel = new User();
        $target = $userModel->findById((int) $id);
        if (!$target || !in_array($target['role_slug'], ['staff', 'super_admin'], true)) {
            $this->abort404();
        }
        if ($target['role_slug'] === 'super_admin') {
            $this->requireMainAdmin();
        }

        $grantable = $this->grantableModuleKeys();
        $submitted = (array) ($_POST['permissions'] ?? []);
        $moduleActions = [];
        $before = (new UserPermission())->forUser((int) $id);

        foreach (array_keys(Modules::ALL) as $moduleKey) {
            // A Super Admin can never grant a Staff member a module they can't reach themselves —
            // the real anti-escalation guard, enforced here regardless of what the form submitted.
            if ($grantable !== null && !in_array($moduleKey, $grantable, true)) {
                continue;
            }
            $moduleActions[$moduleKey] = $submitted[$moduleKey] ?? [];
        }

        (new UserPermission())->setMany((int) $id, $moduleActions);
        Permission::clearCache();

        $granted = [];
        $revoked = [];
        foreach ($moduleActions as $moduleKey => $actions) {
            $hadAccessBefore = !empty($before[$moduleKey]['can_view'] ?? false);
            $hasAccessNow = !empty($actions['view']);
            if ($hasAccessNow && !$hadAccessBefore) {
                $granted[] = Modules::label($moduleKey);
            } elseif ($hadAccessBefore && !$hasAccessNow) {
                $revoked[] = Modules::label($moduleKey);
            }
        }

        $roleLabel = ucfirst(str_replace('_', ' ', $target['role_slug']));
        $actorLabel = Auth::hasRole('main_admin') ? 'Main Admin' : 'Super Admin';
        if ($granted) {
            $this->logActivity('permission_granted', "$actorLabel granted " . implode(', ', $granted) . " access to $roleLabel {$target['name']} (#$id)");
        }
        if ($revoked) {
            $this->logActivity('permission_revoked', "$actorLabel revoked " . implode(', ', $revoked) . " access from $roleLabel {$target['name']} (#$id)");
        }
        if (!$granted && !$revoked) {
            $this->logActivity('permissions_updated', "$actorLabel updated permissions for $roleLabel #$id: {$target['name']} (no view-access changes)");
        }

        flash('success', 'Permissions updated successfully.');
        redirect('admin/roles/' . $id . '/permissions');
    }

    // ---------------------------------------------------------------- Module Locks (Main Admin only)

    public function moduleLocks(): void
    {
        $this->requireMainAdmin();

        $lockModel = new ModuleLock();
        $current = $lockModel->all();

        $this->adminView('roles/locks', [
            'pageTitle' => 'Module Locks',
            'modules' => Modules::ALL,
            'scopes' => ModuleLock::SCOPES,
            'scopeLabels' => ModuleLock::LABELS,
            'current' => $current,
        ]);
    }

    public function saveModuleLocks(): void
    {
        $this->requireMainAdmin();
        Security::requireCsrf();

        $lockModel = new ModuleLock();
        $before = $lockModel->all();
        $submitted = (array) ($_POST['scope'] ?? []);
        $actorId = (int) Auth::user()['id'];

        foreach (array_keys(Modules::ALL) as $moduleKey) {
            // A module missing from the submitted body is left untouched, never defaulted to
            // 'everyone' — a partial/tampered POST must not be able to silently unlock modules
            // it didn't mention (this is exactly what caught the bug during testing: Settings
            // got reset to 'everyone' by a request that only touched a different module).
            if (!array_key_exists($moduleKey, $submitted)) {
                continue;
            }
            $newScope = $submitted[$moduleKey];
            if (!in_array($newScope, ModuleLock::SCOPES, true)) {
                continue;
            }
            $oldScope = $before[$moduleKey] ?? 'everyone';
            if ($newScope === $oldScope) {
                continue;
            }

            $lockModel->setScope($moduleKey, $newScope, $actorId);

            $label = Modules::label($moduleKey);
            if ($newScope === 'main_admin_only') {
                $this->logActivity('module_locked', "Main Admin locked $label (Only Main Admin).");
            } elseif ($oldScope === 'main_admin_only' && $newScope === 'everyone') {
                $this->logActivity('module_unlocked', "Main Admin unlocked $label (Everyone).");
            } else {
                $this->logActivity('module_lock_changed', "Main Admin changed $label module lock to " . ModuleLock::LABELS[$newScope] . '.');
            }
        }

        Permission::clearCache();
        flash('success', 'Module locks updated successfully.');
        redirect('admin/roles/locks');
    }

    // ---------------------------------------------------------------- Shared helpers

    private function requireMainAdmin(): void
    {
        if (!Auth::hasRole('main_admin')) {
            http_response_code(403);
            die('403 - Permission Denied');
        }
    }

    /** null = unrestricted (Main Admin can grant anything); array = the acting Super Admin's own reachable modules. */
    private function grantableModuleKeys(): ?array
    {
        if (Auth::hasRole('main_admin')) {
            return null;
        }
        return array_values(array_filter(array_keys(Modules::ALL), fn ($key) => Permission::can($key)));
    }

    private function storeUser(string $roleSlug, string $createFormRedirect): void
    {
        Security::requireCsrf();

        $name = $this->input('name');
        $email = $this->input('email');
        $phone = $this->input('phone');
        $password = $this->rawInput('password');

        $validator = new Validator(['name' => $name, 'email' => $email, 'phone' => $phone]);
        $validator->required('name', 'Name')->required('email', 'Email')->email('email')->phone('phone');

        $userModel = new User();
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect($createFormRedirect);
        }
        if ($userModel->emailExists($email)) {
            flash('danger', 'An account with this email already exists.');
            redirect($createFormRedirect);
        }
        if ($password === '' || strlen($password) < 8) {
            flash('danger', 'Password must be at least 8 characters.');
            redirect($createFormRedirect);
        }

        $id = $userModel->create($name, $email, $phone, $password, $roleSlug);
        $this->logActivity(
            $roleSlug . '_created',
            ucfirst(str_replace('_', ' ', $roleSlug)) . " account created #$id: $name"
        );

        flash('success', ucfirst(str_replace('_', ' ', $roleSlug)) . ' account created successfully.');
        redirect('admin/roles');
    }

    private function editUser(string $id, string $roleSlug): void
    {
        $userModel = new User();
        $member = $userModel->findById((int) $id);
        if (!$member || $member['role_slug'] !== $roleSlug) {
            $this->abort404();
        }

        $this->adminView('roles/form', [
            'pageTitle' => 'Edit ' . ucfirst(str_replace('_', ' ', $roleSlug)),
            'targetRole' => $roleSlug,
            'member' => $member,
        ]);
    }

    private function updateUser(string $id, string $roleSlug, string $editFormRedirect): void
    {
        Security::requireCsrf();

        $userModel = new User();
        $target = $userModel->findById((int) $id);
        if (!$target || $target['role_slug'] !== $roleSlug) {
            $this->abort404();
        }

        $name = $this->input('name');
        $email = $this->input('email');
        $phone = $this->input('phone');

        $validator = new Validator(['name' => $name, 'email' => $email, 'phone' => $phone]);
        $validator->required('name', 'Name')->required('email', 'Email')->email('email')->phone('phone');
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect($editFormRedirect);
        }

        $userModel->update((int) $id, ['name' => $name, 'email' => $email, 'phone' => $phone]);

        $newPassword = $this->rawInput('password');
        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                flash('danger', 'Password must be at least 8 characters.');
                redirect($editFormRedirect);
            }
            $userModel->updatePassword((int) $id, $newPassword);
            $this->logActivity($roleSlug . '_password_reset', "Reset password for {$target['name']} (#$id)");
        }

        $this->logActivity($roleSlug . '_updated', "Updated {$target['name']} (#$id)");
        flash('success', 'Account updated successfully.');
        redirect('admin/roles');
    }

    private function toggleSuspend(string $id, string $roleSlug): void
    {
        Security::requireCsrf();

        $userModel = new User();
        $target = $userModel->findById((int) $id);
        if (!$target || $target['role_slug'] !== $roleSlug) {
            $this->abort404();
        }

        $newStatus = $target['status'] === 'suspended' ? 'active' : 'suspended';
        $userModel->update((int) $id, ['status' => $newStatus]);

        $this->logActivity($roleSlug . '_status_toggled', "Set {$target['name']} (#$id) to $newStatus");
        flash('success', "Account is now $newStatus.");
        redirect('admin/roles');
    }

    private function deleteUser(string $id, string $roleSlug): void
    {
        Security::requireCsrf();

        $userModel = new User();
        $target = $userModel->findById((int) $id);
        if (!$target || $target['role_slug'] !== $roleSlug) {
            $this->abort404();
        }

        $userModel->delete((int) $id);
        $this->logActivity($roleSlug . '_deleted', "Deleted {$target['name']} (#$id)");

        flash('success', 'Account deleted.');
        redirect('admin/roles');
    }
}
