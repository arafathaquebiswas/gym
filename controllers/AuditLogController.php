<?php

final class AuditLogController extends AdminController
{
    protected string $moduleKey = 'audit_logs';

    private const PER_PAGE = 30;

    public function index(): void
    {
        $db = Database::connection();

        $filters = [
            'user_id' => $this->input('user_id'),
            'action' => $this->input('action'),
            'from' => $this->input('from'),
            'to' => $this->input('to'),
        ];

        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'l.user_id = :user_id';
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'l.action = :action';
            $params['action'] = $filters['action'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'DATE(l.created_at) >= :from';
            $params['from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'DATE(l.created_at) <= :to';
            $params['to'] = $filters['to'];
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $db->prepare('SELECT COUNT(*) FROM activity_logs l' . $whereSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $page = max(1, (int) $this->input('page', '1'));
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * self::PER_PAGE;

        $stmt = $db->prepare(
            'SELECT l.*, u.name AS user_name
             FROM activity_logs l
             LEFT JOIN users u ON u.id = l.user_id'
            . $whereSql .
            ' ORDER BY l.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', self::PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $admins = $db->query(
            "SELECT DISTINCT u.id, u.name FROM users u
             JOIN activity_logs l ON l.user_id = u.id
             ORDER BY u.name ASC"
        )->fetchAll();

        $actions = $db->query(
            'SELECT DISTINCT action FROM activity_logs ORDER BY action ASC'
        )->fetchAll(PDO::FETCH_COLUMN);

        $this->adminView('audit-log/index', [
            'pageTitle' => 'Audit Log',
            'logs' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'admins' => $admins,
            'actions' => $actions,
        ]);
    }
}
