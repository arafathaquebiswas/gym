<?php
/** @var array|null $sale */
/** @var array $categories */
/** @var array $brands */
/** @var array $products */
$sale = $sale ?? null;
$v = fn ($key, $default = '') => e((string) ($sale[$key] ?? $default));
$scope = $sale['scope'] ?? 'all';
?>
<label>Name</label>
<input type="text" name="name" class="form-control mb-2" value="<?= $v('name') ?>" required>

<label>Discount Percentage (%)</label>
<input type="number" step="0.01" min="0.01" max="99.99" name="discount_percent" class="form-control mb-2" value="<?= $v('discount_percent') ?>" required>

<label>Applies To</label>
<select name="scope" class="form-select mb-2 flash-sale-scope">
  <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>All Products</option>
  <option value="category" <?= $scope === 'category' ? 'selected' : '' ?>>A Category</option>
  <option value="brand" <?= $scope === 'brand' ? 'selected' : '' ?>>A Brand</option>
  <option value="product" <?= $scope === 'product' ? 'selected' : '' ?>>A Single Product</option>
</select>

<div class="scope-picker scope-picker-category <?= $scope === 'category' ? '' : 'd-none' ?> mb-2">
  <label>Category</label>
  <select name="scope_id_category" class="form-select">
    <option value="">— Select category —</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= (int) $cat['id'] ?>" <?= $scope === 'category' && (int) ($sale['scope_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="scope-picker scope-picker-brand <?= $scope === 'brand' ? '' : 'd-none' ?> mb-2">
  <label>Brand</label>
  <select name="scope_id_brand" class="form-select">
    <option value="">— Select brand —</option>
    <?php foreach ($brands as $brand): ?>
      <option value="<?= (int) $brand['id'] ?>" <?= $scope === 'brand' && (int) ($sale['scope_id'] ?? 0) === (int) $brand['id'] ? 'selected' : '' ?>><?= e($brand['name']) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="scope-picker scope-picker-product <?= $scope === 'product' ? '' : 'd-none' ?> mb-2">
  <label>Product</label>
  <select name="scope_id_product" class="form-select">
    <option value="">— Select product —</option>
    <?php foreach ($products as $product): ?>
      <option value="<?= (int) $product['id'] ?>" <?= $scope === 'product' && (int) ($sale['scope_id'] ?? 0) === (int) $product['id'] ? 'selected' : '' ?>><?= e($product['name']) ?> (<?= e($product['sku']) ?>)</option>
    <?php endforeach; ?>
  </select>
</div>

<div class="row g-2">
  <div class="col-6">
    <label>Starts At</label>
    <input type="datetime-local" name="starts_at" class="form-control" value="<?= $sale && $sale['starts_at'] ? date('Y-m-d\TH:i', strtotime($sale['starts_at'])) : '' ?>" required>
  </div>
  <div class="col-6">
    <label>Ends At</label>
    <input type="datetime-local" name="ends_at" class="form-control" value="<?= $sale && $sale['ends_at'] ? date('Y-m-d\TH:i', strtotime($sale['ends_at'])) : '' ?>" required>
  </div>
  <div class="col-6">
    <label>Status</label>
    <select name="is_active" class="form-select">
      <option value="1" <?= ($sale['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
      <option value="0" <?= isset($sale['is_active']) && !$sale['is_active'] ? 'selected' : '' ?>>Inactive</option>
    </select>
  </div>
</div>

<script>
(function () {
  var scripts = document.currentScript;
  var modalBody = scripts.closest('.modal-body');
  var scopeSelect = modalBody.querySelector('.flash-sale-scope');
  function apply() {
    modalBody.querySelectorAll('.scope-picker').forEach(function (el) { el.classList.add('d-none'); });
    var active = modalBody.querySelector('.scope-picker-' + scopeSelect.value);
    if (active) { active.classList.remove('d-none'); }
  }
  scopeSelect.addEventListener('change', apply);
})();
</script>
