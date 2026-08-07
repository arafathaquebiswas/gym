<div class="admin-page-shell">
  <div class="admin-page-header">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Permissions</li>
        </ol>
      </nav>
      <h1 class="admin-page-title">Permissions</h1>
    </div>
    <div class="admin-page-actions">
      <a href="<?= url('/admin/roles') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
  </div>
<?php
/** @var array $target */
/** @var array $modules module_key => label */
/** @var array $actions e.g. ['view','create','edit','delete','export','print','approve'] */
/** @var array $existing module_key => row (from user_permissions) */
/** @var array|null $grantableModules null = unrestricted (Main Admin), else the acting Super Admin's own reachable module keys */

// What each action actually lets someone do — surfaced as tooltips, since "print" and
// "export" in particular are not self-evident from the column header alone.
$actionHints = [
    'view' => 'Open the module and read its records',
    'create' => 'Add new records',
    'edit' => 'Change existing records',
    'delete' => 'Remove records permanently',
    'export' => 'Download records as CSV/Excel',
    'print' => 'Print receipts, invoices and labels',
    'approve' => 'Approve items awaiting review',
];

// Rows the acting admin may actually change. Everything below counts against this,
// never against the full list — a disabled row must not hold a "select all" hostage.
$grantableCount = 0;
foreach ($modules as $moduleKey => $label) {
    if ($grantableModules === null || in_array($moduleKey, $grantableModules, true)) {
        $grantableCount++;
    }
}
?>

<style>
  /* Sticky header and first column. Both need an opaque background of their own —
     sticky cells scroll over the rows beneath them and would otherwise show through. */
  .perm-scroll { max-height: 68vh; overflow: auto; }
  .perm-table { margin: 0; }
  .perm-table thead th {
    position: sticky; top: 0; z-index: 3;
    background: var(--ps-surface); white-space: nowrap;
  }
  .perm-table th.perm-module-col,
  .perm-table td.perm-module-col {
    position: sticky; left: 0; z-index: 2;
    background: var(--ps-surface); min-width: 210px;
  }
  .perm-table thead th.perm-module-col { z-index: 4; }
  .perm-table td { transition: background .12s ease; }
  .perm-table tbody tr:hover td { background: rgba(255, 255, 255, .04); }
  .perm-table tbody tr:hover td.perm-module-col { background: rgba(255, 255, 255, .07); }
  .perm-cell-wrap { display: flex; justify-content: center; }
  .perm-table td:not(.perm-module-col), .perm-table th:not(.perm-module-col) { text-align: center; }
  .perm-table .form-check-input { cursor: pointer; margin: 0; }
  .perm-table .form-check-input:disabled { cursor: not-allowed; }
  .perm-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    gap: .75rem; flex-wrap: wrap; margin-bottom: 1rem;
  }
  @media (max-width: 767.98px) {
    .perm-table th.perm-module-col, .perm-table td.perm-module-col { min-width: 150px; font-size: .8rem; }
    .perm-scroll { max-height: 60vh; }
  }
