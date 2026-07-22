<?php $pageTitle = 'FAQ'; /** @var array $faqs */
$grouped = [];
foreach ($faqs as $faq) {
    $grouped[$faq['category'] ?: 'general'][] = $faq;
}
?>

<section class="hero py-5 text-center">
  <div class="container">
    <span class="hero-badge">FAQ</span>
    <h1>Frequently Asked <span class="text-orange">Questions</span></h1>
    <p class="lead mx-auto" style="max-width:600px">Can't find what you're looking for? <a href="<?= url('/contact') ?>">Contact us</a> directly.</p>
  </div>
</section>

<section class="section pt-0">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <?php foreach ($grouped as $category => $items): ?>
        <h5 class="text-orange text-uppercase mb-3 mt-4"><?= e($category) ?></h5>
        <div class="accordion accordion-ps mb-4" id="faq-<?= e($category) ?>">
          <?php foreach ($items as $i => $faq): $id = e($category) . $i; ?>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q<?= $id ?>">
                <?= e($faq['question']) ?>
              </button>
            </h2>
            <div id="q<?= $id ?>" class="accordion-collapse collapse" data-bs-parent="#faq-<?= e($category) ?>">
              <div class="accordion-body"><?= e($faq['answer']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
