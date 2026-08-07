<?php

final class ProductVariantAdminController extends AdminController
{
    protected string $moduleKey = 'store';

    public function store(string $id): void
    {
        Security::requireCsrf();

        $productModel = new Product();
        if (!$productModel->find((int) $id)) {
            $this->abort404();
        }

        $error = $this->validate(null);
        if ($error) {
            flash('danger', $error);
            redirect('admin/products/' . $id . '/edit');
        }

        $imagePath = Upload::handle($_FILES['image'] ?? [], 'variants');

        $variantModel = new ProductVariant();
        $variantModel->create([
            'product_id' => (int) $id,
            'sku' => $this->resolveSku((int) $id, null),
            'barcode' => $this->input('barcode'),
            'price' => $this->input('price') !== '' ? (float) $this->input('price') : null,
            'offer_price' => $this->input('offer_price') !== '' ? (float) $this->input('offer_price') : null,
            'stock_qty' => (int) $this->input('stock_qty', '0'),
            'weight' => $this->input('weight') !== '' ? (float) $this->input('weight') : null,
            'image' => $imagePath,
            'status' => $this->input('status') === 'inactive' ? 'inactive' : 'active',
        ], $this->collectValueIds());

        $this->logActivity('product_variant_created', "Added a variant to product #$id");
        flash('success', 'Variant added successfully.');
        redirect('admin/products/' . $id . '/edit');
    }

    public function update(string $id, string $variantId): void
    {
        Security::requireCsrf();

        $variantModel = new ProductVariant();
        $variant = $variantModel->find((int) $variantId);
        if (!$variant || (int) $variant['product_id'] !== (int) $id) {
            $this->abort404();
        }

        $error = $this->validate((int) $variantId);
        if ($error) {
            flash('danger', $error);
            redirect('admin/products/' . $id . '/edit');
        }

        $data = [
            'sku' => $this->resolveSku((int) $id, (int) $variantId),
            'barcode' => $this->input('barcode'),
            'price' => $this->input('price') !== '' ? (float) $this->input('price') : null,
            'offer_price' => $this->input('offer_price') !== '' ? (float) $this->input('offer_price') : null,
            'stock_qty' => (int) $this->input('stock_qty', '0'),
            'weight' => $this->input('weight') !== '' ? (float) $this->input('weight') : null,
            'status' => $this->input('status') === 'inactive' ? 'inactive' : 'active',
        ];

        $imagePath = Upload::handle($_FILES['image'] ?? [], 'variants');
        if ($imagePath) {
            Upload::delete($variant['image']);
            $data['image'] = $imagePath;
        }

        $variantModel->update((int) $variantId, $data, $this->collectValueIds());
        $this->logActivity('product_variant_updated', "Updated variant #$variantId for product #$id");

        flash('success', 'Variant updated successfully.');
        redirect('admin/products/' . $id . '/edit');
    }

    public function destroy(string $id, string $variantId): void
    {
        Security::requireCsrf();

        $variantModel = new ProductVariant();
        $variant = $variantModel->find((int) $variantId);
        if (!$variant || (int) $variant['product_id'] !== (int) $id) {
            $this->abort404();
        }

        Upload::delete($variant['image']);
        $variantModel->delete((int) $variantId);
        $this->logActivity('product_variant_deleted', "Deleted variant #$variantId from product #$id");

        flash('success', 'Variant deleted.');
        redirect('admin/products/' . $id . '/edit');
    }

    public function adjustStock(string $id, string $variantId): void
    {
        Security::requireCsrf();

        $variantModel = new ProductVariant();
        $variant = $variantModel->find((int) $variantId);
        if (!$variant || (int) $variant['product_id'] !== (int) $id) {
            $this->abort404();
        }

        $delta = (int) $this->input('delta', '0');
        if ($delta === 0) {
            flash('danger', 'Enter a non-zero quantity to adjust.');
            redirect('admin/products/' . $id . '/edit');
        }

        $variantModel->adjustStock((int) $variantId, $delta);
        $this->logActivity('product_variant_stock_adjusted', "Adjusted stock for variant #$variantId by $delta");

        flash('success', 'Variant stock adjusted.');
        redirect('admin/products/' . $id . '/edit');
    }

    private function collectValueIds(): array
    {
        return array_map('intval', (array) ($_POST['attribute_value_ids'] ?? []));
    }

    /**
     * The typed SKU, or one derived from the product and the variant's weight when the
     * admin left it blank — SKU is optional on the form, but the column is UNIQUE and
     * NOT NULL, so something has to fill it. Suffixed until it stops colliding.
     */
    private function resolveSku(int $productId, ?int $excludeId): string
    {
        $typed = $this->input('sku');
        if ($typed !== '') {
            return $typed;
        }

        $product = (new Product())->find($productId);
        $base = strtoupper($product['sku'] ?? 'VAR');
        $weight = ProductVariant::weightLabel($this->input('weight') ?: null);
        $suffix = $weight !== null ? strtoupper(str_replace(' ', '', $weight)) : 'V';

        $variantModel = new ProductVariant();
        $candidate = "$base-$suffix";
        $n = 2;
        while ($variantModel->skuExists($candidate, $excludeId)) {
            $candidate = "$base-$suffix-" . $n++;
        }

        return $candidate;
    }

    private function validate(?int $excludeId): ?string
    {
        $sku = $this->input('sku');
        // Blank is allowed — resolveSku() generates one. Only a typed SKU can clash.
        if ($sku !== '' && (new ProductVariant())->skuExists($sku, $excludeId)) {
            return 'That SKU is already used by another variant.';
        }
        $barcode = $this->input('barcode');
        if ($barcode !== '' && (new ProductVariant())->barcodeExists($barcode, $excludeId)) {
            return 'That barcode is already used by another variant.';
        }
        $offerPrice = $this->input('offer_price');
        $price = $this->input('price');
        if ($offerPrice !== '' && $price !== '' && (float) $offerPrice >= (float) $price) {
            return 'Variant offer price must be lower than its price.';
        }

        return null;
    }
}
