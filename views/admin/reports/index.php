<?php
$reports = [
    ['sales', 'Sales Report', 'bi-graph-up', 'Daily/monthly POS sales totals'],
    ['revenue', 'Revenue Report', 'bi-cash-stack', 'Revenue by type (membership, store, etc.)'],
    ['monthly-revenue', 'Monthly Revenue', 'bi-graph-up-arrow', 'Revenue trend over the last 12 months'],
    ['products', 'Product Report', 'bi-box-seam', 'Top/least selling products, combined online + POS'],
    ['stock', 'Inventory Report', 'bi-clipboard-data', 'Current stock levels and recent stock movements'],
    ['delivery', 'Delivery Report', 'bi-truck', 'Delivery orders by status, zone, and driver'],
    ['pickup', 'Pickup Report', 'bi-shop-window', 'Pickup orders by status and time slot'],
    ['customers', 'Customer Report', 'bi-person-lines-fill', 'Top customers and repeat vs one-time buyers'],
    ['coupons', 'Coupon Report', 'bi-ticket-perforated', 'Coupon usage and discount given'],
    ['offer-performance', 'Offer Performance', 'bi-percent', 'Which discount tier actually drove each sale'],
    ['members', 'Members', 'bi-people', 'New members and status breakdown'],
    ['renewals', 'Membership Renewals', 'bi-arrow-repeat', 'Upcoming expiries and recent renewals'],
    ['attendance', 'Attendance', 'bi-calendar-check', 'Daily check-in counts'],
    ['trainer-income', 'Trainer Income', 'bi-person-badge', 'Trainer fee payments'],
    ['store-sales', 'Store Sales (POS only)', 'bi-shop', 'POS product sales by item and category'],
    ['online-orders', 'Online Orders', 'bi-bag-check', 'Online storefront orders by day'],
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
