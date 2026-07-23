<?php
/** @var string $productsJson */
?>
<div class="row g-4">
  <div class="col-lg-7">
    <div class="admin-card">
      <label>Search Product <small class="text-white-50">(name, SKU, or scan barcode)</small></label>
      <input type="text" id="posSearch" class="form-control mb-3" placeholder="Type to search or scan a barcode…" autofocus>

      <div id="posProductGrid" class="row g-2" style="max-height:480px; overflow-y:auto;"></div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="admin-card">
      <h6 class="mb-3">Cart</h6>
      <div class="table-responsive mb-3">
        <table class="admin-table" id="posCartTable">
          <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th><th></th></tr></thead>
          <tbody id="posCartBody">
            <tr id="posCartEmptyRow"><td colspan="5" class="text-white-50 text-center py-3">Cart is empty</td></tr>
          </tbody>
        </table>
      </div>

      <form method="post" action="<?= url('/admin/pos/checkout') ?>" id="posCheckoutForm">
        <?= Security::csrfField() ?>
        <input type="hidden" name="cart_json" id="posCartJson" value="[]">

        <div class="row g-2 mb-2">
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

        <div class="d-flex justify-content-between fs-5 mb-3">
          <span>Total</span>
          <span class="text-orange fw-bold" id="posTotalDisplay">৳0.00</span>
        </div>

        <button type="submit" class="btn btn-ps w-100" id="posCheckoutBtn" disabled>Complete Sale</button>
      </form>
    </div>
  </div>
</div>

<script>
(function () {
  const products = <?= $productsJson ?>;
  const cart = {}; // product_id -> {product, qty}

  const grid = document.getElementById('posProductGrid');
  const search = document.getElementById('posSearch');
  const cartBody = document.getElementById('posCartBody');
  const cartJson = document.getElementById('posCartJson');
  const discountInput = document.getElementById('posDiscount');
  const totalDisplay = document.getElementById('posTotalDisplay');
  const checkoutBtn = document.getElementById('posCheckoutBtn');

  function money(n) { return '৳' + Number(n).toFixed(2); }

  function renderGrid(filter) {
    const term = (filter || '').trim().toLowerCase();
    const matches = !term ? products : products.filter(p =>
      p.name.toLowerCase().includes(term) ||
      (p.sku && p.sku.toLowerCase().includes(term)) ||
      (p.barcode && p.barcode.toLowerCase() === term)
    );

    grid.innerHTML = matches.slice(0, 60).map(p => `
      <div class="col-6 col-md-4">
        <div class="pos-product-tile" data-id="${p.id}">
          <div class="fw-semibold small">${p.name}</div>
          <div class="text-white-50 small">${p.sku}</div>
          <div class="text-orange small">${p.offer_is_live ? money(p.display_price) + ' <s>' + money(p.selling_price) + '</s>' : money(p.selling_price)}</div>
          <div class="text-white-50 small">Stock: ${p.stock_qty}</div>
        </div>
      </div>
    `).join('') || '<p class="text-white-50 text-center py-4">No products found.</p>';

    grid.querySelectorAll('.pos-product-tile').forEach(el => {
      el.addEventListener('click', () => addToCart(parseInt(el.dataset.id, 10)));
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
      cartBody.innerHTML = '<tr id="posCartEmptyRow"><td colspan="5" class="text-white-50 text-center py-3">Cart is empty</td></tr>';
    } else {
      cartBody.innerHTML = lines.map(line => {
        const price = line.product.display_price;
        const subtotal = price * line.qty;
        return `
          <tr>
            <td>${line.product.name}</td>
            <td>
              <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-ps-outline btn-sm" data-action="dec" data-id="${line.product.id}">-</button>
                <span>${line.qty}</span>
                <button type="button" class="btn btn-ps-outline btn-sm" data-action="inc" data-id="${line.product.id}">+</button>
              </div>
            </td>
            <td>${money(price)}</td>
            <td>${money(subtotal)}</td>
            <td><button type="button" class="btn btn-link text-danger p-0" data-action="remove" data-id="${line.product.id}"><i class="bi bi-trash"></i></button></td>
          </tr>
        `;
      }).join('');
    }

    cartBody.querySelectorAll('[data-action]').forEach(btn => {
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
    const discount = parseFloat(discountInput.value || '0') || 0;
    const total = Math.max(0, subtotal - discount);

    totalDisplay.textContent = money(total);
    checkoutBtn.disabled = lines.length === 0;

    cartJson.value = JSON.stringify(lines.map(l => ({ product_id: l.product.id, qty: l.qty })));
  }

  search.addEventListener('input', () => renderGrid(search.value));
  discountInput.addEventListener('input', updateTotals);

  document.getElementById('posCheckoutForm').addEventListener('submit', () => {
    updateTotals();
  });

  renderGrid('');
})();
</script>
