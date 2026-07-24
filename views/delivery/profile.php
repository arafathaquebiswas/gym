<?php
/** @var array $user */
?>
<div class="row g-4">
  <div class="col-md-6">
    <div class="admin-card">
      <h6 class="mb-3">My Profile</h6>
      <form method="post" action="<?= url('/delivery/profile') ?>" class="row g-3 admin-form">
        <?= Security::csrfField() ?>
        <div class="col-12">
          <label>Full Name *</label>
          <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
        </div>
        <div class="col-12">
          <label>Email</label>
          <input type="text" class="form-control" value="<?= e($user['email']) ?>" disabled>
          <small class="text-white-50">Contact an admin to change your login email.</small>
        </div>
        <div class="col-12">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-ps btn-sm">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <div class="col-md-6">
    <div class="admin-card">
      <h6 class="mb-3">Change Password</h6>
      <form method="post" action="<?= url('/delivery/password') ?>" class="row g-3 admin-form">
        <?= Security::csrfField() ?>
        <div class="col-12">
          <label>Current Password *</label>
          <input type="password" name="current_password" class="form-control" required>
        </div>
        <div class="col-12">
          <label>New Password *</label>
          <input type="password" name="new_password" class="form-control" minlength="8" required>
        </div>
        <div class="col-12">
          <label>Confirm New Password *</label>
          <input type="password" name="new_password_confirm" class="form-control" minlength="8" required>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-ps-outline btn-sm">Change Password</button>
        </div>
      </form>
    </div>
  </div>
</div>
