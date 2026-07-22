<?php
/** @var array $pkg */
/** @var bool $showFeatures */
/** @var string $ctaLabel */
/** @var string $ctaUrl */
$showFeatures = $showFeatures ?? true;
$ctaLabel = $ctaLabel ?? 'Choose Plan';
$ctaUrl = $ctaUrl ?? url('/register');
$badgeClass = $pkg['badge'] ? 'badge-' . strtolower(str_replace(' ', '-', $pkg['badge'])) : '';
$months = (int) round($pkg['duration_days'] / 30);
?>
<div class="glass-card pkg-card <?= $pkg['is_featured'] ? 'featured' : '' ?>" data-pkg-card>
  <?php if ($pkg['badge']): ?><span class="offer-badge <?= e($badgeClass) ?>"><?= e($pkg['badge']) ?></span><?php endif; ?>

  <h5><?= e($pkg['name']) ?></h5>
  <small class="text-white-50"><?= $months ?> month<?= $months > 1 ? 's' : '' ?> full access</small>

  <?php if ($pkg['offer_is_live']): ?>
    <div class="offer-only">
      <div class="pkg-price mt-2">
        <span class="pkg-old-price">৳<?= number_format((float) $pkg['regular_price']) ?></span>
        <div class="pkg-now-label">Now Only</div>
        ৳<?= number_format((float) $pkg['offer_price']) ?>
      </div>
      <span class="pkg-savings">Save ৳<?= number_format((float) $pkg['savings_amount']) ?> (<?= (int) round((float) $pkg['savings_percentage']) ?>% OFF)</span>

      <?php if ($pkg['offer_end_date']): ?>
      <div class="offer-countdown-label mt-3">Offer Ends In</div>
      <div class="offer-countdown" data-offer-countdown="<?= e($pkg['offer_end_date']) ?>">
        <div class="offer-countdown-unit"><div class="num js-days">00</div><div class="label">Days</div></div>
        <div class="offer-countdown-unit"><div class="num js-hours">00</div><div class="label">Hrs</div></div>
        <div class="offer-countdown-unit"><div class="num js-minutes">00</div><div class="label">Min</div></div>
      </div>
      <?php endif; ?>
    </div>
    <div class="offer-expired-fallback d-none">
      <div class="pkg-price mt-2">৳<?= number_format((float) $pkg['regular_price']) ?></div>
    </div>
  <?php else: ?>
    <div class="pkg-price mt-2">৳<?= number_format((float) $pkg['regular_price']) ?></div>
  <?php endif; ?>

  <?php if ($showFeatures && !empty($pkg['features'])): ?>
  <ul class="mt-3">
    <?php foreach ($pkg['features'] as $feature): ?>
      <li><i class="bi bi-check-circle"></i> <?= e($feature['feature_text']) ?></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <a href="<?= e($ctaUrl) ?>" class="btn btn-ps mt-auto"><?= e($ctaLabel) ?></a>
</div>
