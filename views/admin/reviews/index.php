<?php
/** @var array $reviews */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
$statusColors = ['pending' => 'secondary', 'approved' => 'success', 'hidden' => 'dark'];
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Reviews (<?= (int) $total ?>)</h6>
  </div>

  <form method="get" action="<?= url('/admin/reviews') ?>" class="admin-toolbar admin-form">
    <select name="status" class="form-select form-select-sm">
      <option value="">All Statuses</option>
      <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
      <option value="hidden" <?= $filters['status'] === 'hidden' ? 'selected' : '' ?>>Hidden</option>
    </select>
    <select name="rating" class="form-select form-select-sm">
      <option value="">All Ratings</option>
      <?php for ($s = 5; $s >= 1; $s--): ?><option value="<?= $s ?>" <?= (string) $filters['rating'] === (string) $s ? 'selected' : '' ?>><?= $s ?> Star<?= $s === 1 ? '' : 's' ?></option><?php endfor; ?>
    </select>
    <select name="sort" class="form-select form-select-sm">
      <option value="">Newest First</option>
      <option value="oldest" <?= $filters['sort'] === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
    </select>
    <button type="submit" class="btn btn-ps-outline btn-sm">Filter</button>
  </form>

  <?php if (empty($reviews)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No reviews match your filters.</p>
  <?php else: ?>
  <?php foreach ($reviews as $review): ?>
  <div class="admin-card mb-3" style="background:rgba(255,255,255,.03)">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div>
        <strong><?= e($review['product_name']) ?></strong>
        <div class="text-white-50 small">by <?= e($review['member_name']) ?> &middot; <?= format_date($review['created_at'], 'd M Y') ?></div>
        <div>
          <?php for ($s = 1; $s <= 5; $s++): ?><i class="bi <?= $s <= $review['rating'] ? 'bi-star-fill text-orange' : 'bi-star text-white-50' ?> small"></i><?php endfor; ?>
        </div>
      </div>
      <span class="badge text-bg-<?= $statusColors[$review['status']] ?? 'secondary' ?>"><?= e(ucfirst($review['status'])) ?></span>
    </div>
    <?php if ($review['comment']): ?><p class="mt-2 mb-0"><?= nl2br(e($review['comment'])) ?></p><?php endif; ?>

    <?php if ($review['admin_reply']): ?>
    <div class="glass-card p-2 mt-2">
      <div class="text-white-50 small">Admin reply:</div>
      <div class="small"><?= nl2br(e($review['admin_reply'])) ?></div>
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-3 flex-wrap">
      <?php if ($review['status'] !== 'approved'): ?>
      <form method="post" action="<?= url('/admin/reviews/' . $review['id'] . '/approve') ?>">
        <?= Security::csrfField() ?>
        <button type="submit" class="btn btn-outline-success btn-sm">Approve</button>
      </form>
      <?php endif; ?>
      <?php if ($review['status'] !== 'hidden'): ?>
      <form method="post" action="<?= url('/admin/reviews/' . $review['id'] . '/hide') ?>">
        <?= Security::csrfField() ?>
        <button type="submit" class="btn btn-outline-secondary btn-sm">Hide</button>
      </form>
      <?php endif; ?>
      <button type="button" class="btn btn-ps-outline btn-sm" data-bs-toggle="modal" data-bs-target="#replyModal<?= $review['id'] ?>">Reply</button>
      <form method="post" action="<?= url('/admin/reviews/' . $review['id'] . '/delete') ?>" onsubmit="return confirm('Delete this review permanently?');">
        <?= Security::csrfField() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
      </form>
    </div>

    <div class="modal fade" id="replyModal<?= $review['id'] ?>" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark">
          <form method="post" action="<?= url('/admin/reviews/' . $review['id'] . '/reply') ?>">
            <?= Security::csrfField() ?>
            <div class="modal-header"><h6 class="modal-title">Reply to Review</h6></div>
            <div class="modal-body">
              <textarea name="admin_reply" class="form-control" rows="3"><?= e($review['admin_reply'] ?? '') ?></textarea>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-ps btn-sm">Post Reply</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= url('/admin/reviews?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
