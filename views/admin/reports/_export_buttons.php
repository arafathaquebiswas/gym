<?php
/** Appends export format to the active report filters. */
?>
<?php if (Permission::can('reports', 'export')): ?>
<div class="d-flex gap-2 mb-3">
  <button type="button" class="btn btn-ps btn-sm" data-export-module="reports"><i class="bi bi-download me-1"></i> Export Options</button>
  <a href="?<?= e(http_build_query(array_merge($_GET, ['export' => 'xlsx']))) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</a>
  <a href="?<?= e(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-filetype-csv"></i> CSV</a>
  <a href="?<?= e(http_build_query(array_merge($_GET, ['export' => 'pdf']))) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-filetype-pdf"></i> PDF</a>
</div>
<?php endif; ?>
