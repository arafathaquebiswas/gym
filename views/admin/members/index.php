<?php
/** @var array $members */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
/** @var array $trainers */
/** @var array $stats */
$statusLabels = ['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired'];
$statusColors = ['pending' => 'secondary', 'active' => 'success', 'expired' => 'dark'];
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
    <?php if (Feature::trainerModuleOn()): ?>
    <select name="trainer_id" class="form-select form-select-sm">
      <option value="">All Trainers</option>
      <?php foreach ($trainers as $trainer): ?>
        <option value="<?= (int) $trainer['id'] ?>" <?= (string) $filters['trainer_id'] === (string) $trainer['id'] ? 'selected' : '' ?>><?= e($trainer['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
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
      <?php if (Feature::trainerModuleOn()): ?><option value="assign_trainer">Assign Trainer</option><?php endif; ?>
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
    <select id="bulkPaymentMethod" class="form-select form-select-sm bulk-extra payment-method-select" style="max-width:150px">
      <option value="" disabled selected>Select Payment Method</option>
      <option value="cash">Cash</option>
      <option value="card">Card</option>
      <option value="bkash">bKash</option>
      <option value="nagad">Nagad</option>
      <option value="rocket">Rocket</option>
      <option value="bank_transfer">Bank Transfer</option>
    </select>
    <input type="text" id="bulkReferenceNo" class="form-control form-control-sm bulk-extra reference-no-input d-none" style="max-width:170px" placeholder="Transaction/Reference ID">
    <input type="text" id="bulkCouponCode" class="form-control form-control-sm bulk-extra" style="max-width:150px" placeholder="Coupon (optional)">

    <?php if (Feature::trainerModuleOn()): ?>
    <select id="bulkTrainerSelect" class="form-select form-select-sm bulk-extra" style="max-width:200px">
      <?php foreach ($trainers as $trainer): ?>
        <option value="<?= (int) $trainer['id'] ?>"><?= e($trainer['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>

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
          <th></th><th>Photo</th><th>Member ID</th><th>Money Received No.</th><th>Name</th><th>Phone</th><th>Status</th><th>Package</th><th>Trainer</th><th>Expiry</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $member): ?>
        <tr>
          <td><input type="checkbox" class="row-check" value="<?= (int) $member['id'] ?>" data-name="<?= e($member['name']) ?>"></td>
          <td>
            <button type="button" class="btn btn-link text-white-50 p-0" data-bs-toggle="collapse" data-bs-target="#memberDetails<?= $member['id'] ?>" aria-expanded="false">
              <i class="bi bi-chevron-right details-chevron"></i>
            </button>
          </td>
          <td><?= media_tile($member['photo'], $member['name'], 'bi-person', 'thumb') ?></td>
          <td><span class="text-white-50 small"><?= e($member['member_code']) ?></span></td>
          <td><span class="text-white-50 small"><?= e($member['money_received_no'] ?? '—') ?></span></td>
          <td><a href="<?= url('/admin/members/' . $member['id']) ?>" class="text-white fw-semibold text-decoration-none"><?= e($member['name']) ?></a></td>
          <td><?= e($member['phone'] ?? '—') ?></td>
          <td><span class="badge text-bg-<?= $statusColors[$member['status']] ?? 'secondary' ?>"><?= e($statusLabels[$member['status']] ?? $member['status']) ?></span></td>
          <td><?= e($member['package_name'] ?? '—') ?></td>
          <td><?= e($member['trainer_name'] ?? '—') ?></td>
          <td><?= $member['subscription_end'] ? format_date($member['subscription_end']) : '—' ?></td>
          <td>
            <div class="dropdown">
              <button type="button" class="btn btn-ps-outline btn-sm" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button>
              <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                <li><a class="dropdown-item" href="<?= url('/admin/members/' . $member['id']) ?>"><i class="bi bi-eye me-2"></i>View</a></li>
                <li><a class="dropdown-item" href="<?= url('/admin/members/' . $member['id'] . '/edit') ?>"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                <li>
                  <button type="button" class="dropdown-item js-open-renew"
                    data-id="<?= (int) $member['id'] ?>"
                    data-name="<?= e($member['name']) ?>"
                    data-trainer-id="<?= (int) ($member['trainer_id'] ?? 0) ?>">
                    <i class="bi bi-arrow-repeat me-2"></i>Renew Membership
                  </button>
                </li>
                <li><a class="dropdown-item" href="<?= url('/admin/members/' . $member['id'] . '/payments') ?>"><i class="bi bi-receipt me-2"></i>Payment History</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/delete') ?>" onsubmit="return confirm('Delete this member permanently? This removes their login, subscriptions, and attendance history.');">
                    <?= Security::csrfField() ?>
                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Delete</button>
                  </form>
                </li>
              </ul>
            </div>
          </td>
        </tr>
        <tr class="collapse" id="memberDetails<?= $member['id'] ?>">
          <td></td>
          <td colspan="11">
            <div class="d-flex flex-wrap gap-4 py-2 small text-white-50">
              <div><span class="text-white-50">Email</span><br><span class="text-white"><?= e($member['email']) ?></span></div>
              <div><span class="text-white-50">Locker</span><br><span class="text-white"><?= e($member['locker_number'] ?? '—') ?></span></div>
              <div><span class="text-white-50">Join Date</span><br><span class="text-white"><?= format_date($member['join_date']) ?></span></div>
              <div><span class="text-white-50">Attendance</span><br><span class="text-white"><?= (int) $member['attendance_this_month'] ?> / mo</span></div>
              <div><span class="text-white-50">BMI</span><br><span class="text-white"><?= $member['bmi'] ? e((string) $member['bmi']) . ' (' . e($member['bmi_category']) . ')' : '—' ?></span></div>
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

<div class="modal fade" id="renewMembershipModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark">
      <form method="post" id="renewMembershipForm" class="admin-form">
        <?= Security::csrfField() ?>
        <div class="modal-header">
          <h5 class="modal-title">Renew Membership — <span id="renewMemberName"></span></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label>Package</label>
            <select name="package_id" id="renewPackageSelect" class="form-select" required>
              <option value="">Select a package</option>
              <?php foreach ($packages as $package): ?>
                <option value="<?= (int) $package['id'] ?>"
                  data-duration="<?= (int) $package['duration_days'] ?>"
                  data-price="<?= (float) $package['display_price'] ?>">
                  <?= e($package['name']) ?> (৳<?= number_format((float) $package['display_price']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label>Duration (days)</label>
            <input type="number" min="1" name="duration_days" id="renewDuration" class="form-control">
          </div>
          <?php if (Feature::trainerModuleOn()): ?>
          <div class="col-md-6">
            <label>Trainer <small class="text-white-50">(optional)</small></label>
            <select name="trainer_id" id="renewTrainerSelect" class="form-select">
              <option value="">— No change / None —</option>
              <?php foreach ($trainers as $trainer): ?>
                <option value="<?= (int) $trainer['id'] ?>"><?= e($trainer['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="col-md-6">
            <label>Discount (৳) <small class="text-white-50">(optional)</small></label>
            <input type="number" step="0.01" min="0" name="discount" id="renewDiscount" class="form-control" value="0">
          </div>
          <div class="col-md-6">
            <label>Payment Method</label>
            <select name="payment_method" class="form-select payment-method-select" required>
              <option value="" disabled selected>Select Payment Method</option>
              <option value="cash">Cash</option>
              <option value="card">Card</option>
              <option value="bkash">bKash</option>
              <option value="nagad">Nagad</option>
              <option value="rocket">Rocket</option>
              <option value="bank_transfer">Bank Transfer</option>
            </select>
          </div>
          <div class="col-md-6 reference-no-wrap d-none">
            <label>Transaction / Reference ID</label>
            <input type="text" name="reference_no" class="form-control reference-no-input" placeholder="e.g. bKash transaction ID">
          </div>
          <div class="col-md-6">
            <label>Amount Received (৳)</label>
            <input type="number" step="0.01" min="0" name="price_paid" id="renewAmountReceived" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label>Renewal Date</label>
            <input type="date" name="start_date" id="renewStartDate" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-12">
            <label>Notes <small class="text-white-50">(optional)</small></label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-link text-white-50" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-ps btn-sm">Renew Membership</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  var renewModalEl = document.getElementById('renewMembershipModal');
  if (!renewModalEl) return;

  var form = document.getElementById('renewMembershipForm');
  var nameEl = document.getElementById('renewMemberName');
  var packageSelect = document.getElementById('renewPackageSelect');
  var durationField = document.getElementById('renewDuration');
  var amountField = document.getElementById('renewAmountReceived');
  var discountField = document.getElementById('renewDiscount');
  var trainerSelect = document.getElementById('renewTrainerSelect');

  function applyPackageDefaults() {
    var opt = packageSelect.options[packageSelect.selectedIndex];
    if (!opt || !opt.value) return;
    durationField.value = opt.getAttribute('data-duration') || '';
    var price = parseFloat(opt.getAttribute('data-price') || '0');
    var discount = parseFloat(discountField.value || '0') || 0;
    amountField.value = Math.max(0, price - discount).toFixed(2);
  }

  packageSelect.addEventListener('change', applyPackageDefaults);
  discountField.addEventListener('input', applyPackageDefaults);

  document.querySelectorAll('.js-open-renew').forEach(function (btn) {
    btn.addEventListener('click', function () {
      form.action = '<?= url('/admin/members') ?>/' + btn.getAttribute('data-id') + '/renew';
      nameEl.textContent = btn.getAttribute('data-name');
      packageSelect.value = '';
      durationField.value = '';
      discountField.value = '0';
      amountField.value = '';
      if (trainerSelect) trainerSelect.value = btn.getAttribute('data-trainer-id') || '';
      bootstrap.Modal.getOrCreateInstance(renewModalEl).show();
    });
  });
})();
</script>

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
    renew: ['bulkPackageSelect', 'bulkStartDate', 'bulkPaymentMethod', 'bulkReferenceNo', 'bulkCouponCode'],
    assign_trainer: ['bulkTrainerSelect'],
    notify: ['bulkNotifySubject', 'bulkNotifyMessage'],
    delete: [],
    assign_locker: [],
  };
  var NO_REFERENCE_METHODS = ['cash', 'card'];
  var bulkPaymentMethod = document.getElementById('bulkPaymentMethod');
  var bulkReferenceNo = document.getElementById('bulkReferenceNo');

  function updateBulkReferenceVisibility() {
    if (!bulkPaymentMethod || !bulkReferenceNo) return;
    var needsReference = bulkPaymentMethod.value !== '' && NO_REFERENCE_METHODS.indexOf(bulkPaymentMethod.value) === -1;
    bulkReferenceNo.classList.toggle('d-none', !needsReference);
  }
  if (bulkPaymentMethod) {
    bulkPaymentMethod.addEventListener('change', updateBulkReferenceVisibility);
  }

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
    if (action === 'renew') {
      updateBulkReferenceVisibility();
    }
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
      var method = document.getElementById('bulkPaymentMethod').value;
      if (!method) { alert('Please select a payment method.'); return; }
      var reference = document.getElementById('bulkReferenceNo').value.trim();
      if (NO_REFERENCE_METHODS.indexOf(method) === -1 && !reference) {
        alert('Please enter the transaction/reference ID for the selected payment method.');
        return;
      }
      if (!confirm('Renew membership for ' + n + ' selected member(s)?')) return;
      submitBulk('renew', [
        ['package_id', document.getElementById('bulkPackageSelect').value],
        ['start_date', document.getElementById('bulkStartDate').value],
        ['payment_method', method],
        ['reference_no', reference],
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
