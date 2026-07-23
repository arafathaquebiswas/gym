<?php
/** @var array $suppliers */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Suppliers</h6>
    <div class="d-flex gap-2">
      <a href="<?= url('/admin/products') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Products</a>
      <a href="<?= url('/admin/purchases') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-truck"></i> Purchases</a>
      <button type="button" class="btn btn-ps btn-sm" data-bs-toggle="modal" data-bs-target="#addSupplierModal"><i class="bi bi-plus-lg"></i> Add Supplier</button>
    </div>
  </div>

  <?php if (empty($suppliers)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No suppliers yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Contact Person</th><th>Phone</th><th>Email</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($suppliers as $supplier): ?>
        <tr>
          <td><?= e($supplier['name']) ?></td>
          <td><?= e($supplier['contact_person'] ?? '—') ?></td>
          <td><?= e($supplier['phone'] ?? '—') ?></td>
          <td><?= e($supplier['email'] ?? '—') ?></td>
          <td>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-ps-outline btn-sm" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?= $supplier['id'] ?>"><i class="bi bi-pencil"></i></button>
              <form method="post" action="<?= url('/admin/suppliers/' . $supplier['id'] . '/delete') ?>" onsubmit="return confirm('Delete this supplier? Only allowed if no products or purchases reference it.');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>

            <div class="modal fade" id="editSupplierModal<?= $supplier['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark">
                  <form method="post" action="<?= url('/admin/suppliers/' . $supplier['id']) ?>">
                    <?= Security::csrfField() ?>
                    <div class="modal-header"><h6 class="modal-title">Edit Supplier</h6></div>
                    <div class="modal-body">
                      <label>Name</label>
                      <input type="text" name="name" class="form-control mb-2" value="<?= e($supplier['name']) ?>" required>
                      <label>Contact Person</label>
                      <input type="text" name="contact_person" class="form-control mb-2" value="<?= e($supplier['contact_person'] ?? '') ?>">
                      <label>Phone</label>
                      <input type="text" name="phone" class="form-control mb-2" value="<?= e($supplier['phone'] ?? '') ?>">
                      <label>Email</label>
                      <input type="email" name="email" class="form-control mb-2" value="<?= e($supplier['email'] ?? '') ?>">
                      <label>Address</label>
                      <textarea name="address" class="form-control" rows="2"><?= e($supplier['address'] ?? '') ?></textarea>
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

<div class="modal fade" id="addSupplierModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark">
      <form method="post" action="<?= url('/admin/suppliers') ?>">
        <?= Security::csrfField() ?>
        <div class="modal-header"><h6 class="modal-title">Add Supplier</h6></div>
        <div class="modal-body">
          <label>Name</label>
          <input type="text" name="name" class="form-control mb-2" required>
          <label>Contact Person</label>
          <input type="text" name="contact_person" class="form-control mb-2">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control mb-2">
          <label>Email</label>
          <input type="email" name="email" class="form-control mb-2">
          <label>Address</label>
          <textarea name="address" class="form-control" rows="2"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-ps btn-sm">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>
