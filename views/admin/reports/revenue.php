<div class="admin-page-shell">
  <div class="admin-page-header">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Revenue Report</li>
        </ol>
      </nav>
      <h1 class="admin-page-title">Revenue Report</h1>
    </div>
    <div class="admin-page-actions">
      <a href="/admin/reports" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
  </div>
<?php
/** @var array $rows */
/** @var float $grandTotal */
$typeLabels = ['admission' => 'Admission', 'membership' => 'Membership', 'store_sale' => 'Store Sale', 'trainer_fee' => 'Trainer Fee', 'expense' => 'Expense', 'income' => 'Other Income', 'refund' => 'Refund'];
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Revenue Report</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>

  <div class="fs-4 fw-bold text-orange mb-3"><?= money($grandTotal) ?> <span class="fs-6 text-white-50 fw-normal">total in range</span></div>

  <?php if (empty($rows)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No payments recorded in this date range.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Type</th><th>Payments</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e($typeLabels[$row['type']] ?? ucfirst($row['type'])) ?></td>
          <td><?= (int) $row['payment_count'] ?></td>
          <td class="fw-semibold"><?= money((float) $row['total']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
</div>
