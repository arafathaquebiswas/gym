<?php

final class SupplierAdminController extends AdminController
{
    protected string $moduleKey = 'store';

    public function index(): void
    {
        $this->adminView('suppliers/index', [
            'pageTitle' => 'Suppliers',
            'suppliers' => (new Supplier())->all(),
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $name = $this->input('name');
        if ($name === '') {
            flash('danger', 'Supplier name is required.');
            redirect('admin/suppliers');
        }

        (new Supplier())->create([
            'name' => $name,
            'contact_person' => $this->input('contact_person'),
            'phone' => $this->input('phone'),
            'email' => $this->input('email'),
            'address' => $this->input('address'),
        ]);

        $this->logActivity('supplier_created', "Created supplier: $name");
        flash('success', 'Supplier added successfully.');
        redirect('admin/suppliers');
    }

    public function update(string $id): void
    {
        Security::requireCsrf();

        $supplierModel = new Supplier();
        if (!$supplierModel->find((int) $id)) {
            $this->abort404();
        }

        $name = $this->input('name');
        if ($name === '') {
            flash('danger', 'Supplier name is required.');
            redirect('admin/suppliers');
        }

        $supplierModel->update((int) $id, [
            'name' => $name,
            'contact_person' => $this->input('contact_person'),
            'phone' => $this->input('phone'),
            'email' => $this->input('email'),
            'address' => $this->input('address'),
        ]);

        $this->logActivity('supplier_updated', "Updated supplier #$id: $name");
        flash('success', 'Supplier updated successfully.');
        redirect('admin/suppliers');
    }

    public function destroy(string $id): void
    {
        Security::requireCsrf();

        $supplierModel = new Supplier();
        $supplier = $supplierModel->find((int) $id);
        if (!$supplier) {
            $this->abort404();
        }

        if ($supplierModel->productCount((int) $id) > 0 || $supplierModel->purchaseCount((int) $id) > 0) {
            flash('danger', 'Cannot delete a supplier that still has products or purchase records linked to it.');
            redirect('admin/suppliers');
        }

        $supplierModel->delete((int) $id);
        $this->logActivity('supplier_deleted', "Deleted supplier #$id: {$supplier['name']}");

        flash('success', 'Supplier deleted.');
        redirect('admin/suppliers');
    }
}
