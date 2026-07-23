<?php
$pageTitle = 'Saved Addresses';
/** @var array $addresses */
?>
<section class="section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Saved <span class="text-orange">Addresses</span></h1>
      <div class="d-flex gap-2">
        <a href="<?= url('/account') ?>" class="btn btn-ps-outline btn-sm">Back to Account</a>
        <button type="button" class="btn btn-ps btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal"><i class="bi bi-plus-lg"></i> Add Address</button>
      </div>
    </div>

    <?php if (empty($addresses)): ?>
      <div class="glass-card p-5 text-center text-white-50">No saved addresses yet.</div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($addresses as $addr): ?>
      <div class="col-md-6">
        <div class="glass-card p-4">
          <div class="d-flex justify-content-between">
            <strong><?= e($addr['label']) ?></strong>
            <?php if ($addr['is_default']): ?><span class="badge-ps badge">Default</span><?php endif; ?>
          </div>
          <p class="text-white-50 small mb-2 mt-2">
            <?= e($addr['full_name']) ?> · <?= e($addr['phone']) ?><br>
            <?= e($addr['address']) ?><br>
            <?= e($addr['city']) ?><?= $addr['area'] ? ', ' . e($addr['area']) : '' ?><?= $addr['postal_code'] ? ' - ' . e($addr['postal_code']) : '' ?>
          </p>
          <div class="d-flex gap-2">
            <?php if (!$addr['is_default']): ?>
            <form method="post" action="<?= url('/account/addresses/' . $addr['id'] . '/default') ?>">
              <?= Security::csrfField() ?>
              <button type="submit" class="btn btn-ps-outline btn-sm">Set Default</button>
            </form>
            <?php endif; ?>
            <form method="post" action="<?= url('/account/addresses/' . $addr['id'] . '/delete') ?>" onsubmit="return confirm('Delete this address?');">
              <?= Security::csrfField() ?>
              <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<div class="modal fade" id="addAddressModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark">
      <form method="post" action="<?= url('/account/addresses') ?>">
        <?= Security::csrfField() ?>
        <div class="modal-header"><h6 class="modal-title">Add Address</h6></div>
        <div class="modal-body">
          <label>Label</label>
          <input type="text" name="label" class="form-control mb-2" placeholder="Home, Work, etc." value="Home">
          <label>Full Name</label>
          <input type="text" name="full_name" class="form-control mb-2" required>
          <label>Phone</label>
          <input type="text" name="phone" class="form-control mb-2" required>
          <label>Address</label>
          <input type="text" name="address" class="form-control mb-2" required>
          <div class="row g-2">
            <div class="col-4"><label>City</label><input type="text" name="city" class="form-control mb-2" required></div>
            <div class="col-4"><label>Area</label><input type="text" name="area" class="form-control mb-2"></div>
            <div class="col-4"><label>Postal Code</label><input type="text" name="postal_code" class="form-control mb-2"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-ps btn-sm">Save Address</button>
        </div>
      </form>
    </div>
  </div>
</div>
