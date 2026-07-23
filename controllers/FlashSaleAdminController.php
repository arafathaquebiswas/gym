<?php

final class FlashSaleAdminController extends AdminController
{
    public function index(): void
    {
        $this->adminView('flash-sales/index', [
            'pageTitle' => 'Flash Sales',
            'flashSales' => (new FlashSale())->all(),
            'categories' => (new ProductCategory())->all(),
            'brands' => (new Brand())->all(),
            'products' => (new Product())->allForAdminPicker(),
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $error = $this->validate();
        if ($error) {
            flash('danger', $error);
            redirect('admin/flash-sales');
        }

        $name = $this->input('name');
        (new FlashSale())->create($this->collectFormData());

        $this->logActivity('flash_sale_created', "Created flash sale: $name");
        flash('success', 'Flash sale created successfully.');
        redirect('admin/flash-sales');
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $flashSaleModel = new FlashSale();
        if (!$flashSaleModel->find((int) $id)) {
            $this->abort404();
        }

        $error = $this->validate();
        if ($error) {
            flash('danger', $error);
            redirect('admin/flash-sales');
        }

        $flashSaleModel->update((int) $id, $this->collectFormData());

        $this->logActivity('flash_sale_updated', "Updated flash sale #$id");
        flash('success', 'Flash sale updated successfully.');
        redirect('admin/flash-sales');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $flashSaleModel = new FlashSale();
        if (!$flashSaleModel->find((int) $id)) {
            $this->abort404();
        }

        $flashSaleModel->delete((int) $id);
        $this->logActivity('flash_sale_deleted', "Deleted flash sale #$id");

        flash('success', 'Flash sale deleted.');
        redirect('admin/flash-sales');
    }

    public function toggleActive(string $id): void
    {
        Security::requireCsrf();

        $flashSaleModel = new FlashSale();
        if (!$flashSaleModel->find((int) $id)) {
            $this->abort404();
        }

        $flashSaleModel->toggleActive((int) $id);
        flash('success', 'Flash sale status updated.');
        redirect('admin/flash-sales');
    }

    private function validate(): ?string
    {
        if ($this->input('name') === '') {
            return 'Flash sale name is required.';
        }
        $percent = (float) $this->input('discount_percent', '0');
        if ($percent <= 0 || $percent >= 100) {
            return 'Discount percentage must be between 0 and 100.';
        }
        if (!in_array($this->input('scope'), ['all', 'category', 'brand', 'product'], true)) {
            return 'Invalid scope.';
        }
        if ($this->input('scope') !== 'all' && $this->scopeId() === null) {
            return 'Please choose which category/brand/product this flash sale applies to.';
        }
        if ($this->input('starts_at') === '' || $this->input('ends_at') === '') {
            return 'Please set both a start and end date/time.';
        }
        if (strtotime($this->input('ends_at')) <= strtotime($this->input('starts_at'))) {
            return 'End date/time must be after the start date/time.';
        }

        return null;
    }

    private function collectFormData(): array
    {
        return [
            'name' => $this->input('name'),
            'discount_percent' => (float) $this->input('discount_percent', '0'),
            'scope' => $this->input('scope'),
            'scope_id' => $this->scopeId(),
            'starts_at' => $this->input('starts_at'),
            'ends_at' => $this->input('ends_at'),
            'is_active' => $this->input('is_active') === '1' ? 1 : 0,
        ];
    }

    /** The scope picker is 3 separate <select>s (category/brand/product) to avoid a same-name field collision — only the one matching the chosen scope is read. */
    private function scopeId(): ?int
    {
        $field = match ($this->input('scope')) {
            'category' => 'scope_id_category',
            'brand' => 'scope_id_brand',
            'product' => 'scope_id_product',
            default => null,
        };
        if ($field === null || $this->input($field) === '') {
            return null;
        }
        return (int) $this->input($field);
    }
}
