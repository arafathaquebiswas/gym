<div class="admin-page-shell">
  <div class="admin-page-header">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">Dashboard</li>
        </ol>
      </nav>
      <h1 class="admin-page-title">Dashboard</h1>
    </div>
  </div>
<?php
/** @var int $trainerCount */
/** @var int $activeTrainerCount */
/** @var int $featuredTrainerCount */
/** @var float $todaysRevenue */
/** @var int $todaysNewMembers */
/** @var int $totalMembers */
/** @var int $activePackages */
/** @var int $productCount */
/** @var int $lowStockCount */
/** @var int $pendingRenewals */
/** @var int $attendanceToday */
/** @var int $pendingOnlineOrders */
/** @var int $todaysPosSales */
/** @var int $todaysOnlineOrders */
/** @var int $pendingReviews */
/** @var int $couponsUsedToday */
/** @var array $topProducts */
/** @var array $topMembers */
/** @var int $upcomingTrainerBookings */
/** @var array $memberStatusCounts */
/** @var array $newMembersByMonth */
/** @var array $revenueByDay */
$memberStatusLabels = ['pending' => 'Pending', 'active' => 'Active', 'expired' => 'Expired'];
$memberStatusColors = ['pending' => '#a7a7b0', 'active' => '#2ecc71', 'expired' => '#6c6c74'];
$stats = [
    ["Today's Revenue", money($todaysRevenue), 'bi-cash-stack', null],
    ["Today's POS Sales", $todaysPosSales, 'bi-calculator', 'admin/reports/sales'],
    ["Today's Online Orders", $todaysOnlineOrders, 'bi-bag-check', 'admin/orders'],
    ["Today's New Members", $todaysNewMembers, 'bi-person-plus', 'admin/members'],
    ['Total Members', $totalMembers, 'bi-people', 'admin/members'],
    ['Active Packages', $activePackages, 'bi-box-seam', 'admin/packages'],
    ['Products', $productCount, 'bi-shop', 'admin/products'],
    ['Low Stock', $lowStockCount, 'bi-exclamation-triangle', 'admin/reports/stock'],
    ['New Online Orders', $pendingOnlineOrders, 'bi-bag-check', 'admin/orders?status=pending'],
    ['Pending Reviews', $pendingReviews, 'bi-star', 'admin/reviews'],
    ['Coupons Used Today', $couponsUsedToday, 'bi-ticket-perforated', 'admin/coupons'],
    ['Pending Renewals (7 days)', $pendingRenewals, 'bi-arrow-repeat', 'admin/reports/renewals'],
    ['Attendance Today', $attendanceToday, 'bi-calendar-check', 'admin/reports/attendance'],
    ['Trainers', $trainerCount, 'bi-person-badge', 'admin/trainers'],
    ['Upcoming Trainer Bookings', $upcomingTrainerBookings, 'bi-calendar-event', 'admin/trainers'],
];
?>
<div class="admin-page-section">
  <div class="admin-section-heading">
    <div>
      <h6>Operations Overview</h6>
      <p class="text-white-50 small">A quick snapshot of the most important gym activity.</p>
    </div>
  </div>
  <div class="row g-3 mb-4">
  <?php foreach ($stats as [$label, $value, $icon, $link]): ?>
  <div class="col-6 col-md-4 col-lg-3">
    <?php if ($link): ?><a href="<?= url('/' . $link) ?>" class="text-decoration-none text-white"><?php endif; ?>
      <div class="admin-card h-100">
        <i class="bi <?= $icon ?> text-orange"></i>
        <div class="text-white-50 small mt-1"><?= e($label) ?></div>
        <div class="fs-3 fw-bold text-orange"><?= is_int($value) ? $value : e((string) $value) ?></div>
      </div>
    <?php if ($link): ?></a><?php endif; ?>
  </div>
  <?php endforeach; ?>
  </div>
</div>

