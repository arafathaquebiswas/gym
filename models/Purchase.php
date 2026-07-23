<?php

/** Records stock received from a supplier — purchase_items insert triggers trg_purchase_items_after_insert, which increments products.stock_qty automatically. */
final class Purchase extends Model
{
    /** @param array<int, array{product_id:int, qty:int, unit_cost:float}> $items */
    public function create(?int $supplierId, string $purchaseDate, array $items, int $createdBy): array
    {
        if (!$items) {
            throw new InvalidArgumentException('Add at least one product line to record a purchase.');
        }

        $productModel = new Product();
        $this->db->beginTransaction();
        try {
            $totalAmount = 0.0;
            foreach ($items as $item) {
                $totalAmount += $item['qty'] * $item['unit_cost'];
            }

            $invoiceNo = $this->generateInvoiceNo();

            $stmt = $this->db->prepare(
                'INSERT INTO purchases (supplier_id, invoice_no, purchase_date, total_amount, created_by)
                 VALUES (:supplier_id, :invoice_no, :purchase_date, :total_amount, :created_by)'
            );
            $stmt->execute([
                'supplier_id' => $supplierId,
                'invoice_no' => $invoiceNo,
                'purchase_date' => $purchaseDate,
                'total_amount' => round($totalAmount, 2),
                'created_by' => $createdBy,
            ]);
            $purchaseId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                'INSERT INTO purchase_items (purchase_id, product_id, qty, unit_cost, subtotal)
                 VALUES (:purchase_id, :product_id, :qty, :unit_cost, :subtotal)'
            );
            $stockMovementModel = new StockMovement();
            $restocked = [];

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $qty = (int) $item['qty'];
                $before = $productModel->find($productId);
                if (!$before) {
                    throw new RuntimeException('One of the selected products no longer exists.');
                }

                $itemStmt->execute([
                    'purchase_id' => $purchaseId,
                    'product_id' => $productId,
                    'qty' => $qty,
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => round($qty * $item['unit_cost'], 2),
                ]);
                // trg_purchase_items_after_insert increments products.stock_qty automatically.
                $stockMovementModel->record($productId, $qty, 'purchase', $purchaseId, 'Stock received from supplier', $createdBy);

                if ((int) $before['stock_qty'] <= 0) {
                    $restocked[] = $before;
                }
            }

            $this->db->commit();

            if ((new Setting())->getBool('auto_email_notifications')) {
                foreach ($restocked as $product) {
                    StockNotifier::notifyBackInStock($product);
                }
            }

            return ['id' => $purchaseId, 'invoice_no' => $invoiceNo];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, s.name AS supplier_name, u.name AS created_by_name FROM purchases p
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $purchase = $stmt->fetch();
        return $purchase ?: null;
    }

    public function itemsForPurchase(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT pi.*, p.name AS product_name, p.sku FROM purchase_items pi
             JOIN products p ON p.id = pi.product_id
             WHERE pi.purchase_id = :purchase_id'
        );
        $stmt->execute(['purchase_id' => $id]);
        return $stmt->fetchAll();
    }

    public function paginateForAdmin(int $page = 1, int $perPage = 20): array
    {
        $total = (int) $this->db->query('SELECT COUNT(*) FROM purchases')->fetchColumn();

        $page = max(1, $page);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            'SELECT p.*, s.name AS supplier_name FROM purchases p
             LEFT JOIN suppliers s ON s.id = p.supplier_id
             ORDER BY p.purchase_date DESC, p.id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'perPage' => $perPage, 'totalPages' => $totalPages];
    }

    private function generateInvoiceNo(): string
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM purchases WHERE DATE(created_at) = CURDATE()');
        $seq = (int) $stmt->fetchColumn() + 1;
        return 'PO-' . date('Ymd') . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
