<?php
/** @var array $rows */
$grandTotal = array_sum(array_column($rows, 'total'));
?>
<div class="admin-card mb-4">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">Monthly Revenue <span class="text-white-50 small fw-normal">(last 12 months)</span></h6>
    <a href="<?= url('/admin/reports') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-arrow-left"></i> All Reports</a>
  </div>
  <?php include __DIR__ . '/_export_buttons.php'; ?>
  <div class="fs-4 fw-bold text-orange"><?= money($grandTotal) ?> <span class="fs-6 text-white-50 fw-normal">total over 12 months</span></div>
</div>

<div class="admin-card mb-4">
  <div style="height:220px"><canvas id="chartMonthlyRevenue"></canvas></div>
</div>

<div class="admin-card">
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Month</th><th class="text-end">Revenue</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr><td><?= e($r['month']) ?></td><td class="text-end"><?= money((float) $r['total']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartMonthlyRevenue'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($rows, 'month')) ?>,
    datasets: [{
      label: 'Revenue',
      data: <?= json_encode(array_column($rows, 'total')) ?>,
      borderColor: '#ff6a1a',
      backgroundColor: 'rgba(255,106,26,.15)',
      fill: true,
      tension: .35,
    }],
  },
  options: {
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.06)' } }, x: { grid: { display: false } } },
  },
});
</script>
