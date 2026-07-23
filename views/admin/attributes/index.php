<?php
/** @var array $attributes */
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Product Attributes</h6>
    <div class="d-flex gap-2">
      <a href="<?= url('/admin/products') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Products</a>
      <button type="button" class="btn btn-ps btn-sm" data-bs-toggle="modal" data-bs-target="#addAttributeModal"><i class="bi bi-plus-lg"></i> Add Attribute</button>
    </div>
  </div>
  <p class="text-white-50 small mb-0">Create unlimited attributes (Size, Color, Flavor, Weight, Volume, Material — anything) and their possible values here, then assign them to a product from that product's edit page to build its variants.</p>
</div>

<?php if (empty($attributes)): ?>
  <div class="admin-card text-center py-4 text-white-50">No attributes yet — add your first one above.</div>
<?php else: ?>
<div class="row g-3">
  <?php foreach ($attributes as $attribute): ?>
  <div class="col-md-6">
    <div class="admin-card h-100">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0"><?= e($attribute['name']) ?></h6>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-ps-outline btn-sm" data-bs-toggle="modal" data-bs-target="#editAttributeModal<?= $attribute['id'] ?>"><i class="bi bi-pencil"></i></button>
          <form method="post" action="<?= url('/admin/attributes/' . $attribute['id'] . '/delete') ?>" onsubmit="return confirm('Delete this attribute? Only allowed if no product uses it.');">
            <?= Security::csrfField() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
          </form>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-2">
        <?php foreach ($attribute['values'] as $value): ?>
        <span class="badge text-bg-secondary d-flex align-items-center gap-1">
          <?= e($value['value']) ?>
          <button type="button" class="btn-close btn-close-white" style="font-size:.55rem" data-bs-toggle="modal" data-bs-target="#deleteValueModal<?= $value['id'] ?>"></button>
        </span>
        <div class="modal fade" id="deleteValueModal<?= $value['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content bg-dark">
              <form method="post" action="<?= url('/admin/attribute-values/' . $value['id'] . '/delete') ?>">
                <?= Security::csrfField() ?>
                <div class="modal-header"><h6 class="modal-title">Delete Value</h6></div>
                <div class="modal-body">Delete "<?= e($value['value']) ?>"? Only allowed if no variant uses it.</div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($attribute['values'])): ?>
          <span class="text-white-50 small">No values yet.</span>
        <?php endif; ?>
      </div>

      <form method="post" action="<?= url('/admin/attributes/' . $attribute['id'] . '/values') ?>" class="d-flex gap-2">
        <?= Security::csrfField() ?>
        <input type="text" name="value" class="form-control form-control-sm" placeholder="Add a value (e.g. Medium, Red, 1kg)" required>
        <button type="submit" class="btn btn-ps-outline btn-sm text-nowrap">Add</button>
      </form>
    </div>
  </div>

  <div class="modal fade" id="editAttributeModal<?= $attribute['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark">
        <form method="post" action="<?= url('/admin/attributes/' . $attribute['id']) ?>">
          <?= Security::csrfField() ?>
          <div class="modal-header"><h6 class="modal-title">Edit Attribute</h6></div>
          <div class="modal-body">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="<?= e($attribute['name']) ?>" required>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-ps btn-sm">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="modal fade" id="addAttributeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark">
      <form method="post" action="<?= url('/admin/attributes') ?>">
        <?= Security::csrfField() ?>
        <div class="modal-header"><h6 class="modal-title">Add Attribute</h6></div>
        <div class="modal-body">
          <label>Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Size, Color, Flavor, Weight" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-ps btn-sm">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>
