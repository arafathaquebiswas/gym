<?php
$pageTitle = 'Edit Profile';
/** @var array|null $member */
$user = Auth::user();
?>
<section class="section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Edit <span class="text-orange">Profile</span></h1>
      <a href="<?= url('/account') ?>" class="btn btn-ps-outline btn-sm">Back to Account</a>
    </div>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="glass-card p-4 mb-4">
          <h6 class="mb-3">Profile Details</h6>
          <form method="post" action="<?= url('/account/profile') ?>" enctype="multipart/form-data" class="form-ps row g-3">
            <?= Security::csrfField() ?>
            <div class="col-12 d-flex align-items-center gap-3">
              <div style="width:70px;height:70px;border-radius:50%;overflow:hidden">
                <?= media_tile($member['photo'] ?? null, $user['name'], 'bi-person') ?>
              </div>
              <div class="flex-grow-1">
                <label>Photo</label>
                <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
              </div>
            </div>
            <div class="col-md-6">
              <label>Full Name</label>
              <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label>Email</label>
              <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
            </div>
            <div class="col-md-6">
              <label>Phone</label>
              <input type="text" name="phone" class="form-control" value="<?= e($member['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label>Emergency Contact</label>
              <input type="text" name="emergency_contact" class="form-control" value="<?= e($member['emergency_contact'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label>Address</label>
              <input type="text" name="address" class="form-control" value="<?= e($member['address'] ?? '') ?>">
            </div>
            <div class="col-12">
              <h6 class="mt-2 mb-1">Notification Preferences</h6>
              <div class="form-check">
                <input type="checkbox" name="notify_email" value="1" class="form-check-input" id="notifyEmail" <?= empty($member) || !empty($member['notify_email']) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="notifyEmail">Email me about my orders and membership</label>
              </div>
              <div class="form-check">
                <input type="checkbox" name="notify_promotions" value="1" class="form-check-input" id="notifyPromotions" <?= empty($member) || !empty($member['notify_promotions']) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="notifyPromotions">Email me about promotions and offers</label>
              </div>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-ps">Save Changes</button>
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="glass-card p-4">
          <h6 class="mb-3">Change Password</h6>
          <form method="post" action="<?= url('/account/password') ?>" class="form-ps">
            <?= Security::csrfField() ?>
            <div class="mb-2">
              <label>Current Password</label>
              <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-2">
              <label>New Password</label>
              <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Confirm New Password</label>
              <input type="password" name="new_password_confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-ps-outline w-100">Change Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
