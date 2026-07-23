<?php
/** @var array $member */
/** @var array $payments */
$methodLabels = ['cash' => 'Cash', 'card' => 'Card', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'bank_transfer' => 'Bank Transfer'];
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <a href="<?= url('/admin/members/' . $member['id']) ?>" class="text-white-50 text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to <?= e($member['name']) ?></a>
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
        <tr><th>Date</th><th>Type</th><th>Package</th><th>Amount</th><th>Payment</th><th>Receipt</th></tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $payment): ?>
        <tr>
          <td><?= format_date($payment['paid_at'], 'd M Y') ?></td>
          <td><?= e($payment['type_label']) ?></td>
          <td><?= e($payment['package_name'] ?? '—') ?></td>
          <td><?= money((float) $payment['amount']) ?></td>
          <td><?= e($methodLabels[$payment['method']] ?? ucfirst($payment['method'])) ?></td>
          <td><?= e($member['money_received_no'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
