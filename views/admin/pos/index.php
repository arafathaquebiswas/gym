<?php
/** @var string $productsJson */
/** @var string $membersJson */
/** @var bool $taxEnabled */
/** @var float $taxPercent */
?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="admin-card">
      <div class="pos-search-wrap mb-2">
        <i class="bi bi-search"></i>
        <input type="text" id="posSearch" class="form-control" placeholder="Search by name or SKU, or scan a barcode…" autofocus>
      </div>
      <p class="text-white-50 small mb-3">Tip: press <kbd>/</kbd> to search, <kbd>Esc</kbd> to clear, <kbd>Ctrl</kbd>+<kbd>Enter</kbd> to complete the sale.</p>

      <div id="posChips" class="pos-chips mb-4"></div>

      <div id="posProductGrid" class="pos-product-grid" style="max-height:560px; overflow-y:auto;"></div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="admin-card pos-cart-panel d-flex flex-column" style="min-height:520px;">
      <h6 class="mb-3">Cart</h6>

      <label>Customer</label>
      <select id="posMemberSelect" class="form-select mb-3">
        <option value="">Walk-in Customer</option>
      </select>

      <div id="posCartList" class="flex-grow-1 mb-3" style="overflow-y:auto; max-height:320px;">
        <p id="posCartEmpty" class="text-white-50 text-center py-4 mb-0">Cart is empty — search for a product to get started.</p>
      </div>

      <form method="post" action="<?= url('/admin/pos/checkout') ?>" id="posCheckoutForm" class="mt-auto">
        <?= Security::csrfField() ?>
        <input type="hidden" name="cart_json" id="posCartJson" value="[]">
        <input type="hidden" name="member_id" id="posMemberIdField" value="">

        <div class="row g-2 mb-3">
          <div class="col-6">
            <label>Discount (৳)</label>
            <input type="number" step="0.01" min="0" name="discount" id="posDiscount" class="form-control" value="0">
          </div>
          <div class="col-6">
            <label>Coupon Code</label>
            <input type="text" name="coupon_code" class="form-control" placeholder="Optional">
          </div>
        </div>

        <label>Payment Method</label>
        <select name="payment_method" class="form-select mb-3">
          <option value="cash">Cash</option>
          <option value="card">Card</option>
          <option value="bkash">bKash</option>
          <option value="nagad">Nagad</option>
          <option value="rocket">Rocket</option>
          <option value="bank_transfer">Bank Transfer</option>
        </select>

        <div class="pos-totals mb-3">
          <div class="d-flex justify-content-between small text-white-50"><span>Subtotal</span><span id="posSubtotalDisplay">৳0.00</span></div>
          <div class="d-flex justify-content-between small text-white-50"><span>Discount</span><span id="posDiscountDisplay">− ৳0.00</span></div>
          <?php if ($taxEnabled): ?>
          <div class="d-flex justify-content-between small text-white-50"><span>Tax (<?= e((string) $taxPercent) ?>%)</span><span id="posTaxDisplay">৳0.00</span></div>
          <?php endif; ?>
          <div class="d-flex justify-content-between align-items-baseline pt-2 mt-1" style="border-top:1px solid var(--ps-border)">
            <span class="text-white-50">Total</span>
            <span class="text-orange fw-bold fs-3" id="posTotalDisplay">৳0.00</span>
          </div>
        </div>

        <button type="submit" class="btn btn-ps w-100 py-2" id="posCheckoutBtn" disabled>Complete Sale</button>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="posDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark">
      <div class="modal-header">
        <h6 class="modal-title" id="posDetailsName">Product Details</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 small">
          <div class="col-6"><span class="text-white-50">SKU</span><br><span id="posDetailsSku">—</span></div>
          <div class="col-6"><span class="text-white-50">Barcode</span><br><span id="posDetailsBarcode">—</span></div>
          <div class="col-6"><span class="text-white-50">Category</span><br><span id="posDetailsCategory">—</span></div>
          <div class="col-6"><span class="text-white-50">Stock</span><br><span id="posDetailsStock">—</span></div>
          <div class="col-6"><span class="text-white-50">Regular Price</span><br><span id="posDetailsRegularPrice">—</span></div>
          <div class="col-6"><span class="text-white-50">Offer Price</span><br><span id="posDetailsOfferPrice">—</span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-ps btn-sm" id="posDetailsAddBtn">Add to Cart</button>
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
  let activeChip = ''; // '' = All, '__favorites', '__recent', or a category name
  const FAVORITES_KEY = 'pos_favorites';
  const RECENT_KEY = 'pos_recent';
  const MAX_RECENT = 12;

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
  let detailsProductId = null;

  // Lazy — bootstrap.bundle.js loads at the bottom of the page, after this inline
  // script runs, so instantiating a Modal at top-level here would throw and silently
  // abort the rest of this IIFE (this was the root cause of a permanently blank grid).
  function getDetailsModal() {
    return bootstrap.Modal.getOrCreateInstance(detailsModalEl);
  }

  function money(n) { return '৳' + Number(n).toFixed(2); }
  function escapeHtml(s) { return String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m])); }

  memberSelect.innerHTML += members.map(m => `<option value="${m.id}">${escapeHtml(m.name)}${m.member_code ? ' (' + escapeHtml(m.member_code) + ')' : ''}</option>`).join('');
  memberSelect.addEventListener('change', () => { memberIdField.value = memberSelect.value; });

  function priceHtml(p) {
    return p.offer_is_live
      ? money(p.display_price) + '<s>' + money(p.selling_price) + '</s>'
      : money(p.selling_price);
  }

  function renderChips() {
    const categories = [...new Set(products.map(p => p.category_name).filter(Boolean))].sort();
    const pinned = [
      { key: '', label: 'All' },
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
        <div class="pos-skeleton-block" style="width:64px;height:64px;border-radius:10px;"></div>
        <div class="pos-skeleton-block" style="width:80%;height:14px;"></div>
        <div class="pos-skeleton-block" style="width:50%;height:18px;"></div>
        <div class="pos-skeleton-block" style="width:90%;height:32px;border-radius:8px;"></div>
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
      return `
      <div class="pos-tile" data-id="${p.id}">
        <button type="button" class="pos-tile-fav ${isFav ? 'active' : ''}" data-action="fav" data-id="${p.id}" title="Favorite"><i class="bi ${isFav ? 'bi-star-fill' : 'bi-star'}"></i></button>
        <div class="pos-tile-thumb">${p.image_url ? `<img src="${escapeHtml(p.image_url)}" alt="">` : '<i class="bi bi-box-seam"></i>'}</div>
        ${p.offer_is_live ? '<span class="pos-tile-offer-badge">OFFER</span>' : ''}
        <div class="pos-tile-name">${escapeHtml(p.name)}</div>
        <div class="pos-tile-price">${priceHtml(p)}</div>
        <div class="pos-tile-stock ${p.stock_qty <= 5 ? 'low' : ''}">${p.stock_qty} in stock</div>
        <button type="button" class="btn btn-ps btn-sm pos-tile-add" data-action="add" data-id="${p.id}">Add to Cart</button>
        <button type="button" class="pos-tile-details" data-action="details" data-id="${p.id}">View Details</button>
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

    // A barcode scanner behaves like fast keyboard input ending in Enter — if there's
    // exactly one exact barcode/SKU match, add it straight to cart instead of just filtering.
    if (term) {
      const exact = products.find(p => (p.barcode && p.barcode.toLowerCase() === term) || p.sku.toLowerCase() === term);
      if (exact && matches.length === 1) {
        addToCart(exact.id);
        search.value = '';
        renderGrid('');
      }
    }
  }

  function showDetails(productId) {
    const p = products.find(x => x.id === productId);
    if (!p) return;
    detailsProductId = productId;
    document.getElementById('posDetailsName').textContent = p.name;
    document.getElementById('posDetailsSku').textContent = p.sku || '—';
    document.getElementById('posDetailsBarcode').textContent = p.barcode || '—';
    document.getElementById('posDetailsCategory').textContent = p.category_name || '—';
    document.getElementById('posDetailsStock').textContent = p.stock_qty;
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

  function renderCart() {
    const lines = Object.values(cart);
    if (!lines.length) {
      cartList.innerHTML = '<p id="posCartEmpty" class="text-white-50 text-center py-4 mb-0">Cart is empty — search for a product to get started.</p>';
    } else {
      cartList.innerHTML = lines.map(line => {
        const price = line.product.display_price;
        const subtotal = price * line.qty;
        return `
          <div class="pos-cart-line d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold small">${escapeHtml(line.product.name)}</div>
              <div class="text-white-50 small">${money(price)} × ${line.qty} = <span class="text-white">${money(subtotal)}</span></div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-ps-outline btn-sm" data-action="dec" data-id="${line.product.id}">−</button>
                <span style="min-width:1.5rem;text-align:center;display:inline-block">${line.qty}</span>
                <button type="button" class="btn btn-ps-outline btn-sm" data-action="inc" data-id="${line.product.id}">+</button>
              </div>
              <button type="button" class="btn btn-link text-danger p-0" data-action="remove" data-id="${line.product.id}"><i class="bi bi-trash"></i></button>
            </div>
          </div>
        `;
      }).join('');
    }

    cartList.querySelectorAll('[data-action]').forEach(btn => {
      const id = parseInt(btn.dataset.id, 10);
      btn.addEventListener('click', () => {
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
  }

  search.addEventListener('input', () => renderGrid(search.value));
  discountInput.addEventListener('input', updateTotals);

  checkoutForm.addEventListener('submit', () => {
    updateTotals();
  });

  // Keyboard shortcuts: "/" focuses search, Esc clears it, Ctrl/Cmd+Enter completes the sale.
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
  renderCart();
})();
</script>
