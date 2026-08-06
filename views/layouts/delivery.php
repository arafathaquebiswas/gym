<?php
/** @var string $content */
/** @var array $flashes */
$currentUser = Auth::user();
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Delivery') ?> | PowerSurge Delivery</title>
    <meta name="csrf-token" content="<?= e(Security::csrfToken()) ?>">
    <link rel="icon" type="image/png" href="<?= asset('images/logo/logo.png') ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('css/style.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/admin.css') ?>" rel="stylesheet">
</head>
<body class="admin-body">

<div class="admin-shell">
    <div class="admin-overlay" id="deliveryOverlay" hidden></div>
    <aside class="admin-sidebar" id="deliverySidebar">
        <a href="<?= url('/delivery') ?>" class="admin-brand">
            <img src="<?= asset('images/logo/logo.png') ?>" alt="PowerSurge Gym" height="34">
            <span>Power<span class="text-orange">Surge</span> Delivery</span>
        </a>
        <nav class="admin-nav">
            <a href="<?= url('/delivery') ?>" class="admin-nav-link <?= $currentPath === 'delivery' ? 'active' : '' ?>">
                <i class="bi bi-truck"></i> My Deliveries
            </a>
            <a href="<?= url('/delivery/history') ?>" class="admin-nav-link <?= $currentPath === 'delivery/history' ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i> Delivery History
            </a>
            <a href="<?= url('/delivery/profile') ?>" class="admin-nav-link <?= $currentPath === 'delivery/profile' ? 'active' : '' ?>">
                <i class="bi bi-person"></i> Profile
            </a>
        </nav>
        <div class="admin-sidebar-footer">
            <a href="<?= url('/logout') ?>" class="admin-nav-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar-start">
                <button class="admin-sidebar-toggle" type="button" aria-label="Toggle navigation" aria-controls="deliverySidebar" aria-expanded="false">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0"><?= e($pageTitle ?? 'My Deliveries') ?></h5>
            </div>
            <div class="admin-user">
                <span class="admin-user-pill"><i class="bi bi-person-circle"></i> <?= e($currentUser['name'] ?? '') ?></span>
            </div>
        </header>

        <?php if (!empty($flashes)): ?>
        <div class="px-4 pt-3">
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <main class="admin-content"><?= $content ?></main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var shell = document.querySelector('.admin-shell');
  var sidebar = document.getElementById('deliverySidebar');
  var toggle = document.querySelector('.admin-sidebar-toggle');
  var overlay = document.getElementById('deliveryOverlay');
  var drawerQuery = window.matchMedia('(max-width: 1023px)');
  if (!shell || !sidebar || !toggle || !overlay) return;

  function setDrawerOpen(open) {
    if (!drawerQuery.matches) open = false;
    shell.classList.toggle('admin-sidebar-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    overlay.hidden = !open;
  }

  toggle.addEventListener('click', function () {
    setDrawerOpen(!shell.classList.contains('admin-sidebar-open'));
  });
  overlay.addEventListener('click', function () { setDrawerOpen(false); });
  drawerQuery.addEventListener('change', function () { setDrawerOpen(false); });
  setDrawerOpen(false);
})();

window.printUrlSilently = function(url, btnElement) {
  var originalHtml = '';
  if (btnElement) {
    originalHtml = btnElement.innerHTML;
    btnElement.disabled = true;
    btnElement.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Printing...';
  }

  var iframe = document.getElementById('ps-silent-print-iframe');
  if (!iframe) {
    iframe = document.createElement('iframe');
    iframe.id = 'ps-silent-print-iframe';
    iframe.style.position = 'fixed';
    iframe.style.right = '-9999px';
    iframe.style.bottom = '-9999px';
    iframe.style.width = '1px';
    iframe.style.height = '1px';
    iframe.style.border = '0';
    document.body.appendChild(iframe);
  }

  var hasPrinted = false;
  function triggerPrint() {
    if (hasPrinted) return;
    hasPrinted = true;
    try {
      iframe.contentWindow.focus();
      iframe.contentWindow.print();
    } catch (err) {
      console.error('Silent print error:', err);
      window.open(url, '_blank');
    } finally {
      if (btnElement) {
        setTimeout(function() {
          btnElement.disabled = false;
          btnElement.innerHTML = originalHtml;
        }, 1000);
      }
    }
  }

  iframe.onload = function() {
    setTimeout(triggerPrint, 300);
  };

  iframe.src = url;

  setTimeout(function() {
    if (!hasPrinted) {
      triggerPrint();
    }
  }, 1800);
};
</script>
</body>
</html>
