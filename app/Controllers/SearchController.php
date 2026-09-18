<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Modules\ModuleRegistry;

class SearchController extends Controller
{
    public function index(): void
    {
        $q = trim((string) $this->input('q', ''));
        $results = [];

        if ($q !== '') {
            $pdo = Database::connection();
            foreach (ModuleRegistry::all() as $key => $module) {
                if (!Permission::check(current_user(), $key, 'view')) {
                    continue;
                }
                $searchable = array_filter($module['fields'], fn ($f) => !empty($f['searchable']));
                if (!$searchable) {
                    continue;
                }
                $clauses = [];
                $params = [];
                foreach ($searchable as $f) {
                    $clauses[] = "{$f['name']} LIKE ?";
                    $params[] = "%{$q}%";
                }
                $sql = "SELECT id, {$module['title_field']} AS title FROM {$module['table']}
                        WHERE deleted_at IS NULL AND (" . implode(' OR ', $clauses) . ') LIMIT 5';
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $rows = $stmt->fetchAll();
                if ($rows) {
                    $results[$key] = ['module' => $module, 'rows' => $rows];
                }
            }
        }

        $this->view('search/index', ['title' => 'Search Results', 'q' => $q, 'results' => $results]);
    }
}
