<?php
/** @var array|null $member */
$isEdit = $member !== null;
$action = $isEdit ? url('/admin/delivery-staff/' . $member['id']) : url('/admin/delivery-staff');
$v = fn ($key, $default = '') => e((string) ($member[$key] ?? $default));
?>
<form method="post" action="<?= $action ?>" class="admin-form">
  <?= Security::csrfField() ?>

  <div class="admin-card mb-4">
    <div class="admin-form-section">
      <h6>Delivery Staff Details</h6>
      <div class="row g-3">
        <div class="col-md-4">
          <label>Full Name *</label>
          <input type="text" name="name" class="form-control" value="<?= $v('name') ?>" required>
        </div>
        <div class="col-md-4">
          <label>Email *</label>
          <input type="email" name="email" class="form-control" value="<?= $v('email') ?>" required>
        </div>
        <div class="col-md-4">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= $v('phone') ?>">
        </div>
        <div class="col-md-4">
          <label>Login Password <?= $isEdit ? '' : '*' ?></label>
          <input type="password" name="password" class="form-control" <?= $isEdit ? 'placeholder="Leave blank to keep current password"' : 'required minlength="8"' ?>>
        </div>
        <?php if ($isEdit): ?>
        <div class="col-md-4">
          <label>Status</label>
          <select name="status" class="form-select">
            <option value="active" <?= ($member['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($member['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
          </select>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-flex gap-2 mt-2">
      <button type="submit" class="btn btn-ps"><?= $isEdit ? 'Save Changes' : 'Add Delivery Staff' ?></button>
      <a href="<?= url('/admin/delivery-staff') ?>" class="btn btn-ps-outline">Cancel</a>
    </div>
  </div>
</form>
