<?php
/** @var array|null $currentUser set by views/layouts/main.php before this partial is required */
$navItems = [
    'home' => ['Home', '/'],
    'about' => ['About', '/about'],
    'membership' => ['Membership Plans', '/membership'],
];
if (Feature::trainerModuleOn()) {
    $navItems['personal-training'] = ['Personal Training', '/personal-training'];
}
if (Feature::on('store')) {
    $navItems['store'] = ['Store', '/store'];
}
if (Feature::on('gallery')) {
    $navItems['gallery'] = ['Gallery', '/gallery'];
}
if (Feature::on('blog')) {
    $navItems['blog'] = ['Blog', '/blog'];
}
$navItems['faq'] = ['FAQ', '/faq'];
$navItems['contact'] = ['Contact', '/contact'];
if (!$currentUser) {
    $navItems['login'] = ['Staff Login', '/login'];
}
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
if ($currentPath === '') { $currentPath = 'home'; }
$cartIdentity = Cart::identity();
$cartCount = (new Cart())->count($cartIdentity['user_id'], $cartIdentity['cart_token']);
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-ps sticky-top py-2">
  <div class="container navbar-ps-container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('/') ?>">
      <img src="<?= asset('images/logo/logo.png') ?>" alt="PowerSurge Gym">
      Power<span>Surge</span>
    </a>
    <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#psNav" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="psNav">
      <ul class="navbar-nav navbar-ps-links">
        <?php foreach ($navItems as $key => [$label, $href]): ?>
          <li class="nav-item">
            <a class="nav-link <?= $currentPath === $key || ($key === 'home' && $currentPath === '') ? 'active' : '' ?>" href="<?= url($href) ?>"><?= e($label) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="navbar-ps-actions d-flex gap-2 align-items-center">
        <?php if (Feature::on('store')): ?>
        <a href="<?= url('/cart') ?>" class="btn btn-ps-outline btn-sm position-relative">
          <i class="bi bi-cart3"></i>
          <?php if ($cartCount > 0): ?>
            <span class="badge-ps badge position-absolute top-0 start-100 translate-middle rounded-pill"><?= (int) $cartCount ?></span>
          <?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if ($currentUser): ?>
          <?php /* Only staff/delivery can ever be logged in here — there is no member-facing account. */ ?>
          <a href="<?= url(Auth::hasRole('delivery') ? '/delivery' : '/admin') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-person-circle"></i> <?= e($currentUser['name']) ?></a>
          <a href="<?= url('/logout') ?>" class="btn btn-ps btn-sm">Logout</a>
        <?php else: ?>
          <a href="<?= url('/register') ?>" class="btn btn-ps btn-sm">Join Now</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
