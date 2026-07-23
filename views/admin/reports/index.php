<?php
$reports = [
    ['sales', 'Sales Report', 'bi-graph-up', 'Daily/monthly sales totals'],
    ['revenue', 'Revenue', 'bi-cash-stack', 'Revenue by type (membership, store, etc.)'],
    ['members', 'Members', 'bi-people', 'New members and status breakdown'],
    ['renewals', 'Membership Renewals', 'bi-arrow-repeat', 'Upcoming expiries and recent renewals'],
    ['attendance', 'Attendance', 'bi-calendar-check', 'Daily check-in counts'],
    ['trainer-income', 'Trainer Income', 'bi-person-badge', 'Trainer fee payments'],
    ['store-sales', 'Store Sales', 'bi-shop', 'Product sales by item and category'],
    ['online-orders', 'Online Orders', 'bi-bag-check', 'Online storefront orders by day'],
    ['stock', 'Stock Report', 'bi-box-seam', 'Current inventory and low-stock items'],
];
?>
<div class="row g-3">
  <?php foreach ($reports as [$slug, $label, $icon, $desc]): ?>
  <div class="col-md-6 col-lg-4">
    <a href="<?= url('/admin/reports/' . $slug) ?>" class="admin-card d-block text-decoration-none text-white h-100">
      <i class="bi <?= $icon ?> fs-3 text-orange"></i>
      <h6 class="mt-2 mb-1"><?= e($label) ?></h6>
      <p class="text-white-50 small mb-0"><?= e($desc) ?></p>
    </a>
  </div>
  <?php endforeach; ?>
</div>
