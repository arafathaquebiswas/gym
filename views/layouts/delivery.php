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
    <aside class="admin-sidebar">
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
            <h5 class="mb-0"><?= e($pageTitle ?? 'My Deliveries') ?></h5>
            <div class="admin-user">
                <i class="bi bi-person-circle"></i> <?= e($currentUser['name'] ?? '') ?>
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
</body>
</html>
