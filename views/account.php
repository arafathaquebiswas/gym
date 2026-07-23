<?php
$pageTitle = 'My Account';
/** @var array|null $member */
/** @var array|null $subscription */
/** @var array $bookings */
/** @var array $recentOrders */
/** @var int $wishlistCount */
$user = Auth::user();
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'ready_for_pickup' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'dark'];
?>

<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="glass-card p-4 text-center">
          <div class="mx-auto mb-3" style="width:120px;height:120px;border-radius:50%;overflow:hidden">
            <?= media_tile($member['photo'] ?? null, $user['name'], 'bi-person') ?>
          </div>
          <h5 class="mb-0"><?= e($user['name']) ?></h5>
          <p class="text-white-50 small"><?= e($user['email']) ?></p>
          <?php if ($member): ?>
            <span class="badge-ps badge px-3 py-2"><?= e(ucfirst($member['status'])) ?></span>
            <p class="text-white-50 small mt-3 mb-0">Member ID: <?= e($member['member_code']) ?></p>
            <p class="text-white-50 small">Joined: <?= format_date($member['join_date']) ?></p>
          <?php endif; ?>
          <a href="<?= url('/account/profile') ?>" class="btn btn-ps-outline btn-sm mt-3"><i class="bi bi-pencil"></i> Edit Profile</a>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="glass-card p-4 mb-4">
          <h5 class="mb-3">Membership Status</h5>
          <?php if ($subscription): ?>
            <p class="mb-1"><strong class="text-orange"><?= e($subscription['package_name']) ?></strong></p>
            <p class="text-white-50 mb-0">Active from <?= format_date($subscription['start_date']) ?> to <?= format_date($subscription['end_date']) ?></p>
          <?php else: ?>
            <p class="text-white-50 mb-3">You don't have an active membership package yet. Visit the front desk to activate one.</p>
            <a href="<?= url('/membership') ?>" class="btn btn-ps-outline btn-sm">Browse Membership Plans</a>
          <?php endif; ?>
        </div>
        <div class="glass-card p-4 mb-4">
          <h5 class="mb-3"><i class="bi bi-calendar-check text-orange"></i> My Trainer Bookings</h5>
          <?php if (empty($bookings)): ?>
            <p class="text-white-50 mb-3">You don't have any upcoming trainer sessions booked.</p>
            <a href="<?= url('/personal-training') ?>" class="btn btn-ps-outline btn-sm">Browse Trainers</a>
          <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
            <div class="booking-list-item">
              <strong><?= e($booking['trainer_name']) ?></strong>
              <div class="text-white-50 small">
                <?= format_date($booking['booking_date'], 'D, d M Y') ?> &middot;
                <?= e(date('g:i A', strtotime($booking['start_time']))) ?> - <?= e(date('g:i A', strtotime($booking['end_time']))) ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="glass-card p-4 mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-bag-check text-orange"></i> Order History</h5>
            <a href="<?= url('/account/orders') ?>" class="btn btn-ps-outline btn-sm">View All</a>
          </div>
          <?php if (empty($recentOrders)): ?>
            <p class="text-white-50 mb-3">You haven't placed any store orders yet.</p>
            <a href="<?= url('/store') ?>" class="btn btn-ps-outline btn-sm">Visit the Store</a>
          <?php else: ?>
            <?php foreach ($recentOrders as $order): ?>
            <div class="booking-list-item d-flex justify-content-between align-items-center">
              <div>
                <a href="<?= url('/account/orders/' . $order['id']) ?>" class="text-white text-decoration-none fw-semibold">#<?= e($order['order_no']) ?></a>
                <div class="text-white-50 small"><?= format_date($order['created_at'], 'd M Y') ?> &middot; ৳<?= number_format((float) $order['total']) ?></div>
              </div>
              <span class="badge text-bg-<?= $statusColors[$order['status']] ?? 'secondary' ?>"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="glass-card p-4">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-heart text-orange"></i> Wishlist</h5>
            <a href="<?= url('/account/wishlist') ?>" class="btn btn-ps-outline btn-sm"><?= (int) $wishlistCount ?> item<?= $wishlistCount === 1 ? '' : 's' ?></a>
          </div>
          <p class="text-white-50 mt-2 mb-0"><a href="<?= url('/account/addresses') ?>" class="text-white-50">Manage saved addresses →</a></p>
        </div>
      </div>
    </div>
  </div>
</section>
