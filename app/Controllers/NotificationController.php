<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Notification;

class NotificationController extends Controller
{
    public function open(string $id): void
    {
        $user = current_user();
        Notification::markRead((int) $id, $user);

        $pdo = \App\Core\Database::connection();
        $stmt = $pdo->prepare('SELECT link FROM notifications WHERE id = ?');
        $stmt->execute([$id]);
        $link = $stmt->fetchColumn();

        redirect($link ?: '/dashboard');
    }

    public function readAll(): void
    {
        Notification::markAllRead(current_user());
        $this->back();
    }
}
