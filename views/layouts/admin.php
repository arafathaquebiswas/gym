<?php
/** @var string $content */
/** @var array $flashes */
$currentUser = Auth::user();
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

/**
 * Each item: [key, label, icon, href, children].
 * children (nullable) is an array of [label, href, matchPrefix, excludePrefix] used to build an
 * expandable sub-menu — cross-page shortcuts belong here, in the sidebar, never scattered as
 * ad-hoc buttons inside a page's own content.
 */
$navItems = [];
if (Permission::can('dashboard')) {
    $navItems[] = ['dashboard', 'Dashboard', 'bi-speedometer2', url('/admin'), null];
}
if (Feature::trainerModuleOn() && Permission::can('trainers')) {
    $navItems[] = ['trainers', 'Trainers', 'bi-person-badge', url('/admin/trainers'), null];
}
if (Permission::can('packages')) {
    $navItems[] = ['packages', 'Packages', 'bi-box-seam', url('/admin/packages'), null];
}
if (Feature::on('coupons') && Permission::can('coupons')) {
    $navItems[] = ['coupons', 'Coupons', 'bi-ticket-perforated', url('/admin/coupons'), null];
}
if (Permission::can('members')) {
    $navItems[] = ['members', 'Members', 'bi-people', url('/admin/members'), null];
}
if (Feature::on('store') && Permission::can('store')) {
    $navItems[] = ['products', 'Store', 'bi-shop', url('/admin/products'), [
        ['Products', url('/admin/products'), 'admin/products', 'admin/products/sales'],
        ['Categories', url('/admin/categories'), 'admin/categories', null],
        ['Attributes', url('/admin/attributes'), 'admin/attributes', null],
        ['Brands', url('/admin/brands'), 'admin/brands', null],
        ['Suppliers', url('/admin/suppliers'), 'admin/suppliers', null],
        ['Purchases', url('/admin/purchases'), 'admin/purchases', null],
        ['Sales', url('/admin/products/sales'), 'admin/products/sales', null],
    ]];
}
if (Permission::can('pos')) {
    $navItems[] = ['pos', 'POS', 'bi-calculator', url('/admin/pos'), null];
}
if (Feature::on('store') && Permission::can('orders')) {
    $navItems[] = ['orders', 'Orders', 'bi-bag-check', url('/admin/orders'), null];
}
if (Feature::deliveryOn() && Permission::can('delivery_staff')) {
    $navItems[] = ['delivery-staff', 'Delivery Staff', 'bi-truck', url('/admin/delivery-staff'), null];
}
if (Permission::can('reports')) {
    $navItems[] = ['reports', 'Reports', 'bi-bar-chart', url('/admin/reports'), null];
}
if (Permission::can('messages')) {
    $navItems[] = ['messages', 'Messages', 'bi-envelope', url('/admin/messages'), null];
}
if (Feature::on('reviews') && Permission::can('reviews')) {
    $navItems[] = ['reviews', 'Reviews', 'bi-star', url('/admin/reviews'), null];
}
if (Permission::can('audit_logs')) {
    $navItems[] = ['audit-log', 'Audit Log', 'bi-clock-history', url('/admin/audit-log'), null];
}
if (Permission::can('settings')) {
    $navItems[] = ['settings', 'Settings', 'bi-gear', url('/admin/settings'), null];
}
if (Auth::hasRole('main_admin', 'super_admin')) {
    $navItems[] = ['roles', 'Role Management', 'bi-shield-lock', url('/admin/roles'), null];
}
$unreadMessageCount = (new ContactMessage())->newCount();
$newOrderCount = (new Order())->statusCounts()['pending'] ?? 0;
$pendingReviewCount = (new ProductReview())->pendingCount();

$childIsActive = function (array $child) use ($currentPath): bool {
    [, , $prefix, $exclude] = $child;
    if ($exclude && str_starts_with($currentPath, $exclude)) {
        return false;
    }
    return str_starts_with($currentPath, $prefix);
};
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
            <?php foreach ($navItems as [$key, $label, $icon, $href, $children]): ?>
                <?php
                $groupActive = $children ? array_reduce($children, fn ($carry, $c) => $carry || $childIsActive($c), false) : false;
                $isActive = $groupActive
                    || str_starts_with($currentPath, 'admin/' . $key)
                    || ($key === 'dashboard' && $currentPath === 'admin');
                ?>
                <?php if ($children): ?>
                <a href="#navGroup<?= $key ?>" class="admin-nav-link <?= $isActive ? 'active' : '' ?> d-flex align-items-center" data-bs-toggle="collapse" role="button" aria-expanded="<?= $isActive ? 'true' : 'false' ?>">
                    <i class="bi <?= e($icon) ?>"></i> <?= e($label) ?>
                    <i class="bi bi-chevron-down ms-auto small nav-group-chevron"></i>
                </a>
                <div class="collapse <?= $isActive ? 'show' : '' ?>" id="navGroup<?= $key ?>">
                    <div class="admin-nav-sub">
                        <?php foreach ($children as $child): [$childLabel, $childHref] = $child; ?>
                            <a href="<?= e($childHref) ?>" class="admin-nav-sublink <?= $childIsActive($child) ? 'active' : '' ?>"><?= e($childLabel) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <a href="<?= e($href) ?>" class="admin-nav-link <?= $isActive ? 'active' : '' ?>">
                    <i class="bi <?= e($icon) ?>"></i> <?= e($label) ?>
                    <?php if ($key === 'messages' && $unreadMessageCount > 0): ?><span class="badge text-bg-success ms-1"><?= (int) $unreadMessageCount ?></span><?php endif; ?>
                    <?php if ($key === 'orders' && $newOrderCount > 0): ?><span class="badge text-bg-success ms-1"><?= (int) $newOrderCount ?></span><?php endif; ?>
                    <?php if ($key === 'reviews' && $pendingReviewCount > 0): ?><span class="badge text-bg-success ms-1"><?= (int) $pendingReviewCount ?></span><?php endif; ?>
                </a>
                <?php endif; ?>
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
<script src="<?= asset('js/payment-method-toggle.js') ?>"></script>
<script src="<?= asset('js/membership-payment-fields.js') ?>"></script>
<script src="<?= asset('js/password-toggle.js') ?>"></script>
<script src="<?= asset('js/admin-details-toggle.js') ?>"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $script): ?>
<script src="<?= asset($script) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
