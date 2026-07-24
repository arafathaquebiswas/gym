<?php

final class ReportController extends AdminController
{
    protected string $moduleKey = 'reports';

    public function index(): void
    {
        $this->adminView('reports/index', ['pageTitle' => 'Reports']);
    }

    /** Combined online-order + POS-sale performance per product — powers both the Top Selling and Least Selling sections. */
    public function productReport(): void
    {
        [$from, $to] = $this->dateRange();

        $stmt = Database::connection()->prepare(
            "SELECT p.id, p.name, p.sku, c.name AS category_name, p.stock_qty,
                    COALESCE(SUM(combined.qty), 0) AS qty_sold, COALESCE(SUM(combined.revenue), 0) AS revenue
             FROM products p
             JOIN product_categories c ON c.id = p.category_id
             LEFT JOIN (
                 SELECT oi.product_id, oi.qty, oi.subtotal AS revenue FROM order_items oi
                 JOIN orders o ON o.id = oi.order_id WHERE o.created_at BETWEEN :from1 AND :to1
                 UNION ALL
                 SELECT si.product_id, si.qty, si.subtotal AS revenue FROM sale_items si
                 JOIN sales s ON s.id = si.sale_id WHERE s.sale_date BETWEEN :from2 AND :to2
             ) combined ON combined.product_id = p.id
             WHERE p.status != 'draft'
             GROUP BY p.id, p.name, p.sku, c.name, p.stock_qty
             ORDER BY qty_sold DESC"
        );
        $stmt->execute(['from1' => $from, 'to1' => $to . ' 23:59:59', 'from2' => $from, 'to2' => $to . ' 23:59:59']);
        $rows = $stmt->fetchAll();

        $topSelling = array_slice($rows, 0, 10);
        $leastSelling = array_reverse(array_slice(array_reverse($rows), 0, 10));

        $this->maybeExport('Product Report', ['Product', 'SKU', 'Category', 'Qty Sold', 'Revenue', 'Current Stock'], array_map(
            fn ($r) => [$r['name'], $r['sku'], $r['category_name'], $r['qty_sold'], $r['revenue'], $r['stock_qty']], $rows
        ), "$from to $to");

        $this->adminView('reports/products', [
            'pageTitle' => 'Product Report',
            'rows' => $rows,
            'topSelling' => $topSelling,
            'leastSelling' => $leastSelling,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function deliveryReport(): void
    {
        [$from, $to] = $this->dateRange();
        $db = Database::connection();

        $statusStmt = $db->prepare(
            "SELECT status, COUNT(*) AS cnt FROM orders
             WHERE fulfillment_method = 'delivery' AND created_at BETWEEN :from AND :to
             GROUP BY status"
        );
        $statusStmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $byStatus = $statusStmt->fetchAll();

        $zoneStmt = $db->prepare(
            "SELECT z.name AS zone_name, COUNT(*) AS order_count, SUM(o.total) AS total
             FROM orders o JOIN delivery_zones z ON z.id = o.zone_id
             WHERE o.fulfillment_method = 'delivery' AND o.created_at BETWEEN :from AND :to
             GROUP BY z.id ORDER BY order_count DESC"
        );
        $zoneStmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $byZone = $zoneStmt->fetchAll();

        $driverStmt = $db->prepare(
            "SELECT u.name AS driver_name, COUNT(*) AS assigned_count,
                    SUM(o.status = 'delivered') AS delivered_count
             FROM orders o JOIN users u ON u.id = o.delivery_person_id
             WHERE o.fulfillment_method = 'delivery' AND o.created_at BETWEEN :from AND :to
             GROUP BY u.id ORDER BY assigned_count DESC"
        );
        $driverStmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $byDriver = $driverStmt->fetchAll();

        $this->maybeExport('Delivery Report', ['Zone', 'Orders', 'Total'], array_map(
            fn ($r) => [$r['zone_name'], $r['order_count'], $r['total']], $byZone
        ), "$from to $to");

        $this->adminView('reports/delivery', [
            'pageTitle' => 'Delivery Report',
            'byStatus' => $byStatus,
            'byZone' => $byZone,
            'byDriver' => $byDriver,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function pickupReport(): void
    {
        [$from, $to] = $this->dateRange();
        $db = Database::connection();

        $statusStmt = $db->prepare(
            "SELECT status, COUNT(*) AS cnt FROM orders
             WHERE fulfillment_method = 'pickup' AND created_at BETWEEN :from AND :to
             GROUP BY status"
        );
        $statusStmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $byStatus = $statusStmt->fetchAll();

        $slotStmt = $db->prepare(
            "SELECT ts.label AS slot_label, COUNT(*) AS order_count
             FROM orders o JOIN delivery_time_slots ts ON ts.id = o.time_slot_id
             WHERE o.fulfillment_method = 'pickup' AND o.created_at BETWEEN :from AND :to
             GROUP BY ts.id ORDER BY order_count DESC"
        );
        $slotStmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $bySlot = $slotStmt->fetchAll();

        $this->maybeExport('Pickup Report', ['Status', 'Orders'], array_map(
            fn ($r) => [ucfirst(str_replace('_', ' ', $r['status'])), $r['cnt']], $byStatus
        ), "$from to $to");

        $this->adminView('reports/pickup', [
            'pageTitle' => 'Pickup Report',
            'byStatus' => $byStatus,
            'bySlot' => $bySlot,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function customerReport(): void
    {
        [$from, $to] = $this->dateRange();

        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(u.name, o.guest_name) AS customer_name,
                    COALESCE(u.email, o.guest_email) AS customer_email,
                    COUNT(*) AS order_count, SUM(o.total) AS total_spent
             FROM orders o LEFT JOIN users u ON u.id = o.user_id
             WHERE o.created_at BETWEEN :from AND :to AND o.status != 'cancelled'
             GROUP BY COALESCE(u.id, o.guest_email), customer_name, customer_email
             ORDER BY total_spent DESC LIMIT 50"
        );
        $stmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $rows = $stmt->fetchAll();

        $repeatCustomers = count(array_filter($rows, fn ($r) => (int) $r['order_count'] > 1));

        $this->maybeExport('Customer Report', ['Customer', 'Email', 'Orders', 'Total Spent'], array_map(
            fn ($r) => [$r['customer_name'], $r['customer_email'], $r['order_count'], $r['total_spent']], $rows
        ), "$from to $to");

        $this->adminView('reports/customers', [
            'pageTitle' => 'Customer Report',
            'rows' => $rows,
            'repeatCustomers' => $repeatCustomers,
            'oneTimeCustomers' => count($rows) - $repeatCustomers,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function couponReport(): void
    {
        [$from, $to] = $this->dateRange();

        $stmt = Database::connection()->prepare(
            "SELECT p.code, p.title, p.discount_type, p.discount_value, p.used_count AS lifetime_uses,
                    COUNT(cu.id) AS uses_in_range,
                    COALESCE(SUM(o.discount), 0) + COALESCE(SUM(s.discount), 0) AS approx_discount_given
             FROM promotions p
             LEFT JOIN coupon_usages cu ON cu.promotion_id = p.id AND cu.used_at BETWEEN :from AND :to
             LEFT JOIN orders o ON o.id = cu.order_id
             LEFT JOIN sales s ON s.id = cu.sale_id
             WHERE p.code IS NOT NULL
             GROUP BY p.id ORDER BY uses_in_range DESC"
        );
        $stmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $rows = $stmt->fetchAll();

        $this->maybeExport('Coupon Report', ['Code', 'Title', 'Uses in Range', 'Lifetime Uses', 'Approx. Discount Given'], array_map(
            fn ($r) => [$r['code'], $r['title'], $r['uses_in_range'], $r['lifetime_uses'], $r['approx_discount_given']], $rows
        ), "$from to $to");

        $this->adminView('reports/coupons', [
            'pageTitle' => 'Coupon Report',
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Which price tier actually drove each sale — needs order_items.discount_source, added
     * alongside this report, so this is only accurate for orders placed from now on; earlier
     * orders show as "Regular Price" regardless of what they actually paid.
     */
    public function offerPerformance(): void
    {
        [$from, $to] = $this->dateRange();
        $sourceLabels = ['flash_sale' => 'Flash Sale', 'product_offer' => 'Product Offer', 'category_offer' => 'Category Offer', 'brand_offer' => 'Brand Offer', 'regular_price' => 'Regular Price (no offer)'];

        $stmt = Database::connection()->prepare(
            "SELECT COALESCE(oi.discount_source, 'regular_price') AS source, COUNT(*) AS item_count, SUM(oi.qty) AS qty_sold, SUM(oi.subtotal) AS revenue
             FROM order_items oi JOIN orders o ON o.id = oi.order_id
             WHERE o.created_at BETWEEN :from AND :to
             GROUP BY source ORDER BY revenue DESC"
        );
        $stmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $rows = $stmt->fetchAll();

        $this->maybeExport('Offer Performance', ['Discount Source', 'Line Items', 'Qty Sold', 'Revenue'], array_map(
            fn ($r) => [$sourceLabels[$r['source']] ?? $r['source'], $r['item_count'], $r['qty_sold'], $r['revenue']], $rows
        ), "$from to $to");

        $this->adminView('reports/offer-performance', [
            'pageTitle' => 'Offer Performance',
            'rows' => $rows,
            'sourceLabels' => $sourceLabels,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function monthlyRevenue(): void
    {
        $stmt = Database::connection()->query(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS ym, SUM(amount) AS total
             FROM payments WHERE status = 'completed' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
             GROUP BY ym"
        );
        $byMonth = array_column($stmt->fetchAll(), 'total', 'ym');

        $rows = [];
        for ($i = 11; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i months"));
            $rows[] = ['month' => date('M Y', strtotime("-$i months")), 'total' => (float) ($byMonth[$ym] ?? 0)];
        }

        $this->maybeExport('Monthly Revenue', ['Month', 'Revenue'], array_map(
            fn ($r) => [$r['month'], $r['total']], $rows
        ));

        $this->adminView('reports/monthly-revenue', [
            'pageTitle' => 'Monthly Revenue',
            'rows' => $rows,
        ]);
    }

    public function salesReport(): void
    {
        [$from, $to] = $this->dateRange();
        $group = $this->input('group', 'daily') === 'monthly' ? 'monthly' : 'daily';
        $dateExpr = $group === 'monthly' ? "DATE_FORMAT(sale_date, '%Y-%m')" : 'DATE(sale_date)';

        $stmt = Database::connection()->prepare(
            "SELECT $dateExpr AS period, COUNT(*) AS sale_count, SUM(subtotal) AS subtotal, SUM(discount) AS discount, SUM(total) AS total
             FROM sales WHERE sale_date BETWEEN :from AND :to
             GROUP BY period ORDER BY period ASC"
        );
        $stmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $rows = $stmt->fetchAll();

        $this->maybeExport('Sales Report', ['Period', 'Sales', 'Subtotal', 'Discount', 'Total'], array_map(
            fn ($r) => [$r['period'], $r['sale_count'], $r['subtotal'], $r['discount'], $r['total']], $rows
        ), "$from to $to");

        $this->adminView('reports/sales', [
            'pageTitle' => 'Sales Report',
            'rows' => $rows,
            'group' => $group,
            'from' => $from,
            'to' => $to,
            'grandTotal' => array_sum(array_column($rows, 'total')),
        ]);
    }

    public function revenue(): void
    {
        [$from, $to] = $this->dateRange();

        $stmt = Database::connection()->prepare(
            "SELECT type, COUNT(*) AS payment_count, SUM(amount) AS total
             FROM payments WHERE paid_at BETWEEN :from AND :to AND status = 'completed'
             GROUP BY type ORDER BY total DESC"
        );
        $stmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $rows = $stmt->fetchAll();

        $this->maybeExport('Revenue Report', ['Type', 'Payments', 'Total'], array_map(
            fn ($r) => [$r['type'], $r['payment_count'], $r['total']], $rows
        ), "$from to $to");

        $this->adminView('reports/revenue', [
            'pageTitle' => 'Revenue Report',
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'grandTotal' => array_sum(array_column($rows, 'total')),
        ]);
    }

    public function membersReport(): void
    {
        [$from, $to] = $this->dateRange();
        $db = Database::connection();

        $statusStmt = $db->query(
            "SELECT status, COUNT(*) AS cnt FROM members GROUP BY status"
        );
        $statusBreakdown = $statusStmt->fetchAll();

        $newStmt = $db->prepare(
            "SELECT m.member_code, u.name, u.email, m.join_date, m.status
             FROM members m JOIN users u ON u.id = m.user_id
             WHERE m.join_date BETWEEN :from AND :to
             ORDER BY m.join_date DESC"
        );
        $newStmt->execute(['from' => $from, 'to' => $to]);
        $newMembers = $newStmt->fetchAll();

        $this->maybeExport('Members Report', ['Member Code', 'Name', 'Email', 'Join Date', 'Status'], array_map(
            fn ($r) => [$r['member_code'], $r['name'], $r['email'], $r['join_date'], $r['status']], $newMembers
        ), "New members $from to $to");

        $this->adminView('reports/members', [
            'pageTitle' => 'Members Report',
            'statusBreakdown' => $statusBreakdown,
            'newMembers' => $newMembers,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function renewals(): void
    {
        $db = Database::connection();

        $upcomingStmt = $db->query(
            "SELECT ms.*, u.name, u.phone, p.name AS package_name
             FROM member_subscriptions ms
             JOIN members m ON m.id = ms.member_id
             JOIN users u ON u.id = m.user_id
             JOIN membership_packages p ON p.id = ms.package_id
             WHERE ms.status = 'active' AND ms.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             ORDER BY ms.end_date ASC"
        );

        [$from, $to] = $this->dateRange();
        $renewedStmt = $db->prepare(
            "SELECT ms.*, u.name, u.phone, p.name AS package_name
             FROM member_subscriptions ms
             JOIN members m ON m.id = ms.member_id
             JOIN users u ON u.id = m.user_id
             JOIN membership_packages p ON p.id = ms.package_id
             WHERE DATE(ms.created_at) BETWEEN :from AND :to
             ORDER BY ms.created_at DESC"
        );
        $renewedStmt->execute(['from' => $from, 'to' => $to]);
        $renewed = $renewedStmt->fetchAll();

        $this->maybeExport('Membership Renewals', ['Member', 'Phone', 'Package', 'Renewed On'], array_map(
            fn ($r) => [$r['name'], $r['phone'], $r['package_name'], $r['created_at']], $renewed
        ), "Renewed $from to $to");

        $this->adminView('reports/renewals', [
            'pageTitle' => 'Membership Renewals',
            'upcoming' => $upcomingStmt->fetchAll(),
            'renewed' => $renewed,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function attendance(): void
    {
        [$from, $to] = $this->dateRange();

        $stmt = Database::connection()->prepare(
            "SELECT DATE(check_in) AS day, COUNT(*) AS visits, COUNT(DISTINCT member_id) AS unique_members
             FROM attendance WHERE check_in BETWEEN :from AND :to
             GROUP BY day ORDER BY day ASC"
        );
        $stmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $rows = $stmt->fetchAll();

        $this->maybeExport('Attendance Report', ['Day', 'Visits', 'Unique Members'], array_map(
            fn ($r) => [$r['day'], $r['visits'], $r['unique_members']], $rows
        ), "$from to $to");

        $this->adminView('reports/attendance', [
            'pageTitle' => 'Attendance Report',
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function trainerIncome(): void
    {
        [$from, $to] = $this->dateRange();

        $stmt = Database::connection()->prepare(
            "SELECT t.name, COUNT(p.id) AS payment_count, SUM(p.amount) AS total
             FROM trainers t LEFT JOIN payments p ON p.trainer_id = t.id AND p.type = 'trainer_fee'
                 AND p.paid_at BETWEEN :from AND :to AND p.status = 'completed'
             GROUP BY t.id ORDER BY total DESC"
        );
        $stmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $rows = $stmt->fetchAll();

        $this->maybeExport('Trainer Income Report', ['Trainer', 'Payments', 'Total'], array_map(
            fn ($r) => [$r['name'], $r['payment_count'], $r['total']], $rows
        ), "$from to $to");

        $this->adminView('reports/trainer-income', [
            'pageTitle' => 'Trainer Income Report',
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function storeSales(): void
    {
        [$from, $to] = $this->dateRange();

        $stmt = Database::connection()->prepare(
            "SELECT p.name, c.name AS category_name, SUM(si.qty) AS qty_sold, SUM(si.subtotal) AS revenue
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             JOIN product_categories c ON c.id = p.category_id
             JOIN sales s ON s.id = si.sale_id
             WHERE s.sale_date BETWEEN :from AND :to
             GROUP BY p.id ORDER BY revenue DESC"
        );
        $stmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $byProduct = $stmt->fetchAll();

        $catStmt = Database::connection()->prepare(
            "SELECT c.name AS category_name, SUM(si.qty) AS qty_sold, SUM(si.subtotal) AS revenue
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             JOIN product_categories c ON c.id = p.category_id
             JOIN sales s ON s.id = si.sale_id
             WHERE s.sale_date BETWEEN :from AND :to
             GROUP BY c.id ORDER BY revenue DESC"
        );
        $catStmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);

        $this->maybeExport('Store Sales Report', ['Product', 'Category', 'Qty Sold', 'Revenue'], array_map(
            fn ($r) => [$r['name'], $r['category_name'], $r['qty_sold'], $r['revenue']], $byProduct
        ), "$from to $to");

        $this->adminView('reports/store-sales', [
            'pageTitle' => 'Store Sales Report',
            'byProduct' => $byProduct,
            'byCategory' => $catStmt->fetchAll(),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function onlineOrders(): void
    {
        [$from, $to] = $this->dateRange();

        $stmt = Database::connection()->prepare(
            "SELECT DATE(created_at) AS period, COUNT(*) AS order_count, SUM(total) AS total,
                    SUM(status = 'delivered') AS delivered_count, SUM(status = 'cancelled') AS cancelled_count
             FROM orders WHERE created_at BETWEEN :from AND :to
             GROUP BY period ORDER BY period ASC"
        );
        $stmt->execute(['from' => $from, 'to' => $to . ' 23:59:59']);
        $rows = $stmt->fetchAll();

        $this->maybeExport('Online Orders Report', ['Period', 'Orders', 'Total', 'Delivered', 'Cancelled'], array_map(
            fn ($r) => [$r['period'], $r['order_count'], $r['total'], $r['delivered_count'], $r['cancelled_count']], $rows
        ), "$from to $to");

        $this->adminView('reports/online-orders', [
            'pageTitle' => 'Online Orders Report',
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'grandTotal' => array_sum(array_column($rows, 'total')),
        ]);
    }

    public function stock(): void
    {
        $stmt = Database::connection()->query(
            "SELECT p.*, c.name AS category_name FROM products p
             JOIN product_categories c ON c.id = p.category_id
             WHERE p.status != 'draft'
             ORDER BY (p.stock_qty <= p.min_stock) DESC, p.stock_qty ASC"
        );
        $products = $stmt->fetchAll();

        $totalValue = 0.0;
        foreach ($products as $product) {
            $totalValue += $product['stock_qty'] * $product['buying_price'];
        }

        [$from, $to] = $this->dateRange();
        $movementStmt = Database::connection()->prepare(
            "SELECT type, SUM(GREATEST(change_qty, 0)) AS added, SUM(GREATEST(-change_qty, 0)) AS removed
             FROM stock_movements WHERE DATE(created_at) BETWEEN :from AND :to
             GROUP BY type ORDER BY type ASC"
        );
        $movementStmt->execute(['from' => $from, 'to' => $to]);
        $movements = $movementStmt->fetchAll();

        $this->maybeExport('Inventory Report', ['Product', 'SKU', 'Category', 'Stock', 'Min Stock', 'Buying Price', 'Stock Value'], array_map(
            fn ($p) => [$p['name'], $p['sku'], $p['category_name'], $p['stock_qty'], $p['min_stock'], $p['buying_price'], round($p['stock_qty'] * $p['buying_price'], 2)], $products
        ));

        $this->adminView('reports/stock', [
            'pageTitle' => 'Inventory Report',
            'products' => $products,
            'totalValue' => $totalValue,
            'movements' => $movements,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /** @return array{0:string,1:string} [from, to] as Y-m-d, defaulting to the current month. */
    private function dateRange(): array
    {
        $from = $this->input('from') ?: date('Y-m-01');
        $to = $this->input('to') ?: date('Y-m-d');
        return [$from, $to];
    }

    /**
     * Streams the report as CSV/PDF and exits if ?export=csv|pdf is present — otherwise a no-op.
     * Called after a report's data is fetched but before its normal HTML view renders, so every
     * report exports the exact same rows the admin is looking at.
     * @param array<int, array<int, mixed>> $rows
     */
    private function maybeExport(string $title, array $headers, array $rows, string $subtitle = ''): void
    {
        $export = $this->input('export');
        if ($export === 'csv') {
            ReportExporter::csv($title, $headers, $rows);
        } elseif ($export === 'pdf') {
            ReportExporter::pdf($title, $headers, $rows, $subtitle);
        }
    }
}
