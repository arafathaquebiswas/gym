<?php

final class PurchaseAdminController extends AdminController
{
    protected string $moduleKey = 'purchases';

    public function index(): void
    {
        $page = max(1, (int) $this->input('page', '1'));
        $result = (new Purchase())->paginateForAdmin($page);

        $this->adminView('purchases/index', [
            'pageTitle' => 'Purchases (Restock)',
            'purchases' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
        ]);
    }

    public function create(): void
    {
        $this->adminView('purchases/form', [
            'pageTitle' => 'Record Purchase',
            'suppliers' => (new Supplier())->all(),
            'products' => (new Product())->allForAdminPicker(),
        ]);
    }

    public function store(): void
    {
        Security::requireCsrf();

        $supplierId = $this->input('supplier_id') !== '' ? (int) $this->input('supplier_id') : null;
        $purchaseDate = $this->input('purchase_date') ?: date('Y-m-d');

        $productIds = $_POST['product_id'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        $unitCosts = $_POST['unit_cost'] ?? [];

        $items = [];
        foreach ($productIds as $i => $productId) {
            $productId = (int) $productId;
            $qty = (int) ($qtys[$i] ?? 0);
            $unitCost = (float) ($unitCosts[$i] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $items[] = ['product_id' => $productId, 'qty' => $qty, 'unit_cost' => $unitCost];
        }

        if (!$items) {
            flash('danger', 'Add at least one product line with a quantity greater than zero.');
            redirect('admin/purchases/create');
        }

        try {
            $result = (new Purchase())->create($supplierId, $purchaseDate, $items, (int) Auth::user()['id']);
        } catch (Throwable $e) {
            flash('danger', $e->getMessage());
            redirect('admin/purchases/create');
        }

        $this->logActivity('purchase_recorded', "Recorded purchase {$result['invoice_no']} with " . count($items) . ' product line(s)');
        flash('success', 'Purchase recorded — stock has been updated (' . $result['invoice_no'] . ').');
        redirect('admin/purchases');
    }

    public function show(string $id): void
    {
        $purchaseModel = new Purchase();
        $purchase = $purchaseModel->find((int) $id);
        if (!$purchase) {
            $this->abort404();
        }

        $this->adminView('purchases/show', [
            'pageTitle' => 'Purchase ' . $purchase['invoice_no'],
            'purchase' => $purchase,
            'items' => $purchaseModel->itemsForPurchase((int) $id),
        ]);
    }
}
