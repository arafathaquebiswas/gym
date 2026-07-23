<?php

final class OrderTrackingController extends Controller
{
    public function show(): void
    {
        $this->view('track-order', ['pageTitle' => 'Track Your Order', 'order' => null, 'items' => [], 'history' => []]);
    }

    public function find(): void
    {
        $orderNo = self::normalizeOrderNo($this->input('order_no'));
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
        $orderNo = self::normalizeOrderNo($this->input('order_no'));
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

    /**
     * The confirmation page displays the order as "Order #ORD-20260724-0003" — people
     * naturally copy/type that whole label, but the DB match is on the bare order_no, so
     * "Order #"/a leading "#" has to be stripped before we look it up.
     */
    private static function normalizeOrderNo(string $orderNo): string
    {
        $orderNo = trim($orderNo);
        $orderNo = preg_replace('/^order\s*/i', '', $orderNo) ?? $orderNo;
        $orderNo = ltrim($orderNo, "#\t\n\r\0\x0B ");
        return trim($orderNo);
    }

    private function identityMatches(array $order, string $identity): bool
    {
        if ($identity === '') {
            return false;
        }

        $email = $order['account_email'] ?? $order['guest_email'] ?? '';
        $phone = $order['account_phone'] ?? $order['guest_phone'] ?? '';

        return ($email !== '' && strcasecmp($email, $identity) === 0)
            || ($phone !== '' && self::normalizePhone($phone) === self::normalizePhone($identity));
    }

    /**
     * A phone typed at checkout and one typed later on this form legitimately differ in
     * formatting (spaces, dashes, +880/880 country code, a missing leading 0) even when they're
     * the same number — an exact string match was silently rejecting correct phone numbers.
     * Normalizes to the bare 11-digit Bangladeshi local form (e.g. 01XXXXXXXXX).
     */
    private static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (str_starts_with($digits, '880') && strlen($digits) > 11) {
            $digits = '0' . substr($digits, 3);
        } elseif (strlen($digits) === 10 && !str_starts_with($digits, '0')) {
            $digits = '0' . $digits;
        }
        return $digits;
    }
}
