<?php
/**
 * Scheduled job: recomputes Project/Activity status & progress, raises
 * deadline notifications, and recalculates the Priority engine.
 *
 * Run every 15-30 minutes via cron (Linux/XAMPP) or Windows Task Scheduler:
 *   php /path/to/social-affairs/cron/recalculate.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\ProgressCalculator;
use App\Core\PriorityEngine;

$pdo = Database::connection();

$progress = ProgressCalculator::refreshAll($pdo);
echo '[' . date('Y-m-d H:i:s') . "] Progress refresh: {$progress['projects_updated']} projects, "
    . "{$progress['activities_updated']} activities updated, {$progress['notifications']} notifications sent.\n";

if (class_exists(PriorityEngine::class)) {
    $priority = PriorityEngine::recalculateAll($pdo);
    echo '[' . date('Y-m-d H:i:s') . "] Priority engine: {$priority['scores_updated']} priority scores recalculated.\n";
}

echo '[' . date('Y-m-d H:i:s') . "] Done.\n";
