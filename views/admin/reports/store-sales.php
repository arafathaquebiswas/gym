<div class="admin-page-shell">
  <div class="admin-page-header">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Store Sales Report</li>
        </ol>
      </nav>
      <h1 class="admin-page-title">Store Sales Report</h1>
    </div>
    <div class="admin-page-actions">
      <a href="/admin/reports" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
  </div>
<?php
/** @var array $byProduct */
/** @var array $byCategory */
/** @var string $from */
/** @var string $to */
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Store Sales Report</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>

  <h6 class="mt-3 mb-2">By Category</h6>
  <?php if (empty($byCategory)): ?>
    <p class="text-white-50 text-center py-3 mb-0">No product sales in this date range.</p>
  <?php else: ?>
  <div class="table-responsive mb-3">
    <table class="admin-table">
      <thead><tr><th>Category</th><th>Units Sold</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach ($byCategory as $row): ?>
        <tr><td><?= e($row['category_name']) ?></td><td><?= (int) $row['qty_sold'] ?></td><td><?= money((float) $row['revenue']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="admin-card">
  <h6 class="mb-2">By Product</h6>
  <?php if (empty($byProduct)): ?>
    <p class="text-white-50 text-center py-3 mb-0">No product sales in this date range.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Category</th><th>Units Sold</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach ($byProduct as $row): ?>
        <tr>
          <td><?= e($row['name']) ?></td>
          <td><?= e($row['category_name']) ?></td>
          <td><?= (int) $row['qty_sold'] ?></td>
          <td class="fw-semibold"><?= money((float) $row['revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
</div>
