<?php $pageTitle = 'Contact'; /** @var array $settings */ ?>

<section class="hero py-5 text-center">
  <div class="container">
    <span class="hero-badge">Contact</span>
    <h1>Get In <span class="text-orange">Touch</span></h1>
    <p class="lead mx-auto" style="max-width:600px">Questions about membership, training, or the store? We're here to help.</p>
  </div>
</section>

<section class="section pt-0">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <?php if (!empty($settings['gym_address'])): ?>
        <div class="glass-card p-4 mb-3">
          <i class="bi bi-geo-alt text-orange fs-3"></i>
          <h6 class="mt-2">Address</h6>
          <p class="text-white-50 mb-0"><?= e($settings['gym_address']) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($settings['gym_phone'])): ?>
        <div class="glass-card p-4 mb-3">
          <i class="bi bi-telephone text-orange fs-3"></i>
          <h6 class="mt-2">Phone</h6>
          <p class="text-white-50 mb-0"><?= e($settings['gym_phone']) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($settings['gym_email'])): ?>
        <div class="glass-card p-4 mb-3">
          <i class="bi bi-envelope text-orange fs-3"></i>
          <h6 class="mt-2">Email</h6>
          <p class="text-white-50 mb-0"><?= e($settings['gym_email']) ?></p>
        </div>
        <?php endif; ?>
        <?php if (!empty($settings['whatsapp_number'])): ?>
        <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $settings['whatsapp_number'])) ?>" class="btn btn-ps w-100">
          <i class="bi bi-whatsapp"></i> Chat on WhatsApp
        </a>
        <?php endif; ?>
      </div>
      <div class="col-lg-8">
        <?php if (Feature::on('contact_form')): ?>
        <div class="glass-card p-4 mb-4">
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
              <div class="col-md-6">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control">
              </div>
              <div class="col-md-6">
                <label>Subject</label>
                <input type="text" name="subject" class="form-control">
              </div>
              <div class="col-12">
                <label>Message</label>
                <textarea name="message" class="form-control" rows="5" required></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-ps px-5">Send Message</button>
              </div>
            </div>
          </form>
        </div>
        <?php endif; ?>
        <?php if (!empty($settings['google_map_embed'])): ?>
        <div class="map-frame">
          <iframe src="<?= e($settings['google_map_embed']) ?>" width="100%" height="350" style="border:0;" loading="lazy"></iframe>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php $extraScripts = ['js/contact.js']; ?>
