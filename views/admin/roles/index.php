<?php
/** @var bool $isMainAdmin */
/** @var array $staff */
/** @var array|null $mainAdmin */
/** @var array $superAdmins */
/** @var array $modules */
$statusBadge = fn ($status) => $status === 'suspended' ? 'danger' : ($status === 'inactive' ? 'secondary' : 'success');
?>

<?php if ($isMainAdmin && $mainAdmin): ?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h6 class="mb-0"><i class="bi bi-shield-lock-fill text-orange"></i> Main Admin</h6>
    <a href="<?= url('/admin/roles/locks') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-lock"></i> Module Locks</a>
  </div>
  <div class="d-flex justify-content-between align-items-center mt-3">
    <div>
      <strong><?= e($mainAdmin['name']) ?></strong>
      <div class="text-white-50 small">ID: <?= e($mainAdmin['email']) ?></div>
    </div>
    <span class="badge text-bg-success">Full System Access</span>
  </div>
  <p class="text-white-50 small mb-0 mt-2">View only — cannot be edited, deleted, or demoted from this page.</p>
</div>
<?php endif; ?>

<?php if ($isMainAdmin): ?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Super Admins (<?= count($superAdmins) ?>)</h6>
    <a href="<?= url('/admin/roles/super-admin/create') ?>" class="btn btn-ps btn-sm"><i class="bi bi-plus-lg"></i> Add Super Admin</a>
  </div>
  <?php if (empty($superAdmins)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No super admins yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($superAdmins as $person): ?>
        <tr>
          <td><?= e($person['name']) ?></td>
          <td><?= e($person['email']) ?></td>
          <td><?= e($person['phone'] ?? '—') ?></td>
          <td><span class="badge text-bg-<?= $statusBadge($person['status']) ?>"><?= e(ucfirst($person['status'])) ?></span></td>
          <td>
            <div class="d-flex gap-2">
              <a href="<?= url('/admin/roles/super-admin/' . $person['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
              <a href="<?= url('/admin/roles/' . $person['id'] . '/permissions') ?>" class="btn btn-ps-outline btn-sm" title="Permissions"><i class="bi bi-key"></i></a>
              <form method="post" action="<?= url('/admin/roles/super-admin/' . $person['id'] . '/toggle-suspend') ?>">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-ps-outline btn-sm" title="<?= $person['status'] === 'suspended' ? 'Reactivate' : 'Suspend' ?>">
                  <i class="bi bi-<?= $person['status'] === 'suspended' ? 'play' : 'pause' ?>"></i>
                </button>
              </form>
              <form method="post" action="<?= url('/admin/roles/super-admin/' . $person['id'] . '/delete') ?>" onsubmit="return confirm('Delete this Super Admin account permanently?');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Staff (<?= count($staff) ?>)</h6>
    <a href="<?= url('/admin/roles/staff/create') ?>" class="btn btn-ps btn-sm"><i class="bi bi-plus-lg"></i> Add Staff</a>
  </div>
  <?php if (empty($staff)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No staff accounts yet. Staff only see the modules you assign them.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($staff as $person): ?>
        <tr>
          <td><?= e($person['name']) ?></td>
          <td><?= e($person['email']) ?></td>
          <td><?= e($person['phone'] ?? '—') ?></td>
          <td><span class="badge text-bg-<?= $statusBadge($person['status']) ?>"><?= e(ucfirst($person['status'])) ?></span></td>
          <td>
            <div class="d-flex gap-2">
              <a href="<?= url('/admin/roles/staff/' . $person['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
              <a href="<?= url('/admin/roles/' . $person['id'] . '/permissions') ?>" class="btn btn-ps-outline btn-sm" title="Permissions"><i class="bi bi-key"></i></a>
              <form method="post" action="<?= url('/admin/roles/staff/' . $person['id'] . '/toggle-suspend') ?>">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-ps-outline btn-sm" title="<?= $person['status'] === 'suspended' ? 'Reactivate' : 'Suspend' ?>">
                  <i class="bi bi-<?= $person['status'] === 'suspended' ? 'play' : 'pause' ?>"></i>
                </button>
              </form>
              <form method="post" action="<?= url('/admin/roles/staff/' . $person['id'] . '/delete') ?>" onsubmit="return confirm('Delete this Staff account permanently?');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
