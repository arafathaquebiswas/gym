<?php
/** @var string $targetRole 'staff' or 'super_admin' */
/** @var array|null $member */
$isEdit = $member !== null;
$roleDashed = str_replace('_', '-', $targetRole);
$roleLabel = ucfirst(str_replace('_', ' ', $targetRole));
$action = $isEdit ? url('/admin/roles/' . $roleDashed . '/' . $member['id']) : url('/admin/roles/' . $roleDashed);
$v = fn ($key, $default = '') => e((string) ($member[$key] ?? $default));
?>
<form method="post" action="<?= $action ?>" class="admin-form">
  <?= Security::csrfField() ?>

  <div class="admin-card mb-4">
    <div class="admin-form-section">
      <h6><?= e($roleLabel) ?> Details</h6>
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
        <div class="col-md-6">
          <label>Login Password <?= $isEdit ? '' : '*' ?></label>
          <input type="password" name="password" class="form-control" <?= $isEdit ? 'placeholder="Leave blank to keep current password"' : 'required minlength="8"' ?>>
          <?php if ($isEdit): ?><small class="text-white-50">Fill this in to reset their password.</small><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mt-2">
      <button type="submit" class="btn btn-ps"><?= $isEdit ? 'Save Changes' : 'Add ' . e($roleLabel) ?></button>
      <?php if ($isEdit): ?>
        <a href="<?= url('/admin/roles/' . $member['id'] . '/permissions') ?>" class="btn btn-ps-outline"><i class="bi bi-key"></i> Assign Permissions</a>
      <?php endif; ?>
      <a href="<?= url('/admin/roles') ?>" class="btn btn-ps-outline">Cancel</a>
    </div>
  </div>
</form>