</style>

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

  <form method="post" action="<?= url('/admin/roles/' . $target['id'] . '/permissions') ?>" class="admin-form" id="permForm">
    <?= Security::csrfField() ?>

    <div class="perm-toolbar">
      <div class="form-check mb-0">
        <input type="checkbox" class="form-check-input" id="permSelectAll" <?= $grantableCount === 0 ? 'disabled' : '' ?>>
        <label class="form-check-label fw-semibold" for="permSelectAll">Select All Permissions</label>
      </div>
      <span class="text-white-50 small" id="permCount" aria-live="polite"></span>
    </div>

    <div class="perm-scroll table-responsive">
      <table class="admin-table perm-table" id="permTable">
        <thead>
          <tr>
            <th class="perm-module-col">Module</th>
            <?php foreach ($actions as $action): ?>
              <th>
                <div class="d-flex flex-column align-items-center gap-1">
                  <input type="checkbox" class="form-check-input perm-column-toggle"
                         data-action="<?= e($action) ?>"
                         id="permCol_<?= e($action) ?>"
                         title="Select <?= e($action) ?> for every module"
                         aria-label="Select <?= e($action) ?> for every module"
                         <?= $grantableCount === 0 ? 'disabled' : '' ?>>
                  <label class="text-capitalize mb-0" for="permCol_<?= e($action) ?>"
                         title="<?= e($actionHints[$action] ?? $action) ?>"
                         style="cursor:pointer"><?= e($action) ?></label>
                </div>
              </th>
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
            <td class="perm-module-col">
              <div class="form-check mb-0">
                <input type="checkbox" class="form-check-input perm-module-toggle"
                       data-module="<?= e($moduleKey) ?>"
                       id="permMod_<?= e($moduleKey) ?>"
                       title="Select every permission for <?= e($label) ?>"
                       aria-label="Select every permission for <?= e($label) ?>"
                       <?= !$isGrantable ? 'disabled' : '' ?>>
                <label class="form-check-label" for="permMod_<?= e($moduleKey) ?>"><?= e($label) ?></label>
              </div>
              <?php if (!$isGrantable): ?>
                <span class="text-white-50 small d-block">(you don't have access)</span>
              <?php endif; ?>
            </td>
            <?php foreach ($actions as $action): ?>
            <td>
              <div class="perm-cell-wrap">
                <input type="checkbox" class="form-check-input perm-cell"
                  data-module="<?= e($moduleKey) ?>" data-action="<?= e($action) ?>"
                  name="permissions[<?= e($moduleKey) ?>][<?= e($action) ?>]" value="1"
                  aria-label="<?= e($label) ?> — <?= e($action) ?>"
                  <?= !empty($row["can_$action"]) ? 'checked' : '' ?>
                  <?= !$isGrantable ? 'disabled' : '' ?>>
              </div>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <button type="submit" class="btn btn-ps btn-sm mt-3">Save Permissions</button>
  </form>
</div>
</div>

<script>
(function () {
  var table = document.getElementById('permTable');
  var form = document.getElementById('permForm');
  if (!table || !form) return;

  var globalBox = document.getElementById('permSelectAll');
  var counter = document.getElementById('permCount');

  // Indexes are built once. Every later operation reads these arrays instead of
  // re-querying the DOM, so a 100-module grid costs the same per click as a small one.
  // Disabled cells are excluded outright: they cannot be submitted, so counting them
  // would leave a "select all" permanently indeterminate and unreachable.
  var allCells = [];
  var byModule = Object.create(null);
  var byAction = Object.create(null);

  Array.prototype.forEach.call(table.querySelectorAll('.perm-cell'), function (cell) {
    if (cell.disabled) return;
    allCells.push(cell);
    (byModule[cell.dataset.module] || (byModule[cell.dataset.module] = [])).push(cell);
    (byAction[cell.dataset.action] || (byAction[cell.dataset.action] = [])).push(cell);
  });

  var moduleToggles = {};
  Array.prototype.forEach.call(table.querySelectorAll('.perm-module-toggle'), function (box) {
    moduleToggles[box.dataset.module] = box;
  });
  var columnToggles = {};
  Array.prototype.forEach.call(document.querySelectorAll('.perm-column-toggle'), function (box) {
    columnToggles[box.dataset.action] = box;
  });

  function reflect(box, cells) {
    if (!box) return;
    if (!cells || cells.length === 0) {
      box.checked = false;
      box.indeterminate = false;
      box.disabled = true;
      return;
    }
    var checked = 0;
    for (var i = 0; i < cells.length; i++) {
      if (cells[i].checked) checked++;
    }
    box.checked = checked === cells.length;
    box.indeterminate = checked > 0 && checked < cells.length;
  }

  function updateCounter() {
    if (!counter) return;
    var checked = 0;
    for (var i = 0; i < allCells.length; i++) {
      if (allCells[i].checked) checked++;
    }
    counter.textContent = checked + ' of ' + allCells.length + ' permissions selected';
  }

  function reflectModules() {
    for (var key in moduleToggles) reflect(moduleToggles[key], byModule[key]);
  }
  function reflectColumns() {
    for (var key in columnToggles) reflect(columnToggles[key], byAction[key]);
  }

  function setAll(cells, checked) {
    for (var i = 0; i < cells.length; i++) cells[i].checked = checked;
  }

  // One delegated listener for the whole grid rather than one per checkbox — with
  // seven actions across many modules that is hundreds of listeners avoided.
  form.addEventListener('change', function (event) {
    var el = event.target;
    if (!el || el.type !== 'checkbox') return;

    if (el === globalBox) {
      setAll(allCells, el.checked);
      el.indeterminate = false;
      reflectModules();
      reflectColumns();
    } else if (el.classList.contains('perm-module-toggle')) {
      setAll(byModule[el.dataset.module] || [], el.checked);
      el.indeterminate = false;
      reflectColumns();
      reflect(globalBox, allCells);
    } else if (el.classList.contains('perm-column-toggle')) {
      setAll(byAction[el.dataset.action] || [], el.checked);
      el.indeterminate = false;
      reflectModules();
      reflect(globalBox, allCells);
    } else if (el.classList.contains('perm-cell')) {
      // Only the row and column this cell belongs to can have changed.
      reflect(moduleToggles[el.dataset.module], byModule[el.dataset.module]);
      reflect(columnToggles[el.dataset.action], byAction[el.dataset.action]);
      reflect(globalBox, allCells);
    } else {
      return;
    }

    updateCounter();
  });

  reflectModules();
  reflectColumns();
  reflect(globalBox, allCells);
  updateCounter();
})();
</script>
