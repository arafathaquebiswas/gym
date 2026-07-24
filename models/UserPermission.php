<?php

final class UserPermission extends Model
{
    private const ACTIONS = ['view', 'create', 'edit', 'delete', 'export', 'print', 'approve'];

    /** @return array<string, array{can_view:int,can_create:int,can_edit:int,can_delete:int,can_export:int,can_print:int}> keyed by module_key */
    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_permissions WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[$row['module_key']] = $row;
        }
        return $rows;
    }

    public function find(int $userId, string $moduleKey): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_permissions WHERE user_id = :user_id AND module_key = :module_key');
        $stmt->execute(['user_id' => $userId, 'module_key' => $moduleKey]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Replaces this user's entire permission set in one go — the Assign Permissions form posts
     * one checkbox grid (module x action), so a full replace is simpler and safer than diffing.
     * @param array<string, array<string, bool>> $moduleActions module_key => [action => bool]
     */
    public function setMany(int $userId, array $moduleActions): void
    {
        $this->db->prepare('DELETE FROM user_permissions WHERE user_id = :user_id')->execute(['user_id' => $userId]);

        $stmt = $this->db->prepare(
            'INSERT INTO user_permissions (user_id, module_key, can_view, can_create, can_edit, can_delete, can_export, can_print, can_approve)
             VALUES (:user_id, :module_key, :view, :create, :edit, :delete, :export, :print, :approve)'
        );
        foreach ($moduleActions as $moduleKey => $actions) {
            $stmt->execute([
                'user_id' => $userId,
                'module_key' => $moduleKey,
                'view' => !empty($actions['view']) ? 1 : 0,
                'create' => !empty($actions['create']) ? 1 : 0,
                'edit' => !empty($actions['edit']) ? 1 : 0,
                'delete' => !empty($actions['delete']) ? 1 : 0,
                'export' => !empty($actions['export']) ? 1 : 0,
                'print' => !empty($actions['print']) ? 1 : 0,
                'approve' => !empty($actions['approve']) ? 1 : 0,
            ]);
        }
    }

    public function deleteForUser(int $userId): void
    {
        $this->db->prepare('DELETE FROM user_permissions WHERE user_id = :user_id')->execute(['user_id' => $userId]);
    }

    public static function actions(): array
    {
        return self::ACTIONS;
    }
}
