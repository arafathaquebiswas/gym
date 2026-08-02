<div class="admin-page-shell">
  <div class="admin-page-header">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Trainer Income</li>
        </ol>
      </nav>
      <h1 class="admin-page-title">Trainer Income</h1>
    </div>
    <div class="admin-page-actions">
      <a href="/admin/reports" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
  </div>
<?php
/** @var array $rows */
/** @var string $from */
/** @var string $to */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Trainer Income Report</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>

  <?php if (empty($rows)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No trainers found.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Trainer</th><th>Payments</th><th>Total Earned</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e($row['name']) ?></td>
          <td><?= (int) $row['payment_count'] ?></td>
          <td class="fw-semibold"><?= money((float) ($row['total'] ?? 0)) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="text-white-50 small mt-2 mb-0">Trainer fee payments are logged via the <code>payments</code> table (type = trainer_fee) — none recorded yet if all rows show ৳0.00.</p>
  <?php endif; ?>
</div>
</div>
