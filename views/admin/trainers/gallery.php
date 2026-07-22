<?php
/** @var array $trainer */
/** @var array $images */
?>
<div class="mb-3">
  <a href="<?= url('/admin/trainers/' . $trainer['id'] . '/edit') ?>" class="text-white-50 small"><i class="bi bi-arrow-left"></i> Back to <?= e($trainer['name']) ?></a>
</div>

<div class="admin-card mb-4">
  <h6 class="mb-3">Upload Photos</h6>
  <form method="post" action="<?= url('/admin/trainers/' . $trainer['id'] . '/gallery') ?>" enctype="multipart/form-data" class="admin-form d-flex gap-2 flex-wrap align-items-center">
    <?= Security::csrfField() ?>
    <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple style="max-width:400px">
    <button type="submit" class="btn btn-ps">Upload</button>
  </form>
  <p class="text-white-50 small mt-2 mb-0">JPG, PNG, or WEBP. You can select multiple files at once.</p>
</div>

<div class="admin-card">
  <h6 class="mb-3">Gallery (<?= count($images) ?>)</h6>
  <?php if (empty($images)): ?>
    <p class="text-white-50 mb-0">No gallery photos yet.</p>
  <?php else: ?>
  <div class="gallery-admin-grid">
    <?php foreach ($images as $image): ?>
    <div class="gallery-admin-item">
      <img src="<?= e(url($image['image_path'])) ?>" alt="">
      <form method="post" action="<?= url('/admin/trainers/' . $trainer['id'] . '/gallery/' . $image['id'] . '/delete') ?>" onsubmit="return confirm('Delete this photo?');">
        <?= Security::csrfField() ?>
        <button type="submit"><i class="bi bi-x-lg"></i></button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
