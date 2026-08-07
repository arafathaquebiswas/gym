<div class="admin-page-shell">
  <div class="admin-page-header">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Blog</li>
        </ol>
      </nav>
      <h1 class="admin-page-title">Blog</h1>
    </div>
    <?php if (Permission::can('blog', 'create')): ?>
    <div class="admin-page-actions">
      <a href="<?= url('/admin/blog/create') ?>" class="btn btn-ps btn-sm"><i class="bi bi-plus-lg"></i> New Post</a>
    </div>
    <?php endif; ?>
  </div>
<?php
/** @var array $posts */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
/** @var array $categories */
$statusColors = ['draft' => 'secondary', 'published' => 'success'];
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Posts (<?= (int) $total ?>)</h6>
  </div>

  <form method="get" action="<?= url('/admin/blog') ?>" class="admin-toolbar admin-form">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search title or excerpt" value="<?= e($filters['search']) ?>">
    <select name="status" class="form-select form-select-sm">
      <option value="">All Statuses</option>
      <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
      <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
    </select>
    <select name="category" class="form-select form-select-sm">
      <option value="">All Categories</option>
      <?php foreach ($categories as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $filters['category'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-ps-outline btn-sm">Filter</button>
    <?php if ($filters['search'] || $filters['status'] || $filters['category']): ?>
      <a href="<?= url('/admin/blog') ?>" class="btn btn-link btn-sm text-white-50">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (!$posts): ?>
    <p class="text-white-50 mb-0">
      No blog posts yet.
      <?php if (Permission::can('blog', 'create')): ?>
        <a href="<?= url('/admin/blog/create') ?>">Write the first one</a>.
      <?php endif; ?>
    </p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table align-middle">
      <thead>
        <tr><th>Title</th><th>Category</th><th>Status</th><th>Author</th><th>Published</th><th class="text-end">Views</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $post): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($post['title']) ?></div>
            <div class="small text-white-50"><?= e($post['slug']) ?></div>
          </td>
          <td class="small"><?= e($categories[$post['category']] ?? $post['category']) ?></td>
          <td><span class="badge text-bg-<?= $statusColors[$post['status']] ?? 'secondary' ?>"><?= e(ucfirst($post['status'])) ?></span></td>
          <td class="small"><?= e($post['author_name'] ?? '—') ?></td>
          <td class="small text-nowrap"><?= $post['published_at'] ? format_date($post['published_at']) : '—' ?></td>
          <td class="text-end small"><?= (int) $post['views'] ?></td>
          <td class="text-end text-nowrap">
            <?php if ($post['status'] === 'published'): ?>
              <a href="<?= url('/blog/' . $post['slug']) ?>" target="_blank" rel="noopener" class="btn btn-ps-outline btn-sm" title="View on site"><i class="bi bi-box-arrow-up-right"></i></a>
            <?php endif; ?>
            <?php if (Permission::can('blog', 'edit')): ?>
              <a href="<?= url('/admin/blog/' . (int) $post['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= url('/admin/blog/' . (int) $post['id'] . '/toggle-status') ?>" class="d-inline">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-ps-outline btn-sm" title="<?= $post['status'] === 'published' ? 'Move to draft' : 'Publish' ?>">
                  <i class="bi bi-<?= $post['status'] === 'published' ? 'eye-slash' : 'send' ?>"></i>
                </button>
              </form>
            <?php endif; ?>
            <?php if (Permission::can('blog', 'delete')): ?>
              <form method="post" action="<?= url('/admin/blog/' . (int) $post['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Delete this post permanently?');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <nav class="mt-3"><ul class="pagination pagination-sm mb-0">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <li class="page-item <?= $p === $page ? 'active' : '' ?>">
        <a class="page-link" href="<?= url('/admin/blog?page=' . $p
            . '&search=' . urlencode($filters['search'])
            . '&status=' . urlencode($filters['status'])
            . '&category=' . urlencode($filters['category'])) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul></nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
</div>
