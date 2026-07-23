<?php
/** @var array $member */
/** @var array|null $preferredPackage */
/** @var array $subscriptionHistory */
/** @var array $attendanceLog */
/** @var array|null $openSession */
/** @var array $packages */
/** @var float $trainerFeeDefault */
/** @var float $lockerFineDefault */
/** @var array $eligibleCoupons */
$statusColors = ['pending' => 'secondary', 'active' => 'success', 'suspended' => 'danger', 'frozen' => 'info', 'expired' => 'dark'];
?>
<div class="row g-4">
  <div class="col-md-4">
    <div class="admin-card text-center">
      <?= media_tile($member['photo'], $member['name'], 'bi-person', 'thumb-lg mb-3', null) ?>
      <h5 class="mb-0"><?= e($member['name']) ?></h5>
      <div class="text-white-50 small mb-2">
        <?= e($member['member_code']) ?>
        <?php if (!empty($member['money_received_no'])): ?>
          &nbsp;|&nbsp; Receipt: <?= e($member['money_received_no']) ?>
        <?php endif; ?>
      </div>
      <span class="badge text-bg-<?= $statusColors[$member['status']] ?? 'secondary' ?> mb-3"><?= e(ucfirst($member['status'])) ?></span>
      <ul class="list-unstyled text-start small">
        <li><i class="bi bi-envelope"></i> <?= e($member['email']) ?></li>
        <li><i class="bi bi-telephone"></i> <?= e($member['phone'] ?? '—') ?></li>
        <li><i class="bi bi-person-badge"></i> Trainer: <?= e($member['trainer_name'] ?? '—') ?></li>
        <li><i class="bi bi-box"></i> Locker: <?= e($member['locker_number'] ?? '—') ?></li>
        <li><i class="bi bi-calendar"></i> Joined: <?= format_date($member['join_date']) ?></li>
        <li><i class="bi bi-rulers"></i> BMI: <?= $member['bmi'] ? e((string) $member['bmi']) . ' (' . e($member['bmi_category']) . ')' : '—' ?></li>
      </ul>
      <div class="d-flex gap-2 justify-content-center mt-2">
        <a href="<?= url('/admin/members/' . $member['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        <?php if ($openSession): ?>
        <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/checkout') ?>">
          <?= Security::csrfField() ?>
          <button type="submit" class="btn btn-ps btn-sm"><i class="bi bi-box-arrow-left"></i> Check Out</button>
        </form>
        <?php else: ?>
        <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/checkin') ?>">
          <?= Security::csrfField() ?>
          <button type="submit" class="btn btn-ps btn-sm"><i class="bi bi-box-arrow-in-right"></i> Check In</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <?php $reportedMethodLabels = ['bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer']; ?>
    <?php if ($preferredPackage || !empty($member['registration_notes']) || !empty($member['reported_payment_reference'])): ?>
    <div class="admin-card mb-4" style="border-left:3px solid var(--bs-warning)">
      <h6 class="mb-3"><i class="bi bi-inbox"></i> Online Registration Request</h6>
      <div class="d-flex flex-wrap gap-4 small">
        <?php if ($preferredPackage): ?>
          <div><span class="text-white-50">Preferred Package</span><br><span class="text-white"><?= e($preferredPackage['name']) ?> (৳<?= number_format((float) $preferredPackage['display_price']) ?>)</span></div>
        <?php endif; ?>
        <?php if (!empty($member['trainer_name'])): ?>
          <div><span class="text-white-50">Preferred Trainer</span><br><span class="text-white"><?= e($member['trainer_name']) ?></span></div>
        <?php endif; ?>
      </div>
      <?php if (!empty($member['reported_payment_reference']) || !empty($member['reported_payment_method']) || !empty($member['reported_payer_number'])): ?>
        <div class="mt-2 small alert alert-warning py-2 px-3 mb-0">
          <i class="bi bi-exclamation-triangle"></i> Visitor reports they already paid online —
          <strong><?= e($reportedMethodLabels[$member['reported_payment_method']] ?? '—') ?></strong>,
          <?php if (!empty($member['reported_payer_number'])): ?>
            sender number: <strong><?= e($member['reported_payer_number']) ?></strong>,
          <?php endif; ?>
          ref: <strong><?= e($member['reported_payment_reference'] ?? '—') ?></strong>.
          Not verified — confirm before activating.
        </div>
      <?php endif; ?>
      <?php if (!empty($member['registration_notes'])): ?>
        <div class="mt-2 small"><span class="text-white-50">Notes</span><br><span class="text-white"><?= nl2br(e($member['registration_notes'])) ?></span></div>
      <?php endif; ?>
      <p class="text-white-50 small mb-0 mt-2">Verify payment below to activate this membership — the package they actually pay for may differ from their preference above.</p>
    </div>
    <?php endif; ?>

    <div class="admin-card mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0"><?= $member['status'] === 'pending' ? 'Activate Membership' : 'Renew / Purchase Package' ?></h6>
      </div>
      <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/renew') ?>" class="row g-3 admin-form">
        <?= Security::csrfField() ?>
        <div class="col-md-4">
          <label>Package</label>
          <select name="package_id" id="showRenewPackageSelect" class="form-select" required>
            <option value="">Select a package</option>
            <?php foreach ($packages as $package): ?>
              <option value="<?= (int) $package['id'] ?>" data-price="<?= (float) $package['display_price'] ?>"><?= e($package['name']) ?> (৳<?= number_format((float) $package['display_price']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label>Start Date</label>
          <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-2">
          <label>Price Paid (৳)</label>
          <input type="number" step="0.01" min="0" name="price_paid" id="showRenewPricePaid" class="form-control">
          <small class="text-white-50 d-none" id="showRenewCouponHint"></small>
        </div>
        <div class="col-md-3">
          <label>Payment Method</label>
          <select name="payment_method" class="form-select payment-method-select" required>
            <option value="" disabled selected>Select Payment Method</option>
            <option value="cash">Cash</option>
            <option value="card">Card</option>
            <option value="bkash">bKash</option>
            <option value="nagad">Nagad</option>
            <option value="rocket">Rocket</option>
            <option value="bank_transfer">Bank Transfer</option>
          </select>
        </div>
        <div class="col-md-4 reference-no-wrap d-none">
          <label>Transaction / Reference ID</label>
          <input type="text" name="reference_no" class="form-control reference-no-input" placeholder="e.g. bKash transaction ID">
        </div>

        <?php if (!empty($eligibleCoupons)): ?>
        <div class="col-12">
          <label>Available Coupons <small class="text-white-50">(optional — select a package first to see eligibility)</small></label>
          <div class="d-flex flex-column gap-2" id="showRenewCouponList">
            <?php foreach ($eligibleCoupons as $coupon): ?>
            <label class="coupon-option-admin p-2 px-3 d-flex justify-content-between align-items-center gap-3"
                   data-discount-type="<?= e($coupon['discount_type']) ?>"
                   data-discount-value="<?= (float) $coupon['discount_value'] ?>"
                   data-max-discount="<?= $coupon['max_discount_amount'] !== null ? (float) $coupon['max_discount_amount'] : '' ?>"
                   data-min-purchase="<?= (float) $coupon['min_purchase'] ?>">
              <span class="d-flex align-items-center gap-2">
                <input type="radio" name="coupon_code" value="<?= e($coupon['code']) ?>" class="form-check-input coupon-radio-admin">
                <span>
                  <strong class="text-white"><?= e($coupon['code']) ?></strong>
                  <span class="text-white-50 small d-block"><?= e($coupon['title']) ?></span>
                </span>
              </span>
              <span class="text-orange fw-semibold small text-nowrap">
                <?= $coupon['discount_type'] === 'percent' ? number_format((float) $coupon['discount_value'], 0) . '% Off' : '৳' . number_format((float) $coupon['discount_value']) . ' Off' ?>
              </span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <script>
        (function () {
          var pkgSelect = document.getElementById('showRenewPackageSelect');
          var priceField = document.getElementById('showRenewPricePaid');
          var couponHint = document.getElementById('showRenewCouponHint');
          var list = document.getElementById('showRenewCouponList');
          if (!pkgSelect || !list) return;

          function refreshEligibility() {
            var opt = pkgSelect.options[pkgSelect.selectedIndex];
            var price = opt ? parseFloat(opt.getAttribute('data-price') || '0') : 0;
            list.querySelectorAll('.coupon-option-admin').forEach(function (row) {
              var min = parseFloat(row.dataset.minPurchase || '0');
              var eligible = price > 0 && price >= min;
              row.classList.toggle('coupon-ineligible-admin', !eligible);
              var radio = row.querySelector('.coupon-radio-admin');
              radio.disabled = !eligible;
              if (!eligible && radio.checked) { radio.checked = false; applyCoupon(); }
            });
          }

          // Price Paid always holds the package's raw (pre-coupon) price — the server subtracts
          // the coupon discount from whatever's in this field (renewMember() ->
          // applyMembershipCoupon()). Pre-subtracting it here too would double-apply the coupon.
          // The hint below just previews what the server will actually charge.
          function applyCoupon() {
            var opt = pkgSelect.options[pkgSelect.selectedIndex];
            var price = opt ? parseFloat(opt.getAttribute('data-price') || '0') : 0;
            if (price > 0 && priceField) priceField.value = price.toFixed(2);

            var checked = list.querySelector('.coupon-radio-admin:checked');
            if (!checked || !couponHint) { if (couponHint) couponHint.classList.add('d-none'); return; }
            var row = checked.closest('.coupon-option-admin');
            var type = row.dataset.discountType;
            var value = parseFloat(row.dataset.discountValue || '0');
            var maxDiscount = row.dataset.maxDiscount ? parseFloat(row.dataset.maxDiscount) : null;
            var discount = type === 'percent' ? (price * value / 100) : Math.min(price, value);
            if (maxDiscount !== null && !isNaN(maxDiscount)) discount = Math.min(discount, maxDiscount);
            var finalAmount = Math.max(0, price - discount);
            couponHint.textContent = 'After coupon: ৳' + finalAmount.toFixed(2) + ' — the coupon is applied automatically on save.';
            couponHint.classList.remove('d-none');
          }

          pkgSelect.addEventListener('change', function () { refreshEligibility(); applyCoupon(); });
          list.querySelectorAll('.coupon-radio-admin').forEach(function (radio) {
            radio.addEventListener('change', applyCoupon);
          });
        })();
        </script>
        <?php endif; ?>

        <div class="col-12">
          <button type="submit" class="btn btn-ps btn-sm"><?= $member['status'] === 'pending' ? 'Activate Membership' : 'Confirm Renewal' ?></button>
        </div>
      </form>
    </div>

    <?php if (Feature::trainerFeeOn()): ?>
    <div class="admin-card mb-4">
      <h6 class="mb-3">Charge Trainer Fee</h6>
      <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/charge-trainer-fee') ?>" class="row g-3 admin-form">
        <?= Security::csrfField() ?>
        <div class="col-md-3">
          <label>Amount (৳)</label>
          <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= $trainerFeeDefault > 0 ? e((string) $trainerFeeDefault) : '' ?>" required>
        </div>
        <div class="col-md-3">
          <label>Payment Method</label>
          <select name="payment_method" class="form-select payment-method-select" required>
            <option value="" disabled selected>Select Payment Method</option>
            <option value="cash">Cash</option>
            <option value="card">Card</option>
            <option value="bkash">bKash</option>
            <option value="nagad">Nagad</option>
            <option value="rocket">Rocket</option>
            <option value="bank_transfer">Bank Transfer</option>
          </select>
        </div>
        <div class="col-md-4 reference-no-wrap d-none">
          <label>Transaction / Reference ID</label>
          <input type="text" name="reference_no" class="form-control reference-no-input" placeholder="e.g. bKash transaction ID">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-ps-outline btn-sm">Charge Trainer Fee</button>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="admin-card mb-4">
      <h6 class="mb-3">Charge Locker Fine</h6>
      <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/charge-locker-fine') ?>" class="row g-3 admin-form">
        <?= Security::csrfField() ?>
        <div class="col-md-3">
          <label>Amount (৳)</label>
          <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= $lockerFineDefault > 0 ? e((string) $lockerFineDefault) : '' ?>" required>
        </div>
        <div class="col-md-3">
          <label>Payment Method</label>
          <select name="payment_method" class="form-select payment-method-select" required>
            <option value="" disabled selected>Select Payment Method</option>
            <option value="cash">Cash</option>
            <option value="card">Card</option>
            <option value="bkash">bKash</option>
            <option value="nagad">Nagad</option>
            <option value="rocket">Rocket</option>
            <option value="bank_transfer">Bank Transfer</option>
          </select>
        </div>
        <div class="col-md-4 reference-no-wrap d-none">
          <label>Transaction / Reference ID</label>
          <input type="text" name="reference_no" class="form-control reference-no-input" placeholder="e.g. bKash transaction ID">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-outline-danger btn-sm">Charge Locker Fine</button>
        </div>
      </form>
    </div>

    <div class="admin-card mb-4">
      <h6 class="mb-3">Subscription History</h6>
      <?php if (empty($subscriptionHistory)): ?>
        <p class="text-white-50 mb-0">No subscriptions yet.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Package</th><th>Start</th><th>End</th><th>Price Paid</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($subscriptionHistory as $sub): ?>
            <tr>
              <td><?= e($sub['package_name']) ?></td>
              <td><?= format_date($sub['start_date']) ?></td>
              <td><?= format_date($sub['end_date']) ?></td>
              <td><?= money((float) $sub['price_paid']) ?></td>
              <td><?= e(ucfirst($sub['status'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <div class="admin-card">
      <h6 class="mb-3">Recent Attendance</h6>
      <?php if (empty($attendanceLog)): ?>
        <p class="text-white-50 mb-0">No attendance records yet.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Check In</th><th>Check Out</th></tr></thead>
          <tbody>
            <?php foreach ($attendanceLog as $log): ?>
            <tr>
              <td><?= format_date($log['check_in'], 'd M Y, h:i A') ?></td>
              <td><?= $log['check_out'] ? format_date($log['check_out'], 'd M Y, h:i A') : '—' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
