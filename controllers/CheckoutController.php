<?php

final class CheckoutController extends Controller
{
    /** Card stays POS-only until a real gateway is integrated (see core/PaymentGateway.php). */
    private const ALLOWED_PAYMENT_METHODS = ['cod', 'bkash', 'nagad', 'rocket', 'bank_transfer'];

    public function show(): void
    {
        [$userId, $cartToken] = $this->cartIdentity();
        $productModel = new Product();
        $lines = array_map([$productModel, 'withComputedOffer'], (new Cart())->forIdentity($userId, $cartToken));

        if (!$lines) {
            flash('danger', 'Your cart is empty.');
            redirect('cart');
        }

        $subtotal = array_sum(array_map(fn ($l) => $l['display_price'] * $l['qty'], $lines));

        $settingModel = new Setting();
        $freeShippingMin = (float) $settingModel->get('free_shipping_min_amount', '0');
        $shipping = ($freeShippingMin > 0 && $subtotal >= $freeShippingMin) ? 0.0 : (float) $settingModel->get('shipping_flat_rate', '0');
        $tax = round($subtotal * ((float) $settingModel->get('tax_percent', '0') / 100), 2);

        $savedAddresses = [];
        $member = null;
        if (Auth::hasRole('member')) {
            $savedAddresses = (new CustomerAddress())->forUser((int) Auth::user()['id']);
            $member = (new Member())->findByUserId((int) Auth::user()['id']);
        }

        $this->view('checkout', [
            'pageTitle' => 'Checkout',
            'lines' => $lines,
            'subtotal' => $subtotal,
            'estimatedShipping' => $shipping,
            'estimatedTax' => $tax,
            'freeShippingMin' => $freeShippingMin,
            'savedAddresses' => $savedAddresses,
            'member' => $member,
        ]);
    }

    public function placeOrder(): void
    {
        Security::requireCsrf();

        [$userId, $cartToken] = $this->cartIdentity();
        $cartModel = new Cart();
        $productModel = new Product();
        $lines = array_map([$productModel, 'withComputedOffer'], $cartModel->forIdentity($userId, $cartToken));

        if (!$lines) {
            flash('danger', 'Your cart is empty.');
            redirect('cart');
        }

        $isMember = Auth::hasRole('member');
        $createAccount = !$isMember && $this->input('create_account') === '1';

        $name = $this->input('full_name');
        $email = $this->input('email');
        $phone = $this->input('phone');

        $validator = new Validator([
            'full_name' => $name, 'email' => $email, 'phone' => $phone,
            'delivery_address' => $this->input('delivery_address'),
            'delivery_city' => $this->input('delivery_city'),
            'password' => $this->rawInput('password'),
        ]);
        $validator->required('full_name', 'Full name')
            ->required('email', 'Email')->email('email')
            ->phone('phone')
            ->required('delivery_address', 'Delivery address')
            ->required('delivery_city', 'City');

        if ($createAccount) {
            $validator->minLength('password', 8, 'Password');
        }

        if ($validator->fails()) {
            flash('danger', $validator->firstError());
            redirect('checkout');
        }

        if ($createAccount) {
            $userModel = new User();
            if ($userModel->emailExists($email)) {
                flash('danger', 'An account with this email already exists — please log in first.');
                redirect('checkout');
            }

            $guestCartToken = session_id();
            $newUserId = $userModel->create($name, $email, $phone, $this->rawInput('password'), 'member');
            (new Member())->createForUser($newUserId);

            Auth::login($userModel->findById($newUserId));
            $cartModel->mergeGuestIntoUser($guestCartToken, $newUserId);

            $userId = $newUserId;
            $lines = array_map([$productModel, 'withComputedOffer'], $cartModel->forIdentity($userId, null));
        }

        $customer = [
            'guest_name' => $userId ? null : $name,
            'guest_email' => $userId ? null : $email,
            'guest_phone' => $userId ? null : $phone,
            'delivery_address' => $this->input('delivery_address'),
            'delivery_city' => $this->input('delivery_city'),
            'delivery_area' => $this->input('delivery_area') ?: null,
            'delivery_postal_code' => $this->input('delivery_postal_code') ?: null,
            'order_notes' => $this->rawInput('order_notes') ?: null,
        ];

        $paymentMethod = $this->input('payment_method', 'cod');
        if (!in_array($paymentMethod, self::ALLOWED_PAYMENT_METHODS, true)) {
            flash('danger', 'Please select a valid payment method.');
            redirect('checkout');
        }

        $payment = [
            'method' => $paymentMethod,
            'discount' => 0.0,
            'couponCode' => $this->input('coupon_code') ?: null,
            'reference_no' => $this->input('reference_no') ?: null,
        ];

        $cartLines = array_map(fn ($l) => ['product_id' => (int) $l['id'], 'qty' => (int) $l['qty']], $lines);

        try {
            $result = (new Order())->create($cartLines, $userId, $customer, $payment);
        } catch (Throwable $e) {
            flash('danger', $e->getMessage());
            redirect('checkout');
        }

        if ($userId && $this->input('save_address') === '1') {
            (new CustomerAddress())->create($userId, [
                'label' => 'Home', 'full_name' => $name, 'phone' => $phone,
                'address' => $customer['delivery_address'], 'city' => $customer['delivery_city'],
                'area' => $customer['delivery_area'], 'postal_code' => $customer['delivery_postal_code'],
            ]);
        }

        $cartModel->clear($userId, $userId ? null : $cartToken);
        $_SESSION['_last_order_id'] = $result['id'];

        // A mail failure (e.g. SMTP not configured) must never block the order itself —
        // Mailer::send() already no-ops silently in that case.
        $placedOrder = (new Order())->find($result['id']);
        OrderMailer::sendConfirmation($placedOrder, (new OrderItem())->forOrder($result['id']));

        flash('success', 'Order placed successfully — order #' . $result['order_no']);
        redirect('checkout/confirmation/' . $result['id']);
    }

    public function confirmation(string $id): void
    {
        $order = (new Order())->find((int) $id);
        if (!$order) {
            $this->abort404();
        }

        $ownedByCurrentUser = Auth::check() && (int) $order['user_id'] === (int) Auth::user()['id'];
        $ownedBySession = ($_SESSION['_last_order_id'] ?? null) === (int) $id;
        if (!$ownedByCurrentUser && !$ownedBySession) {
            $this->abort404();
        }

        $this->view('checkout-confirmation', [
            'pageTitle' => 'Order Confirmed',
            'order' => $order,
            'items' => (new OrderItem())->forOrder((int) $id),
        ]);
    }

    private function cartIdentity(): array
    {
        $identity = Cart::identity();
        return [$identity['user_id'], $identity['cart_token']];
    }
}
