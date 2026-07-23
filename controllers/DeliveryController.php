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
}
