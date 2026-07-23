<?php

/**
 * Resolves a payment_method string to its handler. Every method today uses the
 * manual/no-API-call flow. To add a real gateway later: implement PaymentGateway,
 * add its method key(s) to CheckoutController's allow-list, and add a case here —
 * no database change needed since payment_method is a plain VARCHAR.
 */
final class PaymentGatewayFactory
{
    public static function resolve(string $method): PaymentGateway
    {
        return match ($method) {
            default => new ManualPaymentGateway(),
        };
    }
}
