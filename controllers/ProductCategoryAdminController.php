<?php

final class ProductCategoryAdminController extends AdminController
{
    protected string $moduleKey = 'store';

    public function index(): void
    {
        $categoryModel = new ProductCategory();

        $this->adminView('categories/index', [
            'pageTitle' => 'Store — Categories',
            'categories' => $categoryModel->allWithParent(),
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $categoryModel = new ProductCategory();
        $name = $this->input('name');

        if ($name === '') {
            flash('danger', 'Category name is required.');
            redirect('admin/categories');
        }

        $slug = $this->uniqueSlug($categoryModel, $name);
        $imagePath = Upload::handle($_FILES['image'] ?? [], 'categories');

        $categoryModel->create([
            'parent_id' => (int) $this->input('parent_id') ?: null,
            'name' => $name,
            'slug' => $slug,
            'description' => $this->input('description'),
            'image' => $imagePath,
            'status' => $this->input('status') === 'hidden' ? 'hidden' : 'active',
        ]);

        $this->logActivity('category_created', "Created product category: $name");
        flash('success', 'Category added successfully.');
        redirect('admin/categories');
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $categoryModel = new ProductCategory();
        $category = $categoryModel->find((int) $id);
        if (!$category) {
            $this->abort404();
        }

        $name = $this->input('name');
        if ($name === '') {
            flash('danger', 'Category name is required.');
            redirect('admin/categories');
        }

        $data = [
            'parent_id' => (int) $this->input('parent_id') ?: null,
            'name' => $name,
            'description' => $this->input('description'),
            'status' => $this->input('status') === 'hidden' ? 'hidden' : 'active',
            'offer_enabled' => $this->input('offer_enabled') === '1' ? 1 : 0,
            'offer_percent' => $this->input('offer_percent') !== '' ? (float) $this->input('offer_percent') : null,
            'offer_start_date' => $this->input('offer_start_date') ?: null,
            'offer_end_date' => $this->input('offer_end_date') ?: null,
        ];

        $imagePath = Upload::handle($_FILES['image'] ?? [], 'categories');
        if ($imagePath) {
            Upload::delete($category['image']);
            $data['image'] = $imagePath;
        }

        $categoryModel->update((int) $id, $data);

        $this->logActivity('category_updated', "Updated product category #$id: $name");
        flash('success', 'Category updated successfully.');
        redirect('admin/categories');
    }

    public function toggleStatus(string $id): void
    {
        Security::requireCsrf();

        $categoryModel = new ProductCategory();
        if (!$categoryModel->find((int) $id)) {
            $this->abort404();
        }

        $categoryModel->toggleStatus((int) $id);
        $this->logActivity('category_status_toggled', "Toggled visibility for category #$id");

        flash('success', 'Category visibility updated.');
        redirect('admin/categories');
    }

    public function moveUp(string $id): void
    {
        Security::requireCsrf();

        $categoryModel = new ProductCategory();
        if (!$categoryModel->find((int) $id)) {
            $this->abort404();
        }

        $categoryModel->moveUp((int) $id);
        redirect('admin/categories');
    }

    public function moveDown(string $id): void
    {
        Security::requireCsrf();

        $categoryModel = new ProductCategory();
        if (!$categoryModel->find((int) $id)) {
            $this->abort404();
        }

        $categoryModel->moveDown((int) $id);
        redirect('admin/categories');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $categoryModel = new ProductCategory();
        $category = $categoryModel->find((int) $id);
        if (!$category) {
            $this->abort404();
        }

        if ($categoryModel->productCount((int) $id) > 0) {
            flash('danger', 'Cannot delete a category that still has products assigned to it. Move or delete those products first.');
            redirect('admin/categories');
        }

        $categoryModel->delete((int) $id);
        $this->logActivity('category_deleted', "Deleted product category #$id: {$category['name']}");

        flash('success', 'Category deleted.');
        redirect('admin/categories');
    }

    private function uniqueSlug(ProductCategory $categoryModel, string $name): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'category';
        $slug = $base;
        $i = 2;
        while ($categoryModel->slugExists($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
