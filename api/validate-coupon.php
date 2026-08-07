<?php
/**
 * Checkout coupon check — validates a typed code against the live cart and returns
 * the discount it would produce, so the order summary can show the effect before
 * the order is submitted.
 *
 * The subtotal is read from the caller's own cart on the server, never from the
 * request: a client that could name its own subtotal could mint any discount it
 * liked. This endpoint is a preview only — Order::create() re-validates the code
 * and recomputes the discount when the order is actually placed.
 */

require dirname(__DIR__) . '/config/config.php';
require BASE_PATH . '/core/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

Security::requireCsrf();

if (!Feature::on('coupons')) {
    json_response(['valid' => false, 'message' => 'Coupons are not currently available.'], 404);
}

$code = strtoupper(Security::sanitizeString($_POST['code'] ?? ''));

// Empty is not an error: the field is optional, so this simply clears any discount.
if ($code === '') {
    json_response(['valid' => false, 'empty' => true, 'discount' => 0, 'message' => '']);
}

$identity = Cart::identity();
$lines = array_map(
    [new Product(), 'withComputedOffer'],
    (new Cart())->forIdentity($identity['user_id'], $identity['cart_token'])
);

if (!$lines) {
    json_response(['valid' => false, 'discount' => 0, 'message' => 'Your cart is empty.']);
}

$subtotal = 0.0;
foreach ($lines as $line) {
    $subtotal += (float) $line['display_price'] * (int) $line['qty'];
}

$user = Auth::user();
$memberId = null;
if ($user) {
    $member = (new Member())->findByUserId((int) $user['id']);
    $memberId = $member ? (int) $member['id'] : null;
}
$guestEmail = $user ? null : (Security::sanitizeString($_POST['email'] ?? '') ?: null);

$promotionModel = new Promotion();
$promotion = $promotionModel->validCoupon($code, $subtotal, 'product', $memberId, $guestEmail);

if (!$promotion) {
    // Deliberately one message for every rejection — expired, over its usage limit,
    // below the minimum, or simply not a real code. Distinguishing them would let
    // someone probe for valid codes.
    json_response([
        'valid' => false,
        'discount' => 0,
        'message' => 'Invalid or Expired Coupon Code.',
    ]);
}

$discount = $promotionModel->computeDiscount($promotion, $subtotal);

json_response([
    'valid' => true,
    'code' => $promotion['code'],
    'title' => $promotion['title'],
    'discount' => $discount,
    'discount_label' => '৳' . number_format($discount, 0),
    'message' => 'Coupon applied.',
]);