<div class="admin-page-section">
  <div class="admin-section-heading">
    <div>
      <h6>Performance Analytics</h6>
      <p class="text-white-50 small">Membership and revenue trends for the current period.</p>
    </div>
  </div>
  <div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="admin-card h-100">
      <h6 class="mb-3">Member Status Distribution</h6>
      <?php if (empty($memberStatusCounts)): ?>
        <p class="text-white-50 small mb-0">No members yet.</p>
      <?php else: ?>
        <div style="height:140px"><canvas id="chartMemberStatus"></canvas></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="admin-card h-100">
      <h6 class="mb-3">New Member Registrations <span class="text-white-50 small fw-normal">(last 12 months)</span></h6>
      <div style="height:140px"><canvas id="chartNewMembers"></canvas></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="admin-card">
      <h6 class="mb-3">Revenue Trend <span class="text-white-50 small fw-normal">(last 30 days)</span></h6>
      <div style="height:100px"><canvas id="chartRevenue"></canvas></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="admin-card">
      <div class="text-white-50 small">Active on Website</div>
      <div class="fs-2 fw-bold text-orange"><?= (int) $activeTrainerCount ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-card">
      <div class="text-white-50 small">Featured Trainers</div>
      <div class="fs-2 fw-bold text-orange"><?= (int) $featuredTrainerCount ?></div>
    </div>
  </div>
</div>

