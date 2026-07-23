<?php
/** @var array|null $bundle */
/** @var array $items */
/** @var array $products */
$isEdit = $bundle !== null;
$action = $isEdit ? url('/admin/bundles/' . $bundle['id']) : url('/admin/bundles');
$v = fn ($key, $default = '') => e((string) ($bundle[$key] ?? $default));
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0"><?= $isEdit ? 'Edit Bundle' : 'Add Bundle' ?></h6>
    <a href="<?= url('/admin/bundles') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Bundles</a>
  </div>

  <form method="post" action="<?= $action ?>" class="admin-form">
    <?= Security::csrfField() ?>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label>Bundle Name *</label>
        <input type="text" name="name" class="form-control" value="<?= $v('name') ?>" required>
      </div>
      <div class="col-md-3">
        <label>Bundle Price (৳) *</label>
        <input type="number" step="0.01" min="0" name="bundle_price" class="form-control" value="<?= $v('bundle_price') ?>" required>
      </div>
      <div class="col-md-3">
        <label>Status</label>
        <select name="is_active" class="form-select">
          <option value="1" <?= ($bundle['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
          <option value="0" <?= isset($bundle['is_active']) && !$bundle['is_active'] ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div class="col-md-6">
        <label>Starts At <small class="text-white-50">(optional — always available if blank)</small></label>
        <input type="datetime-local" name="starts_at" class="form-control" value="<?= $bundle && $bundle['starts_at'] ? date('Y-m-d\TH:i', strtotime($bundle['starts_at'])) : '' ?>">
      </div>
      <div class="col-md-6">
        <label>Ends At <small class="text-white-50">(optional — no expiry if blank)</small></label>
        <input type="datetime-local" name="ends_at" class="form-control" value="<?= $bundle && $bundle['ends_at'] ? date('Y-m-d\TH:i', strtotime($bundle['ends_at'])) : '' ?>">
      </div>
    </div>

    <h6 class="mb-2">Bundle Items <small class="text-white-50">(at least 2 products required)</small></h6>
    <table class="admin-table w-100 mb-2" id="bundleLines">
      <thead><tr><th>Product</th><th style="width:110px">Qty</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($items)): $items = [['product_id' => '', 'qty' => 1], ['product_id' => '', 'qty' => 1]]; endif; ?>
        <?php foreach ($items as $item): ?>
        <tr class="bundle-line">
          <td>
            <select name="product_id[]" class="form-select form-select-sm" required>
              <option value="">— Select product —</option>
              <?php foreach ($products as $product): ?>
                <option value="<?= (int) $product['id'] ?>" <?= (int) ($item['product_id'] ?: 0) === (int) $product['id'] ? 'selected' : '' ?>><?= e($product['name']) ?> (<?= e($product['sku']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input type="number" name="qty[]" class="form-control form-control-sm" min="1" value="<?= (int) ($item['qty'] ?? 1) ?>" required></td>
          <td><button type="button" class="btn btn-outline-danger btn-sm remove-line"><i class="bi bi-trash"></i></button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button type="button" class="btn btn-ps-outline btn-sm mb-3" id="addLineBtn"><i class="bi bi-plus-lg"></i> Add Line</button>

    <div>
      <button type="submit" class="btn btn-ps"><?= $isEdit ? 'Save Changes' : 'Create Bundle' ?></button>
    </div>
  </form>
</div>

<script>
document.getElementById('addLineBtn').addEventListener('click', function () {
  var tbody = document.querySelector('#bundleLines tbody');
  var clone = tbody.querySelector('.bundle-line').cloneNode(true);
  clone.querySelectorAll('input').forEach(function (input) { input.value = '1'; });
  clone.querySelectorAll('select').forEach(function (select) { select.selectedIndex = 0; });
  tbody.appendChild(clone);
});

document.querySelector('#bundleLines tbody').addEventListener('click', function (e) {
  var btn = e.target.closest('.remove-line');
  if (!btn) return;
  var rows = document.querySelectorAll('#bundleLines .bundle-line');
  if (rows.length > 2) {
    btn.closest('.bundle-line').remove();
  }
});
</script>
