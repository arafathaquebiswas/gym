<?php

/** Restricted dashboard for the 'delivery' role — can only see and update their own assigned orders. */
final class DeliveryController extends Controller
{
    /**
     * "Assigned" isn't in this list — it's the implicit starting state (delivery_person_id set,
     * nothing picked up yet), not something the driver sets themselves. "On the Way" reuses the
     * existing 'shipped' value (relabeled in the view only) rather than adding yet another
     * near-duplicate status; picked_up/delivery_failed are genuinely new ENUM values.
     */
    private const ALLOWED_STATUSES = ['picked_up', 'shipped', 'delivered', 'delivery_failed', 'returned'];

    public const STATUS_LABELS = [
        'picked_up' => 'Picked Up',
        'shipped' => 'On the Way',
        'delivered' => 'Delivered',
        'delivery_failed' => 'Delivery Failed',
        'returned' => 'Returned',
    ];

    public function __construct()
    {
        Auth::requireRole('delivery');
    }

    public function dashboard(): void
    {
        $driverId = (int) Auth::user()['id'];
        $orderModel = new Order();
        $feePerOrder = (float) (new Setting())->get('delivery_fee_per_order', '0');

        $data = [
            'pageTitle' => 'My Deliveries',
            'orders' => $orderModel->forDeliveryPerson($driverId),
            'todayStats' => $orderModel->todaysStatsForDeliveryPerson($driverId),
            'feePerOrder' => $feePerOrder,
        ];

        if ($feePerOrder > 0) {
            $monthStart = date('Y-m-01');
            $today = date('Y-m-d');
            $deliveredThisMonth = $orderModel->completedCountForDeliveryPersonInRange($driverId, $monthStart, $today);
            $data['monthlyEarnings'] = $deliveredThisMonth * $feePerOrder;
            $data['deliveredThisMonth'] = $deliveredThisMonth;
        }

        $this->deliveryView('dashboard', $data);
    }

    public function orderDetail(string $id): void
    {
        $order = (new Order())->find((int) $id);
        if (!$order || (int) ($order['delivery_person_id'] ?? 0) !== (int) Auth::user()['id']) {
            $this->abort404();
        }

        $this->deliveryView('order-detail', [
            'pageTitle' => 'Order ' . $order['order_no'],
            'order' => $order,
            'items' => (new OrderItem())->forOrder((int) $order['id']),
            'history' => (new Order())->statusHistory((int) $order['id']),
        ]);
    }

    public function history(): void
    {
        $driverId = (int) Auth::user()['id'];
        $page = max(1, (int) $this->input('page', '1'));
        $result = (new Order())->forDeliveryPersonHistory($driverId, $page);

        $this->deliveryView('history', [
            'pageTitle' => 'Delivery History',
            'orders' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    public function updateStatus(string $id): void
    {
        Security::requireCsrf();

        $orderModel = new Order();
        $order = $orderModel->find((int) $id);
        if (!$order || (int) ($order['delivery_person_id'] ?? 0) !== (int) Auth::user()['id']) {
            $this->abort404();
        }

        $status = $this->input('status');
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            flash('danger', 'Invalid status.');
            redirect('delivery');
        }

        $note = $this->rawInput('note') ?: null;
        $orderModel->updateStatus((int) $id, $status, (int) Auth::user()['id'], $note);

        flash('success', 'Order status updated.');
        redirect('delivery');
    }

    public function profile(): void
    {
        $this->deliveryView('profile', [
            'pageTitle' => 'My Profile',
            'user' => (new User())->findById((int) Auth::user()['id']),
        ]);
    }

    public function profileUpdate(): void
    {
        Security::requireCsrf();

        $name = $this->input('name');
        $phone = $this->input('phone');

        $validator = new Validator(['name' => $name, 'phone' => $phone]);
        $validator->required('name', 'Full name')->phone('phone');
        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('delivery/profile');
        }

        (new User())->update((int) Auth::user()['id'], ['name' => $name, 'phone' => $phone]);
        $_SESSION['user_name'] = $name;

        flash('success', 'Profile updated successfully.');
        redirect('delivery/profile');
    }

    public function passwordUpdate(): void
    {
        Security::requireCsrf();

        $userId = (int) Auth::user()['id'];
        $userModel = new User();
        $user = $userModel->findById($userId);

        $current = $this->rawInput('current_password');
        $newPassword = $this->rawInput('new_password');
        $confirm = $this->rawInput('new_password_confirm');

        if (!$user || !password_verify($current, $user['password_hash'])) {
            flash('danger', 'Your current password is incorrect.');
            redirect('delivery/profile');
        }
        if (strlen($newPassword) < 8) {
            flash('danger', 'New password must be at least 8 characters.');
            redirect('delivery/profile');
        }
        if ($newPassword !== $confirm) {
            flash('danger', 'New password confirmation does not match.');
            redirect('delivery/profile');
        }

        $userModel->updatePassword($userId, $newPassword);
        flash('success', 'Password changed successfully.');
        redirect('delivery/profile');
    }
}
