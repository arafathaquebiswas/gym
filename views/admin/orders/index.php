<?php
/** @var array $orders */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var array $filters */
/** @var array $statusCounts */
$statusLabels = [
    '' => 'All', 'pending' => 'New', 'confirmed' => 'Confirmed', 'preparing' => 'Preparing',
    'ready_for_pickup' => 'Ready for Pickup', 'shipped' => 'Shipped', 'delivered' => 'Delivered',
    'cancelled' => 'Cancelled', 'returned' => 'Returned',
];
$statusColors = ['pending' => 'secondary', 'confirmed' => 'info', 'preparing' => 'info', 'ready_for_pickup' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger', 'returned' => 'dark'];
?>
<div class="admin-card mb-4">
  <div class="d-flex flex-wrap gap-2">
    <?php foreach ($statusLabels as $val => $label): ?>
      <a href="<?= url('/admin/orders' . ($val ? '?status=' . $val : '')) ?>"
         class="btn btn-sm <?= $filters['status'] === $val ? 'btn-ps' : 'btn-ps-outline' ?>">
        <?= e($label) ?><?php if ($val !== ''): ?> <span class="badge text-bg-<?= $statusColors[$val] ?? 'secondary' ?>"><?= (int) ($statusCounts[$val] ?? 0) ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="admin-card">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0">Orders (<?= (int) $total ?>)</h6>
  </div>
  <form method="get" action="<?= url('/admin/orders') ?>" class="admin-toolbar admin-form">
    <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search order # or customer" value="<?= e($filters['search']) ?>">
    <button type="submit" class="btn btn-ps-outline btn-sm">Filter</button>
  </form>

  <?php if (empty($orders)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No orders found.</p>
  <?php else: ?>

  <form method="post" action="<?= url('/admin/orders/bulk') ?>" id="bulkForm" class="d-none">
    <?= Security::csrfField() ?>
    <input type="hidden" name="bulk_action" id="bulkActionField">
    <input type="hidden" name="bulk_status" id="bulkStatusField">
  </form>
  <div id="bulkToolbar" class="d-none mb-3 d-flex gap-2 align-items-center flex-wrap">
    <span class="text-white-50 small"><span id="bulkCount">0</span> selected</span>
    <select id="bulkActionSelect" class="form-select form-select-sm" style="max-width:180px">
      <option value="status">Update Status</option>
      <option value="export">Export CSV</option>
      <option value="print">Print</option>
      <option value="delete">Delete</option>
    </select>
    <select id="bulkStatusSelect" class="form-select form-select-sm" style="max-width:180px">
      <?php foreach ($statusLabels as $val => $label): if ($val === '') continue; ?>
        <option value="<?= $val ?>"><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" id="bulkApplyBtn" class="btn btn-ps-outline btn-sm">Apply</button>
  </div>

  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th><input type="checkbox" id="selectAllOrders"></th><th>Order #</th><th>Customer</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
          <td><input type="checkbox" class="row-check" value="<?= (int) $order['id'] ?>"></td>
          <td><?= e($order['order_no']) ?></td>
          <td><?= e($order['customer_name']) ?></td>
          <td><?= format_date($order['created_at'], 'd M Y, h:i A') ?></td>
          <td>৳<?= number_format((float) $order['total']) ?></td>
          <td><span class="badge text-bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'secondary' ?>"><?= e(ucfirst($order['payment_status'])) ?></span></td>
          <td><span class="badge text-bg-<?= $statusColors[$order['status']] ?? 'secondary' ?>"><?= e(ucfirst(str_replace('_', ' ', $order['status']))) ?></span></td>
          <td><a href="<?= url('/admin/orders/' . $order['id']) ?>" class="btn btn-ps-outline btn-sm">View</a></td>
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
          <a class="page-link" href="<?= url('/admin/orders?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>

<script>
(function () {
  var checks = document.querySelectorAll('.row-check');
  var toolbar = document.getElementById('bulkToolbar');
  if (!toolbar) return;
  var countEl = document.getElementById('bulkCount');
  var selectAll = document.getElementById('selectAllOrders');
  var actionSelect = document.getElementById('bulkActionSelect');
  var statusSelect = document.getElementById('bulkStatusSelect');

  function update() {
    var checked = document.querySelectorAll('.row-check:checked');
    countEl.textContent = checked.length;
    toolbar.classList.toggle('d-none', checked.length === 0);
  }
  function updateExtraFields() {
    statusSelect.classList.toggle('d-none', actionSelect.value !== 'status');
  }
  checks.forEach(function (c) { c.addEventListener('change', update); });
  actionSelect.addEventListener('change', updateExtraFields);
  updateExtraFields();
  if (selectAll) {
    selectAll.addEventListener('change', function () {
      checks.forEach(function (c) { c.checked = selectAll.checked; });
      update();
    });
  }

  document.getElementById('bulkApplyBtn').addEventListener('click', function () {
    var checked = document.querySelectorAll('.row-check:checked');
    if (!checked.length) return;
    var action = actionSelect.value;
    if (action === 'delete' && !confirm('Delete ' + checked.length + ' selected order(s)? Only cancelled/returned orders can be deleted.')) return;
    if (action === 'status' && !confirm('Update ' + checked.length + ' selected order(s) to "' + statusSelect.options[statusSelect.selectedIndex].text + '"?')) return;

    var form = document.getElementById('bulkForm');
    form.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });
    checked.forEach(function (c) {
      var input = document.createElement('input');
      input.type = 'hidden'; input.name = 'ids[]'; input.value = c.value;
      form.appendChild(input);
    });
    document.getElementById('bulkActionField').value = action;
    document.getElementById('bulkStatusField').value = statusSelect.value;
    form.submit();
  });
})();
</script>
