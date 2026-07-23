<?php
/** @var array $messages */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
/** @var int $newCount */
$statusColors = ['new' => 'success', 'read' => 'secondary', 'replied' => 'info'];
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Messages (<?= (int) $total ?>) <?php if ($newCount > 0): ?><span class="badge text-bg-success"><?= (int) $newCount ?> new</span><?php endif; ?></h6>
  </div>

  <form method="get" action="<?= url('/admin/messages') ?>" class="admin-toolbar admin-form">
    <select name="status" class="form-select form-select-sm">
      <option value="">All Messages</option>
      <option value="new" <?= $filters['status'] === 'new' ? 'selected' : '' ?>>New</option>
      <option value="read" <?= $filters['status'] === 'read' ? 'selected' : '' ?>>Read</option>
      <option value="replied" <?= $filters['status'] === 'replied' ? 'selected' : '' ?>>Replied</option>
    </select>
    <button type="submit" class="btn btn-ps-outline btn-sm">Filter</button>
    <?php if ($filters['status']): ?>
      <a href="<?= url('/admin/messages') ?>" class="btn btn-link btn-sm text-white-50">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (empty($messages)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No messages yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Status</th><th>From</th><th>Subject</th><th>Received</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($messages as $msg): ?>
        <tr class="<?= $msg['status'] === 'new' ? 'fw-semibold' : '' ?>">
          <td><span class="badge text-bg-<?= $statusColors[$msg['status']] ?? 'secondary' ?>"><?= e(ucfirst($msg['status'])) ?></span></td>
          <td>
            <a href="<?= url('/admin/messages/' . $msg['id']) ?>" class="text-white text-decoration-none"><?= e($msg['name']) ?></a>
            <div class="text-white-50 small fw-normal"><?= e($msg['email']) ?></div>
          </td>
          <td><?= e($msg['subject'] ?: '(No subject)') ?></td>
          <td class="fw-normal"><?= format_date($msg['created_at'], 'd M Y, h:i A') ?></td>
          <td>
            <div class="d-flex gap-2">
              <a href="<?= url('/admin/messages/' . $msg['id']) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-eye"></i></a>
              <form method="post" action="<?= url('/admin/messages/' . $msg['id'] . '/delete') ?>" onsubmit="return confirm('Delete this message?');">
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

  <?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= url('/admin/messages?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
