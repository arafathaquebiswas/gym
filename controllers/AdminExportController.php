<?php

final class AdminExportController extends Controller
{
    private const MODULES = [
        'members' => ['members', 'SELECT m.id, m.member_code, u.name, u.email, u.phone, m.status, m.join_date, m.created_at FROM members m JOIN users u ON u.id = m.user_id'],
        'trainers' => ['trainers', 'SELECT id, name, email, phone, specialization, availability_status, is_featured, is_active, created_at FROM trainers'],
        'packages' => ['packages', 'SELECT id, name, category, duration_days, regular_price, offer_price, is_featured, is_active, created_at FROM membership_packages'],
        'coupons' => ['coupons', 'SELECT id, code, title, discount_type, discount_value, is_active, start_date, end_date, created_at FROM promotions'],
        'products' => ['store', 'SELECT id, sku, name, selling_price, stock_qty, status, is_featured, is_archived, created_at FROM products'],
        'categories' => ['store', 'SELECT id, name, status, sort_order FROM product_categories'],
        'attributes' => ['store', 'SELECT id, name, slug, created_at FROM product_attributes'],
        'brands' => ['store', 'SELECT id, name, slug, offer_enabled, offer_percent, created_at FROM brands'],
        'suppliers' => ['store', 'SELECT id, name, contact_person, phone, email, address, created_at FROM suppliers'],
        'purchases' => ['purchases', 'SELECT id, invoice_no, supplier_id, purchase_date, total_amount, created_at FROM purchases'],
        'sales' => ['sales', 'SELECT id, invoice_no, member_id, sale_date, total, payment_method, payment_status FROM sales'],
        'pos' => ['pos', 'SELECT id, invoice_no, member_id, sale_date, total, payment_method, payment_status FROM sales'],
        'orders' => ['orders', 'SELECT id, order_no, fulfillment_method, total, payment_method, payment_status, status, created_at FROM orders'],
        'attendance' => ['members', 'SELECT id, member_id, check_in, check_out, method FROM attendance'],
        'reviews' => ['reviews', 'SELECT id, product_id, member_id, rating, status, admin_reply, created_at FROM product_reviews'],
        'messages' => ['messages', 'SELECT id, name, email, phone, subject, status, created_at FROM contact_messages'],
        'staff' => ['dashboard', "SELECT u.id, u.name, u.email, u.phone, u.status, r.slug AS role, u.created_at FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug IN ('staff', 'super_admin')"],
        'delivery-staff' => ['delivery_staff', "SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'delivery'"],
        'audit-logs' => ['audit_logs', 'SELECT id, user_id, action, description, ip_address, module_key, export_format, file_name, record_count, created_at FROM activity_logs'],
    ];

    public function download(string $module): void
    {
        Auth::requireRole('main_admin', 'super_admin', 'staff', 'admin');
        $module = strtolower($module);
        $config = self::MODULES[$module] ?? null;
        if (!$config) {
            http_response_code(404);
            exit('Export module not found');
        }
        Permission::require($config[0], 'export');

        $format = strtolower((string) ($_GET['format'] ?? 'csv'));
        if (!in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            http_response_code(422);
            exit('Unsupported export format');
        }
        $filters = $this->filters();
        $options = [
            'filename' => trim((string) ($_GET['filename'] ?? '')),
            'include_headers' => ($_GET['include_headers'] ?? '1') !== '0',
            'include_filters' => ($_GET['include_filters'] ?? '1') !== '0',
            'include_logo' => ($_GET['include_logo'] ?? '1') !== '0',
            'include_date' => ($_GET['include_date'] ?? '1') !== '0',
            'include_user' => ($_GET['include_user'] ?? '1') !== '0',
        ];
        $rows = $this->rows($config[1], $filters);
        ExportService::download($module, $format, $rows, $filters, $options);
    }

    private function filters(): array
    {
        return [
            'scope' => in_array($_GET['scope'] ?? '', ['current', 'selected', 'all'], true) ? $_GET['scope'] : 'all',
            'ids' => array_values(array_filter(array_map('intval', explode(',', (string) ($_GET['ids'] ?? ''))))),
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'from' => trim((string) ($_GET['from'] ?? '')),
            'to' => trim((string) ($_GET['to'] ?? '')),
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
        ];
    }

    private function rows(string $sql, array $filters): array
    {
        $stmt = Database::connection()->query($sql);
        $rows = $stmt->fetchAll();
        $rows = array_values(array_filter($rows, function (array $row) use ($filters): bool {
            if ($filters['ids'] && !in_array((int) ($row['id'] ?? 0), $filters['ids'], true)) return false;
            if ($filters['status'] !== '' && array_key_exists('status', $row) && (string) $row['status'] !== $filters['status']) return false;
            if ($filters['search'] !== '') {
                $haystack = strtolower(implode(' ', array_map('strval', $row)));
                if (!str_contains($haystack, strtolower($filters['search']))) return false;
            }
            $date = $row['created_at'] ?? $row['sale_date'] ?? $row['purchase_date'] ?? $row['check_in'] ?? null;
            if ($filters['from'] !== '' && $date && substr((string) $date, 0, 10) < $filters['from']) return false;
            if ($filters['to'] !== '' && $date && substr((string) $date, 0, 10) > $filters['to']) return false;
            return true;
        }));
        if ($filters['scope'] === 'current') {
            $rows = array_slice($rows, ($filters['page'] - 1) * 25, 25);
        }
        return $rows;
    }
}
