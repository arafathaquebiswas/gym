<?php
/** @var array $packages */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">All Packages (<?= count($packages) ?>)</h6>
    <a href="<?= url('/admin/packages/create') ?>" class="btn btn-ps btn-sm"><i class="bi bi-plus-lg"></i> Add Package</a>
  </div>

  <?php if (empty($packages)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No packages yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order</th><th>Name</th><th>Regular Price</th><th>Offer Price</th><th>Discount</th>
          <th>Offer Window</th><th>Badge</th><th>Offer</th><th>Featured</th><th>Visible</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($packages as $pkg): ?>
        <tr>
          <td>
            <div class="order-btns">
              <form method="post" action="<?= url('/admin/packages/' . $pkg['id'] . '/reorder') ?>">
                <?= Security::csrfField() ?><input type="hidden" name="direction" value="up">
                <button type="submit" title="Move up"><i class="bi bi-caret-up-fill"></i></button>
              </form>
              <form method="post" action="<?= url('/admin/packages/' . $pkg['id'] . '/reorder') ?>">
                <?= Security::csrfField() ?><input type="hidden" name="direction" value="down">
                <button type="submit" title="Move down"><i class="bi bi-caret-down-fill"></i></button>
              </form>
            </div>
          </td>
          <td>
            <a href="<?= url('/admin/packages/' . $pkg['id'] . '/edit') ?>" class="text-white fw-semibold text-decoration-none"><?= e($pkg['name']) ?></a>
            <div class="text-white-50 small"><?= (int) round($pkg['duration_days'] / 30) ?> months</div>
          </td>
          <td>৳<?= number_format((float) $pkg['regular_price']) ?></td>
          <td><?= $pkg['offer_price'] ? '৳' . number_format((float) $pkg['offer_price']) : '—' ?></td>
          <td><?= $pkg['discount_percentage'] ? e((string) $pkg['discount_percentage']) . '%' : '—' ?></td>
          <td class="small text-white-50">
            <?= $pkg['offer_start_date'] ? format_date($pkg['offer_start_date']) : '—' ?> &rarr;
            <?= $pkg['offer_end_date'] ? format_date($pkg['offer_end_date']) : '—' ?>
          </td>
          <td><?= $pkg['badge'] ? '<span class="cert-badge">' . e($pkg['badge']) . '</span>' : '—' ?></td>
          <td>
            <form method="post" action="<?= url('/admin/packages/' . $pkg['id'] . '/toggle-offer') ?>">
              <?= Security::csrfField() ?>
              <button type="submit" class="btn btn-link p-0" title="Toggle offer enabled">
                <i class="bi <?= $pkg['offer_enabled'] ? 'bi-toggle-on text-orange fs-5' : 'bi-toggle-off text-white-50 fs-5' ?>"></i>
              </button>
            </form>
            <?= $pkg['offer_is_live'] ? '<div class="status-pill status-available">LIVE</div>' : '' ?>
          </td>
          <td>
            <form method="post" action="<?= url('/admin/packages/' . $pkg['id'] . '/featured') ?>">
              <?= Security::csrfField() ?>
              <button type="submit" class="btn btn-link p-0"><i class="bi <?= $pkg['is_featured'] ? 'bi-star-fill text-orange' : 'bi-star text-white-50' ?>"></i></button>
            </form>
          </td>
          <td>
            <form method="post" action="<?= url('/admin/packages/' . $pkg['id'] . '/toggle-active') ?>">
              <?= Security::csrfField() ?>
              <button type="submit" class="btn btn-link p-0"><i class="bi <?= $pkg['is_active'] ? 'bi-eye-fill text-orange' : 'bi-eye-slash text-white-50' ?>"></i></button>
            </form>
          </td>
          <td>
            <div class="d-flex gap-2">
              <a href="<?= url('/admin/packages/' . $pkg['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= url('/admin/packages/' . $pkg['id'] . '/delete') ?>" onsubmit="return confirm('Delete this package permanently?');">
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
