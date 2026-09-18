<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\PriorityEngine;

class PriorityController extends Controller
{
    private function maybeRecalculate(): void
    {
        $last = $_SESSION['_last_priority_refresh'] ?? 0;
        if (time() - $last < 30) {
            return;
        }
        $_SESSION['_last_priority_refresh'] = time();
        PriorityEngine::recalculateAll(Database::connection());
    }

    public function index(): void
    {
        Permission::requireCapability('priorities', 'view');
        $this->maybeRecalculate();
        $pdo = Database::connection();

        $priorities = $pdo->query(
            "SELECT p.*, c.name AS category_name,
                    COALESCE(p.override_level, p.computed_level) AS effective_level
             FROM priorities p JOIN categories c ON c.id = p.need_category_id
             ORDER BY p.computed_score DESC"
        )->fetchAll();

        $needsByLocationType = $pdo->query(
            "SELECT location_type AS label, COUNT(*) AS total, SUM(number_affected) AS affected
             FROM needs_assessments WHERE deleted_at IS NULL GROUP BY location_type ORDER BY total DESC"
        )->fetchAll();

        $needsBySector = $pdo->query(
            "SELECT c.name AS label, COUNT(*) AS total, SUM(n.number_affected) AS affected
             FROM needs_assessments n JOIN categories c ON c.id = n.need_category_id
             WHERE n.deleted_at IS NULL GROUP BY c.name ORDER BY total DESC"
        )->fetchAll();

        $totalAffected = (int) $pdo->query('SELECT COALESCE(SUM(number_affected),0) FROM needs_assessments WHERE deleted_at IS NULL')->fetchColumn();
        $unresolvedCount = (int) $pdo->query("SELECT COUNT(*) FROM needs_assessments WHERE deleted_at IS NULL AND status NOT IN ('addressed','closed')")->fetchColumn();
        $totalAssessments = (int) $pdo->query('SELECT COUNT(*) FROM needs_assessments WHERE deleted_at IS NULL')->fetchColumn();

        $this->view('priorities/index', [
            'title' => 'Priority Analysis',
            'priorities' => $priorities,
            'needsByLocationType' => $needsByLocationType,
            'needsBySector' => $needsBySector,
            'totalAffected' => $totalAffected,
            'unresolvedCount' => $unresolvedCount,
            'totalAssessments' => $totalAssessments,
            'canOverride' => Permission::check(current_user(), 'priorities', 'manage'),
        ]);
    }

    public function override(string $id): void
    {
        Permission::requireCapability('priorities', 'manage');
        $this->verifyCsrf();
        $level = $this->input('level');
        $reason = trim((string) $this->input('reason'));

        if (!in_array($level, ['high', 'medium', 'low'], true) || $reason === '') {
            flash('error', 'Please select a level and provide a justification for the override.');
            redirect('/priorities');
        }

        PriorityEngine::override((int) $id, $level, $reason, current_user()['id']);
        flash('success', 'Priority level overridden successfully. This action has been logged.');
        redirect('/priorities');
    }
}
