<?php
/** @var array $rows */
/** @var string $from */
/** @var string $to */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Attendance Report</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>

  <div class="fs-4 fw-bold text-orange mb-3"><?= array_sum(array_column($rows, 'visits')) ?> <span class="fs-6 text-white-50 fw-normal">total check-ins in range</span></div>

  <?php if (empty($rows)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No attendance recorded in this date range.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Date</th><th>Visits</th><th>Unique Members</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= format_date($row['day']) ?></td>
          <td><?= (int) $row['visits'] ?></td>
          <td><?= (int) $row['unique_members'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
