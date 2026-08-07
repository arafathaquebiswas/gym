<?php
/** @var array|null $post */
/** @var array $categories */
$isEdit = $post !== null;
$action = $isEdit ? url('/admin/blog/' . (int) $post['id']) : url('/admin/blog');

// On a rejected submit the controller stashes what was typed, so nothing is retyped.
$v = static function (string $key, $fallback = '') use ($post) {
    $old = old($key);                       // already escaped, and consumed once
    if ($old !== '') {
        return $old;
    }
    return e((string) ($post[$key] ?? $fallback));
};
?>
<div class="admin-page-shell">
  <div class="admin-page-header">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= url('/admin/blog') ?>">Blog</a></li>
          <li class="breadcrumb-item active"><?= $isEdit ? 'Edit' : 'New' ?></li>
        </ol>
      </nav>
      <h1 class="admin-page-title"><?= $isEdit ? 'Edit Blog Post' : 'New Blog Post' ?></h1>
    </div>
    <div class="admin-page-actions">
      <a href="<?= url('/admin/blog') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Blog</a>
    </div>
  </div>

  <form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="admin-form">
    <?= Security::csrfField() ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="admin-card">
          <div class="mb-3">
            <label for="postTitle">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" id="postTitle" class="form-control" maxlength="200" value="<?= $v('title') ?>" required>
            <?php if ($isEdit): ?>
              <div class="form-text text-white-50">URL: /blog/<?= e($post['slug']) ?> — kept as-is when the title changes, so existing links keep working.</div>
            <?php endif; ?>
          </div>
          <div class="mb-3">
            <label for="postExcerpt">Excerpt <small class="text-white-50">(optional)</small></label>
            <input type="text" name="excerpt" id="postExcerpt" class="form-control" maxlength="255" value="<?= $v('excerpt') ?>" placeholder="Short summary shown on the blog listing">
          </div>
          <div class="mb-0">
            <label for="postContent">Content <span class="text-danger">*</span></label>
            <textarea name="content" id="postContent" class="form-control" rows="16" required><?= $v('content') ?></textarea>
            <div class="form-text text-white-50">Plain text or basic HTML. Line breaks are preserved on the public page.</div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="admin-card mb-4">
          <h6 class="mb-3">Publishing</h6>
          <div class="mb-3">
            <label for="postStatus">Status</label>
            <?php $status = $v('status', 'draft'); ?>
            <select name="status" id="postStatus" class="form-select">
              <option value="draft" <?= $status === 'published' ? '' : 'selected' ?>>Draft — not visible on the site</option>
              <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published — live on /blog</option>
            </select>
          </div>
          <div class="mb-0">
            <label for="postCategory">Category <span class="text-danger">*</span></label>
            <?php $selectedCategory = $v('category', 'announcements'); ?>
            <select name="category" id="postCategory" class="form-select" required>
              <?php foreach ($categories as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $selectedCategory === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="admin-card mb-4">
          <h6 class="mb-3">Featured Image <small class="text-white-50">(optional)</small></h6>
          <?php if ($isEdit && !empty($post['featured_image'])): ?>
            <div class="mb-2"><?= media_tile($post['featured_image'], $post['title'], 'bi-image', '', null) ?></div>
          <?php endif; ?>
          <input type="file" name="featured_image" class="form-control" accept="image/jpeg,image/png,image/webp">
          <div class="form-text text-white-50">
            JPG, PNG or WebP, up to <?= round(MAX_UPLOAD_SIZE / 1024 / 1024, 1) ?>MB.
            <?= $isEdit && !empty($post['featured_image']) ? 'Uploading a new image replaces the current one.' : '' ?>
          </div>
        </div>

        <div class="admin-card">
          <button type="submit" class="btn btn-ps w-100">
            <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Save Changes' : 'Create Post' ?>
          </button>
        </div>
      </div>
    </div>
  </form>
</div>
