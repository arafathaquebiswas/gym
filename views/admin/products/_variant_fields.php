<?php
/** @var array|null $variant */
/** @var array $variantValueIds */
/** @var array $assignedAttributes */
/** @var array $attributeValuesById */
$isEditVariant = $variant !== null;
$vv = fn ($key, $default = '') => e((string) ($variant[$key] ?? $default));
?>
<div class="row g-2 mb-2">
  <?php foreach ($assignedAttributes as $attr): ?>
  <div class="col-md-4">
    <label><?= e($attr['name']) ?></label>
    <select name="attribute_value_ids[]" class="form-select form-select-sm" required>
      <option value="">— Select —</option>
      <?php foreach ($attributeValuesById[$attr['id']] ?? [] as $val): ?>
        <option value="<?= (int) $val['id'] ?>" <?= in_array((int) $val['id'], $variantValueIds, true) ? 'selected' : '' ?>><?= e($val['value']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-2">
  <div class="col-md-4">
    <label>SKU *</label>
    <input type="text" name="sku" class="form-control form-control-sm" value="<?= $vv('sku') ?>" required>
  </div>
  <div class="col-md-4">
    <label>Barcode</label>
    <input type="text" name="barcode" class="form-control form-control-sm" value="<?= $vv('barcode') ?>">
  </div>
  <div class="col-md-4">
    <label>Status</label>
    <select name="status" class="form-select form-select-sm">
      <option value="active" <?= ($variant['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= ($variant['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
  </div>
  <div class="col-md-3">
    <label>Price (৳) <small class="text-white-50">(blank = product price)</small></label>
    <input type="number" step="0.01" min="0" name="price" class="form-control form-control-sm" value="<?= $vv('price') ?>">
  </div>
  <div class="col-md-3">
    <label>Offer Price (৳)</label>
    <input type="number" step="0.01" min="0" name="offer_price" class="form-control form-control-sm" value="<?= $vv('offer_price') ?>">
  </div>
  <div class="col-md-3">
    <label>Stock Qty *</label>
    <input type="number" min="0" name="stock_qty" class="form-control form-control-sm" value="<?= $vv('stock_qty', '0') ?>" required>
  </div>
  <div class="col-md-3">
    <label>Weight (kg)</label>
    <input type="number" step="0.001" min="0" name="weight" class="form-control form-control-sm" value="<?= $vv('weight') ?>">
  </div>
  <div class="col-12">
    <label>Variant Image <small class="text-white-50">(blank = product image)</small></label>
    <?php if ($isEditVariant && !empty($variant['image'])): ?>
      <div class="mb-2" style="width:70px;height:70px;border-radius:8px;overflow:hidden;"><?= media_tile($variant['image'], 'Variant', 'bi-box-seam') ?></div>
    <?php endif; ?>
    <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
  </div>
</div>
