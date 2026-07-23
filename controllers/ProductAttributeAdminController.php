<?php

final class ProductAttributeAdminController extends AdminController
{
    public function index(): void
    {
        $this->adminView('attributes/index', [
            'pageTitle' => 'Product Attributes',
            'attributes' => (new ProductAttribute())->allWithValues(),
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $attributeModel = new ProductAttribute();
        $name = $this->input('name');
        if ($name === '') {
            flash('danger', 'Attribute name is required.');
            redirect('admin/attributes');
        }

        $slug = $this->uniqueSlug($attributeModel, $name);
        $attributeModel->create($name, $slug);

        $this->logActivity('attribute_created', "Created attribute: $name");
        flash('success', 'Attribute added successfully.');
        redirect('admin/attributes');
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $attributeModel = new ProductAttribute();
        if (!$attributeModel->find((int) $id)) {
            $this->abort404();
        }

        $name = $this->input('name');
        if ($name === '') {
            flash('danger', 'Attribute name is required.');
            redirect('admin/attributes');
        }

        $attributeModel->update((int) $id, $name);
        $this->logActivity('attribute_updated', "Updated attribute #$id: $name");

        flash('success', 'Attribute updated successfully.');
        redirect('admin/attributes');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $attributeModel = new ProductAttribute();
        $attribute = $attributeModel->find((int) $id);
        if (!$attribute) {
            $this->abort404();
        }

        if ($attributeModel->usageCount((int) $id) > 0) {
            flash('danger', 'Cannot delete an attribute that products are still using. Remove it from those products first.');
            redirect('admin/attributes');
        }

        $attributeModel->delete((int) $id);
        $this->logActivity('attribute_deleted', "Deleted attribute #$id: {$attribute['name']}");

        flash('success', 'Attribute deleted.');
        redirect('admin/attributes');
    }

    public function storeValue(string $attributeId): void
    {
        Security::requireCsrf();

        $attributeModel = new ProductAttribute();
        if (!$attributeModel->find((int) $attributeId)) {
            $this->abort404();
        }

        $value = $this->input('value');
        if ($value === '') {
            flash('danger', 'Value is required.');
            redirect('admin/attributes');
        }

        $valueModel = new AttributeValue();
        if ($valueModel->valueExists((int) $attributeId, $value)) {
            flash('danger', 'That value already exists for this attribute.');
            redirect('admin/attributes');
        }

        $valueModel->create((int) $attributeId, $value);
        $this->logActivity('attribute_value_created', "Added value \"$value\" to attribute #$attributeId");

        flash('success', 'Value added successfully.');
        redirect('admin/attributes');
    }

    public function updateValue(string $id): void
    {
        Security::requireCsrf();

        $valueModel = new AttributeValue();
        $existing = $valueModel->find((int) $id);
        if (!$existing) {
            $this->abort404();
        }

        $value = $this->input('value');
        if ($value === '') {
            flash('danger', 'Value is required.');
            redirect('admin/attributes');
        }

        if ($valueModel->valueExists((int) $existing['attribute_id'], $value, (int) $id)) {
            flash('danger', 'That value already exists for this attribute.');
            redirect('admin/attributes');
        }

        $valueModel->update((int) $id, $value);
        $this->logActivity('attribute_value_updated', "Updated attribute value #$id");

        flash('success', 'Value updated successfully.');
        redirect('admin/attributes');
    }

    public function destroyValue(string $id): void
    {
        Security::requireCsrf();

        $valueModel = new AttributeValue();
        $value = $valueModel->find((int) $id);
        if (!$value) {
            $this->abort404();
        }

        if ($valueModel->usageCount((int) $id) > 0) {
            flash('danger', 'Cannot delete a value that variants are still using.');
            redirect('admin/attributes');
        }

        $valueModel->delete((int) $id);
        $this->logActivity('attribute_value_deleted', "Deleted attribute value #$id");

        flash('success', 'Value deleted.');
        redirect('admin/attributes');
    }

    private function uniqueSlug(ProductAttribute $attributeModel, string $name): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'attribute';
        $slug = $base;
        $i = 2;
        while ($attributeModel->slugExists($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
