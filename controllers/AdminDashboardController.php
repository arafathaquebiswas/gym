<?php

final class AdminDashboardController extends AdminController
{
    protected string $moduleKey = 'dashboard';

    public function index(): void
    {
        (new Member())->syncAllStatuses();

        $trainerModel = new Trainer();
        $trainers = $trainerModel->allForAdmin();
        $productStats = (new Product())->adminStatistics();
        $db = Database::connection();

        $todaysRevenue = (float) $db->query(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE(paid_at) = CURDATE() AND status = 'completed'"
        )->fetchColumn();

        $todaysNewMembers = (int) $db->query(
            'SELECT COUNT(*) FROM members WHERE join_date = CURDATE()'
        )->fetchColumn();

        $totalMembers = (int) $db->query('SELECT COUNT(*) FROM members')->fetchColumn();

        $activePackages = (int) $db->query(
            'SELECT COUNT(*) FROM membership_packages WHERE is_active = 1'
        )->fetchColumn();

        $pendingRenewals = (int) $db->query(
            "SELECT COUNT(*) FROM member_subscriptions
             WHERE status = 'active' AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
        )->fetchColumn();

        $attendanceToday = (int) $db->query(
            'SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()'
        )->fetchColumn();

        $pendingOnlineOrders = (int) $db->query(
            "SELECT COUNT(*) FROM orders WHERE status = 'pending'"
        )->fetchColumn();

        $todaysPosSales = (int) $db->query(
            'SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()'
        )->fetchColumn();

        $todaysOnlineOrders = (int) $db->query(
            'SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()'
        )->fetchColumn();

        $pendingReviews = (new ProductReview())->pendingCount();

        $couponsUsedToday = (int) $db->query(
            'SELECT COUNT(*) FROM coupon_usages WHERE DATE(used_at) = CURDATE()'
        )->fetchColumn();

        $topProducts = (new Product())->topSellingForDashboard(5);

        $topMembers = $db->query(
            "SELECT u.name, SUM(p.amount) AS total_spent
             FROM payments p
             JOIN members m ON m.id = p.member_id
             JOIN users u ON u.id = m.user_id
             WHERE p.status = 'completed'
             GROUP BY m.id, u.name
             ORDER BY total_spent DESC LIMIT 5"
        )->fetchAll();

        $upcomingTrainerBookings = (new TrainerBooking())->upcomingCount();

        $memberStatusCounts = $db->query('SELECT status, COUNT(*) AS cnt FROM members GROUP BY status')->fetchAll();
        $newMembersByMonth = $this->monthlySeries(
            $db,
            "SELECT DATE_FORMAT(join_date, '%Y-%m') AS ym, COUNT(*) AS cnt FROM members
             WHERE join_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
             GROUP BY ym",
            'ym', 'cnt'
        );
        $revenueByDay = $this->dailySeries(
            $db,
            "SELECT DATE(paid_at) AS d, SUM(amount) AS total FROM payments
             WHERE status = 'completed' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
             GROUP BY d",
            'd', 'total'
        );

        $this->adminView('dashboard', [
            'pageTitle' => 'Dashboard',
            'trainerCount' => count($trainers),
            'activeTrainerCount' => count(array_filter($trainers, fn ($t) => (int) $t['is_active'] === 1)),
            'featuredTrainerCount' => count(array_filter($trainers, fn ($t) => (int) $t['is_featured'] === 1)),
            'todaysRevenue' => $todaysRevenue,
            'todaysNewMembers' => $todaysNewMembers,
            'totalMembers' => $totalMembers,
            'activePackages' => $activePackages,
            'productCount' => $productStats['total'],
            'lowStockCount' => $productStats['lowStock'],
            'pendingRenewals' => $pendingRenewals,
            'attendanceToday' => $attendanceToday,
            'pendingOnlineOrders' => $pendingOnlineOrders,
            'todaysPosSales' => $todaysPosSales,
            'todaysOnlineOrders' => $todaysOnlineOrders,
            'pendingReviews' => $pendingReviews,
            'couponsUsedToday' => $couponsUsedToday,
            'topProducts' => $topProducts,
            'topMembers' => $topMembers,
            'upcomingTrainerBookings' => $upcomingTrainerBookings,
            'memberStatusCounts' => $memberStatusCounts,
            'newMembersByMonth' => $newMembersByMonth,
            'revenueByDay' => $revenueByDay,
        ]);
    }

    /** Zero-filled monthly counts for the last 12 months (including the current one), regardless of which months actually had rows. */
    private function monthlySeries(PDO $db, string $sql, string $keyColumn, string $valueColumn): array
    {
        $rows = $db->query($sql)->fetchAll();
        $byMonth = array_column($rows, $valueColumn, $keyColumn);

        $labels = [];
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i months"));
            $labels[] = date('M Y', strtotime("-$i months"));
            $data[] = (int) ($byMonth[$ym] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** Zero-filled daily totals for the last 30 days (including today), regardless of which days actually had rows. */
    private function dailySeries(PDO $db, string $sql, string $keyColumn, string $valueColumn): array
    {
        $rows = $db->query($sql)->fetchAll();
        $byDay = array_column($rows, $valueColumn, $keyColumn);

        $labels = [];
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d M', strtotime("-$i days"));
            $data[] = (float) ($byDay[$d] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
