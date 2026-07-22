<?php
/** @var array|null $package */
/** @var array $features */
/** @var array $badges */
$isEdit = $package !== null;
$action = $isEdit ? url('/admin/packages/' . $package['id']) : url('/admin/packages');
$v = fn ($key, $default = '') => e((string) ($package[$key] ?? $default));
$checked = fn ($key) => !empty($package[$key]) ? 'checked' : '';
$offerEndDateValue = $isEdit && $package['offer_end_date'] ? substr($package['offer_end_date'], 0, 10) : '';
?>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="admin-form">
  <?= Security::csrfField() ?>

  <div class="admin-card mb-4">
    <div class="admin-form-section">
      <h6>Basic Information</h6>
      <div class="row g-3">
        <div class="col-md-6">
          <label>Package Name *</label>
          <input type="text" name="name" class="form-control" value="<?= $v('name') ?>" required>
        </div>
        <?php if ($isEdit): ?>
        <div class="col-md-6">
          <label>URL Slug</label>
          <input type="text" name="slug" class="form-control" value="<?= $v('slug') ?>">
        </div>
        <?php endif; ?>
        <div class="col-md-4">
          <label>Category</label>
          <select name="category" class="form-select">
            <?php foreach (['regular', 'student', 'corporate', 'vip', 'premium'] as $cat): ?>
              <option value="<?= $cat ?>" <?= ($package['category'] ?? 'regular') === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label>Duration (days) *</label>
          <input type="number" min="1" name="duration_days" class="form-control" value="<?= $v('duration_days') ?>" required>
        </div>
        <div class="col-md-4">
          <label>Display Order</label>
          <input type="number" name="sort_order" class="form-control" value="<?= $v('sort_order', '0') ?>">
        </div>
        <div class="col-12">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="2"><?= $v('description') ?></textarea>
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Pricing &amp; Offer</h6>
      <div class="row g-3">
        <div class="col-md-3">
          <label>Regular Price (৳) *</label>
          <input type="number" step="0.01" min="0" name="regular_price" class="form-control" value="<?= $v('regular_price') ?>" required>
        </div>
        <div class="col-md-3">
          <label>Offer Price (৳)</label>
          <input type="number" step="0.01" min="0" name="offer_price" class="form-control" value="<?= $v('offer_price') ?>">
          <small class="text-white-50">Must be lower than regular price. Leave blank for no offer.</small>
        </div>
        <div class="col-md-3">
          <label>Offer Start Date</label>
          <input type="date" name="offer_start_date" class="form-control" value="<?= $v('offer_start_date') ?>">
        </div>
        <div class="col-md-3">
          <label>Offer End Date</label>
          <input type="date" name="offer_end_date" class="form-control" value="<?= e($offerEndDateValue) ?>">
          <small class="text-white-50">Offer auto-expires at end of this day.</small>
        </div>
        <?php if ($isEdit && $package['discount_percentage']): ?>
        <div class="col-12">
          <p class="text-white-50 small mb-0">Auto-calculated: Save ৳<?= number_format((float) $package['discount_amount']) ?> (<?= e((string) $package['discount_percentage']) ?>% OFF)</p>
        </div>
        <?php endif; ?>
        <div class="col-md-4 form-check mt-2">
          <input type="checkbox" name="offer_enabled" value="1" class="form-check-input" id="offerEnabled" <?= $checked('offer_enabled') ?>>
          <label class="form-check-label" for="offerEnabled">Offer Enabled</label>
        </div>
        <div class="col-md-4">
          <label>Badge</label>
          <select name="badge" class="form-select">
            <option value="">None</option>
            <?php foreach ($badges as $badge): ?>
              <option value="<?= e($badge) ?>" <?= ($package['badge'] ?? '') === $badge ? 'selected' : '' ?>><?= e($badge) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Package Image</h6>
      <?php if ($isEdit && $package['image']): ?>
        <div class="mb-2"><img src="<?= e(str_starts_with($package['image'], 'uploads/') ? url($package['image']) : asset('images/' . $package['image'])) ?>" style="width:120px;height:80px;object-fit:cover;border-radius:10px" alt=""></div>
      <?php endif; ?>
      <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" style="max-width:400px">
    </div>

    <div class="admin-form-section">
      <h6>Amenities Included</h6>
      <div class="row g-2">
        <?php foreach (['includes_trainer' => 'Personal Trainer', 'includes_locker' => 'Locker', 'includes_steam' => 'Steam Bath', 'includes_sauna' => 'Sauna', 'includes_diet_plan' => 'Diet Plan'] as $field => $label): ?>
        <div class="col-md-4 col-6 form-check">
          <input type="checkbox" name="<?= $field ?>" value="1" class="form-check-input" id="<?= $field ?>" <?= $checked($field) ?>>
          <label class="form-check-label" for="<?= $field ?>"><?= $label ?></label>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Package Features</h6>
      <p class="text-white-50 small">One feature per line, e.g. "Unlimited Gym Access".</p>
      <div id="featuresList">
        <?php if (empty($features)): ?>
          <div class="input-group mb-2">
            <input type="text" name="features[]" class="form-control" placeholder="e.g. Unlimited Gym Access">
            <button type="button" class="btn btn-outline-danger remove-feature">&times;</button>
          </div>
        <?php else: foreach ($features as $feature): ?>
          <div class="input-group mb-2">
            <input type="text" name="features[]" class="form-control" value="<?= e($feature['feature_text']) ?>">
            <button type="button" class="btn btn-outline-danger remove-feature">&times;</button>
          </div>
        <?php endforeach; endif; ?>
      </div>
      <button type="button" id="addFeatureBtn" class="btn btn-ps-outline btn-sm">+ Add Feature</button>
    </div>

    <div class="admin-form-section">
      <h6>Display Settings</h6>
      <div class="row g-3">
        <div class="col-md-4 form-check mt-2">
          <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="isFeatured" <?= $checked('is_featured') ?>>
          <label class="form-check-label" for="isFeatured">Featured Package</label>
        </div>
        <div class="col-md-4 form-check mt-2">
          <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" <?= $isEdit ? $checked('is_active') : 'checked' ?>>
          <label class="form-check-label" for="isActive">Visible on Website</label>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mt-2">
      <button type="submit" class="btn btn-ps"><?= $isEdit ? 'Save Changes' : 'Add Package' ?></button>
      <a href="<?= url('/admin/packages') ?>" class="btn btn-ps-outline">Cancel</a>
    </div>
  </div>
</form>

<script>
document.getElementById('addFeatureBtn').addEventListener('click', function () {
  const wrap = document.createElement('div');
  wrap.className = 'input-group mb-2';
  wrap.innerHTML = '<input type="text" name="features[]" class="form-control" placeholder="e.g. Free Fitness Assessment"><button type="button" class="btn btn-outline-danger remove-feature">&times;</button>';
  document.getElementById('featuresList').appendChild(wrap);
});
document.getElementById('featuresList').addEventListener('click', function (e) {
  if (e.target.classList.contains('remove-feature')) {
    e.target.closest('.input-group').remove();
  }
});
</script>
