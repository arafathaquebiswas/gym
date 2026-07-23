<?php
/** @var array $byStatus */
/** @var array $byZone */
/** @var array $byDriver */
/** @var string $from */
/** @var string $to */
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'packed' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'dark'];
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Delivery Report</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>

  <div class="d-flex flex-wrap gap-2">
    <?php foreach ($byStatus as $s): ?>
      <span class="badge text-bg-<?= $statusColors[$s['status']] ?? 'secondary' ?> fs-6"><?= e(ucfirst(str_replace('_', ' ', $s['status']))) ?>: <?= (int) $s['cnt'] ?></span>
    <?php endforeach; ?>
    <?php if (empty($byStatus)): ?><p class="text-white-50 small mb-0">No delivery orders in this range.</p><?php endif; ?>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-6">
    <div class="admin-card h-100">
      <h6 class="mb-3">By Delivery Zone</h6>
      <?php if (empty($byZone)): ?>
        <p class="text-white-50 small mb-0">No data.</p>
      <?php else: ?>
      <table class="admin-table mb-0">
        <thead><tr><th>Zone</th><th class="text-end">Orders</th><th class="text-end">Total</th></tr></thead>
        <tbody>
          <?php foreach ($byZone as $z): ?>
          <tr><td><?= e($z['zone_name']) ?></td><td class="text-end"><?= (int) $z['order_count'] ?></td><td class="text-end"><?= money((float) $z['total']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-6">
    <div class="admin-card h-100">
      <h6 class="mb-3">By Delivery Driver</h6>
      <?php if (empty($byDriver)): ?>
        <p class="text-white-50 small mb-0">No assigned deliveries in this range.</p>
      <?php else: ?>
      <table class="admin-table mb-0">
        <thead><tr><th>Driver</th><th class="text-end">Assigned</th><th class="text-end">Delivered</th></tr></thead>
        <tbody>
          <?php foreach ($byDriver as $d): ?>
          <tr><td><?= e($d['driver_name']) ?></td><td class="text-end"><?= (int) $d['assigned_count'] ?></td><td class="text-end"><?= (int) $d['delivered_count'] ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>
