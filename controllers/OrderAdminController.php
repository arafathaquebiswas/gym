<?php

final class OrderAdminController extends AdminController
{
    protected string $moduleKey = 'orders';

    private const STATUSES = ['pending', 'confirmed', 'preparing', 'packed', 'ready_for_pickup', 'shipped', 'delivered', 'cancelled', 'returned'];

    public function index(): void
    {
        $orderModel = new Order();

        $filters = [
            'status' => $this->input('status'),
            'search' => $this->input('search'),
        ];
        $page = max(1, (int) $this->input('page', '1'));
        $result = $orderModel->paginateForAdmin($filters, $page);

        $this->adminView('orders/index', [
            'pageTitle' => 'Orders',
            'orders' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'filters' => $filters,
            'statusCounts' => $orderModel->statusCounts(),
        ]);
    }

    public function bulkAction(): void
    {
        Security::requireCsrf();

        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $action = $this->input('bulk_action');

        if (!$ids) {
            flash('danger', 'No orders selected.');
            redirect('admin/orders');
        }

        $orderModel = new Order();

        if ($action === 'export') {
            $this->exportCsv($orderModel, $ids);
            return;
        }
        if ($action === 'print') {
            $this->printOrders($orderModel, $ids);
            return;
        }

        $count = 0;

        if ($action === 'delete') {
            foreach ($ids as $id) {
                $order = $orderModel->find($id);
                if ($order && in_array($order['status'], ['cancelled', 'returned'], true)) {
                    $orderModel->delete($id);
                    $count++;
                }
            }
            $this->logActivity('orders_bulk_deleted', "Bulk-deleted $count order(s)");
            flash('success', "$count order(s) deleted. Only cancelled/returned orders can be bulk-deleted.");
        } elseif ($action === 'status') {
            $status = $this->input('bulk_status');
            if (!in_array($status, self::STATUSES, true)) {
                flash('danger', 'Invalid status.');
                redirect('admin/orders');
            }
            foreach ($ids as $id) {
                if ($orderModel->find($id)) {
                    $orderModel->updateStatus($id, $status, (int) Auth::user()['id'], 'Bulk status update');
                    $count++;
                }
            }
            $this->logActivity('orders_bulk_status', "Bulk-updated $count order(s) to $status");
            flash('success', "$count order(s) updated to $status.");
        } else {
            flash('danger', 'Invalid bulk action.');
        }

        redirect('admin/orders');
    }

