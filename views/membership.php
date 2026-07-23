<?php
$pageTitle = 'Membership Plans';
/** @var array $packages */
/** @var array $comparisonFeatures */
/** @var array $liveOffers */
/** @var array $trainers */
/** @var array $faqs */
?>

<section class="hero py-5 text-center">
  <div class="container">
    <span class="hero-badge">Membership Plans</span>
    <h1>A Plan for <span class="text-orange">Every Goal</span></h1>
    <p class="lead mx-auto" style="max-width:650px">Everything about joining, pricing, and renewing at PowerSurge — packages, offers, comparisons, and trainer add-ons, all in one place.</p>
    <div class="d-flex gap-3 justify-content-center mt-4 flex-wrap">
      <a href="<?= url('/register') ?>" class="btn btn-ps btn-lg">Join Now</a>
      <a href="<?= url('/contact') ?>" class="btn btn-ps-outline btn-lg">Visit / Contact to Renew</a>
    </div>
  </div>
</section>

<?php if (!empty($liveOffers)): ?>
<section class="section pt-0">
  <div class="container">
    <div class="glass-card p-4 text-center" style="border-color: var(--ps-orange)">
      <h6 class="text-orange mb-1"><i class="bi bi-lightning-charge-fill"></i> Current Offers &amp; Discounts</h6>
      <p class="text-white-50 mb-0">
        <?php foreach ($liveOffers as $i => $offer): ?>
          <?= $i > 0 ? ' &middot; ' : '' ?><strong class="text-white"><?= e($offer['name']) ?></strong> — Save <?= (int) round((float) $offer['savings_percentage']) ?>% (ends <?= format_date($offer['offer_end_date'], 'd M') ?>)
        <?php endforeach; ?>
      </p>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section <?= empty($liveOffers) ? '' : 'pt-0' ?>">
  <div class="container text-center">
    <h2 class="section-title">Membership Benefits</h2>
    <div class="row g-4">
      <?php foreach ([
          ['bi-cpu', 'Full Equipment Access', 'Strength, cardio, and free-weight zones — no restrictions.'],
          ['bi-person-badge', 'Certified Trainers', 'On-floor guidance from certified coaches every session.'],
          ['bi-clock-history', 'Open 6 Days a Week', 'Sat–Thu 7:00 AM–11:00 PM, Fri 5:00 PM–10:00 PM.'],
          ['bi-clipboard-pulse', 'Progress Tracking', 'Attendance, BMI, and goal tracking through your member profile.'],
      ] as [$icon, $title, $desc]): ?>
      <div class="col-md-6 col-lg-3">
        <div class="glass-card p-4 h-100">
          <i class="bi <?= $icon ?> text-orange fs-1"></i>
          <h6 class="mt-3"><?= e($title) ?></h6>
          <p class="text-white-50 small mb-0"><?= e($desc) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section bg-ps-black">
  <div class="container text-center">
    <h2 class="section-title">Membership Packages &amp; Pricing</h2>
    <p class="section-subtitle mx-auto">Choose the duration that fits — every plan includes full gym access.</p>
    <div class="row g-4 justify-content-center">
      <?php foreach ($packages as $pkg): ?>
      <div class="col-md-6 col-lg-4">
        <?php if (Feature::on('membership_sales')): ?>
          <?php $this->partial('partials/package-card', ['pkg' => $pkg]); ?>
        <?php else: ?>
          <?php $this->partial('partials/package-card', ['pkg' => $pkg, 'ctaLabel' => 'Contact Us', 'ctaUrl' => url('/contact')]); ?>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if (!empty($comparisonFeatures)): ?>
<section class="section">
  <div class="container">
    <h2 class="section-title text-center">Package Comparison</h2>
    <div class="table-responsive mt-4">
      <table class="table table-dark table-borderless align-middle text-center">
        <thead>
          <tr class="text-white-50 text-uppercase small">
            <th class="text-start">Feature</th>
            <?php foreach ($packages as $pkg): ?><th><?= e($pkg['name']) ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <tr style="border-bottom:1px solid var(--ps-border)">
            <td class="text-start fw-semibold">Price</td>
            <?php foreach ($packages as $pkg): ?>
              <td>৳<?= number_format((float) $pkg['display_price']) ?></td>
            <?php endforeach; ?>
          </tr>
          <tr style="border-bottom:1px solid var(--ps-border)">
            <td class="text-start fw-semibold">Duration</td>
            <?php foreach ($packages as $pkg): ?>
              <td><?= (int) round($pkg['duration_days'] / 30) ?> mo</td>
            <?php endforeach; ?>
          </tr>
          <?php foreach ($comparisonFeatures as $featureText): ?>
          <tr style="border-bottom:1px solid var(--ps-border)">
            <td class="text-start"><?= e($featureText) ?></td>
            <?php foreach ($packages as $pkg):
              $has = in_array($featureText, array_column($pkg['features'], 'feature_text'), true); ?>
              <td><i class="bi <?= $has ? 'bi-check-circle text-orange' : 'bi-x-circle text-white-50' ?>"></i></td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($trainers)): ?>
<section class="section bg-ps-black">
  <div class="container text-center">
    <h2 class="section-title">Personal Trainer Add-on Pricing</h2>
    <p class="section-subtitle mx-auto">Want 1-on-1 coaching alongside your membership? No extra admission fee — just the trainer's monthly rate.</p>
    <div class="row g-3 justify-content-center mt-2">
      <?php foreach ($trainers as $trainer): if (!$trainer['monthly_pt_price']) continue; ?>
      <div class="col-md-4">
        <div class="glass-card p-3 text-center">
          <h6 class="mb-1"><?= e($trainer['name']) ?></h6>
          <p class="text-white-50 small mb-2"><?= e($trainer['specialization']) ?></p>
          <div class="trainer-pt-price fs-5">৳<?= number_format((float) $trainer['monthly_pt_price']) ?> / month</div>
          <a href="<?= url('/trainers/' . e($trainer['slug'])) ?>" class="btn btn-ps-outline btn-sm mt-2">View Profile</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($faqs)): ?>
<section class="section">
  <div class="container">
    <h2 class="section-title text-center mb-4">Membership &amp; Pricing FAQ</h2>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="accordion accordion-ps" id="membershipFaq">
          <?php foreach ($faqs as $i => $faq): ?>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mfaq<?= $i ?>">
                <?= e($faq['question']) ?>
              </button>
            </h2>
            <div id="mfaq<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#membershipFaq">
              <div class="accordion-body"><?= e($faq['answer']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section text-center bg-ps-black">
  <div class="container">
    <h2 class="section-title mb-3">Ready to Start?</h2>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
      <a href="<?= url('/register') ?>" class="btn btn-ps btn-lg">Join Now</a>
      <a href="<?= url('/contact') ?>" class="btn btn-ps-outline btn-lg">Visit / Contact to Renew</a>
    </div>
  </div>
</section>

<?php $extraScripts = ['js/pricing-countdown.js']; ?>
