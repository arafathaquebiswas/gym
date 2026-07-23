<?php
$pageTitle = 'Checkout';
/** @var array $lines */
/** @var float $subtotal */
/** @var float $estimatedShipping */
/** @var float $estimatedTax */
/** @var float $freeShippingMin */
/** @var array $savedAddresses */
/** @var array|null $member */
$isMember = Auth::hasRole('member');
$currentUser = Auth::user();
?>

<section class="section">
  <div class="container">
    <h1 class="mb-4">Checkout</h1>
    <form method="post" action="<?= url('/checkout/place') ?>" class="form-ps">
      <?= Security::csrfField() ?>
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="glass-card p-4 mb-4">
            <h6 class="mb-3">Delivery Details</h6>
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
              <div class="col-12">
                <label>Order Notes</label>
                <textarea name="order_notes" class="form-control" rows="2" placeholder="Any special instructions..."></textarea>
              </div>
              <?php if ($isMember): ?>
              <div class="col-12 form-check">
                <input type="checkbox" name="save_address" value="1" class="form-check-input" id="saveAddress" checked>
                <label class="form-check-label small" for="saveAddress">Save this address for next time</label>
              </div>
              <?php endif; ?>
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
              <input type="radio" name="payment_method" value="<?= $val ?>" class="form-check-input payment-method-radio" id="pm_<?= $val ?>" <?= $val === 'cod' ? 'checked' : '' ?>>
              <label class="form-check-label" for="pm_<?= $val ?>"><?= $label ?></label>
            </div>
            <?php endforeach; ?>
            <div id="referenceNoField" class="mt-2 d-none">
              <label>Transaction / Reference ID</label>
              <input type="text" name="reference_no" class="form-control" placeholder="e.g. bKash transaction ID">
              <p class="text-white-50 small mt-1">Enter the transaction ID from your payment app — our team will verify it.</p>
            </div>
            <div class="mt-3">
              <label>Coupon Code</label>
              <input type="text" name="coupon_code" class="form-control" placeholder="Optional">
            </div>
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
              <span><?= $estimatedShipping > 0 ? '৳' . number_format($estimatedShipping) : 'Free' ?></span>
            </div>
            <?php if ($estimatedTax > 0): ?>
            <div class="d-flex justify-content-between"><span class="text-white-50">Tax</span><span>৳<?= number_format($estimatedTax) ?></span></div>
            <?php endif; ?>
            <hr>
            <div class="d-flex justify-content-between fw-bold fs-5"><span>Estimated Total</span><span class="text-orange">৳<?= number_format($subtotal + $estimatedShipping + $estimatedTax) ?></span></div>
            <p class="text-white-50 small mt-2">
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
    document.getElementById('referenceNoField').classList.toggle('d-none', this.value === 'cod');
  });
});

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
