<?php
$pageTitle = $trainer['name'];
/** @var array $trainer */
/** @var array $weeklySchedule */
/** @var int|null $dailyHours */
/** @var string $minBookingDate */
/** @var string $maxBookingDate */
/** @var array $gallery */
/** @var array $reviews */
/** @var float|null $averageRating */
/** @var int $reviewCount */
/** @var bool $canReview */

$availabilityLabels = ['available' => 'Available', 'busy' => 'Busy', 'on_leave' => 'On Leave', 'offline' => 'Offline'];
$availabilityClasses = ['available' => 'badge-available', 'busy' => 'badge-busy', 'on_leave' => 'badge-on-leave', 'offline' => 'badge-offline'];
$certifications = array_filter(array_map('trim', explode(',', $trainer['certifications'] ?? '')));
$achievements = array_filter(array_map('trim', explode(',', $trainer['achievements'] ?? '')));
$defaultPhoto = asset('images/defaults/default-trainer.svg');
$coverSrc = $trainer['cover_photo']
    ? (str_starts_with($trainer['cover_photo'], 'uploads/') ? url($trainer['cover_photo']) : asset('images/' . $trainer['cover_photo']))
    : null;
?>

<?php if ($coverSrc): ?>
<div class="trainer-cover-banner" style="background-image:url('<?= e($coverSrc) ?>')"></div>
<?php endif; ?>

