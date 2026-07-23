<?php
/** @var string $content */
/** @var array $flashes */
$currentUser = Auth::user();
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

$navItems = [
    ['dashboard', 'Dashboard', 'bi-speedometer2', url('/admin')],
    ['trainers', 'Trainers', 'bi-person-badge', url('/admin/trainers')],
    ['packages', 'Packages', 'bi-box-seam', url('/admin/packages')],
    ['coupons', 'Coupons', 'bi-ticket-perforated', url('/admin/coupons')],
    ['members', 'Members', 'bi-people', url('/admin/members')],
    ['products', 'Store', 'bi-shop', url('/admin/products')],
    ['pos', 'POS', 'bi-calculator', url('/admin/pos')],
    ['orders', 'Orders', 'bi-bag-check', url('/admin/orders')],
    ['reports', 'Reports', 'bi-bar-chart', url('/admin/reports')],
    ['messages', 'Messages', 'bi-envelope', url('/admin/messages')],
    ['reviews', 'Reviews', 'bi-star', url('/admin/reviews')],
    ['audit-log', 'Audit Log', 'bi-clock-history', url('/admin/audit-log')],
    ['settings', 'Settings', 'bi-gear', url('/admin/settings')],
];
$unreadMessageCount = (new ContactMessage())->newCount();
$newOrderCount = (new Order())->statusCounts()['pending'] ?? 0;
$pendingReviewCount = (new ProductReview())->pendingCount();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin') ?> | PowerSurge Admin</title>
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
        <a href="<?= url('/admin') ?>" class="admin-brand">
            <img src="<?= asset('images/logo/logo.png') ?>" alt="PowerSurge Gym" height="34">
            <span>Power<span class="text-orange">Surge</span> Admin</span>
        </a>
        <nav class="admin-nav">
            <?php foreach ($navItems as [$key, $label, $icon, $href]): ?>
                <?php $isActive = str_starts_with($currentPath, 'admin/' . $key)
                    || ($key === 'dashboard' && $currentPath === 'admin')
                    || ($key === 'products' && str_starts_with($currentPath, 'admin/categories')); ?>
                <a href="<?= e($href) ?>" class="admin-nav-link <?= $isActive ? 'active' : '' ?>">
                    <i class="bi <?= e($icon) ?>"></i> <?= e($label) ?>
                    <?php if ($key === 'messages' && $unreadMessageCount > 0): ?><span class="badge text-bg-success ms-1"><?= (int) $unreadMessageCount ?></span><?php endif; ?>
                    <?php if ($key === 'orders' && $newOrderCount > 0): ?><span class="badge text-bg-success ms-1"><?= (int) $newOrderCount ?></span><?php endif; ?>
                    <?php if ($key === 'reviews' && $pendingReviewCount > 0): ?><span class="badge text-bg-success ms-1"><?= (int) $pendingReviewCount ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar-footer">
            <a href="<?= url('/') ?>" class="admin-nav-link"><i class="bi bi-box-arrow-up-left"></i> View Website</a>
            <a href="<?= url('/logout') ?>" class="admin-nav-link"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <h5 class="mb-0"><?= e($pageTitle ?? 'Dashboard') ?></h5>
            <div class="admin-user">
                <i class="bi bi-person-circle"></i> <?= e($currentUser['name'] ?? '') ?>
                <span class="badge-ps badge ms-2"><?= e(ucfirst(str_replace('_', ' ', $currentUser['role'] ?? ''))) ?></span>
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

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $script): ?>
<script src="<?= asset($script) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
