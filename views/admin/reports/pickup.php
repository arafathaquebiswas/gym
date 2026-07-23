<?php
/** @var array $byStatus */
/** @var array $bySlot */
/** @var string $from */
/** @var string $to */
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'packed' => 'info', 'ready_for_pickup' => 'info', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'dark'];
$statusLabels = ['delivered' => 'Picked Up'];
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Pickup Report</h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_filter.php'; ?>
  <?php include __DIR__ . '/_export_buttons.php'; ?>

  <div class="d-flex flex-wrap gap-2">
    <?php foreach ($byStatus as $s): ?>
      <span class="badge text-bg-<?= $statusColors[$s['status']] ?? 'secondary' ?> fs-6"><?= e($statusLabels[$s['status']] ?? ucfirst(str_replace('_', ' ', $s['status']))) ?>: <?= (int) $s['cnt'] ?></span>
    <?php endforeach; ?>
    <?php if (empty($byStatus)): ?><p class="text-white-50 small mb-0">No pickup orders in this range.</p><?php endif; ?>
  </div>
</div>

<div class="admin-card">
  <h6 class="mb-3">By Pickup Time Slot</h6>
  <?php if (empty($bySlot)): ?>
    <p class="text-white-50 small mb-0">No pickup time slot data in this range.</p>
  <?php else: ?>
  <table class="admin-table mb-0">
    <thead><tr><th>Time Slot</th><th class="text-end">Orders</th></tr></thead>
    <tbody>
      <?php foreach ($bySlot as $s): ?>
      <tr><td><?= e($s['slot_label']) ?></td><td class="text-end"><?= (int) $s['order_count'] ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
