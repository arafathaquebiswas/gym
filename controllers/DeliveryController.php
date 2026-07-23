<?php

/** Restricted dashboard for the 'delivery' role — can only see and update their own assigned orders. */
final class DeliveryController extends Controller
{
    private const ALLOWED_STATUSES = ['shipped', 'delivered'];

    public function __construct()
    {
        Auth::requireRole('delivery');
    }

    public function dashboard(): void
    {
        $orders = (new Order())->forDeliveryPerson((int) Auth::user()['id']);

        $this->deliveryView('dashboard', [
            'pageTitle' => 'My Deliveries',
            'orders' => $orders,
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

        $orderModel->updateStatus((int) $id, $status, (int) Auth::user()['id'], null);

        flash('success', 'Order status updated.');
        redirect('delivery');
    }
}
