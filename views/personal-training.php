<?php $pageTitle = 'Personal Training'; /** @var array $trainers */ /** @var array $teamPhotos */ ?>

<section class="hero py-5 text-center">
  <div class="container">
    <span class="hero-badge">Personal Training</span>
    <h1>Train <span class="text-orange">1-on-1</span> With Our Coaches</h1>
    <p class="lead mx-auto" style="max-width:650px">Get a program built for your body, your schedule, and your goals — guided by certified coaches every step of the way.</p>
  </div>
</section>

<section class="section">
  <div class="container text-center">
    <div class="row g-4">
      <?php foreach ([
          ['bi-clipboard-data', 'Personalized Program', 'A training plan mapped to your fitness level and goals.'],
          ['bi-egg-fried', 'Nutrition Guidance', 'Simple, sustainable diet direction to match your training.'],
          ['bi-graph-up-arrow', 'Progress Reviews', 'Regular check-ins to track strength, weight, and consistency.'],
      ] as [$icon, $title, $desc]): ?>
      <div class="col-md-4">
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

<section class="section bg-ps-black text-center">
  <div class="container">
    <h2 class="section-title">Our Personal Trainers</h2>
    <div class="row g-4">
      <?php foreach ($trainers as $trainer): ?>
      <div class="col-md-4">
        <?php $this->partial('partials/trainer-card', ['trainer' => $trainer]); ?>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="text-white-50 small mt-4">Click "Book Trainer" on any profile above to see their weekly schedule and reserve a session, or <a href="<?= url('/contact') ?>">contact us</a> with questions.</p>
  </div>
</section>

<?php if (!empty($teamPhotos)): ?>
<section class="section">
  <div class="container text-center">
    <h2 class="section-title">Our Team &amp; Community</h2>
    <p class="section-subtitle mx-auto">Life on the PowerSurge floor — the people who train here every day.</p>
    <div class="row g-3">
      <?php foreach ($teamPhotos as $item): ?>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="gallery-item">
          <?= media_tile($item['image_path'], $item['title'] ?? 'Team photo') ?>
          <div class="gallery-caption"><?= e($item['title'] ?? '') ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
