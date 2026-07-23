<?php

/**
 * Extension point for real payment gateways (Stripe, SSLCommerz, AmarPay, ...).
 * orders.payment_method is a plain VARCHAR (not an ENUM) specifically so a new
 * gateway's method key can be added here without a database migration —
 * see PaymentGatewayFactory::resolve() to wire one in.
 */
interface PaymentGateway
{
    /**
     * @param array{order_id:int, amount:float, method:string, reference_no:?string} $order
     * @return array{status:string, reference:?string} status is one of pending|verified|failed
     */
    public function capture(array $order): array;
}
