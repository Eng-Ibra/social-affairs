<?php

namespace App\Core;

class Notification
{
    public static function toUser(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $message, $type, $link]);
    }

    public static function toRole(int $roleId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO notifications (role_target, title, message, type, link) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$roleId, $title, $message, $type, $link]);
    }

    public static function unreadCount(array $user): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND (user_id = ? OR role_target = ?)'
        );
        $stmt->execute([$user['id'], $user['role_id']]);
        return (int) $stmt->fetchColumn();
    }

    public static function recentFor(array $user, int $limit = 8): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT * FROM notifications WHERE (user_id = ? OR role_target = ?) ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $user['id'], \PDO::PARAM_INT);
        $stmt->bindValue(2, $user['role_id'], \PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function markRead(int $id, array $user): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR role_target = ?)'
        );
        $stmt->execute([$id, $user['id'], $user['role_id']]);
    }

    public static function markAllRead(array $user): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR role_target = ?)'
        );
        $stmt->execute([$user['id'], $user['role_id']]);
    }
}
