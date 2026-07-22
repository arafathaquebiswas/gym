<?php $pageTitle = 'About Us'; /** @var array $trainers */ ?>

<section class="hero py-5">
  <div class="container text-center">
    <span class="hero-badge">About PowerSurge</span>
    <h1>Built for People Who <span class="text-orange">Show Up</span></h1>
    <p class="lead mx-auto" style="max-width:700px">PowerSurge Gym opened its doors with one goal: give every member — beginner or competitive athlete — the tools, coaching, and community to keep training hard for the long run.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6">
        <div style="min-height:340px;border-radius:var(--ps-radius);overflow:hidden">
          <img src="<?= asset('images/gyminterior/gyminterior3.jpg') ?>" alt="PowerSurge Gym training floor" class="photo-tile" style="min-height:340px">
        </div>
      </div>
      <div class="col-lg-6">
        <h2 class="section-title">Our Story</h2>
        <p class="text-white-50">PowerSurge Gym started as a single training floor and has grown into a full-service fitness center with strength equipment, group classes, personal training, and a nutrition-focused supplement store — all under one roof.</p>
        <p class="text-white-50">We believe fitness should be accessible, safe, and genuinely enjoyable. That's why every trainer on our floor is certified, every machine is maintained on a strict schedule, and every new member gets a real onboarding — not just a keycard.</p>
        <div class="row g-3 mt-2">
          <div class="col-6"><div class="glass-card p-3 text-center"><div class="stat-num text-orange fs-3 fw-bold">1200+</div><small class="text-white-50">Members</small></div></div>
          <div class="col-6"><div class="glass-card p-3 text-center"><div class="stat-num text-orange fs-3 fw-bold">8 yrs</div><small class="text-white-50">In Operation</small></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section bg-ps-black text-center">
  <div class="container">
    <h2 class="section-title">Our Mission &amp; Values</h2>
    <div class="row g-4 mt-2">
      <?php foreach ([
          ['bi-bullseye', 'Mission', 'Make sustainable strength and fitness achievable for everyone in our community.'],
          ['bi-heart', 'Integrity', 'Honest coaching, transparent pricing, and equipment you can trust.'],
          ['bi-people', 'Community', 'A floor where beginners and competitors train side by side, respectfully.'],
      ] as [$icon, $title, $desc]): ?>
      <div class="col-md-4">
        <div class="glass-card p-4 h-100">
          <i class="bi <?= $icon ?> text-orange fs-1"></i>
          <h5 class="mt-3"><?= e($title) ?></h5>
          <p class="text-white-50 small mb-0"><?= e($desc) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section text-center">
  <div class="container">
    <h2 class="section-title">Our Trainers</h2>
    <div class="row g-4">
      <?php foreach ($trainers as $trainer): ?>
      <div class="col-md-4">
        <?php $this->partial('partials/trainer-card', ['trainer' => $trainer]); ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
