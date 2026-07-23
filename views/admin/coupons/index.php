<?php
/** @var array $coupons */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
$appliesLabels = ['product' => 'Store Products', 'membership' => 'Membership', 'trainer' => 'Personal Trainer', 'both' => 'Entire Order'];
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Coupons (<?= (int) $total ?>)</h6>
    <a href="<?= url('/admin/coupons/create') ?>" class="btn btn-ps btn-sm"><i class="bi bi-plus-lg"></i> Add Coupon</a>
  </div>

  <form method="get" action="<?= url('/admin/coupons') ?>" class="admin-toolbar admin-form">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search code or name" value="<?= e($filters['search']) ?>">
    <select name="status" class="form-select form-select-sm">
      <option value="">All Statuses</option>
      <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>
    <button type="submit" class="btn btn-ps-outline btn-sm">Filter</button>
  </form>

  <?php if (empty($coupons)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No coupons yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Code</th><th>Name</th><th>Discount</th><th>Applies To</th><th>Usage</th><th>Valid</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($coupons as $coupon): ?>
        <tr>
          <td><code><?= e($coupon['code']) ?></code></td>
          <td><?= e($coupon['title']) ?></td>
          <td>
            <?= $coupon['discount_type'] === 'percent' ? (float) $coupon['discount_value'] . '%' : money((float) $coupon['discount_value']) ?>
            <?php if ($coupon['max_discount_amount']): ?><div class="text-white-50 small">Max <?= money((float) $coupon['max_discount_amount']) ?></div><?php endif; ?>
          </td>
          <td><?= e($appliesLabels[$coupon['applies_to']] ?? $coupon['applies_to']) ?></td>
          <td><?= (int) $coupon['used_count'] ?><?= $coupon['usage_limit'] ? ' / ' . (int) $coupon['usage_limit'] : '' ?></td>
          <td><?= format_date($coupon['start_date']) ?> – <?= format_date($coupon['end_date']) ?></td>
          <td>
            <form method="post" action="<?= url('/admin/coupons/' . $coupon['id'] . '/toggle-active') ?>">
              <?= Security::csrfField() ?>
              <button type="submit" class="btn btn-link p-0" title="Toggle active">
                <span class="badge text-bg-<?= $coupon['is_active'] ? 'success' : 'secondary' ?>"><?= $coupon['is_active'] ? 'Active' : 'Inactive' ?></span>
              </button>
            </form>
          </td>
          <td>
            <div class="d-flex gap-2">
              <a href="<?= url('/admin/coupons/' . $coupon['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= url('/admin/coupons/' . $coupon['id'] . '/duplicate') ?>">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-ps-outline btn-sm" title="Duplicate"><i class="bi bi-copy"></i></button>
              </form>
              <form method="post" action="<?= url('/admin/coupons/' . $coupon['id'] . '/delete') ?>" onsubmit="return confirm('Delete this coupon?');">
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
          <a class="page-link" href="<?= url('/admin/coupons?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
