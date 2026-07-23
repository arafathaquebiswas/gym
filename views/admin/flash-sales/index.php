<?php
/** @var array $flashSales */
/** @var array $categories */
/** @var array $brands */
/** @var array $products */
$scopeLabel = function (array $sale) use ($categories, $brands, $products) {
    if ($sale['scope'] === 'all') {
        return 'All Products';
    }
    $list = match ($sale['scope']) {
        'category' => $categories,
        'brand' => $brands,
        'product' => $products,
        default => [],
    };
    foreach ($list as $item) {
        if ((int) $item['id'] === (int) $sale['scope_id']) {
            return ucfirst($sale['scope']) . ': ' . $item['name'];
        }
    }
    return ucfirst($sale['scope']);
};
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Flash Sales</h6>
    <div class="d-flex gap-2">
      <a href="<?= url('/admin/settings') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Settings</a>
      <button type="button" class="btn btn-ps btn-sm" data-bs-toggle="modal" data-bs-target="#addFlashSaleModal"><i class="bi bi-plus-lg"></i> Add Flash Sale</button>
    </div>
  </div>

  <?php if (empty($flashSales)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No flash sales yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Scope</th><th>Discount</th><th>Window</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($flashSales as $sale): ?>
        <tr>
          <td><?= e($sale['name']) ?></td>
          <td><?= e($scopeLabel($sale)) ?></td>
          <td><?= (float) $sale['discount_percent'] ?>%</td>
          <td class="small"><?= format_date($sale['starts_at'], 'd M Y, h:i A') ?> — <?= format_date($sale['ends_at'], 'd M Y, h:i A') ?></td>
          <td>
            <form method="post" action="<?= url('/admin/flash-sales/' . $sale['id'] . '/toggle-active') ?>">
              <?= Security::csrfField() ?>
              <button type="submit" class="badge text-bg-<?= $sale['is_active'] ? 'success' : 'secondary' ?> border-0">
                <?= $sale['is_active'] ? 'Active' : 'Inactive' ?>
              </button>
            </form>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-ps-outline btn-sm" data-bs-toggle="modal" data-bs-target="#editFlashSaleModal<?= $sale['id'] ?>"><i class="bi bi-pencil"></i></button>
              <form method="post" action="<?= url('/admin/flash-sales/' . $sale['id'] . '/delete') ?>" onsubmit="return confirm('Delete this flash sale?');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>

            <div class="modal fade" id="editFlashSaleModal<?= $sale['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark">
                  <form method="post" action="<?= url('/admin/flash-sales/' . $sale['id']) ?>">
                    <?= Security::csrfField() ?>
                    <div class="modal-header"><h6 class="modal-title">Edit Flash Sale</h6></div>
                    <div class="modal-body">
                      <?php include __DIR__ . '/_fields.php'; ?>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-ps btn-sm">Save</button>
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
  <?php endif; ?>
</div>

<div class="modal fade" id="addFlashSaleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark">
      <form method="post" action="<?= url('/admin/flash-sales') ?>">
        <?= Security::csrfField() ?>
        <div class="modal-header"><h6 class="modal-title">Add Flash Sale</h6></div>
        <div class="modal-body">
          <?php $sale = null; include __DIR__ . '/_fields.php'; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-ps btn-sm">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>

