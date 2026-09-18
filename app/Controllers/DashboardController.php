<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $pdo = Database::connection();

        $counts = [];
        $tables = [
            'camps' => 'camps', 'villages' => 'villages', 'host_communities' => 'host_communities',
            'organizations' => 'organizations', 'complaints' => 'complaints', 'activities' => 'activities',
            'events' => 'events', 'beneficiaries' => 'beneficiaries', 'refugees' => 'refugees',
            'pwd' => 'persons_with_disabilities', 'schools' => 'schools', 'health_facilities' => 'health_facilities',
            'projects' => 'projects',
        ];
        foreach ($tables as $key => $table) {
            $counts[$key] = (int) $pdo->query("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL")->fetchColumn();
        }
        $counts['priorities_high'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM priorities WHERE COALESCE(override_level, computed_level) = 'high'"
        )->fetchColumn();

        // Needs by sector
        $needsBySector = $pdo->query(
            "SELECT c.name AS label, COUNT(*) AS total FROM needs_assessments n
             JOIN categories c ON c.id = n.need_category_id
             WHERE n.deleted_at IS NULL GROUP BY c.name ORDER BY total DESC LIMIT 8"
        )->fetchAll();

        $complaintStatus = $pdo->query(
            "SELECT status AS label, COUNT(*) AS total FROM complaints WHERE deleted_at IS NULL GROUP BY status"
        )->fetchAll();

        $projectStatus = $pdo->query(
            "SELECT status AS label, COUNT(*) AS total FROM projects WHERE deleted_at IS NULL GROUP BY status"
        )->fetchAll();

        $activityStatus = $pdo->query(
            "SELECT status AS label, COUNT(*) AS total FROM activities WHERE deleted_at IS NULL GROUP BY status"
        )->fetchAll();

        $populationByGender = $pdo->query(
            "SELECT
                (SELECT COALESCE(SUM(male_count),0) FROM camps WHERE deleted_at IS NULL) +
                (SELECT COALESCE(SUM(male_count),0) FROM host_communities WHERE deleted_at IS NULL) AS male,
                (SELECT COALESCE(SUM(female_count),0) FROM camps WHERE deleted_at IS NULL) +
                (SELECT COALESCE(SUM(female_count),0) FROM host_communities WHERE deleted_at IS NULL) AS female"
        )->fetch();

        $beneficiaryByVulnerability = $pdo->query(
            "SELECT COALESCE(NULLIF(vulnerability,''), 'Unspecified') AS label, COUNT(*) AS total
             FROM beneficiaries WHERE deleted_at IS NULL GROUP BY label ORDER BY total DESC LIMIT 8"
        )->fetchAll();

        $topPriorities = $pdo->query(
            "SELECT p.*, c.name AS category_name FROM priorities p
             JOIN categories c ON c.id = p.need_category_id
             ORDER BY p.computed_score DESC LIMIT 5"
        )->fetchAll();

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'counts' => $counts,
            'needsBySector' => $needsBySector,
            'complaintStatus' => $complaintStatus,
            'projectStatus' => $projectStatus,
            'activityStatus' => $activityStatus,
            'populationByGender' => $populationByGender,
            'beneficiaryByVulnerability' => $beneficiaryByVulnerability,
            'topPriorities' => $topPriorities,
        ]);
    }
}
