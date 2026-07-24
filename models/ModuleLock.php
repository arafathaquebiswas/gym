<?php

final class ModuleLock extends Model
{
    public const SCOPES = [
        'everyone',
        'main_admin_super_admin',
        'main_admin_staff',
        'main_admin_delivery',
        'main_admin_super_admin_staff',
        'main_admin_super_admin_delivery',
        'main_admin_only',
    ];

    public const LABELS = [
        'everyone' => 'Everyone',
        'main_admin_super_admin' => 'Main Admin + Super Admin',
        'main_admin_staff' => 'Main Admin + Staff',
        'main_admin_delivery' => 'Main Admin + Delivery Man',
        'main_admin_super_admin_staff' => 'Main Admin + Super Admin + Staff',
        'main_admin_super_admin_delivery' => 'Main Admin + Super Admin + Delivery Man',
        'main_admin_only' => 'Only Main Admin',
    ];

    /** @return array<string, string> module_key => scope, only for modules that have an explicit lock row */
    public function all(): array
    {
        $rows = $this->db->query('SELECT module_key, scope FROM module_locks')->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['module_key']] = $row['scope'];
        }
        return $map;
    }

    public function scopeFor(string $moduleKey): string
    {
        $stmt = $this->db->prepare('SELECT scope FROM module_locks WHERE module_key = :module_key');
        $stmt->execute(['module_key' => $moduleKey]);
        $scope = $stmt->fetchColumn();
        return $scope !== false ? $scope : 'everyone';
    }

    public function setScope(string $moduleKey, string $scope, int $updatedBy): void
    {
        if (!in_array($scope, self::SCOPES, true)) {
            throw new InvalidArgumentException("Invalid module lock scope: $scope");
        }
        $this->db->prepare(
            'INSERT INTO module_locks (module_key, scope, updated_by) VALUES (:module_key, :scope, :updated_by)
             ON DUPLICATE KEY UPDATE scope = :scope2, updated_by = :updated_by2'
        )->execute([
            'module_key' => $moduleKey, 'scope' => $scope, 'updated_by' => $updatedBy,
            'scope2' => $scope, 'updated_by2' => $updatedBy,
        ]);
    }
}
