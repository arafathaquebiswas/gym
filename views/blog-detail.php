<?php
$pageTitle = $post['title'];
/** @var array $post */
/** @var array $recentPosts */
$cats = ['workout_tips' => 'Workout Tips', 'diet_tips' => 'Diet Tips', 'fitness_news' => 'Fitness News', 'announcements' => 'Announcements'];
?>

<section class="section">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-8">
        <span class="badge-cat badge mb-3"><?= e($cats[$post['category']] ?? $post['category']) ?></span>
        <h1><?= e($post['title']) ?></h1>
        <p class="text-white-50"><?= format_date($post['published_at']) ?> &middot; <?= (int) $post['views'] ?> views</p>
        <div class="img-placeholder my-4" style="min-height:320px"><i class="bi bi-journal-text"></i></div>
        <div class="text-white-50 fs-6" style="line-height:1.9"><?= $post['content'] /* trusted admin-authored HTML */ ?></div>
      </div>
      <div class="col-lg-4">
        <div class="glass-card p-4">
          <h6 class="mb-3">More Articles</h6>
          <?php foreach ($recentPosts as $rp): if ($rp['id'] === $post['id']) continue; ?>
            <a href="<?= url('/blog/' . $rp['slug']) ?>" class="d-block text-white mb-3 text-decoration-none">
              <strong class="d-block"><?= e($rp['title']) ?></strong>
              <small class="text-white-50"><?= format_date($rp['published_at']) ?></small>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
