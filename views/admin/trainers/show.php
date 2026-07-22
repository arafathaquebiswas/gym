<?php
/** @var array $trainer */
/** @var int $assignedMemberCount */
/** @var array $bookings */
/** @var int $reviewCount */
/** @var float|null $averageRating */
?>
<div class="mb-3">
  <a href="<?= url('/admin/trainers') ?>" class="text-white-50 small"><i class="bi bi-arrow-left"></i> Back to Trainers</a>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="admin-card text-center">
      <img src="<?= e($trainer['photo'] ? (str_starts_with($trainer['photo'], 'uploads/') ? url($trainer['photo']) : asset('images/' . $trainer['photo'])) : asset('images/defaults/default-trainer.svg')) ?>" style="width:140px;height:140px;object-fit:cover;border-radius:50%" alt="">
      <h5 class="mt-3 mb-0"><?= e($trainer['name']) ?></h5>
      <p class="text-white-50 small"><?= e($trainer['job_title'] ?? $trainer['specialization'] ?? '') ?></p>
      <a href="<?= url('/admin/trainers/' . $trainer['id'] . '/edit') ?>" class="btn btn-ps btn-sm w-100 mt-2">Edit Trainer</a>
      <a href="<?= url('/trainers/' . $trainer['slug']) ?>" target="_blank" class="btn btn-ps-outline btn-sm w-100 mt-2">View Public Profile</a>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-3">
        <div class="admin-card text-center"><div class="text-white-50 small">Assigned Members</div><div class="fs-3 fw-bold text-orange"><?= (int) $assignedMemberCount ?></div><?= $trainer['max_members'] ? '<div class="text-white-50 small">of ' . (int) $trainer['max_members'] . ' max</div>' : '' ?></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="admin-card text-center"><div class="text-white-50 small">Upcoming Bookings</div><div class="fs-3 fw-bold text-orange"><?= count($bookings) ?></div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="admin-card text-center"><div class="text-white-50 small">Reviews</div><div class="fs-3 fw-bold text-orange"><?= (int) $reviewCount ?></div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="admin-card text-center"><div class="text-white-50 small">Avg. Rating</div><div class="fs-3 fw-bold text-orange"><?= $averageRating ?? '—' ?></div></div>
      </div>
    </div>

    <div class="admin-card">
      <h6 class="mb-3">Upcoming Bookings</h6>
      <?php if (empty($bookings)): ?>
        <p class="text-white-50 mb-0">No upcoming bookings.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>Member</th><th>Date</th><th>Time</th></tr></thead>
          <tbody>
            <?php foreach ($bookings as $booking): ?>
            <tr>
              <td><?= e($booking['member_name']) ?></td>
              <td><?= format_date($booking['booking_date'], 'D, d M Y') ?></td>
              <td><?= e(date('g:i A', strtotime($booking['start_time']))) ?> - <?= e(date('g:i A', strtotime($booking['end_time']))) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
