<?php

final class OrderTrackingController extends Controller
{
    public function show(): void
    {
        $this->view('track-order', ['pageTitle' => 'Track Your Order', 'order' => null, 'items' => [], 'history' => []]);
    }

    public function find(): void
    {
        $orderNo = trim($this->input('order_no'));
        $identity = trim($this->input('identity'));

        $order = $orderNo !== '' ? (new Order())->findByOrderNo($orderNo) : null;

        if ($order && !$this->identityMatches($order, $identity)) {
            $order = null;
        }

        if (!$order) {
            flash('danger', 'We could not find an order matching that order number and email/phone. Please double-check and try again.');
            redirect('track-order');
        }

        $this->view('track-order', [
            'pageTitle' => 'Order ' . $order['order_no'],
            'order' => $order,
            'items' => (new OrderItem())->forOrder((int) $order['id']),
            'history' => (new Order())->statusHistory((int) $order['id']),
        ]);
    }

    /** Re-verifies order_no+identity via POST (never a bare GET link) before streaming the PDF. */
    public function invoice(): void
    {
        $orderNo = trim($this->input('order_no'));
        $identity = trim($this->input('identity'));
        $order = $orderNo !== '' ? (new Order())->findByOrderNo($orderNo) : null;

        if (!$order || !$this->identityMatches($order, $identity)) {
            $this->abort404();
        }

        $items = (new OrderItem())->forOrder((int) $order['id']);
        $pdf = Invoice::generateForOrder($order, $items);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $order['order_no'] . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function identityMatches(array $order, string $identity): bool
    {
        if ($identity === '') {
            return false;
        }

        $email = $order['account_email'] ?? $order['guest_email'] ?? '';
        $phone = $order['account_phone'] ?? $order['guest_phone'] ?? '';

        return ($email !== '' && strcasecmp($email, $identity) === 0)
            || ($phone !== '' && $phone === $identity);
    }
}
