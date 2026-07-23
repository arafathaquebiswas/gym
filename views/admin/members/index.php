<?php
/** @var array $members */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
/** @var array $trainers */
/** @var array $stats */
$statusLabels = ['pending' => 'Pending', 'active' => 'Active', 'suspended' => 'Suspended', 'frozen' => 'Frozen', 'expired' => 'Expired'];
$statusColors = ['pending' => 'secondary', 'active' => 'success', 'suspended' => 'danger', 'frozen' => 'info', 'expired' => 'dark'];
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Total Members</div><div class="fs-3 fw-bold text-orange"><?= (int) $stats['total'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Active</div><div class="fs-3 fw-bold text-orange"><?= (int) $stats['active'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Pending</div><div class="fs-3 fw-bold text-orange"><?= (int) $stats['pending'] ?></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="admin-card"><div class="text-white-50 small">Expired</div><div class="fs-3 fw-bold text-orange"><?= (int) $stats['expired'] ?></div></div>
  </div>
</div>

<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">All Members (<?= (int) $total ?>)</h6>
    <a href="<?= url('/admin/members/create') ?>" class="btn btn-ps btn-sm"><i class="bi bi-plus-lg"></i> Add Member</a>
  </div>

  <form method="get" action="<?= url('/admin/members') ?>" class="admin-toolbar admin-form">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, phone, email, code" value="<?= e($filters['search']) ?>">
    <select name="status" class="form-select form-select-sm">
      <option value="">All Statuses</option>
      <?php foreach ($statusLabels as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $filters['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="trainer_id" class="form-select form-select-sm">
      <option value="">All Trainers</option>
      <?php foreach ($trainers as $trainer): ?>
        <option value="<?= (int) $trainer['id'] ?>" <?= (string) $filters['trainer_id'] === (string) $trainer['id'] ? 'selected' : '' ?>><?= e($trainer['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="sort" class="form-select form-select-sm">
      <option value="">Newest First</option>
      <option value="name" <?= $filters['sort'] === 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
      <option value="expiry" <?= $filters['sort'] === 'expiry' ? 'selected' : '' ?>>Expiry (Soonest)</option>
    </select>
    <button type="submit" class="btn btn-ps-outline btn-sm">Filter</button>
    <?php if ($filters['search'] || $filters['status'] || $filters['trainer_id'] || $filters['sort']): ?>
      <a href="<?= url('/admin/members') ?>" class="btn btn-link btn-sm text-white-50">Clear</a>
    <?php endif; ?>
  </form>

  <?php if (empty($members)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No members match your filters.</p>
  <?php else: ?>

  <form method="post" action="<?= url('/admin/members/bulk') ?>" id="bulkForm" class="d-none">
    <?= Security::csrfField() ?>
    <input type="hidden" name="bulk_action" id="bulkActionField">
  </form>
  <div id="bulkToolbar" class="d-none mb-3 d-flex gap-2 align-items-center flex-wrap">
    <span class="text-white-50 small"><span id="bulkCount">0</span> selected</span>
    <select id="bulkActionSelect" class="form-select form-select-sm" style="max-width:180px">
      <option value="renew">Renew Membership</option>
      <option value="assign_trainer">Assign Trainer</option>
      <option value="assign_locker">Assign Locker</option>
      <option value="notify">Send Notification</option>
      <option value="delete">Delete</option>
    </select>

    <select id="bulkPackageSelect" class="form-select form-select-sm bulk-extra" style="max-width:200px">
      <?php foreach ($packages as $pkg): ?>
        <option value="<?= (int) $pkg['id'] ?>"><?= e($pkg['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="date" id="bulkStartDate" class="form-control form-control-sm bulk-extra" style="max-width:160px" value="<?= date('Y-m-d') ?>">
    <select id="bulkPaymentMethod" class="form-select form-select-sm bulk-extra" style="max-width:150px">
      <option value="cash">Cash</option>
      <option value="card">Card</option>
      <option value="bkash">bKash</option>
      <option value="nagad">Nagad</option>
      <option value="bank_transfer">Bank Transfer</option>
    </select>
    <input type="text" id="bulkCouponCode" class="form-control form-control-sm bulk-extra" style="max-width:150px" placeholder="Coupon (optional)">

    <select id="bulkTrainerSelect" class="form-select form-select-sm bulk-extra" style="max-width:200px">
      <?php foreach ($trainers as $trainer): ?>
        <option value="<?= (int) $trainer['id'] ?>"><?= e($trainer['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <input type="text" id="bulkNotifySubject" class="form-control form-control-sm bulk-extra" style="max-width:200px" placeholder="Subject">
    <input type="text" id="bulkNotifyMessage" class="form-control form-control-sm bulk-extra" style="max-width:260px" placeholder="Message">

    <button type="button" id="bulkApplyBtn" class="btn btn-ps-outline btn-sm">Apply</button>
  </div>

  <div id="bulkLockerPanel" class="d-none mb-3 admin-card" style="background:rgba(255,255,255,.03)">
    <div class="text-white-50 small mb-2">Enter a locker number for each selected member:</div>
    <div id="bulkLockerInputs" class="d-flex flex-column gap-2"></div>
    <button type="button" id="bulkLockerSubmitBtn" class="btn btn-ps btn-sm mt-2">Assign Lockers</button>
  </div>

  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th><input type="checkbox" id="selectAllMembers"></th>
          <th>Photo</th><th>Name</th><th>Phone</th><th>Email</th><th>Package</th><th>Trainer</th>
          <th>Locker</th><th>Status</th><th>Join Date</th><th>Expiry</th><th>Attendance</th><th>BMI</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $member): ?>
        <tr>
          <td><input type="checkbox" class="row-check" value="<?= (int) $member['id'] ?>" data-name="<?= e($member['name']) ?>"></td>
          <td><?= media_tile($member['photo'], $member['name'], 'bi-person', 'thumb') ?></td>
          <td>
            <a href="<?= url('/admin/members/' . $member['id']) ?>" class="text-white fw-semibold text-decoration-none"><?= e($member['name']) ?></a>
            <div class="text-white-50 small"><?= e($member['member_code']) ?></div>
          </td>
          <td><?= e($member['phone'] ?? '—') ?></td>
          <td><?= e($member['email']) ?></td>
          <td><?= e($member['package_name'] ?? '—') ?></td>
          <td><?= e($member['trainer_name'] ?? '—') ?></td>
          <td><?= e($member['locker_number'] ?? '—') ?></td>
          <td><span class="badge text-bg-<?= $statusColors[$member['status']] ?? 'secondary' ?>"><?= e($statusLabels[$member['status']] ?? $member['status']) ?></span></td>
          <td><?= format_date($member['join_date']) ?></td>
          <td><?= $member['subscription_end'] ? format_date($member['subscription_end']) : '—' ?></td>
          <td><?= (int) $member['attendance_this_month'] ?> / mo</td>
          <td><?= $member['bmi'] ? e((string) $member['bmi']) . ' (' . e($member['bmi_category']) . ')' : '—' ?></td>
          <td>
            <div class="d-flex gap-2">
              <a href="<?= url('/admin/members/' . $member['id']) ?>" class="btn btn-ps-outline btn-sm" title="View"><i class="bi bi-eye"></i></a>
              <a href="<?= url('/admin/members/' . $member['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
              <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/delete') ?>" onsubmit="return confirm('Delete this member permanently? This removes their login, subscriptions, and attendance history.');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="<?= url('/admin/members?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>

<script>
(function () {
  var toolbar = document.getElementById('bulkToolbar');
  if (!toolbar) return;

  var lockerPanel = document.getElementById('bulkLockerPanel');
  var countEl = document.getElementById('bulkCount');
  var selectAll = document.getElementById('selectAllMembers');
  var actionSelect = document.getElementById('bulkActionSelect');
  var form = document.getElementById('bulkForm');

  var fieldsByAction = {
    renew: ['bulkPackageSelect', 'bulkStartDate', 'bulkPaymentMethod', 'bulkCouponCode'],
    assign_trainer: ['bulkTrainerSelect'],
    notify: ['bulkNotifySubject', 'bulkNotifyMessage'],
    delete: [],
    assign_locker: [],
  };

  function checked() {
    return Array.prototype.slice.call(document.querySelectorAll('.row-check:checked'));
  }

  function update() {
    var n = checked().length;
    countEl.textContent = n;
    toolbar.classList.toggle('d-none', n === 0);
    if (n === 0) lockerPanel.classList.add('d-none');
    updateExtraFields();
  }

  function updateExtraFields() {
    var action = actionSelect.value;
    document.querySelectorAll('.bulk-extra').forEach(function (el) { el.classList.add('d-none'); });
    (fieldsByAction[action] || []).forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.classList.remove('d-none');
    });
    if (action === 'assign_locker' && checked().length > 0) {
      buildLockerInputs();
      lockerPanel.classList.remove('d-none');
    } else {
      lockerPanel.classList.add('d-none');
    }
  }

  function buildLockerInputs() {
    var container = document.getElementById('bulkLockerInputs');
    container.innerHTML = '';
    checked().forEach(function (c) {
      var row = document.createElement('div');
      row.className = 'd-flex align-items-center gap-2';
      var label = document.createElement('span');
      label.className = 'text-white-50 small';
      label.style.minWidth = '160px';
      label.textContent = c.getAttribute('data-name');
      var input = document.createElement('input');
      input.type = 'text';
      input.className = 'form-control form-control-sm';
      input.style.maxWidth = '160px';
      input.placeholder = 'Locker #';
      input.dataset.memberId = c.value;
      row.appendChild(label);
      row.appendChild(input);
      container.appendChild(row);
    });
  }

  function submitBulk(action, extra) {
    form.querySelectorAll('input[name="ids[]"], input[name^="locker["]').forEach(function (el) { el.remove(); });
    checked().forEach(function (c) {
      var input = document.createElement('input');
      input.type = 'hidden'; input.name = 'ids[]'; input.value = c.value;
      form.appendChild(input);
    });
    document.getElementById('bulkActionField').value = action;
    (extra || []).forEach(function (pair) {
      var input = document.createElement('input');
      input.type = 'hidden'; input.name = pair[0]; input.value = pair[1];
      form.appendChild(input);
    });
    form.submit();
  }

  document.querySelectorAll('.row-check').forEach(function (c) { c.addEventListener('change', update); });
  actionSelect.addEventListener('change', updateExtraFields);
  if (selectAll) {
    selectAll.addEventListener('change', function () {
      document.querySelectorAll('.row-check').forEach(function (c) { c.checked = selectAll.checked; });
      update();
    });
  }

  document.getElementById('bulkApplyBtn').addEventListener('click', function () {
    var n = checked().length;
    if (!n) return;
    var action = actionSelect.value;

    if (action === 'delete') {
      if (!confirm('Delete ' + n + ' selected member(s)? This removes their login, subscriptions, and attendance history.')) return;
      submitBulk('delete');
    } else if (action === 'renew') {
      if (!confirm('Renew membership for ' + n + ' selected member(s)?')) return;
      submitBulk('renew', [
        ['package_id', document.getElementById('bulkPackageSelect').value],
        ['start_date', document.getElementById('bulkStartDate').value],
        ['payment_method', document.getElementById('bulkPaymentMethod').value],
        ['coupon_code', document.getElementById('bulkCouponCode').value],
      ]);
    } else if (action === 'assign_trainer') {
      if (!confirm('Assign the selected trainer to ' + n + ' member(s)?')) return;
      submitBulk('assign_trainer', [['trainer_id', document.getElementById('bulkTrainerSelect').value]]);
    } else if (action === 'notify') {
      var subject = document.getElementById('bulkNotifySubject').value.trim();
      var message = document.getElementById('bulkNotifyMessage').value.trim();
      if (!subject || !message) { alert('Please provide both a subject and a message.'); return; }
      submitBulk('notify', [['notify_subject', subject], ['notify_message', message]]);
    } else if (action === 'assign_locker') {
      alert('Use the "Assign Lockers" button below to submit locker numbers.');
    }
  });

  document.getElementById('bulkLockerSubmitBtn').addEventListener('click', function () {
    var inputs = document.querySelectorAll('#bulkLockerInputs input');
    var any = false;
    form.querySelectorAll('input[name="ids[]"], input[name^="locker["]').forEach(function (el) { el.remove(); });
    inputs.forEach(function (input) {
      if (input.value.trim() === '') return;
      any = true;
      var idInput = document.createElement('input');
      idInput.type = 'hidden'; idInput.name = 'ids[]'; idInput.value = input.dataset.memberId;
      form.appendChild(idInput);
      var lockerInput = document.createElement('input');
      lockerInput.type = 'hidden'; lockerInput.name = 'locker[' + input.dataset.memberId + ']'; lockerInput.value = input.value.trim();
      form.appendChild(lockerInput);
    });
    if (!any) { alert('Please enter at least one locker number.'); return; }
    document.getElementById('bulkActionField').value = 'assign_locker';
    form.submit();
  });

  updateExtraFields();
})();
</script>
