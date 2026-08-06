<?php
/**
 * Storefront filter sidebar. Everything lives in one GET form so filters compose instead of
 * replacing each other, and submitting always drops ?page — a narrower result set has fewer pages.
 *
 * @var array $categories
 * @var array $brands
 * @var array $filters
 * @var array $priceRange
 * @var array $facets
 * @var bool $hasReviews
 */
$f = $filters;
$activeCount = count(array_filter([
    $f['category'], $f['brand'], $f['availability'], $f['min_price'], $f['max_price'],
    $f['on_sale'], $f['best_seller'], $f['min_rating'],
]));

// Accordion: exactly one section is open — the one the visitor is filtering on, else Category.
// Keeping the rest collapsed is what lets the whole panel fit one product card's height.
$openGroup = match (true) {
    $f['min_price'] !== null || $f['max_price'] !== null => 'price',
    (bool) $f['availability'] => 'availability',
    $f['on_sale'] || $f['best_seller'] => 'offers',
    (bool) $f['min_rating'] => 'rating',
    (bool) $f['brand'] => 'brand',
    default => 'category',
};
$isOpen = fn (string $group) => $openGroup === $group ? 'open' : '';
?>
<form method="get" action="<?= url('/store') ?>" class="shop-sidebar shop-filters glass-card" id="shopFilters">
  <div class="sidebar-header shop-filters-head">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <h6 class="mb-0 fw-bold"><i class="bi bi-sliders text-orange me-1"></i> Filters<?= $activeCount ? ' <span class="filter-count">' . $activeCount . '</span>' : '' ?></h6>
      <?php if ($activeCount || $f['search']): ?>
        <a href="<?= url('/store') ?>" class="small text-white-50 text-decoration-underline">Clear all</a>
      <?php endif; ?>
    </div>
    <div class="input-group input-group-sm mb-2">
      <input type="text" name="q" value="<?= e($f['search'] ?? '') ?>" class="form-control" placeholder="Search products...">
      <button class="btn btn-ps" type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
    </div>
    <select name="sort" class="form-select form-select-sm" aria-label="Sort products">
      <option value="">Newest First</option>
      <option value="price_low" <?= $f['sort'] === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
      <option value="price_high" <?= $f['sort'] === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
      <option value="discount" <?= $f['sort'] === 'discount' ? 'selected' : '' ?>>Biggest Discount</option>
      <?php if ($hasReviews): ?><option value="rating" <?= $f['sort'] === 'rating' ? 'selected' : '' ?>>Top Rated</option><?php endif; ?>
      <option value="name" <?= $f['sort'] === 'name' ? 'selected' : '' ?>>Name: A to Z</option>
    </select>
  </div>

  <div class="sidebar-content shop-filters-scroll">
    <details class="filter-group" data-group="category" <?= $isOpen('category') ?>>
      <summary>Category</summary>
      <ul class="filter-list">
        <li>
          <label class="filter-opt">
            <input type="radio" name="category" value="" <?= !$f['category'] ? 'checked' : '' ?>>
            <span>All Products</span>
          </label>
        </li>
        <?php foreach ($categories as $cat): ?>
        <li>
          <label class="filter-opt">
            <input type="radio" name="category" value="<?= e($cat['slug']) ?>" <?= $f['category'] === $cat['slug'] ? 'checked' : '' ?>>
            <span><?= e($cat['name']) ?></span>
          </label>
          <?php if (!empty($cat['children'])): ?>
          <ul class="filter-list filter-sublist">
            <?php foreach ($cat['children'] as $child): ?>
            <li>
              <label class="filter-opt">
                <input type="radio" name="category" value="<?= e($child['slug']) ?>" <?= $f['category'] === $child['slug'] ? 'checked' : '' ?>>
                <span><?= e($child['name']) ?></span>
              </label>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </details>

    <details class="filter-group" data-group="price" <?= $isOpen('price') ?>>
      <summary>Price (৳)</summary>
      <div class="filter-body">
        <div class="d-flex align-items-center gap-2 mb-2">
          <input type="number" name="min_price" class="form-control form-control-sm" min="0"
                 placeholder="<?= (int) $priceRange['min'] ?>" value="<?= $f['min_price'] !== null ? (int) $f['min_price'] : '' ?>" aria-label="Minimum price">
          <span class="text-white-50 small">to</span>
          <input type="number" name="max_price" class="form-control form-control-sm" min="0"
                 placeholder="<?= (int) $priceRange['max'] ?>" value="<?= $f['max_price'] !== null ? (int) $f['max_price'] : '' ?>" aria-label="Maximum price">
        </div>
        <div class="d-flex flex-wrap gap-1">
          <?php
          $steps = [[null, 1000], [1000, 3000], [3000, 6000], [6000, null]];
          foreach ($steps as [$lo, $hi]):
              $isOn = ($f['min_price'] !== null || $f['max_price'] !== null)
                  && (int) $f['min_price'] === (int) $lo && (int) $f['max_price'] === (int) $hi;
              $label = $lo === null ? 'Under ৳' . number_format($hi) : ($hi === null ? '৳' . number_format($lo) . '+' : '৳' . number_format($lo) . '–' . number_format($hi));
          ?>
          <button type="button" class="price-chip <?= $isOn ? 'active' : '' ?>" data-min="<?= $lo ?>" data-max="<?= $hi ?>"><?= $label ?></button>
          <?php endforeach; ?>
        </div>
      </div>
    </details>

    <?php if (!empty($brands)): ?>
    <details class="filter-group" data-group="brand" <?= $isOpen('brand') ?>>
      <summary>Brand</summary>
      <ul class="filter-list">
        <li>
          <label class="filter-opt">
            <input type="radio" name="brand" value="" <?= !$f['brand'] ? 'checked' : '' ?>>
            <span>All Brands</span>
          </label>
        </li>
        <?php foreach ($brands as $brand): ?>
        <li>
          <label class="filter-opt">
            <input type="radio" name="brand" value="<?= e($brand['slug']) ?>" <?= $f['brand'] === $brand['slug'] ? 'checked' : '' ?>>
            <span><?= e($brand['name']) ?></span>
          </label>
        </li>
        <?php endforeach; ?>
      </ul>
    </details>
    <?php endif; ?>

    <details class="filter-group" data-group="availability" <?= $isOpen('availability') ?>>
      <summary>Availability</summary>
      <ul class="filter-list">
        <?php
        $availOpts = [
            '' => ['Any', null],
            'in' => ['🟢 In stock', $facets['in']],
            'low' => ['🟡 Only a few left', $facets['low']],
            'out' => ['🔴 Out of stock', $facets['out']],
        ];
        foreach ($availOpts as $value => [$label, $count]):
        ?>
        <li>
          <label class="filter-opt">
            <input type="radio" name="availability" value="<?= $value ?>" <?= (string) $f['availability'] === (string) $value ? 'checked' : '' ?>>
            <span><?= $label ?></span>
            <?php if ($count !== null): ?><em class="filter-facet"><?= $count ?></em><?php endif; ?>
          </label>
        </li>
        <?php endforeach; ?>
      </ul>
    </details>

    <details class="filter-group" data-group="offers" <?= $isOpen('offers') ?>>
      <summary>Offers</summary>
      <ul class="filter-list">
        <li>
          <label class="filter-opt">
            <input type="checkbox" name="on_sale" value="1" <?= $f['on_sale'] ? 'checked' : '' ?>>
            <span>On Sale / Discounted</span>
            <em class="filter-facet"><?= (int) $facets['on_sale'] ?></em>
          </label>
        </li>
        <li>
          <label class="filter-opt">
            <input type="checkbox" name="best_seller" value="1" <?= $f['best_seller'] ? 'checked' : '' ?>>
            <span><i class="bi bi-fire text-warning"></i> Best Sellers</span>
            <em class="filter-facet"><?= (int) $facets['best_seller'] ?></em>
          </label>
        </li>
      </ul>
    </details>

    <?php if ($hasReviews): ?>
    <details class="filter-group" data-group="rating" <?= $isOpen('rating') ?>>
      <summary>Customer Rating</summary>
      <ul class="filter-list">
        <li>
          <label class="filter-opt">
            <input type="radio" name="min_rating" value="" <?= !$f['min_rating'] ? 'checked' : '' ?>>
            <span>Any rating</span>
          </label>
        </li>
        <?php foreach ([4, 3, 2] as $stars): ?>
        <li>
          <label class="filter-opt">
            <input type="radio" name="min_rating" value="<?= $stars ?>" <?= (int) $f['min_rating'] === $stars ? 'checked' : '' ?>>
            <span class="text-warning"><?= str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) ?></span>
            <span class="text-white-50 small">&amp; up</span>
          </label>
        </li>
        <?php endforeach; ?>
      </ul>
    </details>
    <?php endif; ?>
  </div>

  <div class="sidebar-footer shop-filters-foot">
    <button type="submit" class="btn btn-ps btn-sm w-100"><i class="bi bi-funnel me-1"></i> Apply Filters</button>
  </div>
</form>
