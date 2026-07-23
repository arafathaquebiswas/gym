<footer class="footer-ps">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <h5 class="text-white mb-3 d-flex align-items-center gap-2">
          <img src="<?= asset('images/logo/logo.png') ?>" alt="PowerSurge Gym" height="36">
          Power<span class="text-orange">Surge</span> Gym
        </h5>
        <p><?= e($settings['gym_tagline'] ?? 'Train Hard. Surge Ahead.') ?></p>
        <div class="mt-3">
          <?php if (!empty($settings['facebook_url'])): ?>
          <a href="<?= e($settings['facebook_url']) ?>" class="social-btn"><i class="bi bi-facebook"></i></a>
          <?php endif; ?>
          <?php if (!empty($settings['instagram_url'])): ?>
          <a href="<?= e($settings['instagram_url']) ?>" class="social-btn"><i class="bi bi-instagram"></i></a>
          <?php endif; ?>
          <?php if (!empty($settings['whatsapp_number'])): ?>
          <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $settings['whatsapp_number'])) ?>" class="social-btn"><i class="bi bi-whatsapp"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Explore</h6>
        <ul class="list-unstyled">
          <li><a href="<?= url('/about') ?>">About Us</a></li>
          <li><a href="<?= url('/membership') ?>">Membership Plans</a></li>
          <?php if (Feature::trainerModuleOn()): ?><li><a href="<?= url('/personal-training') ?>">Personal Training</a></li><?php endif; ?>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6>Company</h6>
        <ul class="list-unstyled">
          <?php if (Feature::on('store')): ?>
          <li><a href="<?= url('/store') ?>">Store</a></li>
          <li><a href="<?= url('/track-order') ?>">Track Order</a></li>
          <?php endif; ?>
          <?php if (Feature::on('gallery')): ?><li><a href="<?= url('/gallery') ?>">Gallery</a></li><?php endif; ?>
          <?php if (Feature::on('blog')): ?><li><a href="<?= url('/blog') ?>">Blog</a></li><?php endif; ?>
          <li><a href="<?= url('/faq') ?>">FAQ</a></li>
        </ul>
      </div>
      <div class="col-lg-4">
        <h6>Contact</h6>
        <ul class="list-unstyled">
          <?php if (!empty($settings['gym_address'])): ?><li class="mb-2"><i class="bi bi-geo-alt text-orange me-2"></i><?= e($settings['gym_address']) ?></li><?php endif; ?>
          <?php if (!empty($settings['gym_phone'])): ?><li class="mb-2"><i class="bi bi-telephone text-orange me-2"></i><?= e($settings['gym_phone']) ?></li><?php endif; ?>
          <?php if (!empty($settings['gym_email'])): ?><li class="mb-2"><i class="bi bi-envelope text-orange me-2"></i><?= e($settings['gym_email']) ?></li><?php endif; ?>
          <?php if (!empty($settings['business_hours'])): ?><li><i class="bi bi-clock text-orange me-2"></i><?= e($settings['business_hours']) ?></li><?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="divider-ps"></div>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <small>&copy; <?= date('Y') ?> PowerSurge Gym. All rights reserved.</small>
      <small>Built with <i class="bi bi-heart-fill text-orange"></i> for a stronger you.</small>
      <small><a href="<?= url('/login') ?>" class="text-white-50">Staff Login</a></small>
    </div>
  </div>
</footer>
