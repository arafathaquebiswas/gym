<?php
$pageTitle = 'Home';
/** @var array $packages */
/** @var array $products */
/** @var array $trainers */
/** @var array $galleryItems */
/** @var array $testimonials */
/** @var array $promotions */
/** @var array $faqs */
/** @var array $settings */
?>

<!-- HERO -->
<section class="hero hero-photo" style="background-image: url('<?= asset('images/pic/1stpic.jpg') ?>');">
  <div class="hero-overlay"></div>
  <div class="container position-relative">
    <div class="row">
      <div class="col-lg-7">
        <span class="hero-badge"><i class="bi bi-fire"></i> Bangladesh's Modern Fitness Center</span>
        <h1>Train Hard. <br><span class="text-orange">Surge</span> Ahead.</h1>
        <p class="lead mt-3">PowerSurge Gym gives you world-class equipment, certified trainers, and a community that pushes you further — every single day.</p>
        <div class="d-flex gap-3 mt-4 flex-wrap">
          <a href="<?= url('/register') ?>" class="btn btn-ps btn-lg">Join Now <i class="bi bi-arrow-right"></i></a>
          <a href="<?= url('/membership') ?>" class="btn btn-ps-outline btn-lg">View Plans</a>
        </div>
        <div class="hero-stats">
          <div><div class="stat-num">1200+</div><div class="stat-label">Active Members</div></div>
          <div><div class="stat-num">15+</div><div class="stat-label">Certified Trainers</div></div>
          <div><div class="stat-num">8</div><div class="stat-label">Years Running</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CALL TO ACTION -->
<section class="py-4" style="background: linear-gradient(135deg, var(--ps-orange), var(--ps-orange-dark));">
  <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 text-white">
    <h4 class="mb-0"><i class="bi bi-calendar-check"></i> Free trial session this week — no commitment required.</h4>
    <a href="<?= url('/contact') ?>" class="btn btn-light fw-bold">Book Your Free Session</a>
  </div>
</section>

<!-- MEMBERSHIP PLANS -->
<section class="section" id="plans">
  <div class="container text-center">
    <h2 class="section-title">Membership Plans</h2>
    <p class="section-subtitle mx-auto">Flexible plans built around your goals — upgrade, freeze, or renew anytime.</p>
    <div class="row g-4 justify-content-center">
      <?php foreach (array_slice($packages, 0, 4) as $pkg): ?>
      <div class="col-md-6 col-lg-4">
        <?php $this->partial('partials/package-card', ['pkg' => $pkg, 'showFeatures' => false, 'ctaLabel' => 'See Details', 'ctaUrl' => url('/membership')]); ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- WHY CHOOSE US -->
<section class="section bg-ps-black">
  <div class="container text-center">
    <h2 class="section-title">Why Choose PowerSurge</h2>
    <p class="section-subtitle mx-auto">Everything you need to build strength, confidence, and consistency.</p>
    <div class="row g-4">
      <?php
      $whyUs = [
          ['bi-cpu', 'Modern Equipment', 'Premium strength and cardio machines, maintained daily.'],
          ['bi-person-badge', 'Certified Trainers', 'Every trainer is certified and specializes in real results.'],
          ['bi-clock-history', 'Flexible Hours', 'Sat–Thu 7:00 AM–11:00 PM, Fri 5:00 PM–10:00 PM.'],
          ['bi-shield-check', 'Safe & Clean', 'Sanitized equipment and a safety-first culture.'],
      ];
      foreach ($whyUs as [$icon, $title, $desc]):
      ?>
      <div class="col-6 col-lg-3">
        <div class="glass-card p-4 h-100">
          <i class="bi <?= $icon ?> text-orange fs-1"></i>
          <h6 class="mt-3"><?= e($title) ?></h6>
          <p class="text-white-50 small mb-0"><?= e($desc) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TRAINERS -->
<section class="section">
  <div class="container text-center">
    <h2 class="section-title">Meet Our Trainers</h2>
    <p class="section-subtitle mx-auto">Experienced professionals dedicated to your progress.</p>
    <div class="row g-4">
      <?php foreach ($trainers as $trainer): ?>
      <div class="col-md-4">
        <?php $this->partial('partials/trainer-card', ['trainer' => $trainer]); ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- TRANSFORMATION GALLERY -->
