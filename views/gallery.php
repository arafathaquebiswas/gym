<?php
$pageTitle = 'Gallery';
/** @var array $items */
/** @var string|null $activeCategory */
$cats = ['gym' => 'Gym', 'events' => 'Events', 'competitions' => 'Competitions', 'transformation' => 'Transformation', 'team' => 'Team & Community'];
?>

<section class="hero py-5 text-center">
  <div class="container">
    <span class="hero-badge">Gallery</span>
    <h1>Life Inside <span class="text-orange">PowerSurge</span></h1>
    <p class="lead mx-auto" style="max-width:600px">Gym floor, events, competitions, and member transformations.</p>
  </div>
</section>

<section class="section pt-0">
  <div class="container">
    <div class="d-flex gap-2 justify-content-center flex-wrap mb-5">
      <a href="<?= url('/gallery') ?>" class="btn btn-sm <?= !$activeCategory ? 'btn-ps' : 'btn-ps-outline' ?>">All</a>
      <?php foreach ($cats as $slug => $label): ?>
        <a href="<?= url('/gallery?category=' . $slug) ?>" class="btn btn-sm <?= $activeCategory === $slug ? 'btn-ps' : 'btn-ps-outline' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($items)): ?>
      <div class="glass-card p-5 text-center text-white-50">No images in this category yet.</div>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($items as $item): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <div class="gallery-item">
          <?= media_tile($item['image_path'], $item['title'] ?? ucfirst($item['category'])) ?>
          <div class="gallery-caption"><?= e($item['title'] ?? ucfirst($item['category'])) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
