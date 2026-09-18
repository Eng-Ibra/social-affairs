<?php

namespace App\Core;

use PDO;

class Auth
{
    public static function attempt(string $email, string $password): array
    {
        $pdo = Database::connection();
        $maxAttempts = (int) self::setting('max_login_attempts', 5);
        $lockoutMinutes = (int) self::setting('login_lockout_minutes', 15);

        $recentFailures = self::recentFailedAttempts($email, $lockoutMinutes);
        if ($recentFailures >= $maxAttempts) {
            self::recordAttempt($email, false);
            return [false, "Too many failed attempts. Please try again in {$lockoutMinutes} minutes."];
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::recordAttempt($email, false);
            Audit::log('login_failed', 'users', null, "Failed login attempt for {$email}");
            return [false, 'Invalid email or password.'];
        }

        if ($user['status'] === 'pending') {
            return [false, 'Your account is pending administrator approval.'];
        }
        if ($user['status'] === 'rejected') {
            return [false, 'Your account registration was rejected. Contact the administrator.'];
        }
        if ($user['status'] === 'suspended') {
            return [false, 'Your account has been suspended. Contact the administrator.'];
        }

        self::recordAttempt($email, true);

        $update = $pdo->prepare('UPDATE users SET last_login_at = NOW(), last_login_ip = ?, failed_login_attempts = 0 WHERE id = ?');
        $update->execute([client_ip(), $user['id']]);

        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;

        Audit::log('login', 'users', (int) $user['id'], "{$user['name']} logged in");

        return [true, 'Login successful.'];
    }

    public static function logout(): void
    {
        $user = current_user();
        if ($user) {
            Audit::log('logout', 'users', (int) $user['id'], "{$user['name']} logged out");
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function refreshSessionUser(): void
    {
        $user = current_user();
        if (!$user) {
            return;
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $fresh = $stmt->fetch();
        if (!$fresh || $fresh['status'] !== 'approved') {
            self::logout();
            return;
        }
        unset($fresh['password_hash']);
        $_SESSION['user'] = $fresh;
    }

    private static function recentFailedAttempts(string $email, int $minutes): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE email = ? AND success = 0 AND created_at > (NOW() - INTERVAL ? MINUTE)'
        );
        $stmt->execute([$email, $minutes]);
        return (int) $stmt->fetchColumn();
    }

    private static function recordAttempt(string $email, bool $success): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)');
        $stmt->execute([$email, client_ip(), $success ? 1 : 0]);
    }

    public static function setting(string $key, $default = null)
    {
        static $cache = [];
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $cache[$key] = ($value !== false ? $value : $default);
    }
}
