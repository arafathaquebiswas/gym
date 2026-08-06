<div class="admin-page-shell">
  <div class="admin-page-header">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Payment Verification</li>
        </ol>
      </nav>
      <h1 class="admin-page-title">Payment Verification</h1>
    </div>
  </div>
<?php
/** @var array $registrations */
/** @var array $counts */
/** @var string $activeStatus */
$statusColors = ['pending' => 'warning', 'verified' => 'success', 'rejected' => 'danger'];
$methodLabels = ['bkash' => 'bKash', 'nagad' => 'Nagad'];
$typeLabels = ['qr' => 'QR Payment', 'mobile' => 'Mobile Number Payment'];
?>
<div class="admin-card">
  <p class="text-muted small">
    Online bKash/Nagad payments submitted through the membership registration form. Compare the
    screenshot against the Transaction ID before verifying — a membership cannot be activated
    until its payment is verified.
  </p>

  <form method="get" action="<?= url('/admin/registrations') ?>" class="admin-toolbar admin-form">
    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">All (<?= array_sum($counts) ?>)</option>
      <?php foreach (Member::PAYMENT_STATUSES as $value => $label): ?>
        <option value="<?= $value ?>" <?= $activeStatus === $value ? 'selected' : '' ?>><?= $label ?> (<?= (int) $counts[$value] ?>)</option>
      <?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="btn btn-ps-outline btn-sm">Filter</button></noscript>
  </form>

  <?php if (!$registrations): ?>
    <p class="text-muted mb-0">No online payments to review<?= $activeStatus !== '' ? ' with this status' : '' ?>.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table admin-table align-middle">
      <thead>
        <tr>
          <th>Member</th>
          <th>Plan</th>
          <th>Payment</th>
          <th class="text-end">Amount</th>
          <th>Transaction ID</th>
          <th>Screenshot</th>
          <th>Registered</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($registrations as $row): ?>
        <?php
          $amount = (float) ($row['registration_amount'] ?? 0);
          $discount = (float) ($row['registration_coupon_discount'] ?? 0);
          $payable = max(0, $amount - $discount);
        ?>
        <tr>
          <td>
            <a href="<?= url('/admin/members/' . (int) $row['id']) ?>"><strong><?= e($row['name']) ?></strong></a>
            <div class="small text-muted"><?= e($row['phone'] ?? '—') ?></div>
          </td>
          <td><?= e($row['package_name'] ?? '—') ?></td>
          <td>
            <?= e($methodLabels[$row['payment_method']] ?? $row['payment_method']) ?>
            <div class="small text-muted"><?= e($typeLabels[$row['payment_type']] ?? $row['payment_type']) ?></div>
          </td>
          <td class="text-end">
            <strong>৳<?= number_format($payable, 2) ?></strong>
            <?php if ($discount > 0): ?>
              <div class="small text-muted">
                <?= e($row['registration_coupon_code'] ?? 'Coupon') ?>: −৳<?= number_format($discount, 2) ?>
              </div>
            <?php endif; ?>
          </td>
          <td><code><?= e($row['transaction_id'] ?? '—') ?></code></td>
          <td>
            <?php if (!empty($row['payment_screenshot'])): ?>
              <a href="<?= url($row['payment_screenshot']) ?>" target="_blank" rel="noopener" title="Open full size">
                <img src="<?= url($row['payment_screenshot']) ?>" alt="Payment screenshot for <?= e($row['name']) ?>" style="height:56px;width:auto;border-radius:6px" loading="lazy">
              </a>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="small"><?= e(date('d M Y, g:i a', strtotime($row['created_at']))) ?></td>
          <td>
            <span class="badge bg-<?= $statusColors[$row['payment_status']] ?? 'secondary' ?>">
              <?= e(Member::PAYMENT_STATUSES[$row['payment_status']] ?? $row['payment_status']) ?>
            </span>
            <?php if (!empty($row['verified_by_name'])): ?>
              <div class="small text-muted">by <?= e($row['verified_by_name']) ?><?= $row['verified_at'] ? ', ' . e(date('d M Y', strtotime($row['verified_at']))) : '' ?></div>
            <?php endif; ?>
            <?php if (!empty($row['rejection_reason'])): ?>
              <div class="small text-danger">Reason: <?= e($row['rejection_reason']) ?></div>
            <?php endif; ?>
          </td>
          <td class="text-end text-nowrap">
            <?php if ($row['payment_status'] !== 'verified'): ?>
              <form method="post" action="<?= url('/admin/registrations/' . (int) $row['id'] . '/verify') ?>" class="d-inline"
                    onsubmit="return confirm('Verify this payment? Check the screenshot matches Transaction ID <?= e($row['transaction_id']) ?> first.')">
                <input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>">
                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i> Verify</button>
              </form>
            <?php endif; ?>
            <?php if ($row['payment_status'] !== 'rejected'): ?>
              <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?= (int) $row['id'] ?>">
                <i class="bi bi-x-lg"></i> Reject
              </button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php foreach ($registrations as $row): ?>
    <?php if ($row['payment_status'] === 'rejected') { continue; } ?>
    <div class="modal fade" id="rejectModal<?= (int) $row['id'] ?>" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= url('/admin/registrations/' . (int) $row['id'] . '/reject') ?>" class="modal-content">
          <input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>">
          <div class="modal-header">
            <h5 class="modal-title">Reject payment — <?= e($row['name']) ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p class="small text-muted mb-2">
              Transaction ID <code><?= e($row['transaction_id'] ?? '—') ?></code> ·
              <?= e($methodLabels[$row['payment_method']] ?? $row['payment_method']) ?>
            </p>
            <label for="reason<?= (int) $row['id'] ?>" class="form-label">Reason <span class="text-muted">(optional)</span></label>
            <input type="text" id="reason<?= (int) $row['id'] ?>" name="rejection_reason" class="form-control" maxlength="255"
                   placeholder="e.g. Screenshot does not match the Transaction ID">
            <p class="small text-muted mt-2 mb-0">Recorded against the registration and shown in this list.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm">Reject Payment</button>
          </div>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>
</div>
