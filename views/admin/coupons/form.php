<?php
/** @var array|null $coupon */
$isEdit = $coupon !== null;
$action = $isEdit ? url('/admin/coupons/' . $coupon['id']) : url('/admin/coupons');
$v = fn ($key, $default = '') => e((string) ($coupon[$key] ?? $default));
?>
<form method="post" action="<?= $action ?>" class="admin-form">
  <?= Security::csrfField() ?>

  <div class="admin-card mb-4">
    <div class="admin-form-section">
      <h6>Coupon Details</h6>
      <div class="row g-3">
        <div class="col-md-4">
          <label>Coupon Code *</label>
          <input type="text" name="code" class="form-control text-uppercase" value="<?= $v('code') ?>" required style="letter-spacing:1px">
        </div>
        <div class="col-md-8">
          <label>Coupon Name *</label>
          <input type="text" name="title" class="form-control" value="<?= $v('title') ?>" required>
        </div>
        <div class="col-12">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="2"><?= $v('description') ?></textarea>
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Discount</h6>
      <div class="row g-3">
        <div class="col-md-3">
          <label>Discount Type</label>
          <select name="discount_type" class="form-select">
            <option value="percent" <?= ($coupon['discount_type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>Percentage</option>
            <option value="fixed" <?= ($coupon['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount</option>
          </select>
        </div>
        <div class="col-md-3">
          <label>Discount Value *</label>
          <input type="number" step="0.01" min="0" name="discount_value" class="form-control" value="<?= $v('discount_value', '0') ?>" required>
        </div>
        <div class="col-md-3">
          <label>Maximum Discount (৳) <small class="text-white-50">(optional cap)</small></label>
          <input type="number" step="0.01" min="0" name="max_discount_amount" class="form-control" value="<?= $v('max_discount_amount') ?>">
        </div>
        <div class="col-md-3">
          <label>Minimum Order Amount (৳)</label>
          <input type="number" step="0.01" min="0" name="min_purchase" class="form-control" value="<?= $v('min_purchase', '0') ?>">
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Scope &amp; Usage</h6>
      <div class="row g-3">
        <div class="col-md-3">
          <label>Applicable To</label>
          <select name="applies_to" class="form-select">
            <option value="product" <?= ($coupon['applies_to'] ?? 'both') === 'product' ? 'selected' : '' ?>>Store Products</option>
            <option value="membership" <?= ($coupon['applies_to'] ?? '') === 'membership' ? 'selected' : '' ?>>Membership</option>
            <option value="trainer" <?= ($coupon['applies_to'] ?? '') === 'trainer' ? 'selected' : '' ?>>Personal Trainer</option>
            <option value="both" <?= ($coupon['applies_to'] ?? 'both') === 'both' ? 'selected' : '' ?>>Entire Order</option>
          </select>
          <p class="text-white-50 small mt-1 mb-0">"Personal Trainer" is schema-ready but not yet enforced anywhere — trainer bookings have no checkout step yet.</p>
        </div>
        <div class="col-md-3">
          <label>Maximum Usage <small class="text-white-50">(total, optional)</small></label>
          <input type="number" min="0" name="usage_limit" class="form-control" value="<?= $v('usage_limit') ?>">
        </div>
        <div class="col-md-3">
          <label>Usage Per Customer <small class="text-white-50">(optional)</small></label>
          <input type="number" min="0" name="per_customer_limit" class="form-control" value="<?= $v('per_customer_limit') ?>">
        </div>
        <div class="col-md-3 form-check mt-4 pt-2">
          <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" <?= $isEdit ? (!empty($coupon['is_active']) ? 'checked' : '') : 'checked' ?>>
          <label class="form-check-label" for="isActive">Active</label>
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Validity Window</h6>
      <div class="row g-3">
        <div class="col-md-6">
          <label>Start Date *</label>
          <input type="date" name="start_date" class="form-control" value="<?= $v('start_date', date('Y-m-d')) ?>" required>
        </div>
        <div class="col-md-6">
          <label>Expiry Date *</label>
          <input type="date" name="end_date" class="form-control" value="<?= $v('end_date') ?>" required>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mt-2">
      <button type="submit" class="btn btn-ps"><?= $isEdit ? 'Save Changes' : 'Create Coupon' ?></button>
      <a href="<?= url('/admin/coupons') ?>" class="btn btn-ps-outline">Cancel</a>
    </div>
  </div>
</form>
