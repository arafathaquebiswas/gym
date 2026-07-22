<?php
/** @var int $trainerCount */
/** @var int $activeTrainerCount */
/** @var int $featuredTrainerCount */
?>
<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="admin-card">
      <div class="text-white-50 small">Total Trainers</div>
      <div class="fs-2 fw-bold text-orange"><?= (int) $trainerCount ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card">
      <div class="text-white-50 small">Active on Website</div>
      <div class="fs-2 fw-bold text-orange"><?= (int) $activeTrainerCount ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card">
      <div class="text-white-50 small">Featured</div>
      <div class="fs-2 fw-bold text-orange"><?= (int) $featuredTrainerCount ?></div>
    </div>
  </div>
</div>

<div class="admin-card">
  <h6 class="mb-3">Quick Actions</h6>
  <a href="<?= url('/admin/trainers') ?>" class="btn btn-ps me-2"><i class="bi bi-person-badge"></i> Manage Trainers</a>
  <a href="<?= url('/admin/trainers/create') ?>" class="btn btn-ps-outline"><i class="bi bi-plus-lg"></i> Add New Trainer</a>
  <p class="text-white-50 small mt-4 mb-0">Member management, Store/POS, Reports, and Settings are coming in later phases of the admin panel.</p>
</div>
