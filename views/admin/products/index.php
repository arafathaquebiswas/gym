<?php
/** @var array $products */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
/** @var array $categories */
/** @var array $brands */
/** @var array $stats */
$statusLabels = ['draft' => 'Draft', 'published' => 'Published', 'hidden' => 'Hidden'];
$statusColors = ['draft' => 'secondary', 'published' => 'success', 'hidden' => 'dark'];
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Total Products</div><div class="fs-3 fw-bold text-orange"><?= (int) $stats['total'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Low Stock</div><div class="fs-3 fw-bold text-orange"><?= (int) $stats['lowStock'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Stock Value</div><div class="fs-3 fw-bold text-orange"><?= money($stats['stockValue']) ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="admin-card d-flex flex-column justify-content-center">
      <a href="<?= url('/admin/categories') ?>" class="btn btn-ps-outline btn-sm mb-1"><i class="bi bi-tags"></i> Categories</a>
      <a href="<?= url('/admin/brands') ?>" class="btn btn-ps-outline btn-sm mb-1"><i class="bi bi-award"></i> Brands</a>
      <a href="<?= url('/admin/suppliers') ?>" class="btn btn-ps-outline btn-sm mb-1"><i class="bi bi-people"></i> Suppliers</a>
      <a href="<?= url('/admin/purchases') ?>" class="btn btn-ps-outline btn-sm mb-1"><i class="bi bi-truck"></i> Purchases</a>
      <a href="<?= url('/admin/products/sales') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-receipt"></i> View Sales</a>
    </div>
  </div>
</div>

<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">All Products (<?= (int) $total ?>)</h6>
    <a href="<?= url('/admin/products/create') ?>" class="btn btn-ps btn-sm"><i class="bi bi-plus-lg"></i> Add Product</a>
  </div>

  <form method="get" action="<?= url('/admin/products') ?>" class="admin-toolbar admin-form">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, SKU, barcode" value="<?= e($filters['search']) ?>">
    <select name="category_id" class="form-select form-select-sm">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int) $cat['id'] ?>" <?= (string) $filters['category_id'] === (string) $cat['id'] ? 'selected' : '' ?>>
          <?= $cat['parent_id'] ? '— ' : '' ?><?= e($cat['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="brand_id" class="form-select form-select-sm">
      <option value="">All Brands</option>
      <?php foreach ($brands as $brand): ?>
        <option value="<?= (int) $brand['id'] ?>" <?= (string) $filters['brand_id'] === (string) $brand['id'] ? 'selected' : '' ?>><?= e($brand['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" class="form-select form-select-sm">
      <option value="">All Statuses</option>
      <?php foreach ($statusLabels as $val => $label): ?>
        <option value="<?= $val ?>" <?= $filters['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <select name="sort" class="form-select form-select-sm">
      <option value="">Newest First</option>
      <option value="name" <?= $filters['sort'] === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
      <option value="stock_low" <?= $filters['sort'] === 'stock_low' ? 'selected' : '' ?>>Stock (Low-High)</option>
      <option value="price_high" <?= $filters['sort'] === 'price_high' ? 'selected' : '' ?>>Price (High-Low)</option>
    </select>
    <div class="form-check form-check-inline mt-1">
      <input type="checkbox" name="low_stock" value="1" class="form-check-input" id="lowStockFilter" <?= $filters['low_stock'] ? 'checked' : '' ?>>
      <label class="form-check-label small" for="lowStockFilter">Low stock only</label>
    </div>
    <button type="submit" class="btn btn-ps-outline btn-sm">Filter</button>
    <?php if ($filters['search'] || $filters['category_id'] || $filters['brand_id'] || $filters['status'] || $filters['low_stock'] || $filters['sort']): ?>
      <a href="<?= url('/admin/products') ?>" class="btn btn-link btn-sm text-white-50">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (empty($products)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No products match your filters.</p>
  <?php else: ?>

  <form method="post" action="<?= url('/admin/products/bulk') ?>" id="bulkForm" class="d-none">
    <?= Security::csrfField() ?>
    <input type="hidden" name="bulk_action" id="bulkActionField">
    <input type="hidden" name="bulk_category_id" id="bulkCategoryField">
    <input type="hidden" name="bulk_discount_percent" id="bulkDiscountField">
    <input type="hidden" name="bulk_status" id="bulkStatusField">
  </form>
  <div id="bulkToolbar" class="d-none mb-3 d-flex gap-2 align-items-center flex-wrap">
    <span class="text-white-50 small"><span id="bulkCount">0</span> selected</span>
    <select id="bulkActionSelect" class="form-select form-select-sm" style="max-width:180px">
      <option value="set_status">Set Status</option>
      <option value="change_category">Change Category</option>
      <option value="apply_discount">Apply Discount</option>
      <option value="delete">Delete</option>
    </select>
    <select id="bulkStatusSelect" class="form-select form-select-sm d-none" style="max-width:160px">
      <?php foreach ($statusLabels as $val => $label): ?>
        <option value="<?= $val ?>"><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <select id="bulkCategorySelect" class="form-select form-select-sm d-none" style="max-width:200px">
      <?php foreach ($categories as $cat): ?>
        <option value="<?= (int) $cat['id'] ?>"><?= $cat['parent_id'] ? '— ' : '' ?><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="number" id="bulkDiscountInput" class="form-control form-control-sm d-none" style="max-width:120px" placeholder="Discount %" min="1" max="99">
    <button type="button" id="bulkApplyBtn" class="btn btn-ps-outline btn-sm">Apply</button>
  </div>

  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th><input type="checkbox" id="selectAllProducts"></th>
          <th>Photo</th><th>Name</th><th>SKU</th><th>Category</th><th>Price</th><th>Offer</th>
          <th>Stock</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $product): ?>
        <tr class="<?= $product['stock_qty'] <= $product['min_stock'] ? 'table-danger bg-opacity-25' : '' ?>">
          <td><input type="checkbox" class="row-check" value="<?= (int) $product['id'] ?>"></td>
          <td><?= media_tile($product['image'], $product['name'], 'bi-box-seam', 'thumb') ?></td>
          <td>
            <a href="<?= url('/admin/products/' . $product['id'] . '/edit') ?>" class="text-white fw-semibold text-decoration-none"><?= e($product['name']) ?></a>
            <?php if (!empty($product['brand_name'])): ?><div class="text-white-50 small"><?= e($product['brand_name']) ?></div><?php endif; ?>
          </td>
          <td><?= e($product['sku']) ?></td>
          <td><?= e($product['category_name']) ?></td>
          <td>৳<?= number_format((float) $product['selling_price']) ?></td>
          <td><?= $product['offer_is_live'] ? '৳' . number_format((float) $product['offer_price']) . ' <span class="badge text-bg-success">Live</span>' : '—' ?></td>
          <td>
            <?= (int) $product['stock_qty'] ?>
            <?php if ($product['stock_qty'] <= $product['min_stock']): ?><i class="bi bi-exclamation-triangle-fill text-danger" title="Low stock"></i><?php endif; ?>
          </td>
          <td>
            <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/status') ?>" class="product-status-form">
              <?= Security::csrfField() ?>
              <select name="status" class="form-select form-select-sm status-select text-bg-<?= $statusColors[$product['status']] ?? 'secondary' ?>" onchange="this.form.submit()">
                <?php foreach ($statusLabels as $val => $label): ?>
                  <option value="<?= $val ?>" <?= $product['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <td>
            <div class="d-flex gap-2">
              <a href="<?= url('/admin/products/' . $product['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-pencil"></i></a>
              <button type="button" class="btn btn-ps-outline btn-sm" data-bs-toggle="modal" data-bs-target="#adjustStockModal<?= $product['id'] ?>"><i class="bi bi-box-seam"></i></button>
              <a href="<?= url('/admin/products/' . $product['id'] . '/history') ?>" class="btn btn-ps-outline btn-sm" title="Inventory History"><i class="bi bi-clock-history"></i></a>
              <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/toggle-featured') ?>">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-ps-outline btn-sm" title="Toggle Featured"><i class="bi <?= $product['is_featured'] ? 'bi-star-fill text-orange' : 'bi-star' ?>"></i></button>
              </form>
              <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/delete') ?>" onsubmit="return confirm('Delete this product permanently?');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>

            <div class="modal fade" id="adjustStockModal<?= $product['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark">
                  <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/adjust-stock') ?>">
                    <?= Security::csrfField() ?>
                    <div class="modal-header"><h6 class="modal-title">Adjust Stock — <?= e($product['name']) ?></h6></div>
                    <div class="modal-body">
                      <label>Quantity Change <small class="text-white-50">(negative to reduce)</small></label>
                      <input type="number" name="delta" class="form-control mb-2" required>
                      <label>Reason</label>
                      <input type="text" name="reason" class="form-control" placeholder="e.g. Damaged, Stock-take correction">
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-ps btn-sm">Apply</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= url('/admin/products?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>

<script>
(function () {
  var checks = document.querySelectorAll('.row-check');
  var toolbar = document.getElementById('bulkToolbar');
  if (!toolbar) return;
  var countEl = document.getElementById('bulkCount');
  var selectAll = document.getElementById('selectAllProducts');
  var actionSelect = document.getElementById('bulkActionSelect');
  var categorySelect = document.getElementById('bulkCategorySelect');
  var discountInput = document.getElementById('bulkDiscountInput');
  var statusSelect = document.getElementById('bulkStatusSelect');

  function update() {
    var checked = document.querySelectorAll('.row-check:checked');
    countEl.textContent = checked.length;
    toolbar.classList.toggle('d-none', checked.length === 0);
  }
  function updateExtraFields() {
    categorySelect.classList.toggle('d-none', actionSelect.value !== 'change_category');
    discountInput.classList.toggle('d-none', actionSelect.value !== 'apply_discount');
    statusSelect.classList.toggle('d-none', actionSelect.value !== 'set_status');
  }
  checks.forEach(function (c) { c.addEventListener('change', update); });
  actionSelect.addEventListener('change', updateExtraFields);
  updateExtraFields();
  if (selectAll) {
    selectAll.addEventListener('change', function () {
      checks.forEach(function (c) { c.checked = selectAll.checked; });
      update();
    });
  }

  document.getElementById('bulkApplyBtn').addEventListener('click', function () {
    var checked = document.querySelectorAll('.row-check:checked');
    if (!checked.length) return;
    var action = actionSelect.value;
    if (!confirm('Apply "' + action + '" to ' + checked.length + ' selected product(s)?')) return;

    var form = document.getElementById('bulkForm');
    form.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });
    checked.forEach(function (c) {
      var input = document.createElement('input');
      input.type = 'hidden'; input.name = 'ids[]'; input.value = c.value;
      form.appendChild(input);
    });
    document.getElementById('bulkActionField').value = action;
    document.getElementById('bulkCategoryField').value = categorySelect.value;
    document.getElementById('bulkDiscountField').value = discountInput.value;
    document.getElementById('bulkStatusField').value = statusSelect.value;
    form.submit();
  });
})();
</script>
