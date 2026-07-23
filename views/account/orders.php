<?php
$pageTitle = 'My Orders';
/** @var array $orders */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'packed' => 'info', 'ready_for_pickup' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'dark'];
?>
<section class="section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">My <span class="text-orange">Orders</span></h1>
      <a href="<?= url('/account') ?>" class="btn btn-ps-outline btn-sm">Back to Account</a>
    </div>

    <?php if (empty($orders)): ?>
      <div class="glass-card p-5 text-center">
        <p class="text-white-50 mb-3">You haven't placed any orders yet.</p>
        <a href="<?= url('/store') ?>" class="btn btn-ps">Start Shopping</a>
      </div>
    <?php else: ?>
    <div class="glass-card p-4">
      <div class="table-responsive">
        <table class="admin-table w-100">
          <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
              <td><?= e($order['order_no']) ?></td>
              <td><?= format_date($order['created_at'], 'd M Y') ?></td>
              <td>৳<?= number_format((float) $order['total']) ?></td>
              <td><?= e(strtoupper(str_replace('_', ' ', $order['payment_method']))) ?></td>
              <td><span class="badge text-bg-<?= $statusColors[$order['status']] ?? 'secondary' ?>"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span></td>
              <td><a href="<?= url('/account/orders/' . $order['id']) ?>" class="btn btn-ps-outline btn-sm">View</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($totalPages > 1): ?>
      <nav class="mt-3">
        <ul class="pagination pagination-ps justify-content-center">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= url('/account/orders?page=' . $p) ?>"><?= $p ?></a></li>
          <?php endfor; ?>
        </ul>
      </nav>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