<div class="admin-page-section">
  <div class="admin-section-heading">
    <div>
      <h6>Sales & Member Insights</h6>
      <p class="text-white-50 small">Best-performing products and top members at a glance.</p>
    </div>
  </div>
  <div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="admin-card h-100">
      <h6 class="mb-3">Top Products <span class="text-white-50 small fw-normal">(last 90 days)</span></h6>
      <?php if (empty($topProducts)): ?>
        <p class="text-white-50 small mb-0">No sales data yet.</p>
      <?php else: ?>
        <table class="admin-table mb-0">
          <thead><tr><th>Product</th><th class="text-end">Qty Sold</th></tr></thead>
          <tbody>
            <?php foreach ($topProducts as $p): ?>
              <tr><td><?= e($p['name']) ?></td><td class="text-end"><?= (int) $p['total_qty'] ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-6">
    <div class="admin-card h-100">
      <h6 class="mb-3">Top Members <span class="text-white-50 small fw-normal">(by total spend)</span></h6>
      <?php if (empty($topMembers)): ?>
        <p class="text-white-50 small mb-0">No payment data yet.</p>
      <?php else: ?>
        <table class="admin-table mb-0">
          <thead><tr><th>Member</th><th class="text-end">Total Spent</th></tr></thead>
          <tbody>
            <?php foreach ($topMembers as $m): ?>
              <tr><td><?= e($m['name']) ?></td><td class="text-end"><?= money((float) $m['total_spent']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
<div class="row g-4 mb-4">
  <div class="col-12">
    <div class="admin-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0"><i class="bi bi-download text-orange me-2"></i>Export Activity</h6>
        <a href="<?= url('/admin/audit-log') ?>" class="btn btn-ps-outline btn-sm">View Audit Log <i class="bi bi-arrow-right ms-1"></i></a>
      </div>
      <?php if (empty($latestExports)): ?>
        <p class="text-white-50 small mb-0 py-2">No export activity recorded yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="admin-table mb-0">
            <thead>
              <tr>
                <th>Time</th>
                <th>Who Exported</th>
                <th>Module</th>
                <th>File Name</th>
                <th>Format</th>
                <th>Records</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($latestExports as $exp): ?>
              <?php
                $fmt = strtolower($exp['export_format'] ?? '');
                $fmtBadge = match($fmt) {
                    'xlsx' => 'text-bg-success',
                    'csv' => 'text-bg-info',
                    'pdf' => 'text-bg-danger',
                    default => 'text-bg-secondary'
                };
              ?>
              <tr class="cursor-pointer" onclick="window.location.href='<?= url('/admin/audit-log') ?>'">
                <td class="text-nowrap small text-white-50"><?= format_date($exp['created_at'], 'd M Y, h:i A') ?></td>
                <td>
                  <strong><?= e($exp['display_name']) ?></strong>
                  <?php if (!empty($exp['user_role'])): ?><span class="badge text-bg-dark border border-secondary ms-1 small"><?= e(ucfirst(str_replace('_', ' ', $exp['user_role']))) ?></span><?php endif; ?>
                </td>
                <td><span class="badge text-bg-secondary"><?= e(ucfirst(str_replace(['_', '-'], ' ', $exp['module_key'] ?? $exp['action']))) ?></span></td>
                <td class="font-monospace small text-orange"><?= e($exp['file_name'] ?? '—') ?></td>
                <td><span class="badge <?= $fmtBadge ?> text-uppercase"><?= e($fmt ?: 'export') ?></span></td>
                <td><span class="badge text-bg-primary"><?= (int) ($exp['record_count'] ?? 0) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>

<div class="admin-card">
  <h6 class="mb-3">Quick Actions</h6>
  <div class="d-flex flex-wrap gap-2">
    <a href="<?= url('/admin/pos') ?>" class="btn btn-ps"><i class="bi bi-calculator"></i> New Sale</a>
    <a href="<?= url('/admin/members/create') ?>" class="btn btn-ps-outline"><i class="bi bi-person-plus"></i> Add Member</a>
    <a href="<?= url('/admin/trainers/create') ?>" class="btn btn-ps-outline"><i class="bi bi-plus-lg"></i> Add Trainer</a>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline"><i class="bi bi-bar-chart"></i> View Reports</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
  Chart.defaults.color = '#a7a7b0';
  Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
  Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

  <?php if (!empty($memberStatusCounts)): ?>
  new Chart(document.getElementById('chartMemberStatus'), {
    type: 'pie',
    data: {
      labels: <?= json_encode(array_map(fn ($r) => $memberStatusLabels[$r['status']] ?? ucfirst($r['status']), $memberStatusCounts)) ?>,
      datasets: [{
        data: <?= json_encode(array_map(fn ($r) => (int) $r['cnt'], $memberStatusCounts)) ?>,
        backgroundColor: <?= json_encode(array_map(fn ($r) => $memberStatusColors[$r['status']] ?? '#ff6a1a', $memberStatusCounts)) ?>,
        borderColor: '#17171c',
        borderWidth: 2,
      }],
    },
    options: {
      plugins: { legend: { position: 'bottom' } },
      maintainAspectRatio: false,
    },
  });
  <?php endif; ?>

  new Chart(document.getElementById('chartNewMembers'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($newMembersByMonth['labels']) ?>,
      datasets: [{
        label: 'New Members',
        data: <?= json_encode($newMembersByMonth['data']) ?>,
        backgroundColor: '#ff6a1a',
        borderRadius: 6,
        maxBarThickness: 36,
      }],
    },
    options: {
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(255,255,255,.06)' } },
        x: { grid: { display: false } },
      },
      plugins: { legend: { display: false } },
    },
  });

  new Chart(document.getElementById('chartRevenue'), {
    type: 'line',
    data: {
      labels: <?= json_encode($revenueByDay['labels']) ?>,
      datasets: [{
        label: 'Revenue (৳)',
        data: <?= json_encode($revenueByDay['data']) ?>,
        borderColor: '#ff6a1a',
        backgroundColor: 'rgba(255,106,26,.15)',
        fill: true,
        tension: .35,
        pointRadius: 2,
        pointHoverRadius: 5,
      }],
    },
    options: {
      maintainAspectRatio: false,
      scales: {
        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.06)' } },
        x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } },
      },
      plugins: { legend: { display: false } },
    },
  });
})();
</script>
</div>
