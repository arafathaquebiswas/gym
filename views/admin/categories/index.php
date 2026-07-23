<?php
/** @var array $categories */
$topLevel = array_filter($categories, fn ($c) => !$c['parent_id']);
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Product Categories</h6>
    <div class="d-flex gap-2">
      <a href="<?= url('/admin/products') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Products</a>
      <button type="button" class="btn btn-ps btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="bi bi-plus-lg"></i> Add Category</button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Parent</th><th>Slug</th><th>Products</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
        <tr>
          <td><?= $cat['parent_id'] ? '— ' : '' ?><?= e($cat['name']) ?></td>
          <td><?= e($cat['parent_name'] ?? '—') ?></td>
          <td><code><?= e($cat['slug']) ?></code></td>
          <td><?= (int) $cat['product_count'] ?></td>
          <td>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-ps-outline btn-sm" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= $cat['id'] ?>"><i class="bi bi-pencil"></i></button>
              <form method="post" action="<?= url('/admin/categories/' . $cat['id'] . '/delete') ?>" onsubmit="return confirm('Delete this category? Only allowed if no products use it.');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>

            <div class="modal fade" id="editCategoryModal<?= $cat['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark">
                  <form method="post" action="<?= url('/admin/categories/' . $cat['id']) ?>">
                    <?= Security::csrfField() ?>
                    <div class="modal-header"><h6 class="modal-title">Edit Category</h6></div>
                    <div class="modal-body">
                      <label>Name</label>
                      <input type="text" name="name" class="form-control mb-2" value="<?= e($cat['name']) ?>" required>
                      <label>Parent Category</label>
                      <select name="parent_id" class="form-select mb-2">
                        <option value="">— None (top-level) —</option>
                        <?php foreach ($topLevel as $parent): if ((int) $parent['id'] === (int) $cat['id']) continue; ?>
                          <option value="<?= (int) $parent['id'] ?>" <?= (int) ($cat['parent_id'] ?? 0) === (int) $parent['id'] ? 'selected' : '' ?>><?= e($parent['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <label>Description</label>
                      <input type="text" name="description" class="form-control mb-2" value="<?= e($cat['description'] ?? '') ?>">
                      <hr>
                      <h6 class="small text-white-50">Category Offer</h6>
                      <div class="row g-2">
                        <div class="col-6">
                          <label>Enabled</label>
                          <select name="offer_enabled" class="form-select form-select-sm">
                            <option value="0" <?= empty($cat['offer_enabled']) ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= !empty($cat['offer_enabled']) ? 'selected' : '' ?>>Yes</option>
                          </select>
                        </div>
                        <div class="col-6">
                          <label>Discount %</label>
                          <input type="number" step="0.01" min="0" max="100" name="offer_percent" class="form-control form-control-sm" value="<?= e((string) ($cat['offer_percent'] ?? '')) ?>">
                        </div>
                        <div class="col-6">
                          <label>Start Date</label>
                          <input type="date" name="offer_start_date" class="form-control form-control-sm" value="<?= e($cat['offer_start_date'] ?? '') ?>">
                        </div>
                        <div class="col-6">
                          <label>End Date</label>
                          <input type="date" name="offer_end_date" class="form-control form-control-sm" value="<?= e($cat['offer_end_date'] ?? '') ?>">
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-ps btn-sm">Save</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark">
      <form method="post" action="<?= url('/admin/categories') ?>">
        <?= Security::csrfField() ?>
        <div class="modal-header"><h6 class="modal-title">Add Category</h6></div>
        <div class="modal-body">
          <label>Name</label>
          <input type="text" name="name" class="form-control mb-2" required>
          <label>Parent Category</label>
          <select name="parent_id" class="form-select mb-2">
            <option value="">— None (top-level) —</option>
            <?php foreach ($topLevel as $parent): ?>
              <option value="<?= (int) $parent['id'] ?>"><?= e($parent['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <label>Description</label>
          <input type="text" name="description" class="form-control">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-ps btn-sm">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>
