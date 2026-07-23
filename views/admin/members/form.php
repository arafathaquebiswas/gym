<?php
/** @var array|null $member */
/** @var array $trainers */
/** @var array $packages */
$isEdit = $member !== null;
$action = $isEdit ? url('/admin/members/' . $member['id']) : url('/admin/members');
$v = fn ($key, $default = '') => e((string) ($member[$key] ?? $default));
?>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="admin-form">
  <?= Security::csrfField() ?>

  <div class="admin-card mb-4">
    <div class="admin-form-section">
      <h6>Account</h6>
      <div class="row g-3">
        <div class="col-md-4">
          <label>Full Name *</label>
          <input type="text" name="name" class="form-control" value="<?= $v('name') ?>" required>
        </div>
        <div class="col-md-4">
          <label>Phone Number *</label>
          <input type="text" name="phone" class="form-control" value="<?= $v('phone') ?>" required>
        </div>
        <div class="col-md-4">
          <label>Email <small class="text-white-50">(optional)</small></label>
          <input type="email" name="email" class="form-control" value="<?= $v('email') ?>">
        </div>
        <?php if (!$isEdit): ?>
        <div class="col-md-4">
          <label>Login Password <small class="text-white-50">(leave blank to auto-generate)</small></label>
          <input type="password" name="password" class="form-control">
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Personal Details</h6>
      <div class="row g-3">
        <div class="col-md-3">
          <label>Date of Birth</label>
          <input type="date" name="dob" class="form-control" value="<?= $v('dob') ?>">
        </div>
        <div class="col-md-3">
          <label>Gender</label>
          <select name="gender" class="form-select">
            <option value="">—</option>
            <option value="male" <?= ($member['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= ($member['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
            <option value="other" <?= ($member['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div class="col-md-3">
          <label>Blood Group</label>
          <input type="text" name="blood_group" class="form-control" value="<?= $v('blood_group') ?>" placeholder="e.g. O+">
        </div>
        <div class="col-md-3">
          <label>Emergency Contact</label>
          <input type="text" name="emergency_contact" class="form-control" value="<?= $v('emergency_contact') ?>">
        </div>
        <div class="col-12">
          <label>Address</label>
          <input type="text" name="address" class="form-control" value="<?= $v('address') ?>">
        </div>
        <div class="col-md-3">
          <label>Height (cm)</label>
          <input type="number" step="0.1" min="0" name="height_cm" class="form-control" value="<?= $v('height_cm') ?>">
        </div>
        <div class="col-md-3">
          <label>Weight (kg)</label>
          <input type="number" step="0.1" min="0" name="weight_kg" class="form-control" value="<?= $v('weight_kg') ?>">
        </div>
        <div class="col-md-6">
          <label>Fitness Goal</label>
          <input type="text" name="fitness_goal" class="form-control" value="<?= $v('fitness_goal') ?>" placeholder="e.g. Weight loss, Muscle gain">
        </div>
        <div class="col-12">
          <label>Medical Notes</label>
          <textarea name="medical_notes" class="form-control" rows="2"><?= $v('medical_notes') ?></textarea>
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Membership</h6>
      <div class="row g-3">
        <div class="col-md-3">
          <label>Joining Date *</label>
          <input type="date" name="join_date" class="form-control" value="<?= $v('join_date', date('Y-m-d')) ?>" required>
        </div>
        <?php if ($isEdit): ?>
        <div class="col-md-3">
          <label>Status</label>
          <?php $statusLabels = ['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired']; ?>
          <div>
            <span class="badge text-bg-<?= ['pending' => 'secondary', 'active' => 'success', 'expired' => 'dark'][$member['status']] ?? 'secondary' ?> py-2 px-3">
              <?= e($statusLabels[$member['status']] ?? ucfirst($member['status'])) ?>
            </span>
          </div>
          <small class="text-white-50">Set automatically by package purchases/renewals — not editable here.</small>
        </div>
        <?php endif; ?>
        <?php if (Feature::trainerModuleOn()): ?>
        <div class="col-md-3">
          <label>Assigned Trainer</label>
          <select name="trainer_id" class="form-select">
            <option value="">— None —</option>
            <?php foreach ($trainers as $trainer): ?>
              <option value="<?= (int) $trainer['id'] ?>" <?= (string) ($member['trainer_id'] ?? '') === (string) $trainer['id'] ? 'selected' : '' ?>><?= e($trainer['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="col-md-3">
          <label>Locker Number</label>
          <input type="text" name="locker_number" class="form-control" value="<?= $v('locker_number') ?>">
        </div>
      </div>
    </div>

    <?php if (!$isEdit): ?>
    <div class="admin-form-section">
      <h6>Membership Package <small class="text-white-50">(mandatory — a walk-in Add Member always activates immediately)</small></h6>
      <div class="row g-3">
        <div class="col-md-4">
          <label>Package *</label>
          <select name="package_id" id="initialPackageSelect" class="form-select" required>
            <option value="" disabled selected>Select a package</option>
            <?php foreach ($packages as $package): ?>
              <option value="<?= (int) $package['id'] ?>" data-duration="<?= (int) $package['duration_days'] ?>" data-price="<?= (float) $package['display_price'] ?>">
                <?= e($package['name']) ?> (৳<?= number_format((float) $package['display_price']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Start Date *</label>
          <input type="date" name="start_date" id="initialStartDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="col-md-4">
          <label>Expiry Date <small class="text-white-50">(auto-calculated)</small></label>
          <input type="text" id="initialExpiryDisplay" class="form-control" value="—" readonly>
        </div>
        <div class="col-md-4">
          <label>Coupon Code <small class="text-white-50">(optional)</small></label>
          <input type="text" name="coupon_code" class="form-control text-uppercase" placeholder="e.g. SAVE10">
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Payment Information <small class="text-white-50">(mandatory — the membership stays Pending until this is complete)</small></h6>
      <div class="row g-3">
        <div class="col-md-4">
          <label>Payment Method *</label>
          <select name="payment_method" class="form-select payment-method-select" required>
            <option value="" disabled selected>Select Payment Method</option>
            <option value="cash">Cash</option>
            <option value="bkash">bKash</option>
            <option value="nagad">Nagad</option>
            <option value="rocket">Rocket</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="card">Card</option>
          </select>
        </div>
        <div class="col-md-4">
          <label>Amount Received (৳) *</label>
          <input type="number" step="0.01" min="0.01" name="price_paid" id="initialAmountReceived" class="form-control" required>
        </div>

        <div class="col-md-4 d-none" data-payment-fields="bkash,nagad,rocket">
          <label>Sender Number *</label>
          <input type="text" name="payer_number" class="form-control" data-payment-required placeholder="e.g. 017XXXXXXXX">
        </div>
        <div class="col-md-4 d-none" data-payment-fields="bkash,nagad,rocket,card,bank_transfer">
          <label>Transaction ID / Reference / Approval Number *</label>
          <input type="text" name="reference_no" class="form-control" data-payment-required>
        </div>
        <div class="col-md-3 d-none" data-payment-fields="card">
          <label>Card Type <small class="text-white-50">(optional)</small></label>
          <input type="text" name="card_type" class="form-control" placeholder="e.g. Visa, Mastercard">
        </div>
        <div class="col-md-3 d-none" data-payment-fields="card">
          <label>Last 4 Digits <small class="text-white-50">(optional)</small></label>
          <input type="text" name="card_last4" maxlength="4" class="form-control" placeholder="1234">
        </div>
        <div class="col-md-4 d-none" data-payment-fields="bank_transfer">
          <label>Bank Name *</label>
          <input type="text" name="bank_name" class="form-control" data-payment-required>
        </div>
        <div class="col-md-4 d-none" data-payment-fields="bank_transfer">
          <label>Account Number <small class="text-white-50">(optional)</small></label>
          <input type="text" name="account_number" class="form-control">
        </div>
      </div>
    </div>
    <script>
    (function () {
      var pkg = document.getElementById('initialPackageSelect');
      var startDate = document.getElementById('initialStartDate');
      var expiryDisplay = document.getElementById('initialExpiryDisplay');
      var amountField = document.getElementById('initialAmountReceived');
      if (!pkg || !startDate || !expiryDisplay) return;

      function recalc() {
        var opt = pkg.options[pkg.selectedIndex];
        if (!opt || !opt.value || !startDate.value) { expiryDisplay.value = '—'; return; }
        var duration = parseInt(opt.getAttribute('data-duration') || '0', 10);
        var start = new Date(startDate.value + 'T00:00:00');
        start.setDate(start.getDate() + duration);
        expiryDisplay.value = start.toISOString().slice(0, 10);
        if (amountField && !amountField.value) {
          amountField.value = parseFloat(opt.getAttribute('data-price') || '0').toFixed(2);
        }
      }
      pkg.addEventListener('change', recalc);
      startDate.addEventListener('change', recalc);
    })();
    </script>
    <?php endif; ?>

    <div class="admin-form-section">
      <h6>Photo</h6>
      <div class="row g-3">
        <div class="col-md-6">
          <?php if ($isEdit && $member['photo']): ?>
            <div class="mb-2"><?= media_tile($member['photo'], $member['name'], 'bi-person', '', null) ?></div>
          <?php endif; ?>
          <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mt-2">
      <button type="submit" class="btn btn-ps"><?= $isEdit ? 'Save Changes' : 'Add Member' ?></button>
      <a href="<?= url('/admin/members') ?>" class="btn btn-ps-outline">Cancel</a>
    </div>
  </div>
</form>
