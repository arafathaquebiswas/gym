<?php

final class ProductAdminController extends AdminController
{
    public function index(): void
    {
        $productModel = new Product();

        $filters = [
            'search' => $this->input('search'),
            'category_id' => $this->input('category_id'),
            'brand_id' => $this->input('brand_id'),
            'status' => $this->input('status'),
            'low_stock' => $this->input('low_stock'),
            'sort' => $this->input('sort'),
        ];
        $page = max(1, (int) $this->input('page', '1'));

        $result = $productModel->paginateForAdmin($filters, $page);

        $this->adminView('products/index', [
            'pageTitle' => 'Store — Products',
            'products' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'filters' => $filters,
            'categories' => (new ProductCategory())->allWithParent(),
            'brands' => (new Brand())->all(),
            'stats' => $productModel->adminStatistics(),
        ]);
    }

    public function create(): void
    {
        $this->adminView('products/form', [
            'pageTitle' => 'Add Product',
            'product' => null,
            'categories' => (new ProductCategory())->allWithParent(),
            'brands' => (new Brand())->all(),
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $data = $this->collectFormData();
        $productModel = new Product();

        $error = $this->validateProduct($data, $productModel, null);
        if ($error) {
            flash('danger', $error);
            redirect('admin/products/create');
        }

        $data['slug'] = $this->uniqueSlug($productModel, $data['name']);

        $imagePath = Upload::handle($_FILES['image'] ?? [], 'products');
        if ($imagePath) {
            $data['image'] = $imagePath;
        }

        $id = $productModel->create($data);
        $this->logActivity('product_created', "Created product #$id: {$data['name']}");

        flash('success', 'Product added successfully.');
        redirect('admin/products/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        $productModel = new Product();
        $product = $productModel->find((int) $id);
        if (!$product) {
            $this->abort404();
        }

        $this->adminView('products/form', [
            'pageTitle' => 'Edit Product',
            'product' => $product,
            'categories' => (new ProductCategory())->allWithParent(),
            'brands' => (new Brand())->all(),
            'gallery' => (new ProductImage())->forProduct((int) $id),
        ]);
    }

    /** Accepts one or more files from a multi-file input named "images[]" — mirrors TrainerAdminController::galleryUpload(). */
    public function galleryUpload(string $id): void
    {
        Security::requireCsrf();

        $productModel = new Product();
        if (!$productModel->find((int) $id)) {
            $this->abort404();
        }

        $galleryModel = new ProductImage();
        $files = $_FILES['images'] ?? null;
        $uploaded = 0;

        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $name) {
                if ($name === '') {
                    continue;
                }
                $file = [
                    'name' => $files['name'][$i], 'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i],
                ];
                $path = Upload::handle($file, 'products');
                if ($path) {
                    $galleryModel->add((int) $id, $path);
                    $uploaded++;
                }
            }
        }

        if ($uploaded > 0) {
            $this->logActivity('product_gallery_upload', "Added $uploaded gallery photo(s) for product #$id");
            flash('success', $uploaded . ' photo(s) added to the gallery.');
        } else {
            flash('danger', Upload::lastError() ?? 'No valid images were uploaded.');
        }

        redirect('admin/products/' . $id . '/edit');
    }

    public function galleryDelete(string $id, string $imageId): void
    {
        Security::requireCsrf();

        $galleryModel = new ProductImage();
        $image = $galleryModel->find((int) $imageId);

        if (!$image || (int) $image['product_id'] !== (int) $id) {
            $this->abort404();
        }

        Upload::delete($image['image_path']);
        $galleryModel->delete((int) $imageId);
        $this->logActivity('product_gallery_delete', "Removed gallery photo #$imageId for product #$id");

        flash('success', 'Photo removed from gallery.');
        redirect('admin/products/' . $id . '/edit');
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $productModel = new Product();
        $product = $productModel->find((int) $id);
        if (!$product) {
            $this->abort404();
        }

        $data = $this->collectFormData();

        $error = $this->validateProduct($data, $productModel, (int) $id);
        if ($error) {
            flash('danger', $error);
            redirect('admin/products/' . $id . '/edit');
        }

        $imagePath = Upload::handle($_FILES['image'] ?? [], 'products');
        if ($imagePath) {
            Upload::delete($product['image']);
            $data['image'] = $imagePath;
        }

        $productModel->update((int) $id, $data);
        $this->logActivity('product_updated', "Updated product #$id: {$data['name']}");

        flash('success', 'Product updated successfully.');
        redirect('admin/products/' . $id . '/edit');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $productModel = new Product();
        $product = $productModel->find((int) $id);
        if (!$product) {
            $this->abort404();
        }

        Upload::delete($product['image']);
        $productModel->delete((int) $id);
        $this->logActivity('product_deleted', "Deleted product #$id: {$product['name']}");

        flash('success', 'Product deleted.');
        redirect('admin/products');
    }

    public function bulkAction(): void
    {
        Security::requireCsrf();

        $ids = array_map('intval', (array) ($_POST['ids'] ?? []));
        $action = $this->input('bulk_action');

        if (!$ids) {
            flash('danger', 'No products selected.');
            redirect('admin/products');
        }

        $productModel = new Product();
        $count = 0;

        switch ($action) {
            case 'set_status':
                $status = $this->input('bulk_status');
                if (!in_array($status, Product::STATUSES, true)) {
                    flash('danger', 'Invalid status.');
                    redirect('admin/products');
                }
                foreach ($ids as $id) {
                    if ($productModel->find($id)) {
                        $productModel->setStatus($id, $status);
                        $count++;
                    }
                }
                break;

            case 'delete':
                foreach ($ids as $id) {
                    $product = $productModel->find($id);
                    if ($product) {
                        Upload::delete($product['image']);
                        $productModel->delete($id);
                        $count++;
                    }
                }
                break;

            case 'change_category':
                $categoryId = (int) $this->input('bulk_category_id');
                if (!$categoryId || !(new ProductCategory())->find($categoryId)) {
                    flash('danger', 'Please choose a valid category.');
                    redirect('admin/products');
                }
                foreach ($ids as $id) {
                    if ($productModel->find($id)) {
                        $productModel->update($id, ['category_id' => $categoryId]);
                        $count++;
                    }
                }
                break;

            case 'apply_discount':
                $percent = (float) $this->input('bulk_discount_percent');
                if ($percent <= 0 || $percent >= 100) {
                    flash('danger', 'Discount percentage must be between 0 and 100.');
                    redirect('admin/products');
                }
                foreach ($ids as $id) {
                    $product = $productModel->find($id);
                    if (!$product) {
                        continue;
                    }
                    $offerPrice = round((float) $product['selling_price'] * (1 - $percent / 100), 2);
                    if ($productModel->validateOfferPrice((float) $product['selling_price'], $offerPrice) === null) {
                        $productModel->update($id, ['offer_price' => $offerPrice, 'offer_enabled' => 1]);
                        $count++;
                    }
                }
                break;

            default:
                flash('danger', 'Invalid bulk action.');
                redirect('admin/products');
        }

        $this->logActivity('products_bulk_action', "Bulk-$action on $count product(s)");
        flash('success', "$count product(s) updated.");
        redirect('admin/products');
    }

    public function setStatus(string $id): void
    {
        Security::requireCsrf();

        $productModel = new Product();
        if (!$productModel->find((int) $id)) {
            $this->abort404();
        }

        $status = $this->input('status');
        if (!in_array($status, Product::STATUSES, true)) {
            flash('danger', 'Invalid status.');
            redirect('admin/products');
        }

        $productModel->setStatus((int) $id, $status);
        $this->logActivity('product_status_changed', "Set product #$id status to $status");

        flash('success', 'Status updated.');
        redirect('admin/products');
    }

    public function adjustStock(string $id): void
    {
        Security::requireCsrf();

        $productModel = new Product();
        $product = $productModel->find((int) $id);
        if (!$product) {
            $this->abort404();
        }

        $delta = (int) $this->input('delta', '0');
        $reason = $this->input('reason');

        if ($delta === 0) {
            flash('danger', 'Enter a non-zero quantity to adjust.');
            redirect('admin/products');
        }

        $productModel->adjustStock((int) $id, $delta);
        $this->logActivity('product_stock_adjusted', "Adjusted stock for product #$id by $delta (" . ($reason ?: 'no reason given') . ')');

        flash('success', 'Stock adjusted.');
        redirect('admin/products');
    }

    public function sales(): void
    {
        $saleModel = new Sale();

        $filters = [
            'search' => $this->input('search'),
            'payment_method' => $this->input('payment_method'),
        ];
        $page = max(1, (int) $this->input('page', '1'));
        $result = $saleModel->paginateForAdmin($filters, $page);

        $this->adminView('products/sales', [
            'pageTitle' => 'Store — Sales',
            'sales' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'filters' => $filters,
        ]);
    }

    private function validateProduct(array $data, Product $productModel, ?int $excludeId): ?string
    {
        if ($data['name'] === '') {
            return 'Product name is required.';
        }
        if ($data['sku'] === '') {
            return 'SKU is required.';
        }
        if ($productModel->skuExists($data['sku'], $excludeId)) {
            return 'That SKU is already used by another product.';
        }
        if ($data['selling_price'] <= 0) {
            return 'Selling price must be greater than zero.';
        }

        return $productModel->validateOfferPrice($data['selling_price'], $data['offer_price']);
    }

    private function collectFormData(): array
    {
        return [
            'category_id' => (int) $this->input('category_id'),
            'brand_id' => $this->input('brand_id') !== '' ? (int) $this->input('brand_id') : null,
            'sku' => $this->input('sku'),
            'barcode' => $this->input('barcode') ?: null,
            'name' => $this->input('name'),
            'description' => $this->rawInput('description'),
            'buying_price' => (float) $this->input('buying_price', '0'),
            'selling_price' => (float) $this->input('selling_price', '0'),
            'stock_qty' => (int) $this->input('stock_qty', '0'),
            'min_stock' => (int) $this->input('min_stock', '5'),
            'expiry_date' => $this->input('expiry_date') ?: null,
            'offer_price' => $this->input('offer_price') !== '' ? (float) $this->input('offer_price') : null,
            'offer_enabled' => $this->input('offer_enabled') === '1' ? 1 : 0,
            'offer_start_date' => $this->input('offer_start_date') ?: null,
            'offer_end_date' => $this->input('offer_end_date') ?: null,
            'shipping_charge' => $this->input('shipping_charge') !== '' ? (float) $this->input('shipping_charge') : null,
            'ingredients' => $this->rawInput('ingredients') ?: null,
            'nutrition_facts' => $this->rawInput('nutrition_facts') ?: null,
            'allow_preorder' => $this->input('allow_preorder') === '1' ? 1 : 0,
            'status' => in_array($this->input('status'), Product::STATUSES, true) ? $this->input('status') : 'draft',
        ];
    }

    private function uniqueSlug(Product $productModel, string $name): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'product';
        $slug = $base;
        $i = 2;
        while ($productModel->slugExists($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
