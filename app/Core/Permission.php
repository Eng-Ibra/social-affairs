<?php

namespace App\Core;

class Permission
{
    private static array $cache = [];

    public static function check(?array $user, string $moduleKey, string $capability): bool
    {
        if (!$user || empty($user['role_id'])) {
            return false;
        }
        $roleId = (int) $user['role_id'];
        $perms = self::forRole($roleId);
        return isset($perms[$moduleKey]) && in_array($capability, $perms[$moduleKey], true);
    }

    public static function forRole(int $roleId): array
    {
        if (isset(self::$cache[$roleId])) {
            return self::$cache[$roleId];
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT p.module_key, p.capability FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ?'
        );
        $stmt->execute([$roleId]);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['module_key']][] = $row['capability'];
        }
        return self::$cache[$roleId] = $map;
    }

    public static function requireCapability(string $moduleKey, string $capability): void
    {
        if (!self::check(current_user(), $moduleKey, $capability)) {
            http_response_code(403);
            view('errors/403', ['module' => $moduleKey, 'capability' => $capability], 'layout/app');
            exit;
        }
    }
}
