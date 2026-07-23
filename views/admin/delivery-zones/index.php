<?php
/** @var array $zones */
?>
<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Delivery Zones</h6>
    <div class="d-flex gap-2">
      <a href="<?= url('/admin/settings') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Settings</a>
      <button type="button" class="btn btn-ps btn-sm" data-bs-toggle="modal" data-bs-target="#addZoneModal"><i class="bi bi-plus-lg"></i> Add Zone</button>
    </div>
  </div>
  <p class="text-white-50 small">Each zone's charge replaces the site-wide flat shipping rate for orders placed in that zone. If no zones are added here, checkout falls back to the flat rate configured in Settings.</p>

  <?php if (empty($zones)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No delivery zones yet — checkout is using the flat shipping rate.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Charge</th><th>Order</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($zones as $zone): ?>
        <tr>
          <td><?= e($zone['name']) ?></td>
          <td>৳<?= number_format((float) $zone['charge'], 2) ?></td>
          <td><?= (int) $zone['sort_order'] ?></td>
          <td>
            <form method="post" action="<?= url('/admin/delivery-zones/' . $zone['id']) ?>" class="d-inline">
              <?= Security::csrfField() ?>
              <input type="hidden" name="name" value="<?= e($zone['name']) ?>">
              <input type="hidden" name="charge" value="<?= e((string) $zone['charge']) ?>">
              <input type="hidden" name="sort_order" value="<?= (int) $zone['sort_order'] ?>">
              <input type="hidden" name="is_active" value="<?= $zone['is_active'] ? '0' : '1' ?>">
              <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent">
                <span class="badge text-bg-<?= $zone['is_active'] ? 'success' : 'secondary' ?>"><?= $zone['is_active'] ? 'Active' : 'Inactive' ?></span>
              </button>
            </form>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-ps-outline btn-sm" data-bs-toggle="modal" data-bs-target="#editZoneModal<?= $zone['id'] ?>"><i class="bi bi-pencil"></i></button>
              <form method="post" action="<?= url('/admin/delivery-zones/' . $zone['id'] . '/delete') ?>" onsubmit="return confirm('Delete this zone? Only allowed if no orders reference it.');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>

            <div class="modal fade" id="editZoneModal<?= $zone['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-dark">
                  <form method="post" action="<?= url('/admin/delivery-zones/' . $zone['id']) ?>">
                    <?= Security::csrfField() ?>
                    <div class="modal-header"><h6 class="modal-title">Edit Zone</h6></div>
                    <div class="modal-body">
                      <label>Name</label>
                      <input type="text" name="name" class="form-control mb-2" value="<?= e($zone['name']) ?>" required>
                      <label>Delivery Charge (৳)</label>
                      <input type="number" step="0.01" min="0" name="charge" class="form-control mb-2" value="<?= e((string) $zone['charge']) ?>" required>
                      <label>Display Order</label>
                      <input type="number" min="0" name="sort_order" class="form-control mb-2" value="<?= (int) $zone['sort_order'] ?>">
                      <div class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="zoneActive<?= $zone['id'] ?>" <?= $zone['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="zoneActive<?= $zone['id'] ?>">Active (selectable at checkout)</label>
                      </div>
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

<div class="modal fade" id="addZoneModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark">
      <form method="post" action="<?= url('/admin/delivery-zones') ?>">
        <?= Security::csrfField() ?>
        <div class="modal-header"><h6 class="modal-title">Add Delivery Zone</h6></div>
        <div class="modal-body">
          <label>Name</label>
          <input type="text" name="name" class="form-control mb-2" placeholder="e.g. Dhaka Metro" required>
          <label>Delivery Charge (৳)</label>
          <input type="number" step="0.01" min="0" name="charge" class="form-control mb-2" required>
          <label>Display Order</label>
          <input type="number" min="0" name="sort_order" class="form-control mb-2" value="0">
          <div class="form-check">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="newZoneActive" checked>
            <label class="form-check-label" for="newZoneActive">Active (selectable at checkout)</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-ps btn-sm">Add</button>
        </div>
      </form>
    </div>
  </div>
</div>