    private function exportCsv(Order $orderModel, array $ids): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders-export-' . date('Y-m-d-His') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Order #', 'Customer', 'Date', 'Subtotal', 'Discount', 'Shipping', 'Tax', 'Total', 'Payment Method', 'Payment Status', 'Status'], ',', '"', '\\');

        foreach ($ids as $id) {
            $order = $orderModel->find($id);
            if (!$order) {
                continue;
            }
            fputcsv($out, [
                $order['order_no'],
                $order['account_name'] ?? $order['guest_name'],
                $order['created_at'],
                $order['subtotal'], $order['discount'], $order['shipping_charge'], $order['tax'], $order['total'],
                $order['payment_method'], $order['payment_status'], $order['status'],
            ], ',', '"', '\\');
        }
        fclose($out);
        $this->logActivity('orders_bulk_exported', 'Exported ' . count($ids) . ' order(s) to CSV');
        exit;
    }

    private function printOrders(Order $orderModel, array $ids): void
    {
        $orders = array_filter(array_map(fn ($id) => $orderModel->find($id), $ids));
        $this->adminView('orders/print', ['pageTitle' => 'Print Orders', 'orders' => $orders]);
    }

    public function show(string $id): void
    {
        $orderModel = new Order();
        $order = $orderModel->find((int) $id);
        if (!$order) {
            $this->abort404();
        }

        $this->adminView('orders/show', [
            'pageTitle' => 'Order ' . $order['order_no'],
            'order' => $order,
            'items' => (new OrderItem())->forOrder((int) $id),
            'history' => $orderModel->statusHistory((int) $id),
            'transactions' => $this->paymentTransactions((int) $id),
            'refunds' => $orderModel->refundsForOrder((int) $id),
            'statuses' => self::STATUSES,
            'deliveryStaff' => (new User())->findByRole('delivery'),
            'customerHistory' => $orderModel->historyForCustomer($order, (int) $id),
        ]);
    }

    public function assignDeliveryPerson(string $id): void
    {
        Security::requireCsrf();

        $orderModel = new Order();
        if (!$orderModel->find((int) $id)) {
            $this->abort404();
        }

        $rawPersonId = $this->input('delivery_person_id');
        $deliveryPersonId = null;
        if ($rawPersonId !== '') {
            $person = (new User())->findById((int) $rawPersonId);
            if (!$person || $person['role_slug'] !== 'delivery') {
                flash('danger', 'Invalid delivery staff selected.');
                redirect('admin/orders/' . $id);
            }
            $deliveryPersonId = (int) $rawPersonId;
        }

        $orderModel->assignDeliveryPerson((int) $id, $deliveryPersonId);
        $this->logActivity('order_delivery_person_assigned', "Order #$id delivery person set to " . ($deliveryPersonId ?? 'none'));

        flash('success', $deliveryPersonId ? 'Delivery person assigned.' : 'Delivery person unassigned.');
        redirect('admin/orders/' . $id);
    }

    public function confirmPickup(string $id): void
    {
        Security::requireCsrf();

        $orderModel = new Order();
        $order = $orderModel->find((int) $id);
        if (!$order) {
            $this->abort404();
        }

        if ($order['fulfillment_method'] !== 'pickup') {
            flash('danger', 'This order is not a store pickup order.');
            redirect('admin/orders/' . $id);
        }

        if (!$orderModel->pinMatches($order, $this->input('pin'))) {
            flash('danger', 'Incorrect PIN. Please check with the customer and try again.');
            redirect('admin/orders/' . $id);
        }

        $orderModel->updateStatus((int) $id, 'delivered', (int) Auth::user()['id'], 'Picked up by customer (PIN verified)');
        $this->logActivity('order_pickup_confirmed', "Order #$id pickup confirmed via PIN");

        flash('success', 'Pickup confirmed — order marked as collected.');
        redirect('admin/orders/' . $id);
    }

    public function updateStatus(string $id): void
    {
        Security::requireCsrf();

        $orderModel = new Order();
        if (!$orderModel->find((int) $id)) {
            $this->abort404();
        }

        $status = $this->input('status');
        if (!in_array($status, self::STATUSES, true)) {
            flash('danger', 'Invalid status.');
            redirect('admin/orders/' . $id);
        }

        $orderModel->updateStatus((int) $id, $status, (int) Auth::user()['id'], $this->input('note') ?: null);
        $this->logActivity('order_status_updated', "Order #$id status set to $status");

        flash('success', 'Order status updated.');
        redirect('admin/orders/' . $id);
    }

    public function updateNotes(string $id): void
    {
        Security::requireCsrf();

        $orderModel = new Order();
        if (!$orderModel->find((int) $id)) {
            $this->abort404();
        }

        $orderModel->updateAdminNotes((int) $id, $this->rawInput('admin_notes'));
        $this->logActivity('order_notes_updated', "Updated customer-facing note for order #$id");

        flash('success', 'Note saved.');
        redirect('admin/orders/' . $id);
    }

    public function updatePaymentStatus(string $id): void
    {
        Security::requireCsrf();

        $orderModel = new Order();
        if (!$orderModel->find((int) $id)) {
            $this->abort404();
        }

        $status = $this->input('payment_status');
        if (!in_array($status, ['pending', 'paid', 'failed'], true)) {
            flash('danger', 'Invalid payment status. Use the dedicated Refund button to record a refund.');
            redirect('admin/orders/' . $id);
        }

        $orderModel->updatePaymentStatus((int) $id, $status);
        $this->logActivity('order_payment_status_updated', "Order #$id payment status set to $status");

        flash('success', 'Payment status updated.');
        redirect('admin/orders/' . $id);
    }

    public function refund(string $id): void
    {
        Security::requireCsrf();

        $orderModel = new Order();
        $order = $orderModel->find((int) $id);
        if (!$order) {
            $this->abort404();
        }

        $amount = (float) $this->input('amount', '0');
        $reason = $this->input('reason');

        if ($amount <= 0 || $amount > (float) $order['total']) {
            flash('danger', 'Refund amount must be greater than zero and not exceed the order total.');
            redirect('admin/orders/' . $id);
        }
        if ($reason === '') {
            flash('danger', 'Please provide a reason for the refund.');
            redirect('admin/orders/' . $id);
        }

        $orderModel->refund((int) $id, $amount, $reason, (int) Auth::user()['id']);
        $this->logActivity('order_refunded', "Refunded {$amount} for order #$id: $reason");

        flash('success', 'Refund recorded successfully.');
        redirect('admin/orders/' . $id);
    }

    public function receipt(string $id): void
    {
        $order = (new Order())->find((int) $id);
        if (!$order) {
            $this->abort404();
        }

        $this->adminView('orders/receipt', [
            'pageTitle' => 'Invoice — ' . $order['order_no'],
            'order' => $order,
            'items' => (new OrderItem())->forOrder((int) $id),
        ]);
    }

    public function pdf(string $id): void
    {
        $order = (new Order())->find((int) $id);
        if (!$order) {
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

    private function paymentTransactions(int $orderId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM payment_transactions WHERE order_id = :order_id ORDER BY id DESC');
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }
}
