<?php

/**
 * Today's actual behavior for cod/bkash/nagad/rocket/bank_transfer: no gateway API call —
 * the customer-submitted reference number just sits as "pending" in payment_transactions
 * until an admin manually verifies it (OrderAdminController::updatePaymentStatus).
 */
final class ManualPaymentGateway implements PaymentGateway
{
    public function capture(array $order): array
    {
        return ['status' => 'pending', 'reference' => $order['reference_no'] ?? null];
    }
}
