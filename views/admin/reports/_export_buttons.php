<?php
/** Appends export format to the active report filters. */
?>
<?php if (Permission::can('reports', 'export')): ?>
<div class="d-flex gap-2 mb-3">
  <a href="?<?= e(http_build_query(array_merge($_GET, ['export' => 'xlsx']))) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> Export Excel</a>
  <a href="?<?= e(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-filetype-csv"></i> Export CSV</a>
  <a href="?<?= e(http_build_query(array_merge($_GET, ['export' => 'pdf']))) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-filetype-pdf"></i> Export PDF</a>
</div>
<?php endif; ?>
