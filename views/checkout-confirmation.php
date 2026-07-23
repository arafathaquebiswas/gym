<?php $pageTitle = 'Order Confirmed'; /** @var array $order */ /** @var array $items */ ?>

<section class="section">
  <div class="container">
    <div class="glass-card p-5 text-center mx-auto" style="max-width:700px;">
      <i class="bi bi-check-circle-fill text-orange" style="font-size:3rem"></i>
      <h2 class="mt-3">Thank You!</h2>
      <p class="text-white-50">Your order has been placed successfully.</p>
      <div class="fs-4 fw-bold text-orange mb-4">Order #<?= e($order['order_no']) ?></div>

      <div class="text-start">
        <table class="admin-table w-100 mb-3">
          <thead><tr><th>Item</th><th>Qty</th><th>Subtotal</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): ?>
            <tr><td><?= e($item['product_name']) ?></td><td><?= (int) $item['qty'] ?></td><td>৳<?= number_format((float) $item['subtotal']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="d-flex justify-content-between"><span>Subtotal</span><span>৳<?= number_format((float) $order['subtotal']) ?></span></div>
        <div class="d-flex justify-content-between"><span>Discount</span><span>৳<?= number_format((float) $order['discount']) ?></span></div>
        <div class="d-flex justify-content-between"><span>Shipping</span><span><?= (float) $order['shipping_charge'] > 0 ? '৳' . number_format((float) $order['shipping_charge']) : 'Free' ?></span></div>
        <div class="d-flex justify-content-between"><span>Tax</span><span>৳<?= number_format((float) $order['tax']) ?></span></div>
        <div class="d-flex justify-content-between fw-bold text-orange"><span>Total</span><span>৳<?= number_format((float) $order['total']) ?></span></div>
        <div class="d-flex justify-content-between small text-white-50 mt-2">
          <span>Payment Method</span><span><?= e(strtoupper(str_replace('_', ' ', $order['payment_method']))) ?></span>
        </div>
        <div class="d-flex justify-content-between small text-white-50">
          <span>Delivering to</span><span><?= e($order['delivery_address'] . ', ' . $order['delivery_city']) ?></span>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2 justify-content-center flex-wrap">
        <a href="<?= url('/store') ?>" class="btn btn-ps-outline">Continue Shopping</a>
        <?php if (Auth::hasRole('member')): ?>
          <a href="<?= url('/account/orders') ?>" class="btn btn-ps">View My Orders</a>
        <?php else: ?>
          <a href="<?= url('/track-order') ?>" class="btn btn-ps">Track This Order</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
