<?php
$pageTitle = 'Blog';
/** @var array $posts */
$cats = ['workout_tips' => 'Workout Tips', 'diet_tips' => 'Diet Tips', 'fitness_news' => 'Fitness News', 'announcements' => 'Announcements'];
?>

<section class="hero py-5 text-center">
  <div class="container">
    <span class="hero-badge">Blog</span>
    <h1>Workout Tips &amp; <span class="text-orange">Fitness News</span></h1>
    <p class="lead mx-auto" style="max-width:600px">Training advice, nutrition guidance, and updates from PowerSurge Gym.</p>
  </div>
</section>

<section class="section pt-0">
  <div class="container">
    <div class="d-flex gap-2 justify-content-center flex-wrap mb-5">
      <a href="<?= url('/blog') ?>" class="btn btn-sm <?= !$activeCategory ? 'btn-ps' : 'btn-ps-outline' ?>">All</a>
      <?php foreach ($cats as $slug => $label): ?>
        <a href="<?= url('/blog?category=' . $slug) ?>" class="btn btn-sm <?= $activeCategory === $slug ? 'btn-ps' : 'btn-ps-outline' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($posts)): ?>
      <div class="glass-card p-5 text-center text-white-50">No posts in this category yet.</div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($posts as $post): ?>
      <div class="col-md-6 col-lg-4">
        <a href="<?= url('/blog/' . $post['slug']) ?>" class="text-decoration-none">
          <div class="glass-card blog-card">
            <div class="img-placeholder blog-thumb"><i class="bi bi-journal-text"></i></div>
            <div class="blog-body">
              <span class="badge-cat badge"><?= e($cats[$post['category']] ?? $post['category']) ?></span>
              <h5 class="mt-3 text-white"><?= e($post['title']) ?></h5>
              <p class="text-white-50 small"><?= e($post['excerpt']) ?></p>
              <small class="text-white-50"><?= format_date($post['published_at']) ?></small>
            </div>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if ($totalPages > 1): ?>
    <nav class="mt-5">
      <ul class="pagination pagination-ps justify-content-center">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="<?= url('/blog?page=' . $p . ($activeCategory ? '&category=' . $activeCategory : '')) ?>"><?= $p ?></a>
          </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
