<?php
$pageTitle = 'My Account';
/** @var array|null $member */
/** @var array|null $subscription */
/** @var array $bookings */
$user = Auth::user();
?>

<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="glass-card p-4 text-center">
          <div class="img-placeholder mx-auto mb-3" style="width:120px;height:120px;border-radius:50%"><i class="bi bi-person"></i></div>
          <h5 class="mb-0"><?= e($user['name']) ?></h5>
          <p class="text-white-50 small"><?= e($user['email']) ?></p>
          <?php if ($member): ?>
            <span class="badge-ps badge px-3 py-2"><?= e(ucfirst($member['status'])) ?></span>
            <p class="text-white-50 small mt-3 mb-0">Member ID: <?= e($member['member_code']) ?></p>
            <p class="text-white-50 small">Joined: <?= format_date($member['join_date']) ?></p>
          <?php endif; ?>
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
        <div class="glass-card p-4">
          <h5 class="mb-2"><i class="bi bi-hourglass-split text-orange"></i> Full Member Dashboard Coming Soon</h5>
          <p class="text-white-50 mb-0">Attendance history, QR check-in, invoices, and progress tracking will be available here in the next phase of the PowerSurge platform.</p>
        </div>
      </div>
    </div>
  </div>
</section>
