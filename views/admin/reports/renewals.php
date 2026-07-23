<?php
/** @var array $upcoming */
/** @var array $renewed */
/** @var string $from */
/** @var string $to */
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Expiring in the Next 7 Days</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php if (empty($upcoming)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No memberships expiring soon.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Member</th><th>Phone</th><th>Package</th><th>Expires</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($upcoming as $sub): ?>
        <tr>
          <td><?= e($sub['name']) ?></td>
          <td><?= e($sub['phone'] ?? '—') ?></td>
          <td><?= e($sub['package_name']) ?></td>
          <td><?= format_date($sub['end_date']) ?></td>
          <td><a href="<?= url('/admin/members/' . $sub['member_id']) ?>" class="btn btn-ps-outline btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="admin-card">
  <h6 class="mb-2">Renewed / Purchased in Range</h6>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>

  <?php if (empty($renewed)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No renewals in this date range.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Member</th><th>Package</th><th>Start</th><th>End</th><th>Price Paid</th></tr></thead>
      <tbody>
        <?php foreach ($renewed as $sub): ?>
        <tr>
          <td><?= e($sub['name']) ?></td>
          <td><?= e($sub['package_name']) ?></td>
          <td><?= format_date($sub['start_date']) ?></td>
          <td><?= format_date($sub['end_date']) ?></td>
          <td><?= money((float) $sub['price_paid']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
