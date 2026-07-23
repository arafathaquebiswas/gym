<?php

final class AccountController extends Controller
{
    public function index(): void
    {
        Auth::requireRole('member');
        $userId = (int) Auth::user()['id'];

        $memberModel = new Member();
        $member = $memberModel->findByUserId($userId);
        $subscription = $member ? $memberModel->activeSubscription((int) $member['id']) : null;
        $bookings = $member ? (new TrainerBooking())->upcomingForMember((int) $member['id']) : [];

        $recentOrders = (new Order())->paginateForUser($userId, 1, 3)['items'];
        $wishlistCount = (new Wishlist())->count($userId);

        $this->view('account', [
            'member' => $member, 'subscription' => $subscription, 'bookings' => $bookings,
            'recentOrders' => $recentOrders, 'wishlistCount' => $wishlistCount,
        ]);
    }

    public function profile(): void
    {
        Auth::requireRole('member');
        $memberModel = new Member();
        $member = $memberModel->findByUserId((int) Auth::user()['id']);

        $this->view('account/profile', ['pageTitle' => 'Edit Profile', 'member' => $member]);
    }

    public function profileUpdate(): void
    {
        Security::requireCsrf();
        Auth::requireRole('member');

        $userId = (int) Auth::user()['id'];
        $memberModel = new Member();
        $member = $memberModel->findByUserId($userId);
        if (!$member) {
            $this->abort404();
        }

        $name = $this->input('name');
        $email = $this->input('email');
        $phone = $this->input('phone');

        $validator = new Validator(['name' => $name, 'email' => $email, 'phone' => $phone]);
        $validator->required('name', 'Name')->required('email', 'Email')->email('email')->phone('phone');
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('account/profile');
        }

        $userModel = new User();
        if ($email !== Auth::user()['email'] && $userModel->emailExists($email)) {
            flash('danger', 'That email address is already in use by another account.');
            redirect('account/profile');
        }
        $userModel->update($userId, ['name' => $name, 'email' => $email, 'phone' => $phone]);

        $data = [
            'address' => $this->input('address') ?: null,
            'emergency_contact' => $this->input('emergency_contact') ?: null,
            'notify_email' => $this->input('notify_email') === '1' ? 1 : 0,
            'notify_promotions' => $this->input('notify_promotions') === '1' ? 1 : 0,
        ];

        $photoPath = Upload::handle($_FILES['photo'] ?? [], 'members');
        if ($photoPath) {
            Upload::delete($member['photo']);
            $data['photo'] = $photoPath;
        }

        $memberModel->update((int) $member['id'], $data);

