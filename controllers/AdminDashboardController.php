<?php

final class AdminDashboardController extends AdminController
{
    public function index(): void
    {
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
        ]);
    }
}
