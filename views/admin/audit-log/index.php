<div class="admin-page-shell">
  <div class="admin-page-header">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Audit Log</li>
        </ol>
      </nav>
      <h1 class="admin-page-title">Audit Log</h1>
    </div>
  </div>
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
    <?php if (Permission::can('audit_logs', 'export')): ?>
    <button type="button" class="btn btn-ps-outline btn-sm" data-export-module="audit-logs"><i class="bi bi-download me-1"></i> Export Audit Log</button>
    <?php endif; ?>
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
      <thead>
        <tr>
          <th>User</th>
          <th>Role</th>
          <th>Module</th>
          <th>File</th>
          <th>Format</th>
          <th>Records</th>
          <th>Time</th>
          <th>IP</th>
          <th>Browser</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <?php
          $fmt = strtolower($log['export_format'] ?? '');
          $fmtBadgeClass = match($fmt) {
              'xlsx' => 'text-bg-success',
              'csv' => 'text-bg-info',
              'pdf' => 'text-bg-danger',
              default => 'text-bg-secondary'
          };
          $roleLabel = ucfirst(str_replace('_', ' ', $log['user_role'] ?? 'Admin'));
        ?>
        <tr>
          <td>
            <strong><?= e($log['user_name'] ?? 'System User') ?></strong>
            <?php if (!empty($log['user_id'])): ?><span class="text-white-50 small font-monospace d-block">ID: #<?= (int) $log['user_id'] ?></span><?php endif; ?>
          </td>
          <td><span class="badge text-bg-dark border border-secondary"><?= e($roleLabel) ?></span></td>
          <td>
            <span class="badge text-bg-secondary mb-1"><?= e(ucfirst(str_replace(['_', '-'], ' ', $log['module_key'] ?? $log['action']))) ?></span>
          </td>
          <td>
            <?php if (!empty($log['file_name'])): ?>
              <div class="fw-bold font-monospace text-orange"><i class="bi bi-file-earmark-arrow-down me-1"></i><?= e($log['file_name']) ?></div>
            <?php else: ?>
              <div class="small text-white-50"><?= e($log['description'] ?? '—') ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($fmt): ?>
              <span class="badge <?= $fmtBadgeClass ?> text-uppercase"><?= e($fmt) ?></span>
            <?php else: ?>
              <span class="text-white-50">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?= isset($log['record_count']) && $log['record_count'] !== null ? '<span class="badge text-bg-primary">' . (int) $log['record_count'] . '</span>' : '<span class="text-white-50">—</span>' ?>
          </td>
          <td class="text-nowrap small text-white-50"><?= format_date($log['created_at'], 'd M Y, h:i A') ?></td>
          <td class="text-white-50 small"><?= e($log['ip_address'] ?? '0.0.0.0') ?></td>
          <td class="text-white-50 small">
            <?php if (!empty($log['user_agent'])): ?>
              <div class="text-truncate text-white-50" style="max-width:130px;" title="<?= e($log['user_agent']) ?>"><?= e($log['user_agent']) ?></div>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td>
            <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Completed</span>
          </td>
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
</div>