        flash('success', 'Profile updated successfully.');
        redirect('account/profile');
    }

    public function passwordUpdate(): void
    {
        Security::requireCsrf();
        Auth::requireRole('member');

        $userId = (int) Auth::user()['id'];
        $currentPassword = $this->rawInput('current_password');
        $newPassword = $this->rawInput('new_password');
        $confirmPassword = $this->rawInput('new_password_confirm');

        $userModel = new User();
        $user = $userModel->findById($userId);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            flash('danger', 'Your current password is incorrect.');
            redirect('account/profile');
        }

        $validator = new Validator(['new_password' => $newPassword, 'new_password_confirm' => $confirmPassword]);
        $validator->minLength('new_password', 8, 'New password')->matches('new_password_confirm', 'new_password', 'Password confirmation');
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('account/profile');
        }

        $userModel->updatePassword($userId, $newPassword);

        flash('success', 'Password changed successfully.');
        redirect('account/profile');
    }

    public function orders(): void
    {
        Auth::requireRole('member');
        $page = max(1, (int) $this->input('page', '1'));
        $result = (new Order())->paginateForUser((int) Auth::user()['id'], $page);

        $this->view('account/orders', [
            'pageTitle' => 'My Orders',
            'orders' => $result['items'], 'total' => $result['total'],
            'page' => $result['page'], 'totalPages' => $result['totalPages'],
        ]);
    }

    public function orderDetail(string $id): void
    {
        Auth::requireRole('member');
        $order = (new Order())->find((int) $id);

        if (!$order || (int) $order['user_id'] !== (int) Auth::user()['id']) {
            $this->abort404();
        }

        $this->view('account/order-detail', [
            'pageTitle' => 'Order ' . $order['order_no'],
            'order' => $order,
            'items' => (new OrderItem())->forOrder((int) $id),
            'history' => (new Order())->statusHistory((int) $id),
        ]);
    }

    public function orderInvoice(string $id): void
    {
        Auth::requireRole('member');
        $order = (new Order())->find((int) $id);

        if (!$order || (int) $order['user_id'] !== (int) Auth::user()['id']) {
            $this->abort404();
        }

        $items = (new OrderItem())->forOrder((int) $id);
        $pdf = Invoice::generateForOrder($order, $items);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $order['order_no'] . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    public function addresses(): void
    {
        Auth::requireRole('member');
        $this->view('account/addresses', [
            'pageTitle' => 'Saved Addresses',
            'addresses' => (new CustomerAddress())->forUser((int) Auth::user()['id']),
        ]);
    }

    public function addressStore(): void
    {
        Security::requireCsrf();
        Auth::requireRole('member');

        $name = $this->input('full_name');
        $validator = new Validator(['full_name' => $name, 'phone' => $this->input('phone'), 'address' => $this->input('address'), 'city' => $this->input('city')]);
        $validator->required('full_name', 'Full name')->phone('phone')->required('address', 'Address')->required('city', 'City');

        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('account/addresses');
        }

        (new CustomerAddress())->create((int) Auth::user()['id'], [
            'label' => $this->input('label', 'Home'), 'full_name' => $name, 'phone' => $this->input('phone'),
            'address' => $this->input('address'), 'city' => $this->input('city'),
            'area' => $this->input('area') ?: null, 'postal_code' => $this->input('postal_code') ?: null,
        ]);

        flash('success', 'Address added.');
        redirect('account/addresses');
    }

    public function addressUpdate(string $id): void
    {
        Security::requireCsrf();
        Auth::requireRole('member');

        $addressModel = new CustomerAddress();
        $address = $addressModel->find((int) $id);
        if (!$address || (int) $address['user_id'] !== (int) Auth::user()['id']) {
            $this->abort404();
        }

        $addressModel->update((int) $id, [
            'label' => $this->input('label', 'Home'), 'full_name' => $this->input('full_name'),
            'phone' => $this->input('phone'), 'address' => $this->input('address'),
            'city' => $this->input('city'), 'area' => $this->input('area') ?: null,
            'postal_code' => $this->input('postal_code') ?: null,
        ]);

        flash('success', 'Address updated.');
        redirect('account/addresses');
    }

    public function addressSetDefault(string $id): void
    {
        Security::requireCsrf();
        Auth::requireRole('member');

        $userId = (int) Auth::user()['id'];
        $addressModel = new CustomerAddress();
        $address = $addressModel->find((int) $id);
        if (!$address || (int) $address['user_id'] !== $userId) {
            $this->abort404();
        }

        $addressModel->setDefault($userId, (int) $id);
        flash('success', 'Default address updated.');
        redirect('account/addresses');
    }

    public function addressDelete(string $id): void
    {
        Security::requireCsrf();
        Auth::requireRole('member');

        $addressModel = new CustomerAddress();
        $address = $addressModel->find((int) $id);
        if (!$address || (int) $address['user_id'] !== (int) Auth::user()['id']) {
            $this->abort404();
        }

        $addressModel->delete((int) $id);
        flash('success', 'Address removed.');
        redirect('account/addresses');
    }

    public function wishlist(): void
    {
        Auth::requireRole('member');
        $this->view('account/wishlist', [
            'pageTitle' => 'My Wishlist',
            'items' => (new Wishlist())->forUser((int) Auth::user()['id']),
        ]);
    }

    public function wishlistRemove(): void
    {
        Security::requireCsrf();
        Auth::requireRole('member');

        (new Wishlist())->remove((int) Auth::user()['id'], (int) $this->input('product_id'));
        flash('success', 'Removed from wishlist.');
        redirect('account/wishlist');
    }
}
