<?php

final class SaleItem extends Model
{
    public function forSale(int $saleId): array
    {
        $stmt = $this->db->prepare(
            'SELECT si.*, p.name AS product_name, p.sku
             FROM sale_items si JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = :sale_id'
        );
        $stmt->execute(['sale_id' => $saleId]);
        return $stmt->fetchAll();
    }
}
