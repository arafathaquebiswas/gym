<?php
/** @var array  $notifications  */
/** @var int    $total          */
/** @var int    $page           */
/** @var int    $totalPages     */
/** @var array  $filters        */
/** @var array  $categories     */
/** @var bool   $soundEnabled   */
/** @var string $soundChoice    */
/** @var array  $soundOptions   */
/** @var string $desktopPref    */
/** @var array  $catPrefs       */

$_uid         = (int) Auth::user()['id'];
$_csrfField   = Security::csrfField();
$_csrfToken   = Security::csrfToken();
$_activeFilter = $filters['filter']   ?? 'all';
$_activeCat    = $filters['category'] ?? '';

/** Quick query-string helper preserving all current params except the one being changed */
function _nqs(string $key, string $value, array $base): string {
    $p = $base;
    $p[$key] = $value;
    unset($p['page']);          // reset page when any filter changes
    return '?' . http_build_query(array_filter($p, fn($v) => $v !== ''));
}
?>

<div class="admin-page-shell">

  <!-- ═══════════════════════════════════════════════════════════ PAGE HEADER -->
  <div class="admin-page-header border-bottom pb-3 mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
      <nav aria-label="Breadcrumb"><ol class="breadcrumb mb-1 small">
        <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
        <li class="breadcrumb-item active">Notification Center</li>
      </ol></nav>
      <h1 class="admin-page-title m-0">
        <i class="bi bi-bell-fill text-orange me-2"></i>Notification Center
        <?php if ($total > 0): ?>
          <span class="badge bg-secondary ms-1 fw-normal" style="font-size:.7rem;"><?= number_format($total) ?> total</span>
        <?php endif; ?>
      </h1>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <form method="post" action="<?= url('/admin/notifications/mark-all-read') ?>" class="d-inline">
        <?= $_csrfField ?>
        <?php if ($_activeCat): ?><input type="hidden" name="category" value="<?= e($_activeCat) ?>"><?php endif; ?>
        <button class="btn btn-ps-outline btn-sm"><i class="bi bi-check-all me-1"></i>Mark All Read</button>
      </form>
      <!-- Cleanup (admins only) -->
      <?php if (Auth::hasRole('main_admin', 'super_admin', 'admin')): ?>
      <button type="button" class="btn btn-outline-danger btn-sm" id="notifCleanupBtn"
              title="Delete notifications older than 90 days">
        <i class="bi bi-trash3 me-1"></i>Cleanup (90d)
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════ FILTER TABS -->
  <div class="admin-card p-3 mb-3">
    <!-- Read-status tabs -->
    <div class="d-flex flex-wrap gap-1 mb-3">
      <?php foreach (['all' => 'All', 'unread' => 'Unread', 'read' => 'Read'] as $fk => $fl): ?>
        <a href="<?= url('/admin/notifications') . _nqs('filter', $fk, $filters) ?>"
           class="btn btn-sm <?= $_activeFilter === $fk ? 'btn-ps' : 'btn-ps-outline' ?>">
          <?= e($fl) ?>
        </a>
      <?php endforeach; ?>
      <span class="border-start border-secondary ms-1 ps-2"></span>
      <!-- Category tabs -->
      <a href="<?= url('/admin/notifications') . _nqs('category', '', $filters) ?>"
         class="btn btn-sm <?= $_activeCat === '' ? 'btn-ps' : 'btn-ps-outline' ?>">All Types</a>
      <?php foreach ($categories as $cSlug => $cMeta): ?>
        <a href="<?= url('/admin/notifications') . _nqs('category', $cSlug, $filters) ?>"
           class="btn btn-sm <?= $_activeCat === $cSlug ? 'btn-ps' : 'btn-ps-outline' ?>">
          <?= $cMeta['icon'] ?> <?= e($cMeta['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Search & date-range row -->
    <form method="get" action="<?= url('/admin/notifications') ?>" class="row g-2 align-items-end">
      <input type="hidden" name="filter" value="<?= e($_activeFilter) ?>">
      <?php if ($_activeCat): ?><input type="hidden" name="category" value="<?= e($_activeCat) ?>"><?php endif; ?>
      <div class="col-12 col-md-4">
        <input type="text" name="search" value="<?= e($filters['search']) ?>"
               class="form-control form-control-sm bg-dark border-secondary text-white"
               placeholder="🔍 Search notifications…">
      </div>
      <div class="col-6 col-md-2">
        <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>"
               class="form-control form-control-sm bg-dark border-secondary text-white">
      </div>
      <div class="col-6 col-md-2">
        <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>"
               class="form-control form-control-sm bg-dark border-secondary text-white">
      </div>
      <div class="col-auto">
        <button class="btn btn-ps btn-sm" type="submit"><i class="bi bi-funnel-fill"></i> Filter</button>
        <a href="<?= url('/admin/notifications') ?>" class="btn btn-ps-outline btn-sm">Clear</a>
      </div>
    </form>
  </div>

  <!-- ═══════════════════════════════════════════════════════════ NOTIFICATION LIST -->
  <div class="admin-card p-0 mb-4">
    <?php if (!$notifications): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-25"></i>
        No notifications match your filters.
      </div>
    <?php else: ?>
      <div class="list-group list-group-flush border-0">
        <?php foreach ($notifications as $n):
          $cat = $categories[$n['category']] ?? $categories['system'];
        ?>
          <div class="list-group-item bg-transparent text-white border-secondary border-opacity-25 px-3 py-3
                      d-flex align-items-start gap-3 notif-row <?= $n['is_read'] ? 'opacity-65' : '' ?>"
               data-notif-id="<?= $n['id'] ?>"
               data-link="<?= e($n['link'] ?? '') ?>"
               data-is-read="<?= $n['is_read'] ? '1' : '0' ?>"
               style="cursor: <?= $n['link'] ? 'pointer' : 'default' ?>;">
            <!-- Category badge -->
            <div class="flex-shrink-0 text-center" style="width:38px;">
              <span class="badge <?= e($cat['color']) ?> rounded-pill p-2 fs-6"><?= $cat['icon'] ?></span>
            </div>
            <!-- Body -->
            <div class="flex-grow-1 min-w-0">
              <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                <strong class="<?= $n['is_read'] ? 'text-white-50' : 'text-white' ?>" style="font-size:.88rem;">
                  <?= e($n['title']) ?>
                </strong>
                <?php if (!$n['is_read']): ?>
                  <span class="badge bg-orange text-white flex-shrink-0" style="font-size:.6rem;">NEW</span>
                <?php endif; ?>
              </div>
              <p class="small text-muted mb-1 text-truncate-2"><?= e($n['message']) ?></p>
              <div class="d-flex align-items-center gap-3" style="font-size:.75rem;">
                <span class="text-secondary"><i class="bi bi-clock me-1"></i><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></span>
                <span class="badge <?= e($cat['color']) ?> opacity-75"><?= e($cat['label']) ?></span>
                <?php if ($n['link']): ?>
                  <span class="text-orange"><i class="bi bi-box-arrow-up-right me-1"></i>Click to open</span>
                <?php endif; ?>
              </div>
            </div>
            <!-- Mark-read button -->
            <?php if (!$n['is_read']): ?>
              <button type="button" class="btn btn-sm btn-ps-outline py-1 px-2 flex-shrink-0 notif-mark-btn"
                      data-id="<?= $n['id'] ?>" title="Mark as read">
                <i class="bi bi-check2"></i>
              </button>
            <?php else: ?>
              <i class="bi bi-check2-all text-success opacity-50 flex-shrink-0 mt-1"></i>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <nav class="mb-4"><ul class="pagination pagination-sm justify-content-center mb-0">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link bg-dark border-secondary text-white"
             href="<?= url('/admin/notifications') . _nqs('page', (string)$i, $filters) ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul></nav>
  <?php endif; ?>

  <!-- ═══════════════════════════════════════════════════════════ PREFERENCES PANEL -->
  <div class="admin-card p-4 mb-4">
    <h5 class="fw-bold text-white mb-4"><i class="bi bi-sliders text-orange me-2"></i>Notification Preferences</h5>
    <div class="row g-4">

      <!-- Sound Selection -->
      <div class="col-12 col-md-6">
        <label class="form-label text-white fw-semibold mb-2">🔊 Notification Sound</label>
        <div class="d-flex align-items-center gap-2 mb-2">
          <select class="form-select form-select-sm bg-dark border-secondary text-white" id="soundChoiceSelect" style="max-width:180px;">
            <?php foreach ($soundOptions as $k => $label): ?>
              <option value="<?= e($k) ?>" <?= $soundChoice === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="btn btn-ps-outline btn-sm" id="testSoundBtn">
            <i class="bi bi-play-fill"></i> Test
          </button>
          <div class="form-check form-switch mb-0 ms-2">
            <input class="form-check-input" type="checkbox" id="soundEnabledSwitch"
                   <?= $soundEnabled ? 'checked' : '' ?>>
            <label class="form-check-label text-white-50 small" for="soundEnabledSwitch">Enabled</label>
          </div>
        </div>
        <small class="text-muted">Choose a sound or set to Silent to mute all alert sounds.</small>
      </div>

      <!-- Desktop Notifications -->
      <div class="col-12 col-md-6">
        <label class="form-label text-white fw-semibold mb-2">🖥️ Browser Desktop Notifications</label>
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" id="desktopNotifSwitch"
                   <?= $desktopPref === '1' ? 'checked' : '' ?>>
            <label class="form-check-label text-white-50" for="desktopNotifSwitch">
              Enable desktop notifications
            </label>
          </div>
          <button type="button" class="btn btn-ps-outline btn-sm" id="testDesktopBtn">
            <i class="bi bi-bell"></i> Test
          </button>
        </div>
        <small class="text-muted" id="desktopPermStatus">
          Permission: <span id="desktopPermLabel">checking…</span>
        </small>
      </div>

      <!-- Category opt-in/out -->
      <div class="col-12">
        <label class="form-label text-white fw-semibold mb-2">📂 Notification Categories</label>
        <div class="d-flex flex-wrap gap-3">
          <?php foreach ($categories as $cSlug => $cMeta): ?>
            <div class="form-check form-switch">
              <input class="form-check-input cat-pref-switch" type="checkbox" role="switch"
                     id="cat_<?= e($cSlug) ?>"
                     data-cat="<?= e($cSlug) ?>"
                     <?= ($catPrefs[$cSlug] ?? true) ? 'checked' : '' ?>>
              <label class="form-check-label text-white-50" for="cat_<?= e($cSlug) ?>">
                <?= $cMeta['icon'] ?> <?= e($cMeta['label']) ?>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
        <small class="text-muted mt-1 d-block">Turn off a category to stop receiving those notifications.</small>
      </div>

    </div>
  </div>

</div><!-- /.admin-page-shell -->

<!-- ═══════════════════════════════════════════════════════════ PAGE SCRIPTS -->
<script>
(function () {
  var CSRF = '<?= $_csrfToken ?>';
  var BASE = '<?= url('/admin/notifications') ?>';

  function post(url, data) {
    var fd = new FormData();
    fd.append('_csrf', CSRF);
    fd.append('ajax', '1');
    for (var k in data) fd.append(k, data[k]);
    return fetch(url, { method: 'POST', body: fd });
  }

  /* ── Sound files map ── */
  var SOUNDS = {
    classic_ding : '<?= asset('audio/notif.wav') ?>',
    soft_pop     : '<?= asset('audio/soft_pop.wav') ?>',
    bell         : '<?= asset('audio/bell.wav') ?>',
    chime        : '<?= asset('audio/chime.wav') ?>',
    silent       : null,
  };

  function playSound(choice) {
    var url = SOUNDS[choice] || null;
    if (!url) return;
    try {
      var a = new Audio(url);
      var p = a.play();
      if (p) p.catch(function(){});
    } catch(e){}
  }

  /* ── Sound choice select ── */
  var choiceSelect = document.getElementById('soundChoiceSelect');
  if (choiceSelect) {
    choiceSelect.addEventListener('change', function() {
      post('<?= url('/admin/notifications/sound-choice') ?>', { choice: this.value });
    });
  }

  /* ── Test sound ── */
  var testSoundBtn = document.getElementById('testSoundBtn');
  if (testSoundBtn) {
    testSoundBtn.addEventListener('click', function() {
      var choice = choiceSelect ? choiceSelect.value : 'classic_ding';
      playSound(choice);
    });
  }

  /* ── Sound enabled switch ── */
  var soundSwitch = document.getElementById('soundEnabledSwitch');
  if (soundSwitch) {
    soundSwitch.addEventListener('change', function() {
      post('<?= url('/admin/notifications/toggle-sound') ?>', { enabled: this.checked ? '1' : '0' });
    });
  }

  /* ── Desktop notifications ── */
  var desktopSwitch = document.getElementById('desktopNotifSwitch');
  var permLabel     = document.getElementById('desktopPermLabel');

  function updatePermLabel() {
    if (!('Notification' in window)) {
      if (permLabel) permLabel.textContent = 'Not supported by this browser';
      if (desktopSwitch) desktopSwitch.disabled = true;
      return;
    }
    if (permLabel) permLabel.textContent = Notification.permission;
    if (Notification.permission === 'denied' && desktopSwitch) {
      desktopSwitch.disabled = true;
    }
  }
  updatePermLabel();

  if (desktopSwitch) {
    desktopSwitch.addEventListener('change', function() {
      var enabled = this.checked;
      if (enabled && Notification.permission !== 'granted') {
        Notification.requestPermission().then(function(perm) {
          if (perm !== 'granted') { desktopSwitch.checked = false; return; }
          updatePermLabel();
          post('<?= url('/admin/notifications/desktop-pref') ?>', { enabled: '1' });
        });
      } else {
        post('<?= url('/admin/notifications/desktop-pref') ?>', { enabled: enabled ? '1' : '0' });
      }
    });
  }

  var testDesktopBtn = document.getElementById('testDesktopBtn');
  if (testDesktopBtn) {
    testDesktopBtn.addEventListener('click', function() {
      if (!('Notification' in window)) return;
      if (Notification.permission !== 'granted') {
        Notification.requestPermission().then(function(p) {
          updatePermLabel();
          if (p === 'granted') new Notification('PowerSurge Notifications', {
            body: 'Desktop notifications are working correctly.',
            icon: '<?= asset('images/logo/logo.png') ?>',
          });
        });
      } else {
        new Notification('PowerSurge Notifications', {
          body: 'Desktop notifications are working correctly.',
          icon: '<?= asset('images/logo/logo.png') ?>',
        });
      }
    });
  }

  /* ── Category preference switches ── */
  document.querySelectorAll('.cat-pref-switch').forEach(function(sw) {
    sw.addEventListener('change', function() {
      post('<?= url('/admin/notifications/category-pref') ?>', {
        category: this.dataset.cat,
        enabled: this.checked ? '1' : '0',
      });
    });
  });

  /* ── Click notification row → mark read + navigate ── */
  document.querySelectorAll('.notif-row').forEach(function(row) {
    row.addEventListener('click', function(e) {
      if (e.target.closest('.notif-mark-btn')) return; // handled separately
      var id   = this.dataset.notifId;
      var link = this.dataset.link;
      var read = this.dataset.isRead === '1';
      function goToLink() { if (link) window.location.href = link; }
      if (!read) {
        var fd = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('ajax', '1');
        fetch(BASE + '/' + id + '/read', { method: 'POST', body: fd })
          .then(goToLink).catch(goToLink);
      } else {
        goToLink();
      }
    });
  });

  /* ── Individual mark-read buttons ── */
  document.querySelectorAll('.notif-mark-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      var id  = this.dataset.id;
      var row = this.closest('.notif-row');
      var fd  = new FormData();
      fd.append('_csrf', CSRF);
      fd.append('ajax', '1');
      fetch(BASE + '/' + id + '/read', { method: 'POST', body: fd })
        .then(function() {
          if (row) {
            row.dataset.isRead = '1';
            row.classList.add('opacity-65');
            btn.remove();
          }
        });
    });
  });

  /* ── Cleanup button ── */
  var cleanupBtn = document.getElementById('notifCleanupBtn');
  if (cleanupBtn) {
    cleanupBtn.addEventListener('click', function() {
      if (!confirm('Delete all notifications older than 90 days? This cannot be undone.')) return;
      post('<?= url('/admin/notifications/cleanup') ?>', {})
        .then(function(r) { return r.json(); })
        .then(function(d) {
          alert('Cleanup complete. ' + (d.deleted || 0) + ' notifications deleted.');
          window.location.reload();
        });
    });
  }

})();
</script>

<style>
.opacity-65 { opacity: 0.65; }
.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.notif-row:hover:not(.opacity-65) { background: rgba(255,255,255,.03) !important; }
</style>
