<?php
/** @var array $member */
/** @var array $subscriptionHistory */
/** @var array $attendanceLog */
/** @var array|null $openSession */
/** @var array $packages */
/** @var float $trainerFeeDefault */
/** @var float $lockerFineDefault */
$statusColors = ['pending' => 'secondary', 'active' => 'success', 'suspended' => 'danger', 'frozen' => 'info', 'expired' => 'dark'];
?>
<div class="row g-4">
  <div class="col-md-4">
    <div class="admin-card text-center">
      <?= media_tile($member['photo'], $member['name'], 'bi-person', 'thumb-lg mb-3', null) ?>
      <h5 class="mb-0"><?= e($member['name']) ?></h5>
      <div class="text-white-50 small mb-2">
        <?= e($member['member_code']) ?>
        <?php if (!empty($member['money_received_no'])): ?>
          &nbsp;|&nbsp; Receipt: <?= e($member['money_received_no']) ?>
        <?php endif; ?>
      </div>
      <span class="badge text-bg-<?= $statusColors[$member['status']] ?? 'secondary' ?> mb-3"><?= e(ucfirst($member['status'])) ?></span>
      <ul class="list-unstyled text-start small">
        <li><i class="bi bi-envelope"></i> <?= e($member['email']) ?></li>
        <li><i class="bi bi-telephone"></i> <?= e($member['phone'] ?? '—') ?></li>
        <li><i class="bi bi-person-badge"></i> Trainer: <?= e($member['trainer_name'] ?? '—') ?></li>
        <li><i class="bi bi-box"></i> Locker: <?= e($member['locker_number'] ?? '—') ?></li>
        <li><i class="bi bi-calendar"></i> Joined: <?= format_date($member['join_date']) ?></li>
        <li><i class="bi bi-rulers"></i> BMI: <?= $member['bmi'] ? e((string) $member['bmi']) . ' (' . e($member['bmi_category']) . ')' : '—' ?></li>
      </ul>
      <div class="d-flex gap-2 justify-content-center mt-2">
        <a href="<?= url('/admin/members/' . $member['id'] . '/edit') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        <?php if ($openSession): ?>
        <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/checkout') ?>">
          <?= Security::csrfField() ?>
          <button type="submit" class="btn btn-ps btn-sm"><i class="bi bi-box-arrow-left"></i> Check Out</button>
        </form>
        <?php else: ?>
        <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/checkin') ?>">
          <?= Security::csrfField() ?>
          <button type="submit" class="btn btn-ps btn-sm"><i class="bi bi-box-arrow-in-right"></i> Check In</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="admin-card mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0">Renew / Purchase Package</h6>
      </div>
      <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/renew') ?>" class="row g-3 admin-form">
        <?= Security::csrfField() ?>
        <div class="col-md-4">
          <label>Package</label>
          <select name="package_id" class="form-select" required>
            <option value="">Select a package</option>
            <?php foreach ($packages as $package): ?>
              <option value="<?= (int) $package['id'] ?>" data-price="<?= (float) $package['display_price'] ?>"><?= e($package['name']) ?> (৳<?= number_format((float) $package['display_price']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label>Start Date</label>
          <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-2">
          <label>Price Paid (৳)</label>
          <input type="number" step="0.01" min="0" name="price_paid" class="form-control">
        </div>
        <div class="col-md-3">
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
        <div class="col-md-4 reference-no-wrap d-none">
          <label>Transaction / Reference ID</label>
          <input type="text" name="reference_no" class="form-control reference-no-input" placeholder="e.g. bKash transaction ID">
        </div>
        <div class="col-md-4">
          <label>Coupon Code <small class="text-white-50">(optional)</small></label>
          <input type="text" name="coupon_code" class="form-control text-uppercase" placeholder="e.g. SAVE10">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-ps btn-sm">Confirm Renewal</button>
        </div>
      </form>
    </div>

    <?php if (Feature::trainerFeeOn()): ?>
    <div class="admin-card mb-4">
      <h6 class="mb-3">Charge Trainer Fee</h6>
      <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/charge-trainer-fee') ?>" class="row g-3 admin-form">
        <?= Security::csrfField() ?>
        <div class="col-md-3">
          <label>Amount (৳)</label>
          <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= $trainerFeeDefault > 0 ? e((string) $trainerFeeDefault) : '' ?>" required>
        </div>
        <div class="col-md-3">
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
        <div class="col-md-4 reference-no-wrap d-none">
          <label>Transaction / Reference ID</label>
          <input type="text" name="reference_no" class="form-control reference-no-input" placeholder="e.g. bKash transaction ID">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-ps-outline btn-sm">Charge Trainer Fee</button>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <div class="admin-card mb-4">
      <h6 class="mb-3">Charge Locker Fine</h6>
      <form method="post" action="<?= url('/admin/members/' . $member['id'] . '/charge-locker-fine') ?>" class="row g-3 admin-form">
        <?= Security::csrfField() ?>
        <div class="col-md-3">
          <label>Amount (৳)</label>
          <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="<?= $lockerFineDefault > 0 ? e((string) $lockerFineDefault) : '' ?>" required>
        </div>
        <div class="col-md-3">
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
        <div class="col-md-4 reference-no-wrap d-none">
          <label>Transaction / Reference ID</label>
          <input type="text" name="reference_no" class="form-control reference-no-input" placeholder="e.g. bKash transaction ID">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-outline-danger btn-sm">Charge Locker Fine</button>
        </div>
      </form>
    </div>

    <div class="admin-card mb-4">
      <h6 class="mb-3">Subscription History</h6>
      <?php if (empty($subscriptionHistory)): ?>
        <p class="text-white-50 mb-0">No subscriptions yet.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Package</th><th>Start</th><th>End</th><th>Price Paid</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($subscriptionHistory as $sub): ?>
            <tr>
              <td><?= e($sub['package_name']) ?></td>
              <td><?= format_date($sub['start_date']) ?></td>
              <td><?= format_date($sub['end_date']) ?></td>
              <td><?= money((float) $sub['price_paid']) ?></td>
              <td><?= e(ucfirst($sub['status'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <div class="admin-card">
      <h6 class="mb-3">Recent Attendance</h6>
      <?php if (empty($attendanceLog)): ?>
        <p class="text-white-50 mb-0">No attendance records yet.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>Check In</th><th>Check Out</th></tr></thead>
          <tbody>
            <?php foreach ($attendanceLog as $log): ?>
            <tr>
              <td><?= format_date($log['check_in'], 'd M Y, h:i A') ?></td>
              <td><?= $log['check_out'] ? format_date($log['check_out'], 'd M Y, h:i A') : '—' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
