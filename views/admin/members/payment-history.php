<?php
/** @var array $member */
/** @var array $payments */
$methodLabels = ['cash' => 'Cash', 'card' => 'Card', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'bank_transfer' => 'Bank Transfer'];
?>
<div class="admin-page-shell">
  <div class="admin-page-header">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="<?= url('/admin/members') ?>">Members</a></li>
          <li class="breadcrumb-item active">Payments</li>
        </ol>
      </nav>
      <h1 class="admin-page-title">Payment History</h1>
    </div>
    <div class="admin-page-actions">
      <a href="<?= url('/admin/members/' . $member['id']) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> Back to Profile</a>
    </div>
  </div>

  <div class="admin-card mb-4">
  <div class="d-flex align-items-center gap-3 mb-3">
    <?= media_tile($member['photo'], $member['name'], 'bi-person', 'thumb') ?>
    <div>
      <h6 class="mb-0"><?= e($member['name']) ?></h6>
      <div class="text-white-50 small">
        Member ID: <?= e($member['member_code']) ?>
        &nbsp;|&nbsp;
        Receipt No: <?= e($member['money_received_no'] ?? '—') ?>
      </div>
    </div>
  </div>

  <?php if (empty($payments)): ?>
    <p class="text-white-50 text-center py-4 mb-0">No payments recorded for this member yet.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr><th>Date</th><th>Type</th><th>Package</th><th>Payment Method</th><th>Amount</th><th>TrxID / Ref</th><th>Money Received No.</th></tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $payment): ?>
        <tr>
          <td><?= format_date($payment['paid_at'], 'd M Y') ?></td>
          <td><?= e($payment['type_label']) ?></td>
          <td><?= e($payment['package_name'] ?? '—') ?></td>
          <td><?= e($methodLabels[$payment['method']] ?? ucfirst($payment['method'])) ?></td>
          <td><?= money((float) $payment['amount']) ?></td>
          <td><?= e($payment['reference_no'] ?? '—') ?></td>
          <td><?= e($member['money_received_no'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
