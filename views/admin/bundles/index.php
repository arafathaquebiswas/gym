<?php
/** @var array $bundles */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Bundle Offers</h6>
    <div class="d-flex gap-2">
      <a href="<?= url('/admin/settings') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Settings</a>
      <a href="<?= url('/admin/bundles/create') ?>" class="btn btn-ps btn-sm"><i class="bi bi-plus-lg"></i> Add Bundle</a>
    </div>
  </div>

  <?php if (empty($bundles)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No bundles yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Bundle Price</th><th>Window</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($bundles as $bundle): ?>
        <tr>
          <td><?= e($bundle['name']) ?></td>
          <td>৳<?= number_format((float) $bundle['bundle_price']) ?></td>
          <td class="small">
            <?php if ($bundle['starts_at'] || $bundle['ends_at']): ?>
              <?= $bundle['starts_at'] ? format_date($bundle['starts_at'], 'd M Y') : 'Any time' ?> — <?= $bundle['ends_at'] ? format_date($bundle['ends_at'], 'd M Y') : 'No end' ?>
            <?php else: ?>
              Always available
            <?php endif; ?>
          </td>
          <td>
            <form method="post" action="<?= url('/admin/bundles/' . $bundle['id'] . '/toggle-active') ?>">
              <?= Security::csrfField() ?>
              <button type="submit" class="badge text-bg-<?= $bundle['is_active'] ? 'success' : 'secondary' ?> border-0">
                <?= $bundle['is_active'] ? 'Active' : 'Inactive' ?>
              </button>
            </form>
          </td>
          <td>
            <div class="d-flex gap-2">
              <a href="<?= url('/admin/bundles/' . $bundle['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= url('/admin/bundles/' . $bundle['id'] . '/delete') ?>" onsubmit="return confirm('Delete this bundle?');">
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
