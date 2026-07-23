<?php
/** @var array $staff */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Delivery Staff (<?= count($staff) ?>)</h6>
    <a href="<?= url('/admin/delivery-staff/create') ?>" class="btn btn-ps btn-sm"><i class="bi bi-plus-lg"></i> Add Delivery Staff</a>
  </div>

  <?php if (empty($staff)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No delivery staff added yet.</p>
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
          <td><span class="badge text-bg-<?= $person['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($person['status'])) ?></span></td>
          <td>
            <div class="d-flex gap-2">
              <a href="<?= url('/admin/delivery-staff/' . $person['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= url('/admin/delivery-staff/' . $person['id'] . '/delete') ?>" onsubmit="return confirm('Delete this delivery staff member? This removes their login.');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
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
