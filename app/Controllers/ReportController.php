<?php

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Permission;
use App\Modules\ModuleRegistry;

class ReportController extends Controller
{
    public function index(): void
    {
        Permission::requireCapability('reports', 'view');
        $modules = array_filter(ModuleRegistry::all(), fn ($m) => Permission::check(current_user(), $m['key'], 'view'));
        $this->view('reports/index', ['title' => 'Reports', 'modules' => $modules]);
    }

    public function show(string $slug): void
    {
        Permission::requireCapability('reports', 'view');
        $module = ModuleRegistry::get($slug);
        if (!$module) {
            http_response_code(404);
            $this->view('errors/404', [], 'layout/blank');
            return;
        }
        Permission::requireCapability($module['key'], 'view');

        $rows = (new CrudController())->queryModuleRows($module, $_GET);

        if ($this->input('export') === '1') {
            Audit::log('exported', $module['key'], null, count($rows) . ' records exported via report');
            \App\Core\ExportImport::streamXlsx($module, $rows);
        }

        $this->view('reports/show', [
            'title' => $module['label'] . ' Report',
            'module' => $module,
            'rows' => $rows,
        ], 'layout/app');
    }
}
