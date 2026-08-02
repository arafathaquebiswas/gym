<div class="admin-page-shell">
  <div class="admin-page-header border-bottom pb-3 mb-3 d-flex justify-content-between align-items-center">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Notifications</li>
        </ol>
      </nav>
      <h1 class="admin-page-title m-0">Notification History</h1>
    </div>
    <div>
      <form method="post" action="<?= url('/admin/notifications/mark-all-read') ?>" class="d-inline">
        <?= Security::csrfField() ?>
        <button type="submit" class="btn btn-ps-outline btn-sm">
          <i class="bi bi-check-all me-1"></i> Mark All as Read
        </button>
      </form>
    </div>
  </div>

  <div class="admin-card p-3">
    <?php if (!$notifications): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-bell-slash fs-1 d-block mb-2 text-secondary opacity-50"></i>
        <p class="mb-0">No notifications found.</p>
      </div>
    <?php else: ?>
      <div class="list-group list-group-flush border-0">
        <?php foreach ($notifications as $n): ?>
          <div class="list-group-item bg-transparent text-white border-secondary border-opacity-25 py-3 px-2 d-flex justify-content-between align-items-start gap-3 <?= $n['is_read'] ? 'opacity-75' : 'bg-dark bg-opacity-50' ?>">
            <div class="d-flex align-items-start gap-3">
              <div class="fs-4 text-orange">
                <i class="bi <?= str_contains($n['title'], 'Order') ? 'bi-bag-check-fill' : 'bi-bell-fill' ?>"></i>
              </div>
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                  <h6 class="mb-0 fw-bold <?= $n['is_read'] ? 'text-white-50' : 'text-white' ?>"><?= e($n['title']) ?></h6>
                  <?php if (!$n['is_read']): ?>
                    <span class="badge bg-orange text-white rounded-pill small" style="font-size: 0.65rem;">NEW</span>
                  <?php endif; ?>
                </div>
                <p class="small text-muted mb-2"><?= e($n['message']) ?></p>
                <div class="d-flex align-items-center gap-3 small text-muted">
                  <span><i class="bi bi-clock me-1"></i><?= format_date($n['created_at']) ?> <?= date('h:i A', strtotime($n['created_at'])) ?></span>
                  <?php if ($n['link']): ?>
                    <a href="<?= e($n['link']) ?>" class="text-orange text-decoration-none fw-semibold">
                      <i class="bi bi-box-arrow-up-right me-1"></i> View Order
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <div>
              <?php if (!$n['is_read']): ?>
                <form method="post" action="<?= url('/admin/notifications/' . $n['id'] . '/read') ?>">
                  <?= Security::csrfField() ?>
                  <button type="submit" class="btn btn-sm btn-ps-outline py-1 px-2 text-nowrap" title="Mark as read">
                    <i class="bi bi-check2"></i> Mark Read
                  </button>
                </form>
              <?php else: ?>
                <span class="badge bg-secondary opacity-50 px-2 py-1"><i class="bi bi-check2-all me-1"></i>Read</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
          <ul class="pagination pagination-sm justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link bg-dark border-secondary text-white" href="<?= url('/admin/notifications?page=' . $i) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
