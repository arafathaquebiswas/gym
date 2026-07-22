<?php
/** @var array|null $currentUser set by views/layouts/main.php before this partial is required */
$navItems = [
    'home' => ['Home', '/'],
    'about' => ['About', '/about'],
    'membership' => ['Membership Plans', '/membership'],
    'personal-training' => ['Personal Training', '/personal-training'],
    'store' => ['Store', '/store'],
    'gallery' => ['Gallery', '/gallery'],
    'blog' => ['Blog', '/blog'],
    'faq' => ['FAQ', '/faq'],
    'contact' => ['Contact', '/contact'],
];
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
if ($currentPath === '') { $currentPath = 'home'; }
?>
<nav class="navbar navbar-expand-lg navbar-ps sticky-top py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('/') ?>">
      <img src="<?= asset('images/logo/logo.png') ?>" alt="PowerSurge Gym" height="42">
      Power<span>Surge</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#psNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="psNav">
      <ul class="navbar-nav mx-auto">
        <?php foreach ($navItems as $key => [$label, $href]): ?>
          <li class="nav-item">
            <a class="nav-link <?= $currentPath === $key || ($key === 'home' && $currentPath === '') ? 'active' : '' ?>" href="<?= url($href) ?>"><?= e($label) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="d-flex gap-2">
        <?php if ($currentUser): ?>
          <a href="<?= url(Auth::isStaff() ? '/admin' : '/account') ?>" class="btn btn-ps-outline btn-sm"><i class="bi bi-person-circle"></i> <?= e($currentUser['name']) ?></a>
          <a href="<?= url('/logout') ?>" class="btn btn-ps btn-sm">Logout</a>
        <?php else: ?>
          <a href="<?= url('/login') ?>" class="btn btn-ps-outline btn-sm">Login</a>
          <a href="<?= url('/register') ?>" class="btn btn-ps btn-sm">Join Now</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
