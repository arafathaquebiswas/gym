<?php

final class CheckoutController extends Controller
{
    /** Card stays POS-only until a real gateway is integrated (see core/PaymentGateway.php). */
    private const ALLOWED_PAYMENT_METHODS = ['cod', 'bkash', 'nagad', 'rocket', 'bank_transfer'];

    public function show(): void
    {
        if (!Feature::on('store')) {
            $this->abort404();
        }
        if (!Feature::storeAvailable()) {
            $this->view('store-unavailable', ['pageTitle' => 'Store Unavailable']);
            return;
        }

        if (!Auth::hasRole('member') && !Feature::on('guest_checkout')) {
            flash('danger', 'Please log in to check out.');
            redirect('login');
        }

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
        $shippingEnabled = $settingModel->getBool('shipping_enabled');
        $flatRate = (float) $settingModel->get('shipping_flat_rate', '0');
        $maxOverride = null;
        foreach ($lines as $line) {
            if ($line['shipping_charge'] !== null && $line['shipping_charge'] !== '') {
                $maxOverride = max($maxOverride ?? 0.0, (float) $line['shipping_charge']);
            }
        }

        $shipping = 0.0;
        if ($shippingEnabled && !($freeShippingMin > 0 && $subtotal >= $freeShippingMin)) {
            $shipping = $maxOverride !== null ? max($flatRate, $maxOverride) : $flatRate;
        }

        $tax = $settingModel->getBool('tax_enabled')
            ? round($subtotal * ((float) $settingModel->get('tax_percent', '0') / 100), 2)
            : 0.0;

        $savedAddresses = [];
        $member = null;
        if (Auth::hasRole('member')) {
            $savedAddresses = (new CustomerAddress())->forUser((int) Auth::user()['id']);
            $member = (new Member())->findByUserId((int) Auth::user()['id']);
        }

        $deliveryOn = Feature::deliveryOn();
        $pickupOn = Feature::pickupOn();

        $this->view('checkout', [
            'pageTitle' => 'Checkout',
            'lines' => $lines,
            'subtotal' => $subtotal,
            'estimatedShipping' => $shipping,
            'estimatedTax' => $tax,
            'freeShippingMin' => $freeShippingMin,
            'shippingEnabled' => $shippingEnabled,
            'shippingFlatRate' => $flatRate,
            'shippingMaxOverride' => $maxOverride,
            'savedAddresses' => $savedAddresses,
            'member' => $member,
            'gymName' => $settingModel->get('gym_name', 'PowerSurge Gym'),
            'gymAddress' => $settingModel->get('gym_address'),
            'gymPhone' => $settingModel->get('gym_phone'),
            'deliveryOn' => $deliveryOn,
            'pickupOn' => $pickupOn,
            'zones' => $deliveryOn ? (new DeliveryZone())->allActive() : [],
            'deliverySlots' => $deliveryOn ? (new DeliveryTimeSlot())->allActiveByType('delivery') : [],
            'pickupSlots' => $pickupOn ? (new DeliveryTimeSlot())->allActiveByType('pickup') : [],
        ]);
    }

