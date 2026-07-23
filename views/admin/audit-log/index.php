<?php
/** @var array $logs */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
/** @var array $admins */
/** @var array $actions */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Audit Log (<?= (int) $total ?>)</h6>
  </div>

  <form method="get" action="<?= url('/admin/audit-log') ?>" class="admin-toolbar admin-form">
    <select name="user_id" class="form-select form-select-sm">
      <option value="">All Admins</option>
      <?php foreach ($admins as $admin): ?>
        <option value="<?= (int) $admin['id'] ?>" <?= (string) $filters['user_id'] === (string) $admin['id'] ? 'selected' : '' ?>><?= e($admin['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="action" class="form-select form-select-sm">
      <option value="">All Actions</option>
      <?php foreach ($actions as $action): ?>
        <option value="<?= e($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= e($action) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="date" name="from" class="form-control form-control-sm" value="<?= e($filters['from']) ?>" title="From date">
    <input type="date" name="to" class="form-control form-control-sm" value="<?= e($filters['to']) ?>" title="To date">
    <button type="submit" class="btn btn-ps-outline btn-sm">Filter</button>
    <?php if ($filters['user_id'] || $filters['action'] || $filters['from'] || $filters['to']): ?>
      <a href="<?= url('/admin/audit-log') ?>" class="btn btn-link btn-sm text-white-50">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (empty($logs)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No activity recorded yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Date/Time</th><th>Admin</th><th>Action</th><th>Description</th><th>IP Address</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td class="text-nowrap"><?= format_date($log['created_at'], 'd M Y, h:i A') ?></td>
          <td><?= e($log['user_name'] ?? 'Deleted User') ?></td>
          <td><span class="badge text-bg-secondary"><?= e($log['action']) ?></span></td>
          <td><?= e($log['description'] ?? '—') ?></td>
          <td class="text-white-50"><?= e($log['ip_address'] ?? '—') ?></td>
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
          <a class="page-link" href="<?= url('/admin/audit-log?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
