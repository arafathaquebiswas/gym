<?php
/** @var array $trainer */
$availabilityLabels = ['available' => 'Available', 'busy' => 'Busy', 'on_leave' => 'On Leave', 'offline' => 'Offline'];
$availabilityClasses = ['available' => 'badge-available', 'busy' => 'badge-busy', 'on_leave' => 'badge-on-leave', 'offline' => 'badge-offline'];
$certifications = array_filter(array_map('trim', explode(',', $trainer['certifications'] ?? '')));
$dailyHours = (new TrainerSchedule())->typicalDailyHours((int) $trainer['id']);
$bio = (string) ($trainer['bio'] ?? '');
$bioShort = mb_strlen($bio) > 100 ? mb_substr($bio, 0, 97) . '...' : $bio;
$defaultPhoto = asset('images/defaults/default-trainer.svg');
?>
<div class="glass-card trainer-card h-100 d-flex flex-column">
  <?php if (!empty($trainer['is_featured'])): ?><span class="featured-ribbon"><i class="bi bi-star-fill"></i> Featured</span><?php endif; ?>
  <div class="trainer-photo"><?= media_tile($trainer['photo'], $trainer['name'], 'bi-person-circle', '', $defaultPhoto) ?></div>
  <div class="trainer-card-body flex-grow-1 d-flex flex-column">
    <span class="badge badge-availability <?= e($availabilityClasses[$trainer['availability_status']] ?? '') ?> mx-auto">
      <i class="bi bi-circle-fill"></i> <?= e($availabilityLabels[$trainer['availability_status']] ?? 'Available') ?>
    </span>
    <h5 class="mt-3 mb-0"><?= e($trainer['name']) ?></h5>
    <?php if (!empty($trainer['job_title'])): ?><p class="text-white-50 small mb-1"><?= e($trainer['job_title']) ?></p><?php endif; ?>
    <p class="text-orange small mb-1"><?= e($trainer['specialization']) ?></p>

    <?php if ($certifications): ?>
    <div class="mb-1">
      <?php foreach (array_slice($certifications, 0, 2) as $cert): ?>
        <span class="cert-badge"><i class="bi bi-patch-check-fill"></i> <?= e($cert) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="trainer-meta-row">
      <span><i class="bi bi-award text-orange"></i> <?= (int) $trainer['experience_years'] ?> yrs experience</span>
      <?php if ($dailyHours): ?><span><i class="bi bi-clock text-orange"></i> <?= (int) $dailyHours ?>h/day</span><?php endif; ?>
    </div>

    <?php if ($trainer['monthly_pt_price']): ?>
      <p class="mb-2">Personal Training: <span class="trainer-pt-price">৳<?= number_format((float) $trainer['monthly_pt_price']) ?> / Month</span></p>
    <?php endif; ?>

    <?php if ($bioShort): ?><p class="trainer-bio-short"><?= e($bioShort) ?></p><?php endif; ?>

    <?php if ($trainer['facebook_url'] || $trainer['instagram_url'] || $trainer['linkedin_url']): ?>
    <div class="trainer-socials mb-2">
      <?php if ($trainer['facebook_url']): ?><a href="<?= e($trainer['facebook_url']) ?>" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a><?php endif; ?>
      <?php if ($trainer['instagram_url']): ?><a href="<?= e($trainer['instagram_url']) ?>" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a><?php endif; ?>
      <?php if ($trainer['linkedin_url']): ?><a href="<?= e($trainer['linkedin_url']) ?>" target="_blank" rel="noopener"><i class="bi bi-linkedin"></i></a><?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-auto">
      <a href="<?= url('/trainers/' . e($trainer['slug'])) ?>" class="btn btn-ps-outline flex-grow-1">View Profile</a>
      <a href="<?= url('/trainers/' . e($trainer['slug']) . '#booking') ?>" class="btn btn-ps flex-grow-1">Book Trainer</a>
    </div>
  </div>
</div>
