<?php
/** @var string $from */
/** @var string $to */
?>
<form method="get" class="admin-toolbar admin-form mb-3">
  <label class="text-white-50 small mb-0 align-self-center">From</label>
  <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
  <label class="text-white-50 small mb-0 align-self-center">To</label>
  <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
  <button type="submit" class="btn btn-ps-outline btn-sm">Apply</button>
</form>
