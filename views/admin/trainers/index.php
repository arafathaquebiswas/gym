<?php
/** @var array $trainers */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
/** @var array $specializations */
/** @var array $stats */
$statusLabels = ['available' => 'Available', 'busy' => 'Busy', 'on_leave' => 'On Leave', 'offline' => 'Offline'];
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Total</div><div class="fs-3 fw-bold text-orange"><?= (int) $stats['total'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Active</div><div class="fs-3 fw-bold text-orange"><?= (int) $stats['active'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Featured</div><div class="fs-3 fw-bold text-orange"><?= (int) $stats['featured'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Avg. Experience</div><div class="fs-3 fw-bold text-orange"><?= e((string) $stats['avgExperience']) ?> yrs</div></div>
  </div>
</div>

<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">All Trainers (<?= (int) $total ?>)</h6>
    <a href="<?= url('/admin/trainers/create') ?>" class="btn btn-ps btn-sm"><i class="bi bi-plus-lg"></i> Add Trainer</a>
  </div>

  <form method="get" action="<?= url('/admin/trainers') ?>" class="admin-toolbar admin-form">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name" value="<?= e($filters['search']) ?>">
    <select name="status" class="form-select form-select-sm">
      <option value="">All Statuses</option>
      <?php foreach ($statusLabels as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="specialization" class="form-select form-select-sm">
      <option value="">All Specializations</option>
      <?php foreach ($specializations as $spec): ?>
        <option value="<?= e($spec) ?>" <?= $filters['specialization'] === $spec ? 'selected' : '' ?>><?= e($spec) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="sort" class="form-select form-select-sm">
      <option value="">Display Order</option>
      <option value="experience" <?= $filters['sort'] === 'experience' ? 'selected' : '' ?>>Experience (High-Low)</option>
      <option value="price" <?= $filters['sort'] === 'price' ? 'selected' : '' ?>>Price (High-Low)</option>
    </select>
    <button type="submit" class="btn btn-ps-outline btn-sm">Filter</button>
    <?php if ($filters['search'] || $filters['status'] || $filters['specialization'] || $filters['sort']): ?>
      <a href="<?= url('/admin/trainers') ?>" class="btn btn-link btn-sm text-white-50">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (empty($trainers)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No trainers match your filters.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order</th><th>Photo</th><th>Name</th><th>Specialization</th><th>Experience</th>
          <th>Monthly Fee</th><th>Status</th><th>Featured</th><th>Visible</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($trainers as $trainer): ?>
        <tr>
          <td>
            <div class="order-btns">
              <form method="post" action="<?= url('/admin/trainers/' . $trainer['id'] . '/reorder') ?>">
                <?= Security::csrfField() ?><input type="hidden" name="direction" value="up">
                <button type="submit" title="Move up"><i class="bi bi-caret-up-fill"></i></button>
              </form>
              <form method="post" action="<?= url('/admin/trainers/' . $trainer['id'] . '/reorder') ?>">
                <?= Security::csrfField() ?><input type="hidden" name="direction" value="down">
                <button type="submit" title="Move down"><i class="bi bi-caret-down-fill"></i></button>
              </form>
            </div>
          </td>
          <td>
            <?php if ($trainer['photo']): ?>
              <img src="<?= e(str_starts_with($trainer['photo'], 'uploads/') ? url($trainer['photo']) : asset('images/' . $trainer['photo'])) ?>" class="thumb" alt="">
            <?php else: ?>
              <img src="<?= asset('images/defaults/default-trainer.svg') ?>" class="thumb" alt="">
            <?php endif; ?>
          </td>
          <td>
            <a href="<?= url('/admin/trainers/' . $trainer['id']) ?>" class="text-white fw-semibold text-decoration-none"><?= e($trainer['name']) ?></a>
            <?php if ($trainer['job_title']): ?><div class="text-white-50 small"><?= e($trainer['job_title']) ?></div><?php endif; ?>
          </td>
          <td><?= e($trainer['specialization'] ?? '—') ?></td>
          <td><?= (int) $trainer['experience_years'] ?> yrs</td>
          <td><?= $trainer['monthly_pt_price'] ? '৳' . number_format((float) $trainer['monthly_pt_price']) : '—' ?></td>
          <td>
            <form method="post" action="<?= url('/admin/trainers/' . $trainer['id'] . '/status') ?>" class="d-inline">
              <?= Security::csrfField() ?>
              <select name="status" class="form-select form-select-sm status-pill status-<?= e($trainer['availability_status']) ?>" onchange="this.form.submit()" style="border:none;">
                <?php foreach ($statusLabels as $value => $label): ?>
                  <option value="<?= e($value) ?>" <?= $trainer['availability_status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <td>
            <form method="post" action="<?= url('/admin/trainers/' . $trainer['id'] . '/featured') ?>">
              <?= Security::csrfField() ?>
              <button type="submit" class="btn btn-link p-0" title="Toggle featured">
                <i class="bi <?= $trainer['is_featured'] ? 'bi-star-fill text-orange' : 'bi-star text-white-50' ?>"></i>
              </button>
            </form>
          </td>
          <td>
            <form method="post" action="<?= url('/admin/trainers/' . $trainer['id'] . '/toggle-active') ?>">
              <?= Security::csrfField() ?>
              <button type="submit" class="btn btn-link p-0" title="Show/hide on website">
                <i class="bi <?= $trainer['is_active'] ? 'bi-eye-fill text-orange' : 'bi-eye-slash text-white-50' ?>"></i>
              </button>
            </form>
          </td>
          <td>
            <div class="d-flex gap-2">
              <a href="<?= url('/admin/trainers/' . $trainer['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-pencil"></i></a>
              <a href="<?= url('/admin/trainers/' . $trainer['id'] . '/gallery') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-images"></i></a>
              <form method="post" action="<?= url('/admin/trainers/' . $trainer['id'] . '/delete') ?>" onsubmit="return confirm('Delete this trainer permanently? This also removes their schedule, bookings, gallery, and reviews.');">
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
          <a class="page-link" href="<?= url('/admin/trainers?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