<section class="section <?= $coverSrc ? 'pt-4' : '' ?>">
  <div class="container">
    <nav class="mb-4 small text-white-50">
      <a href="<?= url('/personal-training') ?>" class="text-white-50">Personal Training</a> /
      <span class="text-orange"><?= e($trainer['name']) ?></span>
    </nav>

    <div class="row g-5">
      <div class="col-lg-4">
        <div class="trainer-detail-photo">
          <?= media_tile($trainer['photo'], $trainer['name'], 'bi-person-circle', '', $defaultPhoto) ?>
        </div>
      </div>
      <div class="col-lg-8">
        <span class="badge badge-availability <?= e($availabilityClasses[$trainer['availability_status']] ?? '') ?>">
          <i class="bi bi-circle-fill"></i> <?= e($availabilityLabels[$trainer['availability_status']] ?? 'Available') ?>
        </span>
        <h1 class="mt-3 mb-1"><?= e($trainer['name']) ?></h1>
        <?php if ($trainer['job_title']): ?><p class="text-white-50 mb-1"><?= e($trainer['job_title']) ?></p><?php endif; ?>
        <p class="text-orange fs-5 mb-3"><?= e($trainer['specialization']) ?></p>

        <?php if ($averageRating): ?>
        <div class="mb-3">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="bi <?= $i <= round($averageRating) ? 'bi-star-fill' : 'bi-star' ?> text-orange"></i>
          <?php endfor; ?>
          <span class="text-white-50 small ms-1"><?= e((string) $averageRating) ?> (<?= (int) $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?>)</span>
        </div>
        <?php endif; ?>

        <?php if ($certifications): ?>
        <div class="mb-2">
          <?php foreach ($certifications as $cert): ?>
            <span class="cert-badge"><i class="bi bi-patch-check-fill"></i> <?= e($cert) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($achievements): ?>
        <div class="mb-3">
          <?php foreach ($achievements as $achievement): ?>
            <span class="cert-badge"><i class="bi bi-trophy-fill"></i> <?= e($achievement) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="row g-3 my-3">
          <div class="col-6 col-md-3">
            <div class="glass-card p-3 text-center">
              <div class="stat-num text-orange fs-4 fw-bold"><?= (int) $trainer['experience_years'] ?></div>
              <small class="text-white-50">Years Experience</small>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="glass-card p-3 text-center" data-pkg-card>
              <?php if ($trainer['offer_is_live']): ?>
                <div class="offer-only">
                  <div class="stat-num text-orange fs-4 fw-bold">
                    ৳<?= number_format((float) $trainer['offer_price']) ?>
                    <small class="text-white-50 text-decoration-line-through fs-6">৳<?= number_format((float) $trainer['monthly_pt_price']) ?></small>
                  </div>
                  <small class="text-white-50">Personal Training / Month</small>
                  <div class="pkg-savings small mt-1">Save ৳<?= number_format((float) $trainer['savings_amount']) ?> (<?= (int) $trainer['savings_percentage'] ?>% OFF)</div>
                  <?php if ($trainer['offer_end_date']): ?>
                  <div class="offer-countdown-label mt-2 small">Offer Ends In</div>
                  <div class="offer-countdown" data-offer-countdown="<?= e($trainer['offer_end_date']) ?>">
                    <div class="offer-countdown-unit"><div class="num js-days">00</div><div class="label">Days</div></div>
                    <div class="offer-countdown-unit"><div class="num js-hours">00</div><div class="label">Hrs</div></div>
                    <div class="offer-countdown-unit"><div class="num js-minutes">00</div><div class="label">Min</div></div>
                  </div>
                  <?php endif; ?>
                </div>
                <div class="offer-expired-fallback d-none">
                  <div class="stat-num text-orange fs-4 fw-bold">৳<?= number_format((float) $trainer['monthly_pt_price']) ?></div>
                  <small class="text-white-50">Personal Training / Month</small>
                </div>
              <?php else: ?>
                <div class="stat-num text-orange fs-4 fw-bold">৳<?= number_format((float) $trainer['monthly_pt_price']) ?></div>
                <small class="text-white-50">Personal Training / Month</small>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($trainer['hourly_rate']): ?>
          <div class="col-6 col-md-3">
            <div class="glass-card p-3 text-center">
              <div class="stat-num text-orange fs-4 fw-bold">৳<?= number_format((float) $trainer['hourly_rate']) ?></div>
              <small class="text-white-50">Per Hour</small>
            </div>
          </div>
          <?php endif; ?>
          <?php if ($dailyHours): ?>
          <div class="col-6 col-md-3">
            <div class="glass-card p-3 text-center">
              <div class="stat-num text-orange fs-4 fw-bold"><?= (int) $dailyHours ?>h</div>
              <small class="text-white-50">Per Working Day</small>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <?php if ($trainer['languages_spoken']): ?>
        <p class="text-white-50 small mb-2"><i class="bi bi-translate text-orange"></i> Speaks: <?= e($trainer['languages_spoken']) ?></p>
        <?php endif; ?>

        <?php if ($trainer['bio']): ?>
        <p class="text-white-50"><?= e($trainer['bio']) ?></p>
        <?php endif; ?>

        <?php if ($trainer['facebook_url'] || $trainer['instagram_url'] || $trainer['linkedin_url']): ?>
        <div class="trainer-socials justify-content-start">
          <?php if ($trainer['facebook_url']): ?><a href="<?= e($trainer['facebook_url']) ?>" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a><?php endif; ?>
          <?php if ($trainer['instagram_url']): ?><a href="<?= e($trainer['instagram_url']) ?>" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a><?php endif; ?>
          <?php if ($trainer['linkedin_url']): ?><a href="<?= e($trainer['linkedin_url']) ?>" target="_blank" rel="noopener"><i class="bi bi-linkedin"></i></a><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!empty($gallery)): ?>
    <div class="mt-5">
      <h4 class="mb-3"><i class="bi bi-images text-orange"></i> Photo Gallery</h4>
      <div class="row g-3">
        <?php foreach ($gallery as $image): ?>
        <div class="col-6 col-md-3">
          <div class="gallery-item">
            <?= media_tile($image['image_path'], $trainer['name'] . ' gallery photo') ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="row g-5 mt-2" id="booking">
      <div class="col-lg-5">
        <h4 class="mb-3"><i class="bi bi-calendar-week text-orange"></i> Weekly Schedule</h4>
        <div class="schedule-grid">
          <?php foreach (TrainerSchedule::DAY_LABELS as $dayNum => $label): $day = $weeklySchedule[$dayNum] ?? null; ?>
          <div class="schedule-row <?= (!$day || $day['is_off']) ? 'is-off' : '' ?>">
            <span class="schedule-day"><?= e($label) ?></span>
            <span class="schedule-hours">
              <?php if ($day && !$day['is_off']): ?>
                <?= e(date('g:i A', strtotime($day['start_time']))) ?> - <?= e(date('g:i A', strtotime($day['end_time']))) ?>
              <?php else: ?>
                Off
              <?php endif; ?>
            </span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="col-lg-7">
        <h4 class="mb-3"><i class="bi bi-calendar-check text-orange"></i> Book a Session</h4>
        <?php if (Auth::hasRole('member')): ?>
        <div class="glass-card p-4" id="bookingWidget"
             data-trainer-id="<?= (int) $trainer['id'] ?>"
             data-slots-url="<?= url('/api/trainer-slots.php') ?>"
             data-book-url="<?= url('/trainers/' . e($trainer['slug']) . '/book') ?>"
             data-csrf="<?= e(Security::csrfToken()) ?>">
          <div class="mb-3">
            <label class="form-ps-label text-white-50 mb-2 d-block">Choose a date</label>
            <input type="date" id="bookingDate" class="form-control form-ps-input"
                   min="<?= e($minBookingDate) ?>" max="<?= e($maxBookingDate) ?>" value="<?= e($minBookingDate) ?>">
          </div>
          <div id="bookingSlots" class="slot-grid"></div>
          <div id="bookingMessage" class="mt-3"></div>
        </div>
        <?php elseif (Auth::check()): ?>
        <div class="glass-card p-4 text-center text-white-50">
          Trainer bookings are for members. Staff accounts can view schedules here but don't book sessions.
        </div>
        <?php else: ?>
        <div class="glass-card p-4 text-center">
          <p class="text-white-50 mb-3">Log in as a member to book a session with <?= e($trainer['name']) ?>.</p>
          <a href="<?= url('/login') ?>" class="btn btn-ps">Login to Book</a>
          <a href="<?= url('/register') ?>" class="btn btn-ps-outline ms-2">Create Account</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="mt-5">
      <h4 class="mb-3"><i class="bi bi-chat-square-text text-orange"></i> Customer Reviews</h4>

      <?php if ($canReview): ?>
      <div class="glass-card p-4 mb-4">
        <h6 class="mb-3">Leave a Review</h6>
        <form method="post" action="<?= url('/trainers/' . e($trainer['slug']) . '/review') ?>">
          <?= Security::csrfField() ?>
          <div class="mb-3">
            <select name="rating" class="form-select form-ps-input" style="max-width:200px" required>
              <option value="">Rating</option>
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>"><?= $i ?> Star<?= $i === 1 ? '' : 's' ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <textarea name="comment" class="form-control form-ps-input mb-3" rows="3" placeholder="Optional comment"></textarea>
          <button type="submit" class="btn btn-ps">Submit Review</button>
        </form>
      </div>
      <?php endif; ?>

      <?php if (empty($reviews)): ?>
        <p class="text-white-50">No reviews yet<?= $canReview ? ' — be the first!' : '.' ?></p>
      <?php else: ?>
        <?php foreach ($reviews as $review): ?>
        <div class="glass-card p-3 mb-3">
          <div class="d-flex justify-content-between align-items-start">
            <strong><?= e($review['member_name']) ?></strong>
            <span class="text-orange small"><?php for ($i = 1; $i <= 5; $i++): ?><i class="bi <?= $i <= $review['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i><?php endfor; ?></span>
          </div>
          <?php if ($review['comment']): ?><p class="text-white-50 small mt-2 mb-0"><?= e($review['comment']) ?></p><?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php $extraScripts = ['js/trainer-booking.js', 'js/pricing-countdown.js']; ?>
