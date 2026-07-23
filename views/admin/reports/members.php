<?php
/** @var array $statusBreakdown */
/** @var array $newMembers */
/** @var string $from */
/** @var string $to */
$statusLabels = ['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired'];
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Members by Status</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <div class="row g-3">
    <?php foreach ($statusBreakdown as $row): ?>
    <div class="col-6 col-md-3">
      <div class="admin-card">
        <div class="text-white-50 small"><?= e($statusLabels[$row['status']] ?? $row['status']) ?></div>
        <div class="fs-3 fw-bold text-orange"><?= (int) $row['cnt'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="admin-card">
  <h6 class="mb-2">New Members</h6>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>

  <?php if (empty($newMembers)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No new members joined in this date range.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Join Date</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($newMembers as $m): ?>
        <tr>
          <td><?= e($m['member_code']) ?></td>
          <td><?= e($m['name']) ?></td>
          <td><?= e($m['email']) ?></td>
          <td><?= format_date($m['join_date']) ?></td>
          <td><?= e($statusLabels[$m['status']] ?? $m['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
