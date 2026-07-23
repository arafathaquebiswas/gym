<?php
/** @var array|null $trainer */
/** @var array $weeklySchedule */
$isEdit = $trainer !== null;
$action = $isEdit ? url('/admin/trainers/' . $trainer['id']) : url('/admin/trainers');
$v = fn ($key, $default = '') => e((string) ($trainer[$key] ?? $default));
$checked = fn ($key) => !empty($trainer[$key]) ? 'checked' : '';
$dayLabels = TrainerSchedule::DAY_LABELS;
?>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="admin-form">
  <?= Security::csrfField() ?>

  <div class="admin-card mb-4">
    <div class="admin-form-section">
      <h6>Basic Information</h6>
      <div class="row g-3">
        <div class="col-md-6">
          <label>Full Name *</label>
          <input type="text" name="name" class="form-control" value="<?= $v('name') ?>" required>
        </div>
        <?php if ($isEdit): ?>
        <div class="col-md-6">
          <label>URL Slug</label>
          <input type="text" name="slug" class="form-control" value="<?= $v('slug') ?>">
        </div>
        <?php endif; ?>
        <div class="col-md-4">
          <label>Job Title</label>
          <input type="text" name="job_title" class="form-control" list="jobTitles" value="<?= $v('job_title') ?>" placeholder="e.g. Strength Coach">
          <datalist id="jobTitles">
            <option value="Fitness Coach"><option value="Strength Coach"><option value="Yoga Instructor">
            <option value="HIIT Coach"><option value="Bodybuilding Coach">
          </datalist>
        </div>
        <div class="col-md-4">
          <label>Gender</label>
          <select name="gender" class="form-select">
            <option value="">—</option>
            <option value="male" <?= ($trainer['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= ($trainer['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
            <option value="other" <?= ($trainer['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div class="col-md-4">
          <label>Specialization</label>
          <input type="text" name="specialization" class="form-control" value="<?= $v('specialization') ?>">
        </div>
        <div class="col-md-4">
          <label>Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= $v('phone') ?>">
        </div>
        <div class="col-md-4">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="<?= $v('email') ?>">
        </div>
        <div class="col-md-4">
          <label>Experience (years)</label>
          <input type="number" min="0" name="experience_years" class="form-control" value="<?= $v('experience_years', '0') ?>">
        </div>
        <div class="col-md-4">
          <label>Date of Birth</label>
          <input type="date" name="dob" class="form-control" value="<?= $v('dob') ?>">
        </div>
        <div class="col-md-4">
          <label>Joining Date</label>
          <input type="date" name="joining_date" class="form-control" value="<?= $v('joining_date') ?>">
        </div>
        <div class="col-12">
          <label>Biography / About</label>
          <textarea name="bio" class="form-control" rows="3"><?= $v('bio') ?></textarea>
        </div>
        <div class="col-md-4">
          <label>Certifications <small class="text-white-50">(comma-separated)</small></label>
          <input type="text" name="certifications" class="form-control" value="<?= $v('certifications') ?>">
        </div>
        <div class="col-md-4">
          <label>Achievements <small class="text-white-50">(comma-separated)</small></label>
          <input type="text" name="achievements" class="form-control" value="<?= $v('achievements') ?>">
        </div>
        <div class="col-md-4">
          <label>Languages Spoken <small class="text-white-50">(comma-separated)</small></label>
          <input type="text" name="languages_spoken" class="form-control" value="<?= $v('languages_spoken') ?>">
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Pricing &amp; Capacity</h6>
      <div class="row g-3">
        <div class="col-md-3">
          <label>Monthly PT Fee (৳)</label>
          <input type="number" step="0.01" min="0" name="monthly_pt_price" class="form-control" value="<?= $v('monthly_pt_price') ?>">
        </div>
        <div class="col-md-3">
          <label>Hourly Rate (৳, optional)</label>
          <input type="number" step="0.01" min="0" name="hourly_rate" class="form-control" value="<?= $v('hourly_rate') ?>">
        </div>
        <div class="col-md-3">
          <label>Maximum Members</label>
          <input type="number" min="0" name="max_members" class="form-control" value="<?= $v('max_members') ?>">
        </div>
      </div>
      <div class="row g-3 align-items-end mt-1">
        <div class="col-md-3">
          <label>Offer Price (৳)</label>
          <input type="number" step="0.01" min="0" name="offer_price" class="form-control" value="<?= $v('offer_price') ?>">
        </div>
        <div class="col-md-3">
          <label>Offer Start</label>
          <input type="date" name="offer_start_date" class="form-control" value="<?= $v('offer_start_date') ?>">
        </div>
        <div class="col-md-3">
          <label>Offer End</label>
          <input type="date" name="offer_end_date" class="form-control" value="<?= $v('offer_end_date') ?>">
        </div>
        <div class="col-md-3 form-check mb-2">
          <input type="checkbox" name="offer_enabled" value="1" class="form-check-input" id="offerEnabled" <?= $checked('offer_enabled') ?>>
          <label class="form-check-label" for="offerEnabled">Offer Enabled</label>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-3">
          <label>Availability Status</label>
          <select name="availability_status" class="form-select">
            <?php foreach (['available' => 'Available', 'busy' => 'Busy', 'on_leave' => 'On Leave', 'offline' => 'Offline'] as $val => $label): ?>
              <option value="<?= $val ?>" <?= ($trainer['availability_status'] ?? 'available') === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Photos</h6>
      <div class="row g-3">
        <div class="col-md-6">
          <label>Profile Photo</label>
          <?php if ($isEdit && $trainer['photo']): ?>
            <div class="mb-2"><img src="<?= e(str_starts_with($trainer['photo'], 'uploads/') ? url($trainer['photo']) : asset('images/' . $trainer['photo'])) ?>" style="width:90px;height:90px;object-fit:cover;border-radius:10px" alt=""></div>
          <?php endif; ?>
          <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
          <?php if ($isEdit && $trainer['photo']): ?>
            <form method="post" action="<?= url('/admin/trainers/' . $trainer['id'] . '/delete-photo') ?>" onsubmit="return confirm('Remove profile photo?');" class="mt-2">
              <?= Security::csrfField() ?>
              <button type="submit" class="btn btn-outline-danger btn-sm">Remove Current Photo</button>
            </form>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <label>Cover Photo (optional)</label>
          <?php if ($isEdit && $trainer['cover_photo']): ?>
            <div class="mb-2"><img src="<?= e(str_starts_with($trainer['cover_photo'], 'uploads/') ? url($trainer['cover_photo']) : asset('images/' . $trainer['cover_photo'])) ?>" style="width:160px;height:60px;object-fit:cover;border-radius:10px" alt=""></div>
          <?php endif; ?>
          <input type="file" name="cover_photo" class="form-control" accept="image/jpeg,image/png,image/webp">
          <?php if ($isEdit && $trainer['cover_photo']): ?>
            <form method="post" action="<?= url('/admin/trainers/' . $trainer['id'] . '/delete-cover') ?>" onsubmit="return confirm('Remove cover photo?');" class="mt-2">
              <?= Security::csrfField() ?>
              <button type="submit" class="btn btn-outline-danger btn-sm">Remove Current Cover</button>
            </form>
          <?php endif; ?>
        </div>
        <?php if ($isEdit): ?>
        <div class="col-12">
          <a href="<?= url('/admin/trainers/' . $trainer['id'] . '/gallery') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-images"></i> Manage Photo Gallery</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Social Links</h6>
      <div class="row g-3">
        <div class="col-md-4"><label>Facebook</label><input type="url" name="facebook_url" class="form-control" value="<?= $v('facebook_url') ?>"></div>
        <div class="col-md-4"><label>Instagram</label><input type="url" name="instagram_url" class="form-control" value="<?= $v('instagram_url') ?>"></div>
        <div class="col-md-4"><label>LinkedIn</label><input type="url" name="linkedin_url" class="form-control" value="<?= $v('linkedin_url') ?>"></div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Display Settings</h6>
      <div class="row g-3 align-items-center">
        <div class="col-md-3">
          <label>Display Order</label>
          <input type="number" name="display_order" class="form-control" value="<?= $v('display_order', '0') ?>">
        </div>
        <div class="col-md-3 form-check mt-4 pt-2">
          <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="isFeatured" <?= $checked('is_featured') ?>>
          <label class="form-check-label" for="isFeatured">Featured Trainer</label>
        </div>
        <div class="col-md-3 form-check mt-4 pt-2">
          <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" <?= $isEdit ? $checked('is_active') : 'checked' ?>>
          <label class="form-check-label" for="isActive">Visible on Website</label>
        </div>
      </div>
    </div>

    <?php if ($isEdit): ?>
    <div class="admin-form-section">
      <h6>Weekly Schedule</h6>
      <p class="text-white-50 small">Set working hours per day. Check "Day Off" to mark a day unavailable.</p>
      <?php foreach ($dayLabels as $dayNum => $label): $day = $weeklySchedule[$dayNum] ?? null; ?>
      <div class="schedule-editor-row">
        <strong><?= e($label) ?></strong>
        <input type="time" name="schedule[<?= $dayNum ?>][start]" class="form-control form-control-sm" value="<?= $day && $day['start_time'] ? substr($day['start_time'], 0, 5) : '' ?>">
        <input type="time" name="schedule[<?= $dayNum ?>][end]" class="form-control form-control-sm" value="<?= $day && $day['end_time'] ? substr($day['end_time'], 0, 5) : '' ?>">
        <div class="form-check">
          <input type="checkbox" name="schedule[<?= $dayNum ?>][is_off]" value="1" class="form-check-input" id="off<?= $dayNum ?>" <?= ($day && $day['is_off']) ? 'checked' : '' ?>>
          <label class="form-check-label small" for="off<?= $dayNum ?>">Day Off</label>
        </div>
        <span></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-2">
      <button type="submit" class="btn btn-ps"><?= $isEdit ? 'Save Changes' : 'Add Trainer' ?></button>
      <a href="<?= url('/admin/trainers') ?>" class="btn btn-ps-outline">Cancel</a>
    </div>
  </div>
</form>
