<?php

final class BrandAdminController extends AdminController
{
    public function index(): void
    {
        $brandModel = new Brand();

        $this->adminView('brands/index', [
            'pageTitle' => 'Store — Brands',
            'brands' => $brandModel->allWithProductCount(),
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $brandModel = new Brand();
        $name = $this->input('name');

        if ($name === '') {
            flash('danger', 'Brand name is required.');
            redirect('admin/brands');
        }

        $slug = $this->uniqueSlug($brandModel, $name);

        $logoPath = Upload::handle($_FILES['logo'] ?? [], 'brands');

        $brandModel->create([
            'name' => $name,
            'slug' => $slug,
            'description' => $this->input('description'),
            'logo' => $logoPath,
        ]);

        $this->logActivity('brand_created', "Created brand: $name");
        flash('success', 'Brand added successfully.');
        redirect('admin/brands');
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $brandModel = new Brand();
        $brand = $brandModel->find((int) $id);
        if (!$brand) {
            $this->abort404();
        }

        $name = $this->input('name');
        if ($name === '') {
            flash('danger', 'Brand name is required.');
            redirect('admin/brands');
        }

        $data = [
            'name' => $name,
            'description' => $this->input('description'),
            'offer_enabled' => $this->input('offer_enabled') === '1' ? 1 : 0,
            'offer_percent' => $this->input('offer_percent') !== '' ? (float) $this->input('offer_percent') : null,
            'offer_start_date' => $this->input('offer_start_date') ?: null,
            'offer_end_date' => $this->input('offer_end_date') ?: null,
        ];

        $logoPath = Upload::handle($_FILES['logo'] ?? [], 'brands');
        if ($logoPath) {
            Upload::delete($brand['logo']);
            $data['logo'] = $logoPath;
        }

        $brandModel->update((int) $id, $data);

        $this->logActivity('brand_updated', "Updated brand #$id: $name");
        flash('success', 'Brand updated successfully.');
        redirect('admin/brands');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $brandModel = new Brand();
        $brand = $brandModel->find((int) $id);
        if (!$brand) {
            $this->abort404();
        }

        if ($brandModel->productCount((int) $id) > 0) {
            flash('danger', 'Cannot delete a brand that still has products assigned to it. Reassign or delete those products first.');
            redirect('admin/brands');
        }

        Upload::delete($brand['logo']);
        $brandModel->delete((int) $id);
        $this->logActivity('brand_deleted', "Deleted brand #$id: {$brand['name']}");

        flash('success', 'Brand deleted.');
        redirect('admin/brands');
    }

    private function uniqueSlug(Brand $brandModel, string $name): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'brand';
        $slug = $base;
        $i = 2;
        while ($brandModel->slugExists($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
