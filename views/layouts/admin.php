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
    <div class="admin-overlay" id="adminOverlay" hidden></div>
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="<?= url('/admin') ?>" class="admin-brand">
            <img src="<?= asset('images/logo/logo.png') ?>" alt="PowerSurge Gym" height="34">
            <span>Power<span class="text-orange">Surge</span> Admin</span>
        </a>
        <nav class="admin-nav">
            <?php $navSection = null; foreach ($navItems as [$key, $label, $icon, $href, $children]): ?>
                <?php
                $sectionMap = [
                    'dashboard' => 'Overview',
                    'members' => 'Members & Training',
                    'packages' => 'Members & Training',
                    'trainers' => 'Members & Training',
                    'products' => 'Store & Commerce',
                    'pos' => 'Store & Commerce',
                    'orders' => 'Store & Commerce',
                    'reports' => 'Insights',
                    'messages' => 'Insights',
                    'reviews' => 'Insights',
                    'audit-log' => 'Insights',
                    'delivery-staff' => 'Staff & System',
                    'roles' => 'Staff & System',
                    'settings' => 'Staff & System',
                ];
                $sectionLabel = $sectionMap[$key] ?? null;
                if ($sectionLabel && $sectionLabel !== $navSection) {
                    $navSection = $sectionLabel;
                    echo '<div class="admin-nav-section">' . e($sectionLabel) . '</div>';
                }
                ?>
                <?php
                $groupActive = $children ? array_reduce($children, fn ($carry, $c) => $carry || $childIsActive($c), false) : false;
                $isActive = $groupActive
                    || str_starts_with($currentPath, 'admin/' . $key)
                    || ($key === 'dashboard' && $currentPath === 'admin');
                ?>
                <?php if ($children): ?>
                <a href="#navGroup<?= $key ?>" class="admin-nav-link <?= $isActive ? 'active' : '' ?> d-flex align-items-center" data-nav-group="<?= e($key) ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?= $isActive ? 'true' : 'false' ?>">
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
            <div class="admin-topbar-start">
                <button class="admin-sidebar-toggle" type="button" aria-label="Toggle navigation" aria-controls="adminSidebar" aria-expanded="false">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0"><?= e($pageTitle ?? 'Dashboard') ?></h5>
            </div>
            <div class="admin-user d-flex align-items-center gap-2">
                <!-- In-App Notification Bell Center -->
                <div class="dropdown me-2" id="psNotificationDropdown">
                    <button class="btn btn-sm btn-ps-outline position-relative border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="psNotifBell" title="Notifications">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="badge bg-orange text-white rounded-pill position-absolute top-0 start-100 translate-middle d-none" id="psNotifBadge">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-dark p-0 shadow-lg border-secondary" style="width: 340px; max-height: 420px; overflow-y: auto;">
                        <div class="p-2 border-bottom border-secondary d-flex justify-content-between align-items-center bg-dark">
                            <strong class="small text-white"><i class="bi bi-bell-fill text-orange me-1"></i> Notifications</strong>
                            <div>
                                <button type="button" class="btn btn-link text-white-50 p-0 me-2 small text-decoration-none" id="psNotifMarkAllBtn" style="font-size: 0.75rem;">Mark all read</button>
                                <a href="<?= url('/admin/notifications') ?>" class="text-orange small text-decoration-none" style="font-size: 0.75rem;">View all</a>
                            </div>
                        </div>
                        <div id="psNotifList" class="p-2 small">
                            <div class="text-center text-muted py-3">Loading notifications…</div>
                        </div>
                    </div>
                </div>

                <span class="admin-user-pill"><i class="bi bi-person-circle"></i> <?= e($currentUser['name'] ?? '') ?></span>
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
<script>
(function () {
  var shell = document.querySelector('.admin-shell');
  var sidebar = document.getElementById('adminSidebar');
  var toggle = document.querySelector('.admin-sidebar-toggle');
  var overlay = document.getElementById('adminOverlay');
  if (!shell || !sidebar || !toggle || !overlay) return;

  var storageKey = 'psAdminSidebarState';
  var groupLinks = Array.from(document.querySelectorAll('.admin-nav-link[data-nav-group]'));

  var drawerQuery = window.matchMedia('(max-width: 1023px)');

  function setSidebarOpen(open) {
    // The drawer only exists below the desktop breakpoint. Keeping this state
    // scoped prevents a desktop overlay from ever covering the application.
    if (!drawerQuery.matches) open = false;
    shell.classList.toggle('admin-sidebar-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    overlay.hidden = !open;
  }

  function restoreGroupState() {
    var savedState = {};
    try {
      savedState = JSON.parse(localStorage.getItem(storageKey + ':groups') || '{}');
    } catch (e) {}

    groupLinks.forEach(function (link) {
      var key = link.getAttribute('data-nav-group');
      var collapse = document.getElementById('navGroup' + key);
      if (!collapse) return;
      var shouldExpand = savedState[key] === '1' || link.classList.contains('active') || link.getAttribute('aria-expanded') === 'true';
      if (shouldExpand) {
        collapse.classList.add('show');
        link.setAttribute('aria-expanded', 'true');
      } else {
        collapse.classList.remove('show');
        link.setAttribute('aria-expanded', 'false');
      }
    });
  }

  toggle.addEventListener('click', function () {
    setSidebarOpen(!shell.classList.contains('admin-sidebar-open'));
  });

  overlay.addEventListener('click', function () {
    setSidebarOpen(false);
  });

  groupLinks.forEach(function (link) {
    link.addEventListener('click', function () {
      var key = this.getAttribute('data-nav-group');
      var collapse = document.getElementById('navGroup' + key);
      var nextExpanded = this.getAttribute('aria-expanded') !== 'true';
      if (collapse) {
        this.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
      }
      var savedState = {};
      try {
        savedState = JSON.parse(localStorage.getItem(storageKey + ':groups') || '{}');
      } catch (e) {}
      savedState[key] = nextExpanded ? '1' : '0';
      localStorage.setItem(storageKey + ':groups', JSON.stringify(savedState));
    });
  });

  drawerQuery.addEventListener('change', function () {
    setSidebarOpen(false);
  });
  setSidebarOpen(false);
})();
</script>
<?php include BASE_PATH . '/views/admin/partials/_export_modal.php'; ?>
<script src="<?= asset('js/payment-method-toggle.js') ?>"></script>
<script src="<?= asset('js/membership-payment-fields.js') ?>"></script>
<script src="<?= asset('js/password-toggle.js') ?>"></script>
<script src="<?= asset('js/admin-details-toggle.js') ?>"></script>
<script src="<?= asset('js/admin-bulk-actions.js') ?>"></script>
<script src="<?= asset('js/admin-export-modal.js') ?>"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $script): ?>
<script src="<?= asset($script) ?>"></script>
<?php endforeach; endif; ?>
<script>
(function() {
  var bell = document.getElementById('psNotifBell');
  var badge = document.getElementById('psNotifBadge');
  var notifList = document.getElementById('psNotifList');
  var markAllBtn = document.getElementById('psNotifMarkAllBtn');
  var csrfToken = '<?= Security::csrfToken() ?>';
  var latestUrl = '<?= url('/admin/notifications/latest') ?>';
  var markAllUrl = '<?= url('/admin/notifications/mark-all-read') ?>';

  function fetchNotifications() {
    fetch(latestUrl)
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (!data) return;
        var count = data.unread_count || 0;
        if (count > 0) {
          badge.textContent = count > 99 ? '99+' : count;
          badge.classList.remove('d-none');
        } else {
          badge.classList.add('d-none');
        }

        if (!data.notifications || !data.notifications.length) {
          notifList.innerHTML = '<div class="text-center text-muted py-3">No notifications</div>';
          return;
        }

        notifList.innerHTML = data.notifications.map(function(n) {
          return '<div class="p-2 mb-1 rounded border-bottom border-secondary border-opacity-25 ' + (n.is_read ? 'opacity-75' : 'bg-dark') + '">' +
            '<div class="d-flex justify-content-between align-items-start mb-1">' +
              '<strong class="text-white small" style="font-size:0.8rem;">' + escapeHtml(n.title) + '</strong>' +
              (!n.is_read ? '<span class="badge bg-orange text-white rounded-pill" style="font-size:0.6rem;">NEW</span>' : '') +
            '</div>' +
            '<div class="text-muted" style="font-size:0.75rem;">' + escapeHtml(n.message) + '</div>' +
            '<div class="d-flex justify-content-between align-items-center mt-1" style="font-size:0.7rem;">' +
              '<span class="text-secondary">' + escapeHtml(n.created_at) + '</span>' +
              (n.link ? '<a href="' + escapeHtml(n.link) + '" class="text-orange text-decoration-none">View Order →</a>' : '') +
            '</div>' +
          '</div>';
        }).join('');
      })
      .catch(function(err) { /* silent */ });
  }

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, function(m) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]);
    });
  }

  if (markAllBtn) {
    markAllBtn.addEventListener('click', function(e) {
      e.preventDefault();
      var formData = new FormData();
      formData.append('_csrf', csrfToken);
      formData.append('ajax', '1');
      fetch(markAllUrl, { method: 'POST', body: formData })
        .then(function() { fetchNotifications(); });
    });
  }

  fetchNotifications();
  setInterval(fetchNotifications, 20000);
})();
</script>
</body>
</html>
