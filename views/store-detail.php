<?php
$pageTitle = $product['name'];
/** @var array $product */
/** @var array $images */
/** @var array $reviews */
/** @var array $ratingSummary */
/** @var bool $canReview */
/** @var array $relatedProducts */
/** @var array $frequentlyBoughtWith */
/** @var bool $inWishlist */
/** @var bool $isBestSeller */
/** @var bool $isPopular */
$allImages = array_merge(
    $product['image'] ? [['image_path' => $product['image']]] : [],
    $images
);
$discountPercent = $product['offer_is_live'] && $product['selling_price'] > 0
    ? round((($product['selling_price'] - $product['offer_price']) / $product['selling_price']) * 100)
    : 0;
$isOutOfStock = $product['stock_qty'] <= 0;
$extraScripts = ['js/pricing-countdown.js'];
?>

<section class="section">
  <div class="container">
    <nav class="mb-4 small text-white-50">
      <a href="<?= url('/store') ?>" class="text-white-50">Store</a> /
      <a href="<?= url('/store?category=' . urlencode($product['category_slug'])) ?>" class="text-white-50"><?= e($product['category_name']) ?></a> /
      <span class="text-orange"><?= e($product['name']) ?></span>
    </nav>
    <div class="row g-5">
      <div class="col-lg-6">
        <div style="border-radius:var(--ps-radius);overflow:hidden;cursor:zoom-in" data-bs-toggle="modal" data-bs-target="#zoomModal" id="mainImageWrap">
          <?= media_tile($allImages[0]['image_path'] ?? null, $product['name'], 'bi-box-seam', '', null) ?>
        </div>
        <?php if (count($allImages) > 1): ?>
        <div class="d-flex gap-2 mt-3 flex-wrap">
          <?php foreach ($allImages as $i => $img): ?>
            <div class="gallery-thumb" style="width:70px;height:70px;border-radius:8px;overflow:hidden;cursor:pointer;<?= $i === 0 ? 'outline:2px solid var(--ps-orange)' : '' ?>"
                 onclick="document.getElementById('mainImageWrap').innerHTML=this.innerHTML; document.getElementById('zoomModalBody').innerHTML=this.innerHTML;">
              <?= media_tile($img['image_path'], $product['name'] . ' photo ' . ($i + 1), 'bi-box-seam') ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-6">
        <span class="cat-tag"><?= e($product['category_name']) ?></span>
        <?php if (strtotime($product['created_at']) >= strtotime('-14 days')): ?><span class="badge bg-info text-dark">New Arrival</span><?php endif; ?>
        <?php if ($isBestSeller): ?><span class="badge bg-primary">Best Seller</span><?php endif; ?>
        <?php if ($isPopular): ?><span class="badge" style="background:#ff6a1a">Popular</span><?php endif; ?>
        <h1 class="mt-2 mb-1"><?= e($product['name']) ?></h1>
        <?php if (!empty($product['brand_name'])): ?><p class="mb-2"><a href="<?= url('/store?brand=' . urlencode($product['brand_slug'])) ?>" class="text-white-50">by <?= e($product['brand_name']) ?></a></p><?php endif; ?>

        <?php if (Feature::on('reviews') && $ratingSummary['count'] > 0): ?>
        <div class="mb-3">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <i class="bi <?= $s <= round($ratingSummary['average']) ? 'bi-star-fill text-orange' : 'bi-star text-white-50' ?>"></i>
          <?php endfor; ?>
          <span class="text-white-50 small ms-1"><?= $ratingSummary['average'] ?> (<?= (int) $ratingSummary['count'] ?> review<?= $ratingSummary['count'] === 1 ? '' : 's' ?>)</span>
        </div>
        <?php endif; ?>

        <div class="mb-2" data-pkg-card>
          <?php if ($product['offer_is_live']): ?>
            <div class="offer-only">
              <div class="d-flex align-items-baseline gap-2">
                <div class="pkg-price">৳<?= number_format((float) $product['offer_price']) ?></div>
                <div class="text-white-50 text-decoration-line-through">৳<?= number_format((float) $product['selling_price']) ?></div>
                <span class="badge bg-danger"><?= $discountPercent ?>% OFF</span>
              </div>
              <span class="pkg-savings small">Save ৳<?= number_format((float) $product['selling_price'] - (float) $product['offer_price']) ?> (<?= $discountPercent ?>% OFF)</span>
              <?php if ($product['offer_end_date']): ?>
              <div class="offer-countdown-label mt-2 small">Offer Ends In</div>
              <div class="offer-countdown" data-offer-countdown="<?= e($product['offer_end_date']) ?>">
                <div class="offer-countdown-unit"><div class="num js-days">00</div><div class="label">Days</div></div>
                <div class="offer-countdown-unit"><div class="num js-hours">00</div><div class="label">Hrs</div></div>
                <div class="offer-countdown-unit"><div class="num js-minutes">00</div><div class="label">Min</div></div>
              </div>
              <?php endif; ?>
            </div>
            <div class="offer-expired-fallback d-none">
              <div class="pkg-price">৳<?= number_format((float) $product['selling_price']) ?></div>
            </div>
          <?php else: ?>
            <div class="pkg-price">৳<?= number_format((float) $product['selling_price']) ?></div>
          <?php endif; ?>
        </div>

        <p class="text-white-50"><?= nl2br(e($product['description'])) ?></p>

        <ul class="list-unstyled small text-white-50 mb-3">
          <li>SKU: <?= e($product['sku']) ?></li>
          <?php if ($product['barcode']): ?><li>Barcode: <?= e($product['barcode']) ?></li><?php endif; ?>
        </ul>

        <?php if (!$isOutOfStock): ?>
          <span class="badge bg-success mb-3">In Stock (<?= (int) $product['stock_qty'] ?>)</span>
        <?php elseif ($product['allow_preorder'] && Feature::on('preorder')): ?>
          <span class="badge bg-warning text-dark mb-3">Pre-Order Available</span>
        <?php else: ?>
          <span class="badge bg-danger mb-3">Out of Stock</span>
        <?php endif; ?>

        <?php if (!$isOutOfStock || ($product['allow_preorder'] && Feature::on('preorder'))): ?>
        <form method="post" action="<?= url('/cart/add') ?>" class="d-flex gap-2 align-items-center mb-3">
          <?= Security::csrfField() ?>
          <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
          <input type="hidden" name="redirect_to" value="store/<?= e($product['slug']) ?>">
          <label class="me-1 mb-0">Qty</label>
          <input type="number" name="qty" value="1" min="1" <?= $isOutOfStock ? '' : 'max="' . (int) $product['stock_qty'] . '"' ?> class="form-control" style="width:90px">
          <button type="submit" class="btn btn-ps-outline"><i class="bi bi-cart-plus"></i> Add to Cart</button>
          <button type="submit" name="buy_now" value="1" class="btn btn-ps">Buy Now</button>
        </form>
        <?php else: ?>
          <p class="text-white-50 small">This item is currently unavailable for online purchase.</p>
        <?php endif; ?>

        <?php if (Auth::hasRole('member') && Feature::on('wishlist')): ?>
        <form method="post" action="<?= url('/store/' . $product['slug'] . '/wishlist') ?>">
          <?= Security::csrfField() ?>
          <button type="submit" class="btn btn-link text-white-50 p-0">
            <i class="bi <?= $inWishlist ? 'bi-heart-fill text-orange' : 'bi-heart' ?>"></i> <?= $inWishlist ? 'Saved to Wishlist' : 'Add to Wishlist' ?>
          </button>
        </form>
        <?php endif; ?>

        <?php if ($product['ingredients']): ?>
        <div class="mt-4">
          <h6>Ingredients</h6>
          <p class="text-white-50 small"><?= nl2br(e($product['ingredients'])) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($product['nutrition_facts']): ?>
        <div class="mt-3">
          <h6>Nutrition Facts</h6>
          <p class="text-white-50 small"><?= nl2br(e($product['nutrition_facts'])) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if (Feature::on('reviews')): ?>
    <div class="row mt-5">
      <div class="col-lg-8">
        <h4 class="mb-3">Reviews (<?= (int) $ratingSummary['count'] ?>)</h4>
        <?php if ($canReview): ?>
        <form method="post" action="<?= url('/store/' . $product['slug'] . '/review') ?>" enctype="multipart/form-data" class="glass-card p-4 mb-4">
          <?= Security::csrfField() ?>
          <label class="d-block">Your Rating</label>
          <input type="hidden" name="rating" id="ratingInput" value="5">
          <div id="starPicker" class="mb-2" style="font-size:1.5rem;cursor:pointer;">
            <?php for ($s = 1; $s <= 5; $s++): ?><i class="bi bi-star-fill star-pick text-orange" data-value="<?= $s ?>" style="margin-right:4px;"></i><?php endfor; ?>
          </div>
          <label>Comment</label>
          <textarea name="comment" class="form-control mb-2" rows="3" placeholder="Tell us what you thought..."></textarea>
          <label>Photos <small class="text-white-50">(optional)</small></label>
          <input type="file" name="photos[]" class="form-control mb-2" accept="image/jpeg,image/png,image/webp" multiple>
          <button type="submit" class="btn btn-ps btn-sm">Submit Review</button>
        </form>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
          <p class="text-white-50">No reviews yet<?= $canReview ? '' : ' — reviews are only accepted from verified purchasers' ?>.</p>
        <?php else: ?>
        <?php foreach ($reviews as $review): ?>
        <div class="glass-card p-3 mb-3">
          <div class="d-flex justify-content-between">
            <div>
              <strong><?= e($review['member_name']) ?></strong>
              <span class="badge-ps badge ms-2" style="font-size:.65rem"><i class="bi bi-patch-check-fill"></i> Verified Purchase</span>
            </div>
            <span class="text-white-50 small"><?= format_date($review['created_at']) ?></span>
          </div>
          <div>
            <?php for ($s = 1; $s <= 5; $s++): ?><i class="bi <?= $s <= $review['rating'] ? 'bi-star-fill text-orange' : 'bi-star text-white-50' ?> small"></i><?php endfor; ?>
          </div>
          <?php if ($review['comment']): ?><p class="text-white-50 small mb-0 mt-1"><?= nl2br(e($review['comment'])) ?></p><?php endif; ?>
          <?php if (!empty($review['photos'])): ?>
          <div class="d-flex gap-2 mt-2 flex-wrap">
            <?php foreach ($review['photos'] as $photo): ?>
              <div style="width:60px;height:60px;border-radius:6px;overflow:hidden"><?= media_tile($photo['image_path'], 'Review photo', 'bi-image') ?></div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($review['admin_reply'])): ?>
          <div class="glass-card p-2 mt-2" style="background:rgba(255,106,26,.08)">
            <div class="text-orange small fw-semibold">Reply from PowerSurge Gym:</div>
            <div class="small text-white-50"><?= nl2br(e($review['admin_reply'])) ?></div>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($frequentlyBoughtWith)): ?>
    <div class="mt-5">
      <h4 class="mb-3">Frequently Bought Together</h4>
      <div class="row g-4">
        <?php foreach ($frequentlyBoughtWith as $fbt): ?>
        <div class="col-6 col-lg-3">
          <a href="<?= url('/store/' . $fbt['slug']) ?>" class="text-decoration-none">
            <div class="glass-card product-card">
              <div class="product-thumb"><?= media_tile($fbt['image'], $fbt['name'], 'bi-box-seam') ?></div>
              <h6 class="mb-1 text-white"><?= e($fbt['name']) ?></h6>
              <div class="price">৳<?= number_format((float) $fbt['display_price']) ?></div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($relatedProducts)): ?>
    <div class="mt-5">
      <h4 class="mb-3">Related Products</h4>
      <div class="row g-4">
        <?php foreach ($relatedProducts as $related): ?>
        <div class="col-6 col-lg-3">
          <a href="<?= url('/store/' . $related['slug']) ?>" class="text-decoration-none">
            <div class="glass-card product-card">
              <div class="product-thumb"><?= media_tile($related['image'], $related['name'], 'bi-box-seam') ?></div>
              <h6 class="mb-1 text-white"><?= e($related['name']) ?></h6>
              <div class="price">৳<?= number_format((float) $related['display_price']) ?></div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<div class="modal fade" id="zoomModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark">
      <div class="modal-body p-0" id="zoomModalBody">
        <?= media_tile($allImages[0]['image_path'] ?? null, $product['name'], 'bi-box-seam', 'w-100', null) ?>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var picker = document.getElementById('starPicker');
  if (!picker) return;
  var stars = picker.querySelectorAll('.star-pick');
  var input = document.getElementById('ratingInput');

  function paint(value) {
    stars.forEach(function (star) {
      var isFilled = parseInt(star.dataset.value, 10) <= value;
      star.classList.toggle('bi-star-fill', isFilled);
      star.classList.toggle('bi-star', !isFilled);
      star.classList.toggle('text-orange', isFilled);
      star.classList.toggle('text-white-50', !isFilled);
    });
  }

  stars.forEach(function (star) {
    star.addEventListener('mouseenter', function () { paint(parseInt(star.dataset.value, 10)); });
    star.addEventListener('click', function () {
      input.value = star.dataset.value;
      paint(parseInt(star.dataset.value, 10));
    });
  });
  picker.addEventListener('mouseleave', function () { paint(parseInt(input.value, 10)); });

  paint(parseInt(input.value, 10));
})();
</script>
