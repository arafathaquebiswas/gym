<?php
$pageTitle = 'Something Went Wrong';
/** @var string|null $detail Populated only when APP_DEBUG is on. */
?>

<section class="section text-center">
  <div class="container">
    <i class="bi bi-exclamation-octagon text-orange" style="font-size:4rem"></i>
    <h1 class="mt-3">500</h1>
    <p class="text-white-50">Something went wrong on our end. The team has been notified — please try again in a moment.</p>
    <a href="<?= url('/') ?>" class="btn btn-ps">Back to Home</a>

    <?php if (!empty($detail)): ?>
      <pre class="text-start text-danger mt-4 p-3 bg-black rounded" style="white-space:pre-wrap"><?= e($detail) ?></pre>
    <?php endif; ?>
  </div>
</section>
