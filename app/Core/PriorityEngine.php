<?php

namespace App\Core;

use PDO;

/**
 * Automatic priority scoring for reported needs. Scores are derived purely
 * from recorded needs_assessments data (never invented), combining:
 *   - how many people are affected
 *   - reported severity & urgency
 *   - how many distinct locations report the same need
 *   - how frequently it is reported
 * Authorized users may override the computed level; every override is
 * written to priority_overrides_log for a full audit trail.
 */
class PriorityEngine
{
    /** Sentinel used instead of NULL for the "all locations" aggregate scope, so the unique key still dedupes correctly. */
    public const ALL_SCOPE_ID = 0;

    public static function recalculateAll(PDO $pdo): array
    {
        $rows = $pdo->query(
            "SELECT need_category_id,
                    SUM(number_affected) AS total_affected,
                    AVG(severity) AS avg_severity,
                    AVG(urgency) AS avg_urgency,
                    COUNT(DISTINCT CONCAT(location_type,'-',location_id)) AS locations_count,
                    COUNT(*) AS reports_count
             FROM needs_assessments
             WHERE deleted_at IS NULL AND status != 'closed'
             GROUP BY need_category_id"
        )->fetchAll();

        $updated = 0;
        foreach ($rows as $r) {
            $score = self::score(
                (float) $r['total_affected'],
                (float) $r['avg_severity'],
                (float) $r['avg_urgency'],
                (int) $r['locations_count'],
                (int) $r['reports_count']
            );
            $level = self::levelFor($score);

            $stmt = $pdo->prepare(
                'SELECT id, override_level, computed_level FROM priorities WHERE need_category_id = ? AND location_type = "all" AND location_id = ?'
            );
            $stmt->execute([$r['need_category_id'], self::ALL_SCOPE_ID]);
            $existing = $stmt->fetch();

            if ($existing && $existing['computed_level'] !== 'high' && $level === 'high') {
                self::notifyNewHighPriority($pdo, $r['need_category_id']);
            }

            if ($existing) {
                $pdo->prepare(
                    'UPDATE priorities SET total_affected=?, avg_severity=?, avg_urgency=?, locations_count=?, reports_count=?, computed_score=?, computed_level=? WHERE id=?'
                )->execute([
                    $r['total_affected'], $r['avg_severity'], $r['avg_urgency'],
                    $r['locations_count'], $r['reports_count'], $score, $level, $existing['id'],
                ]);
            } else {
                $pdo->prepare(
                    'INSERT INTO priorities (need_category_id, location_type, location_id, total_affected, avg_severity, avg_urgency, locations_count, reports_count, computed_score, computed_level)
                     VALUES (?, "all", ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $r['need_category_id'], self::ALL_SCOPE_ID, $r['total_affected'], $r['avg_severity'], $r['avg_urgency'],
                    $r['locations_count'], $r['reports_count'], $score, $level,
                ]);
            }
            $updated++;
        }

        // Remove stale priority rows whose need category no longer has any open assessment.
        $activeCategoryIds = array_column($rows, 'need_category_id');
        if ($activeCategoryIds) {
            $placeholders = implode(',', array_fill(0, count($activeCategoryIds), '?'));
            $pdo->prepare("DELETE FROM priorities WHERE location_type='all' AND need_category_id NOT IN ({$placeholders})")
                ->execute($activeCategoryIds);
        } else {
            $pdo->exec("DELETE FROM priorities WHERE location_type='all'");
        }

        return ['scores_updated' => $updated];
    }

    public static function score(float $totalAffected, float $avgSeverity, float $avgUrgency, int $locationsCount, int $reportsCount): float
    {
        $severityComponent = ($avgSeverity / 5) * 30;
        $urgencyComponent = ($avgUrgency / 5) * 30;
        $scaleComponent = min($totalAffected / 500, 1) * 25;
        $spreadComponent = min($locationsCount / 10, 1) * 15;
        return round($severityComponent + $urgencyComponent + $scaleComponent + $spreadComponent, 2);
    }

    public static function levelFor(float $score): string
    {
        if ($score >= 65) {
            return 'high';
        }
        if ($score >= 35) {
            return 'medium';
        }
        return 'low';
    }

    private static function notifyNewHighPriority(PDO $pdo, int $categoryId): void
    {
        $stmt = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
        $stmt->execute([$categoryId]);
        $name = $stmt->fetchColumn();
        $admins = $pdo->query("SELECT id FROM roles WHERE slug IN ('admin','super_admin','department_head')")->fetchAll();
        foreach ($admins as $role) {
            Notification::toRole(
                (int) $role['id'],
                'High Priority Need Identified',
                "\"{$name}\" has crossed into High priority based on newly reported assessments.",
                'priority',
                '/priorities'
            );
        }
    }

    public static function override(int $priorityId, string $newLevel, string $reason, int $userId): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM priorities WHERE id = ?');
        $stmt->execute([$priorityId]);
        $priority = $stmt->fetch();
        if (!$priority) {
            return;
        }
        $oldLevel = $priority['override_level'] ?: $priority['computed_level'];

        $pdo->prepare('UPDATE priorities SET override_level=?, override_by=?, override_reason=?, override_at=NOW() WHERE id=?')
            ->execute([$newLevel, $userId, $reason, $priorityId]);

        $pdo->prepare('INSERT INTO priority_overrides_log (priority_id, user_id, old_level, new_level, reason) VALUES (?,?,?,?,?)')
            ->execute([$priorityId, $userId, $oldLevel, $newLevel, $reason]);

        Audit::log('priority_override', 'priorities', $priorityId, "Priority level overridden from {$oldLevel} to {$newLevel}: {$reason}");
    }
}