    public function placeOrder(): void
    {
        Security::requireCsrf();

        if (!Feature::on('store')) {
            $this->abort404();
        }
        if (!Feature::storeAvailable()) {
            flash('danger', 'The store is temporarily unavailable.');
            redirect('store');
        }

        $isMember = Auth::hasRole('member');
        if (!$isMember && !Feature::on('guest_checkout')) {
            flash('danger', 'Please log in to check out.');
            redirect('login');
        }

        [$userId, $cartToken] = $this->cartIdentity();
        $cartModel = new Cart();
        $productModel = new Product();
        $lines = array_map([$productModel, 'withComputedOffer'], $cartModel->forIdentity($userId, $cartToken));

        if (!$lines) {
            flash('danger', 'Your cart is empty.');
            redirect('cart');
        }

        $createAccount = !$isMember && $this->input('create_account') === '1';

        $name = $this->input('full_name');
        $email = $this->input('email');
        $phone = $this->input('phone');

        $deliveryOn = Feature::deliveryOn();
        $pickupOn = Feature::pickupOn();

        $fulfillmentMethod = $this->input('fulfillment_method', 'delivery');
        if (!in_array($fulfillmentMethod, ['delivery', 'pickup'], true)) {
            $fulfillmentMethod = 'delivery';
        }
        if ($fulfillmentMethod === 'delivery' && !$deliveryOn) {
            $fulfillmentMethod = 'pickup';
        } elseif ($fulfillmentMethod === 'pickup' && !$pickupOn) {
            $fulfillmentMethod = 'delivery';
        }
        $isPickup = $fulfillmentMethod === 'pickup';

        $zoneId = null;
        if (!$isPickup) {
            $activeZones = (new DeliveryZone())->allActive();
            if (!empty($activeZones)) {
                $rawZoneId = $this->input('zone_id');
                $zoneId = $rawZoneId !== '' ? (int) $rawZoneId : null;
                if ($zoneId === null || !in_array($zoneId, array_map('intval', array_column($activeZones, 'id')), true)) {
                    flash('danger', 'Please select your delivery zone.');
                    redirect('checkout');
                }
            }
        }

        $timeSlotId = null;
        $rawTimeSlotId = $isPickup ? $this->input('pickup_time_slot_id') : $this->input('delivery_time_slot_id');
        if ($rawTimeSlotId !== '') {
            $slot = (new DeliveryTimeSlot())->find((int) $rawTimeSlotId);
            if ($slot && (bool) $slot['is_active'] && $slot['type'] === $fulfillmentMethod) {
                $timeSlotId = (int) $slot['id'];
            }
        }

        $validator = new Validator([
            'full_name' => $name, 'email' => $email, 'phone' => $phone,
            'delivery_address' => $this->input('delivery_address'),
            'delivery_city' => $this->input('delivery_city'),
            'password' => $this->rawInput('password'),
        ]);
        $validator->required('full_name', 'Full name')
            ->required('email', 'Email')->email('email')
            ->phone('phone');

        if (!$isPickup) {
            $validator->required('delivery_address', 'Delivery address')
                ->required('delivery_city', 'City');
        }

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
            'fulfillment_method' => $fulfillmentMethod,
            'zone_id' => $zoneId,
            'time_slot_id' => $timeSlotId,
            'delivery_address' => $this->input('delivery_address'),
            'delivery_city' => $this->input('delivery_city'),
            'delivery_area' => $this->input('delivery_area') ?: null,
            'delivery_postal_code' => $this->input('delivery_postal_code') ?: null,
            'order_notes' => $this->rawInput('order_notes') ?: null,
        ];

        $paymentMethod = $this->input('payment_method', '');
        if (!in_array($paymentMethod, self::ALLOWED_PAYMENT_METHODS, true)) {
            flash('danger', 'Please select a payment method.');
            redirect('checkout');
        }

        $referenceNo = $this->input('reference_no') ?: null;
        if ($paymentMethod !== 'cod' && !$referenceNo) {
            flash('danger', 'Please enter the transaction/reference ID for your selected payment method.');
            redirect('checkout');
        }

        $payment = [
            'method' => $paymentMethod,
            'discount' => 0.0,
            'couponCode' => Feature::on('coupons') ? ($this->input('coupon_code') ?: null) : null,
            'reference_no' => $referenceNo,
        ];

        $cartLines = array_map(fn ($l) => ['product_id' => (int) $l['id'], 'qty' => (int) $l['qty']], $lines);

        try {
            $result = (new Order())->create($cartLines, $userId, $customer, $payment);
        } catch (Throwable $e) {
            flash('danger', $e->getMessage());
            redirect('checkout');
        }

        if ($userId && !$isPickup && $this->input('save_address') === '1') {
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
