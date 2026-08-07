<div class="admin-page-shell">
  <div class="admin-page-header border-bottom pb-3 mb-3 d-flex justify-content-between align-items-center">
    <div>
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="<?= url('/admin') ?>">Dashboard</a></li>
          <li class="breadcrumb-item active">POS</li>
        </ol>
      </nav>
      <div class="d-flex align-items-center gap-2">
        <h1 class="admin-page-title m-0">POS Terminal</h1>
        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small">
          <i class="bi bi-circle-fill me-1 small"></i> Terminal Active
        </span>
      </div>
    </div>
    <div class="admin-page-actions d-flex align-items-center gap-2">
      <?php if (Permission::can('pos', 'export')): ?>
      <button type="button" class="btn btn-ps-outline btn-sm" data-export-module="pos">
        <i class="bi bi-download me-1"></i> Export POS Sales
      </button>
      <?php endif; ?>
      <button type="button" class="btn btn-ps-outline btn-sm" onclick="location.reload()" title="Refresh Catalog">
        <i class="bi bi-arrow-clockwise"></i>
      </button>
    </div>
  </div>

<?php
/** @var string $productsJson */
/** @var string $membersJson */
/** @var bool $taxEnabled */
/** @var float $taxPercent */
?>

<div class="row g-3">
  <!-- Left Side: Product Catalog & Search -->
  <div class="col-lg-7 col-xl-8">
    <div class="admin-card p-3 mb-0" style="min-height: calc(100vh - 160px);">
      
      <!-- Search & Scan Header Bar -->
      <div class="pos-search-box mb-3">
        <i class="bi bi-search"></i>
        <input type="text" id="posSearch" class="form-control" placeholder="Search by product name, SKU, or scan barcode…" autofocus>
        <div class="pos-search-actions">
          <button type="button" class="btn btn-sm btn-ps-outline py-1 px-2 border-0" id="posBarcodeBtn" title="Scan Barcode">
            <i class="bi bi-qr-code-scan text-orange"></i>
          </button>
          <button type="button" class="btn btn-sm btn-ps-outline py-1 px-2 border-0" id="posFilterBtn" title="Filter Catalog">
            <i class="bi bi-funnel text-muted"></i>
          </button>
          <span class="badge bg-dark text-muted border px-2 py-1 me-1 small d-none d-md-inline-block">/ or Ctrl+K</span>
        </div>
      </div>

      <!-- Category Filter Chips -->
      <div id="posChips" class="pos-chips-wrap"></div>

      <!-- Product Tiles Grid -->
      <div id="posProductGrid" class="pos-product-grid" style="max-height: calc(100vh - 270px); overflow-y: auto; padding-right: 4px;"></div>
    </div>
  </div>

  <!-- Right Side: Order Cart Panel -->
  <div class="col-lg-5 col-xl-4">
    <div class="admin-card pos-cart-panel d-flex flex-column" style="min-height: calc(100vh - 160px);">
      
      <!-- Cart Header -->
      <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
        <h6 class="m-0 fw-bold d-flex align-items-center gap-2">
          <i class="bi bi-cart3 text-orange"></i> Order Cart
          <span class="badge bg-orange text-white rounded-pill px-2" id="posCartCount">0</span>
        </h6>
        <button type="button" class="btn btn-link text-muted p-0 small text-decoration-none" id="posClearCartBtn" title="Clear Cart">
          <i class="bi bi-trash me-1"></i>Clear
        </button>
      </div>

      <!-- Customer Selection -->
      <div class="mb-3">
        <label class="form-label text-muted small fw-semibold mb-1">Customer / Member</label>
        <select id="posMemberSelect" class="form-select form-select-sm bg-dark text-white border-secondary">
          <option value="">Walk-in Customer</option>
        </select>
      </div>

      <!-- Cart Itemized List -->
      <div id="posCartList" class="flex-grow-1 mb-3" style="overflow-y: auto; max-height: calc(100vh - 480px); padding-right: 2px;">
        <div id="posCartEmpty" class="text-muted text-center py-5">
          <i class="bi bi-bag-plus fs-1 text-secondary opacity-50 d-block mb-2"></i>
          <p class="small mb-0">Cart is empty</p>
          <span class="small text-muted">Click any product to start sale</span>
        </div>
      </div>

      <!-- Checkout Form -->
      <form method="post" action="<?= url('/admin/pos/checkout') ?>" id="posCheckoutForm" class="mt-auto border-top pt-3">
        <?= Security::csrfField() ?>
        <input type="hidden" name="cart_json" id="posCartJson" value="[]">
        <input type="hidden" name="member_id" id="posMemberIdField" value="">

        <!-- Discount & Coupon Row -->
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label text-muted small fw-semibold mb-1">Discount (৳)</label>
            <input type="number" step="0.01" min="0" name="discount" id="posDiscount" class="form-control form-control-sm bg-dark text-white border-secondary" value="0">
          </div>
          <div class="col-6">
            <label class="form-label text-muted small fw-semibold mb-1">Coupon Code</label>
            <input type="text" name="coupon_code" id="posCouponCode" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Optional">
          </div>
        </div>

        <!-- Payment Method Selector -->
        <label class="form-label text-muted small fw-semibold mb-1">Payment Method</label>
        <select name="payment_method" id="posPaymentMethod" class="form-select form-select-sm bg-dark text-white border-secondary mb-3">
          <option value="cash">💵 Cash</option>
          <option value="card">💳 Card</option>
          <option value="bkash">📱 bKash</option>
          <option value="nagad">📱 Nagad</option>
          <option value="rocket">📱 Rocket</option>
          <option value="bank_transfer">🏦 Bank Transfer</option>
        </select>

        <!-- Sticky Totals Summary -->
        <div class="bg-dark bg-opacity-50 p-3 rounded-3 border border-secondary border-opacity-25 mb-3">
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Subtotal</span>
            <span class="text-white fw-semibold" id="posSubtotalDisplay">৳0.00</span>
          </div>
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Discount</span>
            <span class="text-success fw-semibold" id="posDiscountDisplay">− ৳0.00</span>
          </div>
          <?php if ($taxEnabled): ?>
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Tax (<?= e((string) $taxPercent) ?>%)</span>
            <span class="text-white fw-semibold" id="posTaxDisplay">৳0.00</span>
          </div>
          <?php endif; ?>
          <div class="d-flex justify-content-between align-items-baseline pt-2 mt-1 border-top border-secondary border-opacity-25">
            <span class="fw-bold text-white">Grand Total</span>
            <span class="text-orange fw-bold fs-4" id="posTotalDisplay">৳0.00</span>
          </div>
        </div>

        <!-- Complete Sale Button -->
        <button type="submit" class="btn btn-ps w-100 py-2.5 fw-bold fs-6 d-flex align-items-center justify-content-center gap-2" id="posCheckoutBtn" disabled>
          <i class="bi bi-check-circle-fill"></i> Complete Sale (Ctrl+Enter)
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Product Details Modal -->
<div class="modal fade" id="posDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white border-secondary">
      <div class="modal-header border-secondary">
        <h6 class="modal-title fw-bold" id="posDetailsName">Product Details</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 small">
          <div class="col-6"><span class="text-muted">SKU</span><br><strong id="posDetailsSku">—</strong></div>
          <div class="col-6"><span class="text-muted">Barcode</span><br><strong id="posDetailsBarcode">—</strong></div>
          <div class="col-6"><span class="text-muted">Category</span><br><strong id="posDetailsCategory">—</strong></div>
          <div class="col-6"><span class="text-muted">Stock Level</span><br><span id="posDetailsStock" class="badge bg-success-subtle text-success">—</span></div>
          <div class="col-6"><span class="text-muted">Regular Price</span><br><strong id="posDetailsRegularPrice">—</strong></div>
          <div class="col-6"><span class="text-muted">Offer Price</span><br><strong id="posDetailsOfferPrice" class="text-orange">—</strong></div>
        </div>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-ps btn-sm" id="posDetailsAddBtn">
          <i class="bi bi-cart-plus me-1"></i> Add to Cart
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  const products = <?= $productsJson ?>;
  const members = <?= $membersJson ?>;
  const taxEnabled = <?= $taxEnabled ? 'true' : 'false' ?>;
  const taxPercent = <?= (float) $taxPercent ?>;

  const cart = {}; // product_id -> {product, qty}
  let activeChip = ''; // '' = All, '__favorites', '__recent', or category name
  const FAVORITES_KEY = 'pos_favorites';
  const RECENT_KEY = 'pos_recent';
  const DRAFT_KEY = 'pos_draft';
  const MAX_RECENT = 12;

  // True only on the first POS load after a sale was actually created — see
  // PosController::index(). Everything else (Back to POS, a refresh, a failed
  // checkout) leaves the draft alone.
  const draftConsumed = <?= !empty($draftConsumed) ? 'true' : 'false' ?>;

  function loadIds(key) { try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) { return []; } }
  function saveIds(key, ids) { localStorage.setItem(key, JSON.stringify(ids)); }

  let favorites = loadIds(FAVORITES_KEY);
  let recent = loadIds(RECENT_KEY);

  function toggleFavorite(productId) {
    favorites = favorites.includes(productId) ? favorites.filter(id => id !== productId) : [productId, ...favorites];
    saveIds(FAVORITES_KEY, favorites);
    renderGrid(search.value);
  }

  function pushRecent(productId) {
    recent = [productId, ...recent.filter(id => id !== productId)].slice(0, MAX_RECENT);
    saveIds(RECENT_KEY, recent);
  }

  const grid = document.getElementById('posProductGrid');
  const chipsWrap = document.getElementById('posChips');
  const search = document.getElementById('posSearch');
  const cartList = document.getElementById('posCartList');
  const cartCount = document.getElementById('posCartCount');
  const clearCartBtn = document.getElementById('posClearCartBtn');
  const cartJson = document.getElementById('posCartJson');
  const discountInput = document.getElementById('posDiscount');
  const subtotalDisplay = document.getElementById('posSubtotalDisplay');
  const discountDisplay = document.getElementById('posDiscountDisplay');
  const taxDisplay = document.getElementById('posTaxDisplay');
  const totalDisplay = document.getElementById('posTotalDisplay');
  const checkoutBtn = document.getElementById('posCheckoutBtn');
  const checkoutForm = document.getElementById('posCheckoutForm');
  const detailsModalEl = document.getElementById('posDetailsModal');
  const detailsAddBtn = document.getElementById('posDetailsAddBtn');
  const memberSelect = document.getElementById('posMemberSelect');
  const memberIdField = document.getElementById('posMemberIdField');
  const couponInput = document.getElementById('posCouponCode');
  const paymentSelect = document.getElementById('posPaymentMethod');
  let detailsProductId = null;

  function getDetailsModal() {
    return bootstrap.Modal.getOrCreateInstance(detailsModalEl);
  }

  function money(n) { return '৳' + Number(n).toFixed(2); }
  function escapeHtml(s) { return String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); }

  memberSelect.innerHTML += members.map(m => `<option value="${m.id}">${escapeHtml(m.name)}${m.member_code ? ' (' + escapeHtml(m.member_code) + ')' : ''}</option>`).join('');
  memberSelect.addEventListener('change', () => { memberIdField.value = memberSelect.value; });

  function priceHtml(p) {
    if (p.offer_is_live && Number(p.display_price) < Number(p.selling_price)) {
      const pct = p.discount_percent || Math.round(((Number(p.selling_price) - Number(p.display_price)) / Number(p.selling_price)) * 100);
      return `<small class="text-white-50 text-decoration-line-through me-1">${money(p.selling_price)}</small>
              <span class="fw-bold text-warning">${money(p.display_price)}</span>
              ${pct > 0 ? `<span class="badge bg-danger ms-1" style="font-size:0.65rem;">${pct}% OFF</span>` : ''}`;
    }
    return money(p.selling_price);
  }

  function renderChips() {
    const categories = [...new Set(products.map(p => p.category_name).filter(Boolean))].sort();
    const pinned = [
      { key: '', label: 'All Items' },
      { key: '__favorites', label: '★ Favorites' },
      { key: '__recent', label: '⏱ Recent' },
    ];
    const chips = [...pinned, ...categories.map(c => ({ key: c, label: c }))];
    chipsWrap.innerHTML = chips.map(c => `
      <button type="button" class="pos-chip ${activeChip === c.key ? 'active' : ''}" data-key="${escapeHtml(c.key)}">${escapeHtml(c.label)}</button>
    `).join('');
    chipsWrap.querySelectorAll('.pos-chip').forEach(btn => {
      btn.addEventListener('click', () => {
        activeChip = btn.dataset.key;
        renderChips();
        renderGrid(search.value);
      });
    });
  }

  function skeletonHtml(count) {
    return Array.from({ length: count }).map(() => `
      <div class="pos-tile pos-skeleton">
        <div class="pos-skeleton-block" style="width:52px;height:52px;border-radius:8px;"></div>
        <div class="pos-skeleton-block" style="width:80%;height:12px;"></div>
        <div class="pos-skeleton-block" style="width:50%;height:16px;"></div>
        <div class="pos-skeleton-block" style="width:90%;height:28px;border-radius:6px;"></div>
      </div>
    `).join('');
  }

  function renderGrid(filter) {
    const term = (filter || '').trim().toLowerCase();
    let matches = products;
    if (activeChip === '__favorites') {
      matches = matches.filter(p => favorites.includes(p.id));
    } else if (activeChip === '__recent') {
      const order = recent;
      matches = matches.filter(p => order.includes(p.id)).sort((a, b) => order.indexOf(a.id) - order.indexOf(b.id));
    } else if (activeChip) {
      matches = matches.filter(p => p.category_name === activeChip);
    }
    if (term) {
      matches = matches.filter(p =>
        p.name.toLowerCase().includes(term) ||
        (p.sku && p.sku.toLowerCase().includes(term)) ||
        (p.barcode && p.barcode.toLowerCase() === term)
      );
    }

    grid.innerHTML = matches.slice(0, 90).map(p => {
      const isFav = favorites.includes(p.id);
      const isOutOfStock = Number(p.stock_qty) <= 0;
      const isLowStock = !isOutOfStock && Number(p.stock_qty) <= Number(p.min_stock || 5);
      const stockClass = isOutOfStock ? 'text-danger fw-bold' : (isLowStock ? 'text-warning fw-bold' : 'text-success fw-semibold');
      const stockBadgeText = isOutOfStock ? 'Out of Stock (0)' : `${p.stock_qty} in stock`;

      return `
      <div class="pos-tile ${isOutOfStock ? 'opacity-75' : ''}" data-id="${p.id}" onclick="window.posAddToCart(${p.id})">
        <button type="button" class="pos-tile-fav ${isFav ? 'active' : ''}" data-action="fav" data-id="${p.id}" title="Favorite"><i class="bi ${isFav ? 'bi-star-fill' : 'bi-star'}"></i></button>
        <div class="pos-tile-thumb">${p.image_url ? `<img src="${escapeHtml(p.image_url)}" alt="">` : '<i class="bi bi-box-seam"></i>'}</div>
        ${p.offer_is_live ? '<span class="pos-tile-offer-badge">OFFER</span>' : ''}
        <div class="pos-tile-name">${escapeHtml(p.name)}</div>
        <div class="pos-tile-price">${priceHtml(p)}</div>
        <div class="pos-tile-stock ${stockClass}">${stockBadgeText}</div>
        <button type="button" class="btn btn-ps btn-sm pos-tile-add" data-action="add" data-id="${p.id}" ${isOutOfStock ? 'disabled' : ''}>${isOutOfStock ? 'Out of Stock' : 'Add to Cart'}</button>
        <button type="button" class="pos-tile-details" data-action="details" data-id="${p.id}">Details</button>
      </div>
    `;
    }).join('') || '<p class="text-white-50 text-center py-4 w-100">No products found.</p>';

    grid.querySelectorAll('[data-action="add"]').forEach(btn => {
      btn.addEventListener('click', (e) => { e.stopPropagation(); addToCart(parseInt(btn.dataset.id, 10)); });
    });
    grid.querySelectorAll('[data-action="details"]').forEach(btn => {
      btn.addEventListener('click', (e) => { e.stopPropagation(); showDetails(parseInt(btn.dataset.id, 10)); });
    });
    grid.querySelectorAll('[data-action="fav"]').forEach(btn => {
      btn.addEventListener('click', (e) => { e.stopPropagation(); toggleFavorite(parseInt(btn.dataset.id, 10)); });
    });

    if (term) {
      const exact = products.find(p => (p.barcode && p.barcode.toLowerCase() === term) || p.sku.toLowerCase() === term);
      if (exact && matches.length === 1) {
        addToCart(exact.id);
        search.value = '';
        renderGrid('');
      }
    }
  }

  window.posAddToCart = function (id) {
    addToCart(id);
  };

  function showDetails(productId) {
    const p = products.find(x => x.id === productId);
    if (!p) return;
    detailsProductId = productId;
    document.getElementById('posDetailsName').textContent = p.name;
    document.getElementById('posDetailsSku').textContent = p.sku || '—';
    document.getElementById('posDetailsBarcode').textContent = p.barcode || '—';
    document.getElementById('posDetailsCategory').textContent = p.category_name || '—';
    document.getElementById('posDetailsStock').textContent = p.stock_qty + ' in stock';
    document.getElementById('posDetailsRegularPrice').textContent = money(p.selling_price);
    document.getElementById('posDetailsOfferPrice').textContent = p.offer_is_live ? money(p.display_price) : '—';
    getDetailsModal().show();
  }

  detailsAddBtn.addEventListener('click', () => {
    if (detailsProductId !== null) addToCart(detailsProductId);
    getDetailsModal().hide();
  });

  function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    const existing = cart[productId];
    const currentQty = existing ? existing.qty : 0;
    if (currentQty + 1 > product.stock_qty) {
      alert('Not enough stock for ' + product.name);
      return;
    }
    cart[productId] = { product, qty: currentQty + 1 };
    pushRecent(productId);
    renderCart();
  }

  function changeQty(productId, delta) {
    const line = cart[productId];
    if (!line) return;
    const newQty = line.qty + delta;
    if (newQty <= 0) {
      delete cart[productId];
    } else if (newQty > line.product.stock_qty) {
      alert('Not enough stock for ' + line.product.name);
      return;
    } else {
      line.qty = newQty;
    }
    renderCart();
  }

  function removeFromCart(productId) {
    delete cart[productId];
    renderCart();
  }

  clearCartBtn.addEventListener('click', () => {
    if (Object.keys(cart).length === 0) return;
    if (confirm('Clear all items from cart?')) {
      for (const k in cart) delete cart[k];
      renderCart();
    }
  });

  function renderCart() {
    const lines = Object.values(cart);
    const totalItemsCount = lines.reduce((sum, l) => sum + l.qty, 0);
    cartCount.textContent = totalItemsCount;

    if (!lines.length) {
      cartList.innerHTML = `
        <div id="posCartEmpty" class="text-muted text-center py-5">
          <i class="bi bi-bag-plus fs-1 text-secondary opacity-50 d-block mb-2"></i>
          <p class="small mb-0">Cart is empty</p>
          <span class="small text-muted">Click any product to start sale</span>
        </div>`;
    } else {
      cartList.innerHTML = lines.map(line => {
        const price = line.product.display_price;
        const subtotal = price * line.qty;
        return `
          <div class="pos-cart-line d-flex justify-content-between align-items-center">
            <div style="max-width: 60%;">
              <div class="fw-semibold small text-white text-truncate">${escapeHtml(line.product.name)}</div>
              <div class="text-muted small">${money(price)} × ${line.qty} = <span class="text-orange font-monospace">${money(subtotal)}</span></div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div class="d-flex align-items-center gap-1 bg-dark rounded border px-1">
                <button type="button" class="btn btn-sm btn-link text-white text-decoration-none p-0 px-1" data-action="dec" data-id="${line.product.id}">−</button>
                <span class="fw-bold px-1" style="min-width: 1.2rem; text-align: center; font-size: 0.85rem;">${line.qty}</span>
                <button type="button" class="btn btn-sm btn-link text-white text-decoration-none p-0 px-1" data-action="inc" data-id="${line.product.id}">+</button>
              </div>
              <button type="button" class="btn btn-link text-danger p-0 ms-1" data-action="remove" data-id="${line.product.id}" title="Remove Item"><i class="bi bi-trash"></i></button>
            </div>
          </div>
        `;
      }).join('');
    }

    cartList.querySelectorAll('[data-action]').forEach(btn => {
      const id = parseInt(btn.dataset.id, 10);
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        if (btn.dataset.action === 'inc') changeQty(id, 1);
        else if (btn.dataset.action === 'dec') changeQty(id, -1);
        else if (btn.dataset.action === 'remove') removeFromCart(id);
      });
    });

    updateTotals();
  }

  function updateTotals() {
    const lines = Object.values(cart);
    const subtotal = lines.reduce((sum, l) => sum + l.product.display_price * l.qty, 0);
    const discount = Math.min(subtotal, parseFloat(discountInput.value || '0') || 0);
    const netAfterDiscount = Math.max(0, subtotal - discount);
    const tax = taxEnabled ? netAfterDiscount * (taxPercent / 100) : 0;
    const total = Math.max(0, netAfterDiscount + tax);

    subtotalDisplay.textContent = money(subtotal);
    discountDisplay.textContent = '− ' + money(discount);
    if (taxDisplay) taxDisplay.textContent = money(tax);
    totalDisplay.textContent = money(total);
    checkoutBtn.disabled = lines.length === 0;

    cartJson.value = JSON.stringify(lines.map(l => ({ product_id: l.product.id, qty: l.qty })));

    saveDraft();
  }

  /*
   * Draft persistence.
   *
   * The cart lived only in the `cart` object above, so any full page load —
   * Back to POS, a refresh, wandering off to another admin screen — re-ran this
   * script and started from empty. Nothing was clearing it; it simply never
   * outlived the page. It is now mirrored into localStorage on every change and
   * restored on load, so an unfinished transaction survives navigation.
   *
   * Only product ids and quantities are stored, never prices: products are
   * re-resolved from the freshly rendered `products` payload on restore, and the
   * server re-reads every price and stock level again at checkout regardless.
   */
  function saveDraft() {
    try {
      localStorage.setItem(DRAFT_KEY, JSON.stringify({
        lines: Object.values(cart).map(l => ({ product_id: l.product.id, qty: l.qty })),
        discount: discountInput.value || '0',
        coupon: couponInput ? couponInput.value : '',
        payment_method: paymentSelect ? paymentSelect.value : '',
        member_id: memberSelect ? memberSelect.value : '',
        saved_at: Date.now()
      }));
    } catch (e) {
      // A full or disabled localStorage must never break the sale in progress.
    }
  }

  function clearDraft() {
    try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
  }

  function restoreDraft() {
    let draft;
    try { draft = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null'); } catch (e) { return; }
    if (!draft || !Array.isArray(draft.lines)) return;

    draft.lines.forEach(function (line) {
      const product = products.find(p => p.id === line.product_id);
      if (!product) return;                       // delisted or out of stock since
      const qty = Math.min(parseInt(line.qty, 10) || 0, product.stock_qty);
      if (qty > 0) cart[product.id] = { product, qty };
    });

    if (discountInput && draft.discount != null) discountInput.value = draft.discount;
    if (couponInput && draft.coupon) couponInput.value = draft.coupon;
    if (paymentSelect && draft.payment_method) paymentSelect.value = draft.payment_method;
    if (memberSelect && draft.member_id) {
      memberSelect.value = draft.member_id;
      memberSelect.dispatchEvent(new Event('change'));
    }
  }

  search.addEventListener('input', () => renderGrid(search.value));
  discountInput.addEventListener('input', updateTotals);

  // Coupon, payment method and customer do not affect the client-side totals, so
  // they never reached updateTotals() — they still have to be part of the draft.
  [couponInput, paymentSelect, memberSelect].forEach(function (el) {
    if (!el) return;
    el.addEventListener('change', saveDraft);
    el.addEventListener('input', saveDraft);
  });

  checkoutForm.addEventListener('submit', () => {
    updateTotals();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === '/' && document.activeElement !== search) {
      e.preventDefault();
      search.focus();
    } else if (e.key === 'Escape' && document.activeElement === search) {
      search.value = '';
      renderGrid('');
    } else if (e.key === 'Enter' && (e.ctrlKey || e.metaKey) && !checkoutBtn.disabled) {
      e.preventDefault();
      checkoutForm.requestSubmit();
    }
  });

  renderChips();
  grid.innerHTML = skeletonHtml(12);
  requestAnimationFrame(() => renderGrid(''));

  // A completed sale spends its draft; anything else resumes where the admin
  // left off. renderCart() then paints the restored lines and, through
  // updateTotals(), rebuilds cart_json and the totals.
  if (draftConsumed) {
    clearDraft();
  } else {
    restoreDraft();
  }
  renderCart();
})();
</script>
</div>
