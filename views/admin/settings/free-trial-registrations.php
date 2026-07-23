<?php
/** @var array $registrations */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Free Trial Registrations (<?= (int) $total ?>)</h6>
    <a href="<?= url('/admin/settings') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Settings</a>
  </div>

  <?php if (empty($registrations)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No registrations yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Registered</th></tr></thead>
      <tbody>
        <?php foreach ($registrations as $reg): ?>
        <tr>
          <td><?= e($reg['name']) ?></td>
          <td><?= e($reg['phone']) ?></td>
          <td><?= e($reg['email'] ?? '—') ?></td>
          <td><?= format_date($reg['created_at'], 'd M Y, h:i A') ?></td>
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
          <a class="page-link" href="<?= url('/admin/settings/free-trial-registrations?page=' . $i) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
