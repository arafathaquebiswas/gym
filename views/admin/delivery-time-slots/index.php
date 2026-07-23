<?php
/** @var array $deliverySlots */
/** @var array $pickupSlots */

$renderTable = function (array $slots, string $type) {
    ?>
    <div class="table-responsive">
      <table class="admin-table">
        <thead><tr><th>Label</th><th>Order</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($slots as $slot): ?>
          <tr>
            <td><?= e($slot['label']) ?></td>
            <td><?= (int) $slot['sort_order'] ?></td>
            <td><span class="badge text-bg-<?= $slot['is_active'] ? 'success' : 'secondary' ?>"><?= $slot['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-ps-outline btn-sm" data-bs-toggle="modal" data-bs-target="#editSlotModal<?= $slot['id'] ?>"><i class="bi bi-pencil"></i></button>
                <form method="post" action="<?= url('/admin/delivery-time-slots/' . $slot['id'] . '/delete') ?>" onsubmit="return confirm('Delete this time slot?');">
                  <?= Security::csrfField() ?>
                  <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                </form>
              </div>

              <div class="modal fade" id="editSlotModal<?= $slot['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content bg-dark">
                    <form method="post" action="<?= url('/admin/delivery-time-slots/' . $slot['id']) ?>">
                      <?= Security::csrfField() ?>
                      <div class="modal-header"><h6 class="modal-title">Edit Time Slot</h6></div>
                      <div class="modal-body">
                        <label>Label</label>
                        <input type="text" name="label" class="form-control mb-2" value="<?= e($slot['label']) ?>" required>
                        <label>Display Order</label>
                        <input type="number" min="0" name="sort_order" class="form-control mb-2" value="<?= (int) $slot['sort_order'] ?>">
                        <div class="form-check">
                          <input type="checkbox" name="is_active" value="1" class="form-check-input" id="slotActive<?= $slot['id'] ?>" <?= $slot['is_active'] ? 'checked' : '' ?>>
                          <label class="form-check-label" for="slotActive<?= $slot['id'] ?>">Active (selectable at checkout)</label>
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
          <?php if (empty($slots)): ?>
          <tr><td colspan="4" class="text-white-50 text-center py-3">No <?= $type ?> time slots yet — this step is skipped at checkout until at least one is added.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
};
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Delivery Time Slots</h6>
    <div class="d-flex gap-2">
      <a href="<?= url('/admin/settings') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Settings</a>
      <button type="button" class="btn btn-ps btn-sm" data-bs-toggle="modal" data-bs-target="#addSlotModal-delivery"><i class="bi bi-plus-lg"></i> Add Delivery Slot</button>
    </div>
  </div>
  <?= $renderTable($deliverySlots, 'delivery') ?>
</div>

<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Store Pickup Time Slots</h6>
    <button type="button" class="btn btn-ps btn-sm" data-bs-toggle="modal" data-bs-target="#addSlotModal-pickup"><i class="bi bi-plus-lg"></i> Add Pickup Slot</button>
  </div>
  <?= $renderTable($pickupSlots, 'pickup') ?>
</div>

<?php foreach (['delivery' => 'Delivery', 'pickup' => 'Pickup'] as $type => $typeLabel): ?>
<div class="modal fade" id="addSlotModal-<?= $type ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark">
      <form method="post" action="<?= url('/admin/delivery-time-slots') ?>">
        <?= Security::csrfField() ?>
        <input type="hidden" name="type" value="<?= $type ?>">
        <div class="modal-header"><h6 class="modal-title">Add <?= $typeLabel ?> Time Slot</h6></div>
        <div class="modal-body">
          <label>Label</label>
          <input type="text" name="label" class="form-control mb-2" placeholder="e.g. 10:00 AM - 1:00 PM" required>
          <label>Display Order</label>
          <input type="number" min="0" name="sort_order" class="form-control mb-2" value="0">
          <div class="form-check">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="newSlotActive-<?= $type ?>" checked>
            <label class="form-check-label" for="newSlotActive-<?= $type ?>">Active (selectable at checkout)</label>
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
<?php endforeach; ?>
