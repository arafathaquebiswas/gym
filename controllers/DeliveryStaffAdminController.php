<?php

final class DeliveryStaffAdminController extends AdminController
{
    private const ROLE = 'delivery';

    public function index(): void
    {
        $this->adminView('delivery-staff/index', [
            'pageTitle' => 'Delivery Staff',
            'staff' => (new User())->findByRole(self::ROLE),
        ]);
    }

    public function create(): void
    {
        $this->adminView('delivery-staff/form', [
            'pageTitle' => 'Add Delivery Staff',
            'member' => null,
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
