<?php

namespace App\Core;

/**
 * Automatic time/progress calculations for Projects and Activities, and the
 * notification side-effects when something crosses into "near deadline" or
 * "overdue". Used both by the web request (light on-the-fly refresh) and by
 * cron/recalculate.php (authoritative scheduled recompute).
 */
class ProgressCalculator
{
    public static function projectMetrics(array $project): array
    {
        $today = new \DateTimeImmutable('today');
        $start = new \DateTimeImmutable($project['start_date']);
        $end = new \DateTimeImmutable($project['end_date']);

        $totalDays = max(1, $start->diff($end)->days);
        $elapsedRaw = $today < $start ? 0 : $start->diff(min($today, $end))->days;
        $elapsed = max(0, min($totalDays, $elapsedRaw));
        $remaining = max(0, $totalDays - $elapsed);
        $timeProgress = round(($elapsed / $totalDays) * 100, 2);

        $warningDays = (int) Auth::setting('project_deadline_warning_days', 7);

        if (($project['status'] ?? null) === 'completed') {
            $status = 'completed';
        } elseif ($today < $start) {
            $status = 'upcoming';
        } elseif ($today > $end) {
            $status = 'overdue';
        } else {
            $status = ($today->diff($end)->days <= $warningDays) ? 'near_deadline' : 'active';
        }

        return [
            'days_elapsed' => $elapsed,
            'days_remaining' => $today > $end ? 0 : $end->diff($today)->days,
            'total_days' => $totalDays,
            'time_progress_percent' => $timeProgress,
            'status' => $status,
            'is_overdue' => $today > $end && $status !== 'completed',
            'overdue_days' => $today > $end ? $today->diff($end)->days : 0,
        ];
    }

    public static function activityMetrics(array $activity): array
    {
        $today = new \DateTimeImmutable('today');
        $due = new \DateTimeImmutable($activity['due_date']);
        $daysDiff = $today->diff($due)->days;

        if (($activity['status'] ?? null) === 'completed') {
            $status = 'completed';
            $remainingLabel = 'Completed';
        } elseif ($today > $due) {
            $status = 'overdue';
            $remainingLabel = "Overdue by {$daysDiff} day" . ($daysDiff === 1 ? '' : 's');
        } else {
            $status = ($activity['status'] ?? 'pending') === 'in_progress' ? 'in_progress' : 'pending';
            $remainingLabel = $daysDiff === 0 ? 'Due today' : "{$daysDiff} day" . ($daysDiff === 1 ? '' : 's') . ' remaining';
        }

        return [
            'status' => $status,
            'days_remaining' => $today > $due ? 0 : $daysDiff,
            'remaining_label' => $remainingLabel,
            'is_overdue' => $today > $due && $status !== 'completed',
            'overdue_days' => $today > $due ? $daysDiff : 0,
        ];
    }

    /** Recomputes status/progress for every non-completed project & activity, persists changes, and raises deadline notifications. Returns a summary. */
    public static function refreshAll(\PDO $pdo): array
    {
        $summary = ['projects_updated' => 0, 'activities_updated' => 0, 'notifications' => 0];

        $projects = $pdo->query("SELECT * FROM projects WHERE deleted_at IS NULL AND status != 'completed'")->fetchAll();
        foreach ($projects as $project) {
            $metrics = self::projectMetrics($project);
            if ($metrics['status'] !== $project['status'] || abs($metrics['time_progress_percent'] - (float) $project['progress_percent']) > 0.01) {
                $pdo->prepare('UPDATE projects SET status = ?, progress_percent = ? WHERE id = ?')
                    ->execute([$metrics['status'], $metrics['time_progress_percent'], $project['id']]);
                $summary['projects_updated']++;
            }
            if (in_array($metrics['status'], ['near_deadline', 'overdue'], true) && !$project['deadline_notified']) {
                self::notifyDeadline('projects', $project, $metrics['status']);
                $pdo->prepare('UPDATE projects SET deadline_notified = 1 WHERE id = ?')->execute([$project['id']]);
                $summary['notifications']++;
            }
        }

        $activities = $pdo->query("SELECT * FROM activities WHERE deleted_at IS NULL AND status != 'completed'")->fetchAll();
        foreach ($activities as $activity) {
            $metrics = self::activityMetrics($activity);
            $newStatus = $metrics['status'] === 'overdue' ? 'overdue' : $activity['status'];
            if ($newStatus !== $activity['status']) {
                $pdo->prepare('UPDATE activities SET status = ? WHERE id = ?')->execute([$newStatus, $activity['id']]);
                $summary['activities_updated']++;
            }
            $warnDays = (int) Auth::setting('activity_deadline_warning_days', 2);
            if (($metrics['days_remaining'] <= $warnDays || $metrics['status'] === 'overdue') && !$activity['deadline_notified']) {
                self::notifyDeadline('activities', $activity, $metrics['status']);
                $pdo->prepare('UPDATE activities SET deadline_notified = 1 WHERE id = ?')->execute([$activity['id']]);
                $summary['notifications']++;
            }
        }

        return $summary;
    }

    private static function notifyDeadline(string $module, array $row, string $status): void
    {
        $pdo = Database::connection();
        $title = $module === 'projects' ? $row['project_name'] : $row['activity_title'];
        $userIds = array_filter([$row['responsible_person_id'] ?? null, $row['project_manager_id'] ?? null, $row['created_by'] ?? null]);
        $message = $status === 'overdue'
            ? "\"{$title}\" is now overdue."
            : "\"{$title}\" is approaching its deadline.";
        foreach (array_unique($userIds) as $uid) {
            Notification::toUser((int) $uid, ucfirst($status === 'overdue' ? 'Overdue' : 'Deadline approaching'), $message, 'deadline', '/m/' . $module . '/' . $row['id']);
        }
        // also notify admins
        $admins = $pdo->query("SELECT id FROM roles WHERE slug IN ('admin','super_admin')")->fetchAll();
        foreach ($admins as $role) {
            Notification::toRole((int) $role['id'], ucfirst($status === 'overdue' ? 'Overdue' : 'Deadline approaching'), $message, 'deadline', '/m/' . $module . '/' . $row['id']);
        }
    }
}
