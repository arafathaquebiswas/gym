<?php
/** Appends export=csv/pdf to whatever filters are already in the URL — include on every report view, with or without a date filter. */
?>
<div class="d-flex gap-2 mb-3">
  <a href="?<?= e(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-filetype-csv"></i> Export CSV</a>
  <a href="?<?= e(http_build_query(array_merge($_GET, ['export' => 'pdf']))) ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-filetype-pdf"></i> Export PDF</a>
</div>