<section class="section bg-ps-black">
  <div class="container text-center">
    <h2 class="section-title">Transformation Gallery</h2>
    <p class="section-subtitle mx-auto">Real members, real results.</p>
    <div class="row g-3">
      <?php foreach ($galleryItems as $item): ?>
      <div class="col-6 col-md-3">
        <div class="gallery-item">
          <?= media_tile($item['image_path'], $item['title'] ?? 'Gallery photo') ?>
          <div class="gallery-caption"><?= e($item['title'] ?? '') ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <a href="<?= url('/gallery') ?>" class="btn btn-ps-outline mt-4">View Full Gallery</a>
  </div>
</section>

<!-- CUSTOMER REVIEWS -->
<section class="section">
  <div class="container text-center">
    <h2 class="section-title">What Our Members Say</h2>
    <div class="row g-4">
      <?php foreach ($testimonials as $t): ?>
      <div class="col-md-4">
        <div class="glass-card testimonial-card text-start h-100">
          <div class="stars"><?= str_repeat('<i class="bi bi-star-fill"></i> ', (int) $t['rating']) ?></div>
          <p class="text-white-50">&ldquo;<?= e($t['message']) ?>&rdquo;</p>
          <strong><?= e($t['member_name']) ?></strong>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- PRODUCTS -->
<section class="section bg-ps-black">
  <div class="container text-center">
    <h2 class="section-title">From the Store</h2>
    <p class="section-subtitle mx-auto">Supplements and gear to fuel your training.</p>
    <div class="row g-4">
      <?php foreach (array_slice($products, 0, 4) as $product): ?>
      <div class="col-6 col-lg-3">
        <div class="glass-card product-card text-start">
          <a href="<?= url('/store/' . $product['slug']) ?>" class="text-decoration-none">
            <div class="product-thumb"><?= media_tile($product['image'], $product['name'], 'bi-box-seam') ?></div>
            <div class="cat-tag"><?= e($product['brand'] ?? '') ?></div>
            <h6 class="mb-1 text-white"><?= e($product['name']) ?></h6>
            <?php if (!empty($product['offer_is_live'])): ?>
              <div class="price">৳<?= number_format((float) $product['offer_price']) ?> <small class="text-white-50 text-decoration-line-through">৳<?= number_format((float) $product['selling_price']) ?></small></div>
            <?php else: ?>
              <div class="price">৳<?= number_format((float) $product['selling_price']) ?></div>
            <?php endif; ?>
          </a>
          <?php if ((int) $product['stock_qty'] > 0 || $product['allow_preorder']): ?>
          <form method="post" action="<?= url('/cart/add') ?>" class="mt-2">
            <?= Security::csrfField() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <input type="hidden" name="qty" value="1">
            <button type="submit" class="btn btn-ps-outline btn-sm w-100"><i class="bi bi-cart-plus"></i> Add to Cart</button>
          </form>
          <?php else: ?>
          <span class="badge bg-danger mt-2">Out of Stock</span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <a href="<?= url('/store') ?>" class="btn btn-ps mt-4">Visit Store</a>
  </div>
</section>

