<?php
$pageTitle = 'Checkout';
/** @var array $lines */
/** @var float $subtotal */
/** @var float $estimatedShipping */
/** @var float $estimatedTax */
/** @var float $freeShippingMin */
/** @var array $savedAddresses */
/** @var array|null $member */
/** @var string $gymName */
/** @var string|null $gymAddress */
/** @var string|null $gymPhone */
/** @var bool $deliveryOn */
/** @var bool $pickupOn */
/** @var array $zones */
/** @var array $deliverySlots */
/** @var array $pickupSlots */
$isMember = Auth::hasRole('member');
$currentUser = Auth::user();
$defaultFulfillment = $deliveryOn ? 'delivery' : 'pickup';
?>

<section class="section">
  <div class="container">
    <h1 class="mb-4">Checkout</h1>
    <form method="post" action="<?= url('/checkout/place') ?>" class="form-ps">
      <?= Security::csrfField() ?>
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="glass-card p-4 mb-4">
            <h6 class="mb-3">Delivery or Pickup</h6>
            <?php if ($deliveryOn && $pickupOn): ?>
            <div class="d-flex gap-4 mb-3">
              <div class="form-check">
                <input type="radio" name="fulfillment_method" value="delivery" class="form-check-input fulfillment-radio" id="fmDelivery" checked>
                <label class="form-check-label" for="fmDelivery">Home Delivery</label>
              </div>
              <div class="form-check">
                <input type="radio" name="fulfillment_method" value="pickup" class="form-check-input fulfillment-radio" id="fmPickup">
                <label class="form-check-label" for="fmPickup">Store Pickup</label>
              </div>
            </div>
            <?php else: ?>
              <input type="hidden" name="fulfillment_method" value="<?= $defaultFulfillment ?>">
              <p class="text-white-50 small mb-3"><?= $deliveryOn ? 'Home delivery only — store pickup is currently unavailable.' : 'Store pickup only — home delivery is currently unavailable.' ?></p>
            <?php endif; ?>

            <div id="pickupInfo" class="glass-card p-3 mb-3 <?= $defaultFulfillment === 'pickup' ? '' : 'd-none' ?>" style="background:rgba(255,255,255,.03)">
              <p class="mb-1"><i class="bi bi-geo-alt text-orange"></i> Pick up at <strong><?= e($gymName) ?></strong></p>
              <?php if ($gymAddress): ?><p class="text-white-50 small mb-1"><?= e($gymAddress) ?></p><?php endif; ?>
              <?php if ($gymPhone): ?><p class="text-white-50 small mb-0">Phone: <?= e($gymPhone) ?></p><?php endif; ?>
              <?php if (!empty($pickupSlots)): ?>
              <div class="mt-2">
                <label>Preferred Pickup Time</label>
                <select name="pickup_time_slot_id" class="form-select pickup-time-slot">
                  <option value="">No preference</option>
                  <?php foreach ($pickupSlots as $slot): ?>
                    <option value="<?= (int) $slot['id'] ?>"><?= e($slot['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
            </div>

            <div id="deliveryFields" class="<?= $defaultFulfillment === 'pickup' ? 'd-none' : '' ?>">
              <?php if (!empty($zones)): ?>
              <div class="mb-3">
                <label>Delivery Zone *</label>
                <select name="zone_id" id="fZone" class="form-select" <?= $deliveryOn ? 'required' : '' ?>>
                  <option value="">— Select your zone —</option>
                  <?php foreach ($zones as $zone): ?>
                    <option value="<?= (int) $zone['id'] ?>" data-charge="<?= (float) $zone['charge'] ?>"><?= e($zone['name']) ?> (৳<?= number_format((float) $zone['charge']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <?php if (!empty($deliverySlots)): ?>
              <div class="mb-3">
                <label>Preferred Delivery Time</label>
                <select name="delivery_time_slot_id" class="form-select delivery-time-slot">
                  <option value="">No preference</option>
                  <?php foreach ($deliverySlots as $slot): ?>
                    <option value="<?= (int) $slot['id'] ?>"><?= e($slot['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <?php if (!empty($savedAddresses)): ?>
              <div class="mb-3">
                <label>Use a saved address</label>
                <select id="savedAddressPicker" class="form-select">
                  <option value="">— Enter new address —</option>
                  <?php foreach ($savedAddresses as $addr): ?>
                    <option value="<?= (int) $addr['id'] ?>"
                      data-name="<?= e($addr['full_name']) ?>" data-phone="<?= e($addr['phone']) ?>"
                      data-address="<?= e($addr['address']) ?>" data-city="<?= e($addr['city']) ?>"
                      data-area="<?= e($addr['area']) ?>" data-postal="<?= e($addr['postal_code']) ?>">
                      <?= e($addr['label']) ?> — <?= e($addr['address']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>

              <div class="row g-3">
                <div class="col-md-6">
                  <label>City *</label>
                  <input type="text" name="delivery_city" id="fCity" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label>Area</label>
                  <input type="text" name="delivery_area" id="fArea" class="form-control">
                </div>
                <div class="col-md-6">
                  <label>Postal Code</label>
                  <input type="text" name="delivery_postal_code" id="fPostal" class="form-control">
                </div>
                <div class="col-12">
                  <label>Delivery Address *</label>
                  <input type="text" name="delivery_address" id="fAddress" class="form-control" required>
                </div>
                <?php if ($isMember): ?>
                <div class="col-12 form-check">
                  <input type="checkbox" name="save_address" value="1" class="form-check-input" id="saveAddress" checked>
                  <label class="form-check-label small" for="saveAddress">Save this address for next time</label>
                </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <label>Full Name *</label>
                <input type="text" name="full_name" id="fFullName" class="form-control" value="<?= e($currentUser['name'] ?? '') ?>" required>
              </div>
              <div class="col-md-6">
                <label>Phone *</label>
                <input type="text" name="phone" id="fPhone" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" value="<?= e($currentUser['email'] ?? '') ?>" required>
              </div>
              <div class="col-12">
                <label>Order Notes</label>
                <textarea name="order_notes" class="form-control" rows="2" placeholder="Any special instructions..."></textarea>
              </div>
            </div>
          </div>

          <?php if (!$isMember): ?>
          <div class="glass-card p-4 mb-4">
            <div class="form-check mb-2">
              <input type="checkbox" name="create_account" value="1" class="form-check-input" id="createAccount" onchange="document.getElementById('createAccountFields').classList.toggle('d-none', !this.checked)">
              <label class="form-check-label" for="createAccount">Create an account with these details (optional)</label>
            </div>
            <div id="createAccountFields" class="d-none">
              <label>Password</label>
              <input type="password" name="password" class="form-control" placeholder="At least 8 characters">
            </div>
          </div>
          <?php endif; ?>

          <div class="glass-card p-4">
            <h6 class="mb-3">Payment Method</h6>
            <?php foreach (['cod' => 'Cash on Delivery', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'bank_transfer' => 'Bank Transfer'] as $val => $label): ?>
            <div class="form-check">
              <input type="radio" name="payment_method" value="<?= $val ?>" class="form-check-input payment-method-radio" id="pm_<?= $val ?>" required>
              <label class="form-check-label" for="pm_<?= $val ?>"><?= $label ?></label>
            </div>
            <?php endforeach; ?>
            <div id="referenceNoField" class="mt-2 d-none">
              <label>Transaction / Reference ID</label>
              <input type="text" name="reference_no" id="referenceNoInput" class="form-control" placeholder="e.g. bKash transaction ID">
              <p class="text-white-50 small mt-1">Enter the transaction ID from your payment app — our team will verify it.</p>
            </div>
            <?php if (Feature::on('coupons')): ?>
            <div class="mt-3">
              <label>Coupon Code</label>
              <input type="text" name="coupon_code" class="form-control" placeholder="Optional">
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="glass-card p-4">
            <h6 class="mb-3">Order Summary</h6>
            <?php foreach ($lines as $line): ?>
            <div class="d-flex justify-content-between small mb-2">
              <span><?= e($line['name']) ?> × <?= (int) $line['qty'] ?></span>
              <span>৳<?= number_format($line['display_price'] * $line['qty']) ?></span>
            </div>
            <?php endforeach; ?>
            <hr>
            <div class="d-flex justify-content-between"><span class="text-white-50">Subtotal</span><span>৳<?= number_format($subtotal) ?></span></div>
            <div class="d-flex justify-content-between">
              <span class="text-white-50">Shipping</span>
              <span id="summaryShipping"><?= $estimatedShipping > 0 ? '৳' . number_format($estimatedShipping) : 'Free' ?></span>
            </div>
            <?php if ($estimatedTax > 0): ?>
            <div class="d-flex justify-content-between"><span class="text-white-50">Tax</span><span>৳<?= number_format($estimatedTax) ?></span></div>
            <?php endif; ?>
            <hr>
            <div class="d-flex justify-content-between fw-bold fs-5"><span>Estimated Total</span><span class="text-orange" id="summaryTotal">৳<?= number_format($subtotal + $estimatedShipping + $estimatedTax) ?></span></div>
            <p class="text-white-50 small mt-2" id="freeShippingHint">
              Coupon discount (if any) is applied when the order is placed.
              <?php if ($freeShippingMin > 0 && $subtotal < $freeShippingMin): ?>
                Spend ৳<?= number_format($freeShippingMin - $subtotal) ?> more for free shipping!
              <?php endif; ?>
            </p>
            <button type="submit" class="btn btn-ps w-100 mt-3">Place Order</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</section>

<script>
document.querySelectorAll('.payment-method-radio').forEach(function (radio) {
  radio.addEventListener('change', function () {
    var needsReference = this.value !== 'cod';
    document.getElementById('referenceNoField').classList.toggle('d-none', !needsReference);
    document.getElementById('referenceNoInput').required = needsReference;
  });
});

(function () {
  var subtotal = <?= (float) $subtotal ?>;
  var tax = <?= (float) $estimatedTax ?>;
  var shippingEnabled = <?= $shippingEnabled ? 'true' : 'false' ?>;
  var freeShippingMin = <?= (float) $freeShippingMin ?>;
  var flatRate = <?= (float) $shippingFlatRate ?>;
  var maxOverride = <?= $shippingMaxOverride !== null ? (float) $shippingMaxOverride : 'null' ?>;
  var money = function (n) { return '৳' + Math.round(n).toLocaleString('en-US'); };

  var deliveryFields = document.getElementById('deliveryFields');
  var pickupInfo = document.getElementById('pickupInfo');
  var cityInput = document.getElementById('fCity');
  var addressInput = document.getElementById('fAddress');
  var zoneSelect = document.getElementById('fZone');
  var summaryShipping = document.getElementById('summaryShipping');
  var summaryTotal = document.getElementById('summaryTotal');
  var freeShippingHint = document.getElementById('freeShippingHint');

  function computeShipping(isPickup) {
    if (isPickup || !shippingEnabled) return 0;
    if (freeShippingMin > 0 && subtotal >= freeShippingMin) return 0;
    var zoneCharge = null;
    if (zoneSelect && zoneSelect.value) {
      var opt = zoneSelect.options[zoneSelect.selectedIndex];
      zoneCharge = parseFloat(opt.dataset.charge);
    }
    var base = zoneCharge !== null && !isNaN(zoneCharge) ? zoneCharge : flatRate;
    return maxOverride !== null ? Math.max(base, maxOverride) : base;
  }

  function applyFulfillment(method) {
    var isPickup = method === 'pickup';
    if (deliveryFields) deliveryFields.classList.toggle('d-none', isPickup);
    if (pickupInfo) pickupInfo.classList.toggle('d-none', !isPickup);
    if (cityInput) cityInput.required = !isPickup;
    if (addressInput) addressInput.required = !isPickup;
    if (zoneSelect) zoneSelect.required = !isPickup && zoneSelect.dataset.wasRequired === '1';
    if (freeShippingHint) freeShippingHint.classList.toggle('d-none', isPickup);

    var shipping = computeShipping(isPickup);
    summaryShipping.textContent = shipping > 0 ? money(shipping) : 'Free';
    summaryTotal.textContent = money(subtotal + shipping + tax);
  }

  var fulfillmentRadios = document.querySelectorAll('.fulfillment-radio');
  fulfillmentRadios.forEach(function (radio) {
    radio.addEventListener('change', function () { applyFulfillment(this.value); });
  });

  if (zoneSelect) {
    zoneSelect.dataset.wasRequired = zoneSelect.required ? '1' : '0';
    zoneSelect.addEventListener('change', function () {
      var checkedRadio = document.querySelector('.fulfillment-radio:checked');
      applyFulfillment(checkedRadio ? checkedRadio.value : '<?= $defaultFulfillment ?>');
    });
  }

  applyFulfillment('<?= $defaultFulfillment ?>');
})();

var picker = document.getElementById('savedAddressPicker');
if (picker) {
  picker.addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    if (!opt.value) return;
    document.getElementById('fFullName').value = opt.dataset.name || '';
    document.getElementById('fPhone').value = opt.dataset.phone || '';
    document.getElementById('fAddress').value = opt.dataset.address || '';
    document.getElementById('fCity').value = opt.dataset.city || '';
    document.getElementById('fArea').value = opt.dataset.area || '';
    document.getElementById('fPostal').value = opt.dataset.postal || '';
  });
}
</script>
