<?php
/** @var array $target */
/** @var array $modules module_key => label */
/** @var array $actions e.g. ['view','create','edit','delete','export','print'] */
/** @var array $existing module_key => row (from user_permissions) */
/** @var array|null $grantableModules null = unrestricted (Main Admin), else the acting Super Admin's own reachable module keys */
?>
<div class="mb-3">
  <a href="<?= url('/admin/roles') ?>" class="text-white-50 text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to Role Management</a>
</div>

<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Permissions — <?= e($target['name']) ?> <span class="text-white-50 small">(<?= e(ucfirst(str_replace('_', ' ', $target['role_slug']))) ?>)</span></h6>
  </div>
  <p class="text-white-50 small">
    Only checked modules appear in <?= e($target['name']) ?>'s sidebar. Unchecked modules are completely hidden — no menu item, no page, no direct URL access.
    <?php if ($grantableModules !== null): ?>
      <br>You can only grant modules you yourself have access to — modules you can't reach are disabled below.
    <?php endif; ?>
  </p>

  <form method="post" action="<?= url('/admin/roles/' . $target['id'] . '/permissions') ?>" class="admin-form">
    <?= Security::csrfField() ?>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Module</th>
            <?php foreach ($actions as $action): ?>
              <th class="text-capitalize"><?= e($action) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($modules as $moduleKey => $label): ?>
          <?php
            $row = $existing[$moduleKey] ?? null;
            $isGrantable = $grantableModules === null || in_array($moduleKey, $grantableModules, true);
          ?>
          <tr class="<?= !$isGrantable ? 'opacity-50' : '' ?>">
            <td><?= e($label) ?><?php if (!$isGrantable): ?> <span class="text-white-50 small">(you don't have access)</span><?php endif; ?></td>
            <?php foreach ($actions as $action): ?>
            <td>
              <input type="checkbox" class="form-check-input"
                name="permissions[<?= e($moduleKey) ?>][<?= e($action) ?>]" value="1"
                <?= !empty($row["can_$action"]) ? 'checked' : '' ?>
                <?= !$isGrantable ? 'disabled' : '' ?>>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <button type="submit" class="btn btn-ps btn-sm mt-2">Save Permissions</button>
  </form>
</div>
