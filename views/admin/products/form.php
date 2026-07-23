<?php
/** @var array|null $product */
/** @var array $categories */
/** @var array $brands */
/** @var array $gallery */
$gallery ??= [];
$isEdit = $product !== null;
$action = $isEdit ? url('/admin/products/' . $product['id']) : url('/admin/products');
$v = fn ($key, $default = '') => e((string) ($product[$key] ?? $default));
$checked = fn ($key) => !empty($product[$key]) ? 'checked' : '';
?>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="admin-form">
  <?= Security::csrfField() ?>

  <div class="admin-card mb-4">
    <div class="admin-form-section">
      <h6>Basic Information</h6>
      <div class="row g-3">
        <div class="col-md-6">
          <label>Product Name *</label>
          <input type="text" name="name" class="form-control" value="<?= $v('name') ?>" required>
        </div>
        <div class="col-md-3">
          <label>Category *</label>
          <select name="category_id" class="form-select" required>
            <option value="">— Select —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int) $cat['id'] ?>" <?= (string) ($product['category_id'] ?? '') === (string) $cat['id'] ? 'selected' : '' ?>>
                <?= $cat['parent_id'] ? '— ' : '' ?><?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label>Brand</label>
          <select name="brand_id" class="form-select">
            <option value="">— None —</option>
            <?php foreach ($brands as $brand): ?>
              <option value="<?= (int) $brand['id'] ?>" <?= (string) ($product['brand_id'] ?? '') === (string) $brand['id'] ? 'selected' : '' ?>><?= e($brand['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <a href="<?= url('/admin/brands') ?>" class="text-white-50 small" target="_blank">+ Manage Brands</a>
        </div>
        <div class="col-md-4">
          <label>SKU *</label>
          <input type="text" name="sku" class="form-control" value="<?= $v('sku') ?>" required>
        </div>
        <div class="col-md-4">
          <label>Barcode</label>
          <input type="text" name="barcode" class="form-control" value="<?= $v('barcode') ?>">
        </div>
        <div class="col-md-4">
          <label>Expiry Date <small class="text-white-50">(optional)</small></label>
          <input type="date" name="expiry_date" class="form-control" value="<?= $v('expiry_date') ?>">
        </div>
        <div class="col-12">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="3"><?= $v('description') ?></textarea>
        </div>
        <div class="col-md-6">
          <label>Ingredients <small class="text-white-50">(supplements)</small></label>
          <textarea name="ingredients" class="form-control" rows="3"><?= $v('ingredients') ?></textarea>
        </div>
        <div class="col-md-6">
          <label>Nutrition Facts</label>
          <textarea name="nutrition_facts" class="form-control" rows="3"><?= $v('nutrition_facts') ?></textarea>
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Pricing &amp; Stock</h6>
      <div class="row g-3">
        <div class="col-md-3">
          <label>Buying Price (৳)</label>
          <input type="number" step="0.01" min="0" name="buying_price" class="form-control" value="<?= $v('buying_price', '0') ?>">
        </div>
        <div class="col-md-3">
          <label>Selling Price (৳) *</label>
          <input type="number" step="0.01" min="0" name="selling_price" class="form-control" value="<?= $v('selling_price', '0') ?>" required>
        </div>
        <div class="col-md-3">
          <label>Stock Quantity</label>
          <input type="number" min="0" name="stock_qty" class="form-control" value="<?= $v('stock_qty', '0') ?>">
        </div>
        <div class="col-md-3">
          <label>Low Stock Threshold</label>
          <input type="number" min="0" name="min_stock" class="form-control" value="<?= $v('min_stock', '5') ?>">
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Offer / Discount</h6>
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label>Offer Price (৳)</label>
          <input type="number" step="0.01" min="0" name="offer_price" class="form-control" value="<?= $v('offer_price') ?>">
        </div>
        <div class="col-md-3">
          <label>Offer Start</label>
          <input type="date" name="offer_start_date" class="form-control" value="<?= $v('offer_start_date') ?>">
        </div>
        <div class="col-md-3">
          <label>Offer End</label>
          <input type="date" name="offer_end_date" class="form-control" value="<?= $v('offer_end_date') ?>">
        </div>
        <div class="col-md-3 form-check mb-2">
          <input type="checkbox" name="offer_enabled" value="1" class="form-check-input" id="offerEnabled" <?= $checked('offer_enabled') ?>>
          <label class="form-check-label" for="offerEnabled">Offer Enabled</label>
        </div>
        <div class="col-md-4">
          <label>Shipping Charge Override (৳) <small class="text-white-50">(leave blank to use the site-wide flat rate)</small></label>
          <input type="number" step="0.01" min="0" name="shipping_charge" class="form-control" value="<?= $v('shipping_charge') ?>">
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <h6>Main Image</h6>
      <div class="row g-3">
        <div class="col-md-6">
          <?php if ($isEdit && $product['image']): ?>
            <div class="mb-2"><?= media_tile($product['image'], $product['name'], 'bi-box-seam', '', null) ?></div>
          <?php endif; ?>
          <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>
      </div>
    </div>

    <div class="admin-form-section">
      <div class="d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Visibility &amp; Availability</h6>
      </div>
      <div class="row g-3 mt-1">
        <div class="col-md-4">
          <label>Status</label>
          <?php $currentStatus = $product['status'] ?? 'published'; ?>
          <select name="status" class="form-select">
            <option value="draft" <?= $currentStatus === 'draft' ? 'selected' : '' ?>>Draft — not shown publicly, still being prepared</option>
            <option value="published" <?= $currentStatus === 'published' ? 'selected' : '' ?>>Published — live in the store</option>
            <option value="hidden" <?= $currentStatus === 'hidden' ? 'selected' : '' ?>>Hidden — finished but deliberately not shown</option>
          </select>
        </div>
        <div class="col-md-4 form-check align-self-end mb-2">
          <input type="checkbox" name="allow_preorder" value="1" class="form-check-input" id="allowPreorder" <?= $checked('allow_preorder') ?>>
          <label class="form-check-label" for="allowPreorder">Allow Pre-Order when out of stock</label>
        </div>
        <div class="col-md-4 form-check align-self-end mb-2">
          <input type="checkbox" name="bogo_enabled" value="1" class="form-check-input" id="bogoEnabled" <?= $checked('bogo_enabled') ?>>
          <label class="form-check-label" for="bogoEnabled">Buy One Get One (every 2nd unit free)</label>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mt-2">
      <button type="submit" class="btn btn-ps"><?= $isEdit ? 'Save Changes' : 'Add Product' ?></button>
      <a href="<?= url('/admin/products') ?>" class="btn btn-ps-outline">Cancel</a>
    </div>
  </div>
</form>

<?php if ($isEdit): ?>
<div class="admin-card">
  <h6 class="mb-3">Photo Gallery</h6>
  <div class="d-flex flex-wrap gap-3 mb-3">
    <?php foreach ($gallery as $image): ?>
    <div style="width:100px;">
      <div style="width:100px;height:100px;border-radius:8px;overflow:hidden;"><?= media_tile($image['image_path'], $product['name'], 'bi-box-seam') ?></div>
      <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/gallery/' . $image['id'] . '/delete') ?>" onsubmit="return confirm('Remove this photo?');" class="mt-1">
        <?= Security::csrfField() ?>
        <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash"></i></button>
      </form>
    </div>
    <?php endforeach; ?>
    <?php if (empty($gallery)): ?><p class="text-white-50">No gallery photos yet.</p><?php endif; ?>
  </div>
  <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/gallery') ?>" enctype="multipart/form-data">
    <?= Security::csrfField() ?>
    <label>Add Photos</label>
    <input type="file" name="images[]" class="form-control mb-2" accept="image/jpeg,image/png,image/webp" multiple>
    <button type="submit" class="btn btn-ps-outline btn-sm">Upload</button>
  </form>
</div>

<?php
/** @var array $allAttributes */
/** @var array $assignedAttributes */
/** @var array $variants */
/** @var array $tags */
/** @var array $relatedProductIds */
/** @var array $allProducts */
$attributeValuesById = [];
foreach ($allAttributes as $attr) {
    $attributeValuesById[$attr['id']] = $attr['values'];
}
$assignedAttributeIds = array_column($assignedAttributes, 'id');
?>

<div class="admin-card mt-4">
  <h6 class="mb-2">Attributes Used By This Product</h6>
  <p class="text-white-50 small">Pick which attributes apply (e.g. Size, Color) — this drives the value pickers when building variants below.</p>
  <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/attributes') ?>">
    <?= Security::csrfField() ?>
    <div class="d-flex flex-wrap gap-3 mb-3">
      <?php foreach ($allAttributes as $attr): ?>
      <div class="form-check">
        <input type="checkbox" name="attribute_ids[]" value="<?= (int) $attr['id'] ?>" class="form-check-input" id="attr<?= $attr['id'] ?>" <?= in_array((int) $attr['id'], $assignedAttributeIds, true) ? 'checked' : '' ?>>
        <label class="form-check-label" for="attr<?= $attr['id'] ?>"><?= e($attr['name']) ?></label>
      </div>
      <?php endforeach; ?>
      <?php if (empty($allAttributes)): ?>
        <p class="text-white-50 small mb-0">No attributes exist yet — <a href="<?= url('/admin/attributes') ?>">create some first</a>.</p>
      <?php endif; ?>
    </div>
    <button type="submit" class="btn btn-ps-outline btn-sm">Save Attributes</button>
  </form>
</div>

<div class="admin-card mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="mb-0">Variants (<?= count($variants) ?>)</h6>
    <?php if (!empty($assignedAttributeIds)): ?>
      <button type="button" class="btn btn-ps btn-sm" data-bs-toggle="modal" data-bs-target="#addVariantModal"><i class="bi bi-plus-lg"></i> Add Variant</button>
    <?php endif; ?>
  </div>

  <?php if (empty($assignedAttributeIds)): ?>
    <p class="text-white-50 small mb-0">Assign at least one attribute above before adding variants.</p>
  <?php elseif (empty($variants)): ?>
    <p class="text-white-50 text-center py-3 mb-0">No variants yet — this product sells as a single item using the price/stock above.</p>
  <?php else: ?>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>Attributes</th><th>SKU</th><th>Barcode</th><th>Price</th><th>Offer</th><th>Stock</th><th>Weight</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($variants as $variant): ?>
        <tr>
          <td><?= e(implode(', ', array_map(fn ($v) => $v['attribute_name'] . ': ' . $v['value'], $variant['attribute_values']))) ?></td>
          <td><?= e($variant['sku']) ?></td>
          <td><?= e($variant['barcode'] ?? '—') ?></td>
          <td><?= $variant['price'] !== null ? '৳' . number_format((float) $variant['price']) : '<span class="text-white-50">Default</span>' ?></td>
          <td><?= $variant['offer_price'] !== null ? '৳' . number_format((float) $variant['offer_price']) : '—' ?></td>
          <td><?= (int) $variant['stock_qty'] ?></td>
          <td><?= $variant['weight'] !== null ? $variant['weight'] . ' kg' : '—' ?></td>
          <td><span class="badge text-bg-<?= $variant['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($variant['status']) ?></span></td>
          <td>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-ps-outline btn-sm" data-bs-toggle="modal" data-bs-target="#editVariantModal<?= $variant['id'] ?>"><i class="bi bi-pencil"></i></button>
              <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/variants/' . $variant['id'] . '/delete') ?>" onsubmit="return confirm('Delete this variant?');">
                <?= Security::csrfField() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>

        <div class="modal fade" id="editVariantModal<?= $variant['id'] ?>" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-dark">
              <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/variants/' . $variant['id']) ?>" enctype="multipart/form-data">
                <?= Security::csrfField() ?>
                <div class="modal-header"><h6 class="modal-title">Edit Variant</h6></div>
                <div class="modal-body">
                  <?php
                  $variantValueIds = array_column($variant['attribute_values'], 'id');
                  include __DIR__ . '/_variant_fields.php';
                  ?>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
                  <button type="submit" class="btn btn-ps btn-sm">Save</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="modal fade" id="addVariantModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark">
      <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/variants') ?>" enctype="multipart/form-data">
        <?= Security::csrfField() ?>
        <div class="modal-header"><h6 class="modal-title">Add Variant</h6></div>
        <div class="modal-body">
          <?php $variant = null; $variantValueIds = []; include __DIR__ . '/_variant_fields.php'; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-ps-outline btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-ps btn-sm">Add Variant</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="admin-card mt-4">
  <h6 class="mb-3">Tags &amp; Related Products</h6>
  <form method="post" action="<?= url('/admin/products/' . $product['id'] . '/tags-related') ?>">
    <?= Security::csrfField() ?>
    <label>Tags <small class="text-white-50">(comma-separated — unlimited, any text)</small></label>
    <input type="text" name="tags" class="form-control mb-3" value="<?= e(implode(', ', array_column($tags, 'name'))) ?>" placeholder="e.g. best-seller, high-protein, unflavored">

    <label>Related Products <small class="text-white-50">(hand-picked — shown alongside the automatic suggestions)</small></label>
    <select name="related_product_ids[]" class="form-select mb-3" multiple size="6">
      <?php foreach ($allProducts as $p): if ((int) $p['id'] === (int) $product['id']) continue; ?>
        <option value="<?= (int) $p['id'] ?>" <?= in_array((int) $p['id'], $relatedProductIds, true) ? 'selected' : '' ?>><?= e($p['name']) ?> (<?= e($p['sku']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-ps-outline btn-sm">Save Tags &amp; Related Products</button>
  </form>
</div>
<?php endif; ?>
