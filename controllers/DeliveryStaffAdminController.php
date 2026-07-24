<?php

final class DeliveryStaffAdminController extends AdminController
{
    protected string $moduleKey = 'delivery_staff';

    private const ROLE = 'delivery';

    public function index(): void
    {
        $orderModel = new Order();
        $zoneModel = new DeliveryZone();
        $staff = (new User())->findByRole(self::ROLE);

        foreach ($staff as &$person) {
            $person['performance'] = $orderModel->performanceSummaryForPerson((int) $person['id']);
            $zoneIds = $zoneModel->zoneIdsForPerson((int) $person['id']);
            $person['zone_names'] = array_map(
                fn ($z) => $z['name'],
                array_filter($zoneModel->all(), fn ($z) => in_array((int) $z['id'], $zoneIds, true))
            );
        }
        unset($person);

        $this->adminView('delivery-staff/index', [
            'pageTitle' => 'Delivery Staff',
            'staff' => $staff,
        ]);
    }

    public function create(): void
    {
        $this->adminView('delivery-staff/form', [
            'pageTitle' => 'Add Delivery Staff',
            'member' => null,
            'zones' => (new DeliveryZone())->allActive(),
            'assignedZoneIds' => [],
        ]);
    }

    public function store(): void
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
            redirect('admin/delivery-staff/create');
        }
        if ($userModel->emailExists($email)) {
            flash('danger', 'An account with this email already exists.');
            redirect('admin/delivery-staff/create');
        }
        if ($password === '' || strlen($password) < 8) {
            flash('danger', 'Password must be at least 8 characters.');
            redirect('admin/delivery-staff/create');
        }

        $id = $userModel->create($name, $email, $phone, $password, self::ROLE);

        $zoneIds = array_map('intval', (array) ($_POST['zone_ids'] ?? []));
        (new DeliveryZone())->assignZonesToPerson($id, $zoneIds);

        $this->logActivity('delivery_staff_created', "Added delivery staff #$id: $name");

        flash('success', 'Delivery staff member added successfully.');
        redirect('admin/delivery-staff');
    }

    public function edit(string $id): void
    {
        $userModel = new User();
        $member = $userModel->findById((int) $id);
        if (!$member || $member['role_slug'] !== self::ROLE) {
            $this->abort404();
        }

        $this->adminView('delivery-staff/form', [
            'pageTitle' => 'Edit Delivery Staff',
            'member' => $member,
            'zones' => (new DeliveryZone())->allActive(),
            'assignedZoneIds' => (new DeliveryZone())->zoneIdsForPerson((int) $id),
        ]);
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $userModel = new User();
        $staffMember = $userModel->findById((int) $id);
        if (!$staffMember || $staffMember['role_slug'] !== self::ROLE) {
            $this->abort404();
        }

        $name = $this->input('name');
        $email = $this->input('email');
        $phone = $this->input('phone');

        $validator = new Validator(['name' => $name, 'email' => $email, 'phone' => $phone]);
        $validator->required('name', 'Name')->required('email', 'Email')->email('email')->phone('phone');
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('admin/delivery-staff/' . $id . '/edit');
        }

        $userModel->update((int) $id, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'status' => $this->input('status', 'active'),
        ]);

        $zoneIds = array_map('intval', (array) ($_POST['zone_ids'] ?? []));
        (new DeliveryZone())->assignZonesToPerson((int) $id, $zoneIds);

        $newPassword = $this->rawInput('password');
        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                flash('danger', 'Password must be at least 8 characters.');
                redirect('admin/delivery-staff/' . $id . '/edit');
            }
            $userModel->updatePassword((int) $id, $newPassword);
        }

        $this->logActivity('delivery_staff_updated', "Updated delivery staff #$id: $name");
        flash('success', 'Delivery staff member updated successfully.');
        redirect('admin/delivery-staff/' . $id . '/edit');
    }

    /** One-click Activate/Deactivate — the edit form's Status select already covers this; this just saves a click from the list. */
    public function toggleActive(string $id): void
    {
        Security::requireCsrf();

        $userModel = new User();
        $staffMember = $userModel->findById((int) $id);
        if (!$staffMember || $staffMember['role_slug'] !== self::ROLE) {
            $this->abort404();
        }

        $newStatus = $staffMember['status'] === 'active' ? 'inactive' : 'active';
        $userModel->update((int) $id, ['status' => $newStatus]);

        $this->logActivity('delivery_staff_status_toggled', "Set delivery staff #$id ({$staffMember['name']}) to $newStatus");
        flash('success', "Delivery staff member is now $newStatus.");
        redirect('admin/delivery-staff');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $userModel = new User();
        $staffMember = $userModel->findById((int) $id);
        if (!$staffMember || $staffMember['role_slug'] !== self::ROLE) {
            $this->abort404();
        }

        $userModel->delete((int) $id);
        $this->logActivity('delivery_staff_deleted', "Deleted delivery staff #$id: {$staffMember['name']}");

        flash('success', 'Delivery staff member deleted.');
        redirect('admin/delivery-staff');
    }
}
