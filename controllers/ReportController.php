<?php

final class ReportController extends AdminController
{
    public function index(): void
    {
        $this->adminView('reports/index', ['pageTitle' => 'Reports']);
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

        $this->adminView('reports/members', [
            'pageTitle' => 'Members Report',
            'statusBreakdown' => $statusBreakdown,
            'newMembers' => $newStmt->fetchAll(),
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

        $this->adminView('reports/renewals', [
            'pageTitle' => 'Membership Renewals',
            'upcoming' => $upcomingStmt->fetchAll(),
            'renewed' => $renewedStmt->fetchAll(),
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

        $this->adminView('reports/attendance', [
            'pageTitle' => 'Attendance Report',
            'rows' => $stmt->fetchAll(),
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

        $this->adminView('reports/trainer-income', [
            'pageTitle' => 'Trainer Income Report',
            'rows' => $stmt->fetchAll(),
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
             WHERE p.is_active = 1
             ORDER BY (p.stock_qty <= p.min_stock) DESC, p.stock_qty ASC"
        );
        $products = $stmt->fetchAll();

        $totalValue = 0.0;
        foreach ($products as $product) {
            $totalValue += $product['stock_qty'] * $product['buying_price'];
        }

        $this->adminView('reports/stock', [
            'pageTitle' => 'Stock Report',
            'products' => $products,
            'totalValue' => $totalValue,
        ]);
    }

    /** @return array{0:string,1:string} [from, to] as Y-m-d, defaulting to the current month. */
    private function dateRange(): array
    {
        $from = $this->input('from') ?: date('Y-m-01');
        $to = $this->input('to') ?: date('Y-m-d');
        return [$from, $to];
    }
}
