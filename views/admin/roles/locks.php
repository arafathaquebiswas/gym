<?php
/** @var array $modules module_key => label */
/** @var array $scopes list of scope slugs */
/** @var array $scopeLabels scope slug => label */
/** @var array $current module_key => current scope (only modules with an explicit row) */
?>
<div class="mb-3">
  <a href="<?= url('/admin/roles') ?>" class="text-white-50 text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to Role Management</a>
</div>

<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0"><i class="bi bi-lock"></i> Module Locks</h6>
  </div>
  <p class="text-white-50 small">
    Choose who can even see that a module exists. Anyone not in the allowed group gets no sidebar item and a 403 on direct URL — this is checked before that role's own individual permissions.
  </p>

  <form method="post" action="<?= url('/admin/roles/locks') ?>" class="admin-form">
    <?= Security::csrfField() ?>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Module</th>
            <th>Who can access it</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($modules as $moduleKey => $label): ?>
          <?php $scope = $current[$moduleKey] ?? 'everyone'; ?>
          <tr>
            <td><?= e($label) ?></td>
            <td>
              <select name="scope[<?= e($moduleKey) ?>]" class="form-select form-select-sm" style="max-width: 320px;">
                <?php foreach ($scopes as $scopeOption): ?>
                  <option value="<?= e($scopeOption) ?>" <?= $scope === $scopeOption ? 'selected' : '' ?>><?= e($scopeLabels[$scopeOption]) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <button type="submit" class="btn btn-ps btn-sm mt-2">Save Module Locks</button>
  </form>
</div>