<!-- LATEST OFFERS -->
<?php if (!empty($promotions)): ?>
<section class="section">
  <div class="container text-center">
    <h2 class="section-title">Latest Offers</h2>
    <div class="row g-4">
      <?php foreach ($promotions as $promo): ?>
      <div class="col-md-6 col-lg-3">
        <div class="glass-card p-4 text-start h-100">
          <span class="badge-ps badge px-3 py-2 mb-2"><?= e($promo['code'] ?? 'OFFER') ?></span>
          <h6><?= e($promo['title']) ?></h6>
          <p class="text-white-50 small mb-0"><?= e($promo['description']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- BMI CALCULATOR -->
<section class="section bg-ps-black" id="bmi">
  <div class="container">
    <h2 class="section-title text-center">BMI Calculator</h2>
    <p class="section-subtitle mx-auto text-center">Check your Body Mass Index in seconds — no account needed.</p>

    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="glass-card bmi-widget">

          <div class="row g-4">
            <div class="col-md-6">
              <label class="bmi-field-label">Height</label>
              <div class="unit-tabs">
                <button type="button" class="unit-tab active" data-height-unit="ft">Feet &amp; Inches</button>
                <button type="button" class="unit-tab" data-height-unit="in">Inches</button>
                <button type="button" class="unit-tab" data-height-unit="cm">Centimeters</button>
              </div>
              <div class="bmi-input-group" data-height-panel="ft">
                <div class="row g-2">
                  <div class="col-6"><input type="number" step="any" min="0" inputmode="decimal" class="form-control form-ps-input" id="bmiFeet" placeholder="Feet"></div>
                  <div class="col-6"><input type="number" step="any" min="0" inputmode="decimal" class="form-control form-ps-input" id="bmiInches" placeholder="Inches"></div>
                </div>
              </div>
              <div class="bmi-input-group d-none" data-height-panel="in">
                <input type="number" step="any" min="0" inputmode="decimal" class="form-control form-ps-input" id="bmiHeightIn" placeholder="Height in inches">
              </div>
              <div class="bmi-input-group d-none" data-height-panel="cm">
                <input type="number" step="any" min="0" inputmode="decimal" class="form-control form-ps-input" id="bmiHeightCm" placeholder="Height in centimeters">
              </div>
            </div>

            <div class="col-md-6">
              <label class="bmi-field-label">Weight</label>
              <div class="unit-tabs">
                <button type="button" class="unit-tab active" data-weight-unit="kg">Kilograms</button>
                <button type="button" class="unit-tab" data-weight-unit="lbs">Pounds</button>
              </div>
              <div class="bmi-input-group">
                <input type="number" step="any" min="0" inputmode="decimal" class="form-control form-ps-input" id="bmiWeight" placeholder="Weight">
              </div>
            </div>
          </div>

          <div id="bmiError" class="text-danger small mt-3 d-none"></div>

          <div class="d-flex gap-3 mt-4 flex-wrap">
            <button type="button" id="bmiCalculateBtn" class="btn btn-ps btn-lg flex-grow-1">Calculate BMI</button>
            <button type="button" id="bmiResetBtn" class="btn btn-ps-outline btn-lg">Reset</button>
          </div>

          <!-- Result -->
          <div id="bmiResult" class="bmi-result-box mt-4 d-none">
            <div class="text-center">
              <div class="bmi-score-label">Your BMI</div>
              <div class="bmi-score" id="bmiScoreValue">0.0</div>
              <span class="bmi-badge" id="bmiCategoryBadge">Normal</span>
            </div>

            <div class="bmi-progress mt-4">
              <div class="bmi-progress-track">
                <div class="bmi-zone bmi-zone-under"></div>
                <div class="bmi-zone bmi-zone-normal"></div>
                <div class="bmi-zone bmi-zone-over"></div>
                <div class="bmi-zone bmi-zone-obese"></div>
                <div class="bmi-marker" id="bmiMarker">&#9650;</div>
              </div>
              <div class="bmi-progress-labels">
                <span>Underweight</span><span>Normal</span><span>Overweight</span><span>Obese</span>
              </div>
            </div>

            <div class="row g-3 mt-3">
              <div class="col-6 col-md-3">
                <div class="bmi-stat text-center"><div class="bmi-stat-label">BMI Prime</div><div class="bmi-stat-value" id="bmiPrimeValue">-</div></div>
              </div>
              <div class="col-6 col-md-3">
                <div class="bmi-stat text-center"><div class="bmi-stat-label">Healthy Range</div><div class="bmi-stat-value" id="bmiRangeValue">-</div></div>
              </div>
              <div class="col-12 col-md-6">
                <div class="bmi-stat text-center"><div class="bmi-stat-label">Suggestion</div><div class="bmi-stat-value small" id="bmiSuggestionValue">-</div></div>
              </div>
            </div>

            <div class="d-flex gap-2 justify-content-center mt-4 flex-wrap">
              <button type="button" id="bmiCopyBtn" class="btn btn-ps-outline btn-sm"><i class="bi bi-clipboard"></i> Copy Result</button>
              <button type="button" id="bmiPrintBtn" class="btn btn-ps-outline btn-sm"><i class="bi bi-printer"></i> Print</button>
              <button type="button" id="bmiPdfBtn" class="btn btn-ps-outline btn-sm"><i class="bi bi-file-earmark-pdf"></i> Download PDF</button>
            </div>
          </div>

          <!-- WHO reference table -->
          <div class="mt-4">
            <div class="table-responsive">
              <table class="table table-dark table-borderless bmi-table" id="bmiWhoTable">
                <thead><tr class="text-white-50 text-uppercase small"><th>Classification</th><th>BMI</th></tr></thead>
                <tbody>
                  <tr data-min="0" data-max="16"><td>Severe Thinness</td><td>&lt; 16</td></tr>
                  <tr data-min="16" data-max="17"><td>Moderate Thinness</td><td>16 &ndash; 17</td></tr>
                  <tr data-min="17" data-max="18.5"><td>Mild Thinness</td><td>17 &ndash; 18.5</td></tr>
                  <tr data-min="18.5" data-max="25"><td>Normal</td><td>18.5 &ndash; 24.9</td></tr>
                  <tr data-min="25" data-max="30"><td>Overweight</td><td>25 &ndash; 29.9</td></tr>
                  <tr data-min="30" data-max="35"><td>Obese Class I</td><td>30 &ndash; 34.9</td></tr>
                  <tr data-min="35" data-max="40"><td>Obese Class II</td><td>35 &ndash; 39.9</td></tr>
                  <tr data-min="40" data-max="999"><td>Obese Class III</td><td>40+</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- More details -->
          <div class="mt-2">
            <button class="btn btn-link text-orange text-decoration-none p-0" type="button" data-bs-toggle="collapse" data-bs-target="#bmiMoreDetails">
              <i class="bi bi-chevron-down"></i> More Details
            </button>
            <div class="collapse mt-3" id="bmiMoreDetails">
              <div class="bmi-details-card">
                <p><strong>BMI Formula:</strong> BMI = weight (kg) &divide; height (m)&sup2;</p>
                <p><strong>BMI Prime Formula:</strong> BMI Prime = BMI &divide; 25 (the upper limit of the normal range). A value of 1.0 sits exactly at that limit — under 1.0 is within the normal range.</p>
                <p class="mb-0"><strong>Ponderal Index:</strong> PI = weight (kg) &divide; height (m)&sup3; &mdash; similar to BMI but scales better for people who are very tall or very short. Your Ponderal Index: <span id="bmiPonderalValue">-</span> kg/m&sup3;</p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section class="section">
  <div class="container">
    <h2 class="section-title text-center">Frequently Asked Questions</h2>
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="accordion accordion-ps" id="homeFaq">
          <?php foreach (array_slice($faqs, 0, 5) as $i => $faq): ?>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                <?= e($faq['question']) ?>
              </button>
            </h2>
            <div id="faq<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#homeFaq">
              <div class="accordion-body"><?= e($faq['answer']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- MAP + CONTACT -->
<section class="section bg-ps-black">
  <div class="container">
    <h2 class="section-title text-center">Visit Us</h2>
    <div class="row g-4 mt-2 justify-content-center">
      <?php if (!empty($settings['google_map_embed'])): ?>
      <div class="col-lg-6">
        <div class="map-frame">
          <iframe src="<?= e($settings['google_map_embed']) ?>" width="100%" height="400" style="border:0;" loading="lazy"></iframe>
        </div>
      </div>
      <?php endif; ?>
      <div class="col-lg-6">
        <div class="glass-card p-4">
          <h5 class="mb-3">Send Us a Message</h5>
          <div id="contactAlert" class="alert d-none"></div>
          <form id="contactForm" class="form-ps needs-validation" action="<?= url('/api/contact.php') ?>" method="post" novalidate>
            <input type="hidden" name="_csrf" value="<?= e(Security::csrfToken()) ?>">
            <div class="row g-3">
              <div class="col-md-6">
                <label>Name</label>
                <input type="text" name="name" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="col-12">
                <label>Message</label>
                <textarea name="message" class="form-control" rows="4" required></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-ps w-100">Send Message</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php $extraScripts = ['js/contact.js', 'js/bmi-calculator.js', 'js/pricing-countdown.js', 'https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js']; ?>
