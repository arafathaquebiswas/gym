<?php
/** @var array $suppliers */
/** @var array $products */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Record Purchase</h6>
    <a href="<?= url('/admin/purchases') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Purchases</a>
  </div>

  <form method="post" action="<?= url('/admin/purchases') ?>" class="admin-form">
    <?= Security::csrfField() ?>
    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label>Supplier</label>
        <select name="supplier_id" class="form-select">
          <option value="">— No supplier —</option>
          <?php foreach ($suppliers as $supplier): ?>
            <option value="<?= (int) $supplier['id'] ?>"><?= e($supplier['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label>Purchase Date</label>
        <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>
    </div>

    <h6 class="mb-2">Product Lines</h6>
    <table class="admin-table w-100 mb-2" id="purchaseLines">
      <thead><tr><th>Product</th><th style="width:110px">Qty</th><th style="width:140px">Unit Cost (৳)</th><th></th></tr></thead>
      <tbody>
        <tr class="purchase-line">
          <td>
            <select name="product_id[]" class="form-select form-select-sm" required>
              <option value="">— Select product —</option>
              <?php foreach ($products as $product): ?>
                <option value="<?= (int) $product['id'] ?>"><?= e($product['name']) ?> (<?= e($product['sku']) ?>) — current stock: <?= (int) $product['stock_qty'] ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td><input type="number" name="qty[]" class="form-control form-control-sm" min="1" required></td>
          <td><input type="number" name="unit_cost[]" class="form-control form-control-sm" min="0" step="0.01" required></td>
          <td><button type="button" class="btn btn-outline-danger btn-sm remove-line"><i class="bi bi-trash"></i></button></td>
        </tr>
      </tbody>
    </table>
    <button type="button" class="btn btn-ps-outline btn-sm mb-3" id="addLineBtn"><i class="bi bi-plus-lg"></i> Add Line</button>

    <div>
      <button type="submit" class="btn btn-ps">Record Purchase &amp; Update Stock</button>
    </div>
  </form>
</div>

<script>
document.getElementById('addLineBtn').addEventListener('click', function () {
  var tbody = document.querySelector('#purchaseLines tbody');
  var clone = tbody.querySelector('.purchase-line').cloneNode(true);
  clone.querySelectorAll('input').forEach(function (input) { input.value = ''; });
  clone.querySelectorAll('select').forEach(function (select) { select.selectedIndex = 0; });
  tbody.appendChild(clone);
});

document.querySelector('#purchaseLines tbody').addEventListener('click', function (e) {
  var btn = e.target.closest('.remove-line');
  if (!btn) return;
  var rows = document.querySelectorAll('#purchaseLines .purchase-line');
  if (rows.length > 1) {
    btn.closest('.purchase-line').remove();
  }
});
</script>
