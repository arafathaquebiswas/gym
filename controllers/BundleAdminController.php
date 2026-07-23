<?php

final class BundleAdminController extends AdminController
{
    public function index(): void
    {
        $this->adminView('bundles/index', [
            'pageTitle' => 'Bundle Offers',
            'bundles' => (new Bundle())->all(),
        ]);
    }

    public function create(): void
    {
        $this->adminView('bundles/form', [
            'pageTitle' => 'Add Bundle',
            'bundle' => null,
            'items' => [],
            'products' => (new Product())->allForAdminPicker(),
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $bundleModel = new Bundle();
        $name = $this->input('name');
        if ($name === '') {
            flash('danger', 'Bundle name is required.');
            redirect('admin/bundles/create');
        }

        $items = $this->collectItems();
        if (!$items) {
            flash('danger', 'Add at least two product lines to a bundle.');
            redirect('admin/bundles/create');
        }

        $slug = $this->uniqueSlug($bundleModel, $name);

        try {
            $bundleModel->create([
                'name' => $name,
                'slug' => $slug,
                'bundle_price' => (float) $this->input('bundle_price', '0'),
                'is_active' => $this->input('is_active') === '1' ? 1 : 0,
                'starts_at' => $this->input('starts_at') ?: null,
                'ends_at' => $this->input('ends_at') ?: null,
            ], $items);
        } catch (Throwable $e) {
            flash('danger', $e->getMessage());
            redirect('admin/bundles/create');
        }

        $this->logActivity('bundle_created', "Created bundle: $name");
        flash('success', 'Bundle created successfully.');
        redirect('admin/bundles');
    }

    public function edit(string $id): void
    {
        $bundleModel = new Bundle();
        $bundle = $bundleModel->find((int) $id);
        if (!$bundle) {
            $this->abort404();
        }

        $this->adminView('bundles/form', [
            'pageTitle' => 'Edit Bundle',
            'bundle' => $bundle,
            'items' => $bundleModel->itemsFor((int) $id),
            'products' => (new Product())->allForAdminPicker(),
        ]);
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $bundleModel = new Bundle();
        if (!$bundleModel->find((int) $id)) {
            $this->abort404();
        }

        $name = $this->input('name');
        if ($name === '') {
            flash('danger', 'Bundle name is required.');
            redirect('admin/bundles/' . $id . '/edit');
        }

        $items = $this->collectItems();
        if (!$items) {
            flash('danger', 'Add at least two product lines to a bundle.');
            redirect('admin/bundles/' . $id . '/edit');
        }

        $bundleModel->update((int) $id, [
            'name' => $name,
            'bundle_price' => (float) $this->input('bundle_price', '0'),
            'is_active' => $this->input('is_active') === '1' ? 1 : 0,
            'starts_at' => $this->input('starts_at') ?: null,
            'ends_at' => $this->input('ends_at') ?: null,
        ], $items);

        $this->logActivity('bundle_updated', "Updated bundle #$id: $name");
        flash('success', 'Bundle updated successfully.');
        redirect('admin/bundles');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $bundleModel = new Bundle();
        if (!$bundleModel->find((int) $id)) {
            $this->abort404();
        }

        $bundleModel->delete((int) $id);
        $this->logActivity('bundle_deleted', "Deleted bundle #$id");

        flash('success', 'Bundle deleted.');
        redirect('admin/bundles');
    }

    public function toggleActive(string $id): void
    {
        Security::requireCsrf();

        $bundleModel = new Bundle();
        if (!$bundleModel->find((int) $id)) {
            $this->abort404();
        }

        $bundleModel->toggleActive((int) $id);
        flash('success', 'Bundle status updated.');
        redirect('admin/bundles');
    }

    /** @return array<int, array{product_id:int, qty:int}> */
    private function collectItems(): array
    {
        $productIds = $_POST['product_id'] ?? [];
        $qtys = $_POST['qty'] ?? [];

        $items = [];
        foreach ($productIds as $i => $productId) {
            $productId = (int) $productId;
            $qty = (int) ($qtys[$i] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $items[] = ['product_id' => $productId, 'qty' => $qty];
        }

        return count($items) >= 2 ? $items : [];
    }

    private function uniqueSlug(Bundle $bundleModel, string $name): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'bundle';
        $slug = $base;
        $i = 2;
        while ($bundleModel->slugExists($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
