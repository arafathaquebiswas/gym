<?php

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
        ];
    }

    public static function hasRole(string ...$roles): bool
    {
        return self::check() && in_array($_SESSION['user_role'], $roles, true);
    }

    public static function isStaff(): bool
    {
        return self::hasRole('super_admin', 'admin', 'receptionist', 'trainer', 'store_manager');
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role_slug'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('warning', 'Please log in to continue.');
            redirect('login');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!self::hasRole(...$roles)) {
            http_response_code(403);
            die('403 Forbidden');
        }
    }

    /**
     * Returns true if the given identifier (email/IP) is currently locked out
     * due to too many recent failed login attempts.
     */
    public static function isLockedOut(PDO $db, string $email, string $ip): bool
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM login_logs
             WHERE (email = :email OR ip_address = :ip)
               AND status = "failed"
               AND created_at > (NOW() - INTERVAL :minutes MINUTE)'
        );
        $stmt->execute(['email' => $email, 'ip' => $ip, 'minutes' => self::LOCKOUT_MINUTES]);
        return (int) $stmt->fetchColumn() >= self::MAX_ATTEMPTS;
    }

    public static function logAttempt(PDO $db, ?int $userId, string $email, string $status): void
    {
        $stmt = $db->prepare(
            'INSERT INTO login_logs (user_id, email, ip_address, user_agent, status, created_at)
             VALUES (:user_id, :email, :ip, :agent, :status, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'status' => $status,
        ]);
    }
}
