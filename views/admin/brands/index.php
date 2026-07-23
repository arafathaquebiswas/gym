<?php
/** @var array $brands */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Brands</h6>
    <div class="d-flex gap-2">
      <a href="<?= url('/admin/products') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Products</a>
      <button type="button" class="btn btn-ps btn-sm" data-bs-toggle="modal" data-bs-target="#addBrandModal"><i class="bi bi-plus-lg"></i> Add Brand</button>
    </div>
  </div>

  <?php if (empty($brands)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No brands yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Logo</th><th>Name</th><th>Slug</th><th>Products</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($brands as $brand): ?>
        <tr>
          <td><?= media_tile($brand['logo'], $brand['name'], 'bi-tag', 'thumb') ?></td>
          <td><?= e($brand['name']) ?></td>
          <td><code><?= e($brand['slug']) ?></code></td>
          <td><?= (int) $brand['product_count'] ?></td>
          <td>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-ps-outline btn-sm" data-bs-toggle="modal" data-bs-target="#editBrandModal<?= $brand['id'] ?>"><i class="bi bi-pencil"></i></button>
              <form method="post" action="<?= url('/admin/brands/' . $brand['id'] . '/delete') ?>" onsubmit="return confirm('Delete this brand? Only allowed if no products use it.');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>

            <div class="modal fade" id="editBrandModal<?= $brand['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark">
                  <form method="post" action="<?= url('/admin/brands/' . $brand['id']) ?>" enctype="multipart/form-data">
                    <?= Security::csrfField() ?>
                    <div class="modal-header"><h6 class="modal-title">Edit Brand</h6></div>
                    <div class="modal-body">
                      <label>Name</label>
                      <input type="text" name="name" class="form-control mb-2" value="<?= e($brand['name']) ?>" required>
                      <label>Description</label>
                      <textarea name="description" class="form-control mb-2" rows="2"><?= e($brand['description'] ?? '') ?></textarea>
                      <label>Logo</label>
                      <?php if (!empty($brand['logo'])): ?>
                        <div class="mb-2"><?= media_tile($brand['logo'], $brand['name'], 'bi-tag', '', null) ?></div>
                      <?php endif; ?>
                      <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp">
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
  <?php endif; ?>
</div>

<div class="modal fade" id="addBrandModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark">
      <form method="post" action="<?= url('/admin/brands') ?>" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <div class="modal-header"><h6 class="modal-title">Add Brand</h6></div>
        <div class="modal-body">
          <label>Name</label>
          <input type="text" name="name" class="form-control mb-2" required>
          <label>Description</label>
          <textarea name="description" class="form-control mb-2" rows="2"></textarea>
          <label>Logo</label>
          <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-ps btn-sm">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>
