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
          <label>Email *</label>
          <input type="email" name="email" class="form-control" value="<?= $v('email') ?>" required>
        </div>
        <div class="col-md-4">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= $v('phone') ?>">
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
          <label>Join Date</label>
          <input type="date" name="join_date" class="form-control" value="<?= $v('join_date', date('Y-m-d')) ?>">
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
      <h6>Initial Package <small class="text-white-50">(optional — starts a subscription immediately)</small></h6>
      <div class="row g-3">
        <div class="col-md-4">
          <label>Package</label>
          <select name="package_id" id="initialPackageSelect" class="form-select">
            <option value="">— None —</option>
            <?php foreach ($packages as $package): ?>
              <option value="<?= (int) $package['id'] ?>"><?= e($package['name']) ?> (৳<?= number_format((float) $package['display_price']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Price Paid (৳)</label>
          <input type="number" step="0.01" min="0" name="price_paid" class="form-control" placeholder="Defaults to package price">
        </div>
        <div class="col-md-4">
          <label>Payment Method <small class="text-white-50">(required if a package is selected)</small></label>
          <select name="payment_method" id="initialPaymentMethodSelect" class="form-select payment-method-select">
            <option value="" selected>— Select —</option>
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
        <div class="col-md-4">
          <label>Coupon Code <small class="text-white-50">(optional)</small></label>
          <input type="text" name="coupon_code" class="form-control text-uppercase" placeholder="e.g. SAVE10">
        </div>
      </div>
    </div>
    <script>
    (function () {
      var pkg = document.getElementById('initialPackageSelect');
      var method = document.getElementById('initialPaymentMethodSelect');
      if (!pkg || !method) return;
      pkg.addEventListener('change', function () {
        method.required = pkg.value !== '';
      });
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
