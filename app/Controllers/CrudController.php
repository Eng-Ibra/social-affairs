<?php

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\LocationResolver;
use App\Core\Model;
use App\Core\Notification;
use App\Core\Permission;
use App\Core\ProgressCalculator;
use App\Core\Validator;
use App\Modules\ModuleRegistry;
use PDO;

class CrudController extends Controller
{
    private const PER_PAGE = 15;

    private function resolveModule(string $slug): array
    {
        $module = ModuleRegistry::get($slug);
        if (!$module) {
            http_response_code(404);
            view('errors/404', [], 'layout/blank');
            exit;
        }
        return $module;
    }

    private function modelFor(array $module): Model
    {
        return new Model($module['table'], 'id', $module['soft_deletes'] ?? true);
    }

    // ------------------------------------------------------------------
    // LIST
    // ------------------------------------------------------------------
    public function index(string $slug): void
    {
        $module = $this->resolveModule($slug);
        Permission::requireCapability($module['key'], 'view');
        $this->maybeRefreshComputed($module['key']);

        $pdo = Database::connection();
        [$selectSql, $joinSql] = $this->buildSelectAndJoins($module);

        $showTrashed = $this->input('trashed') === '1' && Permission::check(current_user(), $module['key'], 'delete') && ($module['soft_deletes'] ?? true);
        $where = [$showTrashed ? "{$module['table']}.deleted_at IS NOT NULL" : "{$module['table']}.deleted_at IS NULL"];
        $params = [];

        $this->applyScope($module, $where, $params);

        $q = trim((string) $this->input('q', ''));
        if ($q !== '') {
            $searchable = array_filter($module['fields'], fn ($f) => !empty($f['searchable']));
            if ($searchable) {
                $clauses = [];
                foreach ($searchable as $f) {
                    $clauses[] = "{$module['table']}.{$f['name']} LIKE ?";
                    $params[] = "%{$q}%";
                }
                $where[] = '(' . implode(' OR ', $clauses) . ')';
            }
        }

        foreach ($module['fields'] as $f) {
            if (!empty($f['filterable']) && $this->input($f['name']) !== null && $this->input($f['name']) !== '') {
                $where[] = "{$module['table']}.{$f['name']} = ?";
                $params[] = $this->input($f['name']);
            }
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM {$module['table']} {$joinSql} WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sortableFields = array_column($module['fields'], 'name');
        $sortableFields[] = 'created_at';
        $sort = $this->input('sort', 'created_at');
        if (!in_array($sort, $sortableFields, true)) {
            $sort = 'created_at';
        }
        $dir = strtolower((string) $this->input('dir', 'desc')) === 'asc' ? 'ASC' : 'DESC';

        $page = max(1, (int) $this->input('page', 1));
        $perPage = self::PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT {$selectSql} FROM {$module['table']} {$joinSql} WHERE {$whereSql}
                ORDER BY {$module['table']}.{$sort} {$dir} LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if (in_array($module['key'], ['projects', 'activities'], true)) {
            foreach ($rows as &$row) {
                $row['_metrics'] = $module['key'] === 'projects'
                    ? ProgressCalculator::projectMetrics($row)
                    : ProgressCalculator::activityMetrics($row);
            }
            unset($row);
        }

        $this->view('modules/list', [
            'title' => $module['label'],
            'module' => $module,
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
            'sort' => $sort,
            'dir' => $dir,
            'q' => $q,
            'showTrashed' => $showTrashed,
        ]);
    }

    public function restore(string $slug, string $id): void
    {
        $module = $this->resolveModule($slug);
        Permission::requireCapability($module['key'], 'delete');
        $this->verifyCsrf();

        $model = $this->modelFor($module);
        $record = $model->findWithTrashed((int) $id);
        if ($record) {
            $model->restore((int) $id);
            $title = $record[$module['title_field']] ?? $id;
            Audit::log('restored', $module['key'], (int) $id, "{$module['label_singular']} \"{$title}\" restored from archive");
            flash('success', $module['label_singular'] . ' restored successfully.');
        }
        redirect('/m/' . $slug . '?trashed=1');
    }

    // ------------------------------------------------------------------
    // CREATE / STORE
    // ------------------------------------------------------------------
    public function create(string $slug): void
    {
        $module = $this->resolveModule($slug);
        Permission::requireCapability($module['key'], 'create');
        $this->view('modules/form', [
            'title' => 'Add ' . $module['label_singular'],
            'module' => $module,
            'record' => [],
            'mode' => 'create',
        ]);
    }

    public function store(string $slug): void
    {
        $module = $this->resolveModule($slug);
        Permission::requireCapability($module['key'], 'create');
        $this->verifyCsrf();

        $data = $this->collectInput($module);
        $errors = $this->validate($data, $this->rulesFor($module, null));
        if ($errors) {
            redirect('/m/' . $slug . '/create');
        }

        $data = $this->handleFileUploads($module, $data, null);

        if (!empty($module['code_field'])) {
            $data[$module['code_field']] = generate_code($module['code_prefix'], Database::connection(), $module['table'], $module['code_field']);
        }
        $data['created_by'] = current_user()['id'];
        $data = $this->castTypes($module, $data);

        $model = $this->modelFor($module);
        $id = $model->insert($data);

        $title = $data[$module['title_field']] ?? $id;
        Audit::log('created', $module['key'], $id, "{$module['label_singular']} \"{$title}\" created", null, $data);
        $this->notifyOnCreate($module, $id, $data, $title);

        flash('success', $module['label_singular'] . ' created successfully.');
        redirect('/m/' . $slug . '/' . $id);
    }

    // ------------------------------------------------------------------
    // SHOW
    // ------------------------------------------------------------------
    public function show(string $slug, string $id): void
    {
        $module = $this->resolveModule($slug);
        Permission::requireCapability($module['key'], 'view');
        $this->maybeRefreshComputed($module['key']);

        $pdo = Database::connection();
        [$selectSql, $joinSql] = $this->buildSelectAndJoins($module);
        $stmt = $pdo->prepare("SELECT {$selectSql} FROM {$module['table']} {$joinSql} WHERE {$module['table']}.id = ?");
        $stmt->execute([(int) $id]);
        $record = $stmt->fetch();
        if (!$record) {
            http_response_code(404);
            $this->view('errors/404', [], 'layout/blank');
            return;
        }
        $this->authorizeRecordScope($module, $record);

        $metrics = null;
        if ($module['key'] === 'projects') {
            $metrics = ProgressCalculator::projectMetrics($record);
        } elseif ($module['key'] === 'activities') {
            $metrics = ProgressCalculator::activityMetrics($record);
        }

        $this->view('modules/view', [
            'title' => $record[$module['title_field']] ?? $module['label_singular'],
            'module' => $module,
            'record' => $record,
            'metrics' => $metrics,
        ]);
    }

    // ------------------------------------------------------------------
    // EDIT / UPDATE
    // ------------------------------------------------------------------
    public function edit(string $slug, string $id): void
    {
        $module = $this->resolveModule($slug);
        Permission::requireCapability($module['key'], 'edit');
        $model = $this->modelFor($module);
        $record = $model->find((int) $id);
        if (!$record) {
            http_response_code(404);
            $this->view('errors/404', [], 'layout/blank');
            return;
        }
        $this->authorizeRecordScope($module, $record);

        $this->view('modules/form', [
            'title' => 'Edit ' . $module['label_singular'],
            'module' => $module,
            'record' => $record,
            'mode' => 'edit',
        ]);
    }

    public function update(string $slug, string $id): void
    {
        $module = $this->resolveModule($slug);
        Permission::requireCapability($module['key'], 'edit');
        $this->verifyCsrf();

        $model = $this->modelFor($module);
        $old = $model->find((int) $id);
        if (!$old) {
            http_response_code(404);
            $this->view('errors/404', [], 'layout/blank');
            return;
        }
        $this->authorizeRecordScope($module, $old);

        $data = $this->collectInput($module);
        $errors = $this->validate($data, $this->rulesFor($module, (int) $id));
        if ($errors) {
            redirect('/m/' . $slug . '/' . $id . '/edit');
        }

        $data = $this->handleFileUploads($module, $data, $old);
        $data['updated_by'] = current_user()['id'];
        $data = $this->castTypes($module, $data);

        $model->update((int) $id, $data);

        $changed = [];
        foreach ($data as $k => $v) {
            if (($old[$k] ?? null) != $v) {
                $changed[$k] = ['old' => $old[$k] ?? null, 'new' => $v];
            }
        }

        $title = $data[$module['title_field']] ?? $old[$module['title_field']] ?? $id;
        Audit::log(
            'updated',
            $module['key'],
            (int) $id,
            "{$module['label_singular']} \"{$title}\" updated",
            array_map(fn ($c) => $c['old'], $changed),
            array_map(fn ($c) => $c['new'], $changed)
        );

        flash('success', $module['label_singular'] . ' updated successfully.');
        redirect('/m/' . $slug . '/' . $id);
    }

    // ------------------------------------------------------------------
    // DELETE (soft delete / archive)
    // ------------------------------------------------------------------
    public function destroy(string $slug, string $id): void
    {
        $module = $this->resolveModule($slug);
        Permission::requireCapability($module['key'], 'delete');
        $this->verifyCsrf();

        $model = $this->modelFor($module);
        $record = $model->find((int) $id);
        if ($record) {
            $this->authorizeRecordScope($module, $record);
            $model->softDelete((int) $id);
            $title = $record[$module['title_field']] ?? $id;
            Audit::log('deleted', $module['key'], (int) $id, "{$module['label_singular']} \"{$title}\" archived");
            flash('success', $module['label_singular'] . ' archived successfully.');
        }
        redirect('/m/' . $slug);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function notifyOnCreate(array $module, int $id, array $data, $title): void
    {
        if ($module['key'] === 'complaints') {
            $pdo = Database::connection();
            if (!empty($data['assigned_officer_id'])) {
                Notification::toUser((int) $data['assigned_officer_id'], 'New Complaint Assigned', "Complaint \"{$title}\" has been assigned to you.", 'complaint', '/m/complaints/' . $id);
            }
            $admins = $pdo->query("SELECT id FROM roles WHERE slug IN ('admin','super_admin')")->fetchAll();
            foreach ($admins as $role) {
                Notification::toRole((int) $role['id'], 'New Complaint Received', "A new complaint \"{$title}\" was logged and needs review.", 'complaint', '/m/complaints/' . $id);
            }
        } elseif ($module['key'] === 'events') {
            $pdo = Database::connection();
            $admins = $pdo->query("SELECT id FROM roles WHERE slug IN ('admin','super_admin')")->fetchAll();
            foreach ($admins as $role) {
                Notification::toRole((int) $role['id'], 'New Event Scheduled', "\"{$title}\" has been added to the calendar.", 'event', '/m/events/' . $id);
            }
        }
    }

    private function buildSelectAndJoins(array $module): array
    {
        $selects = ["{$module['table']}.*"];
        $joins = [];
        $i = 0;
        foreach ($module['fields'] as $f) {
            if ($f['type'] === 'relation') {
                $alias = 'rel' . $i++;
                // relation_where already contains "type='x'" so prefix alias.
                if (!empty($f['relation_where'])) {
                    $cond = str_replace('type=', "{$alias}.type=", $f['relation_where']);
                    $joins[] = "LEFT JOIN {$f['relation_table']} {$alias} ON {$alias}.id = {$module['table']}.{$f['name']} AND {$cond}";
                } else {
                    $joins[] = "LEFT JOIN {$f['relation_table']} {$alias} ON {$alias}.id = {$module['table']}.{$f['name']}";
                }
                $selects[] = "{$alias}.{$f['relation_display']} AS {$f['name']}_label";
            }
        }
        return [implode(', ', $selects), implode(' ', $joins)];
    }

    private function applyScope(array $module, array &$where, array &$params): void
    {
        if (Permission::check(current_user(), $module['key'], 'view_all')) {
            return;
        }
        $user = current_user();
        $clauses = ["{$module['table']}.created_by = ?"];
        $params[] = $user['id'];
        $fieldNames = array_column($module['fields'], 'name');
        if (in_array('section_id', $fieldNames, true) && !empty($user['section_id'])) {
            $clauses[] = "{$module['table']}.section_id = ?";
            $params[] = $user['section_id'];
        }
        $where[] = '(' . implode(' OR ', $clauses) . ')';
    }

    private function authorizeRecordScope(array $module, array $record): void
    {
        if (Permission::check(current_user(), $module['key'], 'view_all')) {
            return;
        }
        $user = current_user();
        $ownedByUser = (int) ($record['created_by'] ?? 0) === (int) $user['id'];
        $sectionMatch = isset($record['section_id']) && $record['section_id'] && (int) $record['section_id'] === (int) ($user['section_id'] ?? 0);
        if (!$ownedByUser && !$sectionMatch) {
            http_response_code(403);
            view('errors/403', ['module' => $module['key'], 'capability' => 'view'], 'layout/app');
            exit;
        }
    }

    private function collectInput(array $module): array
    {
        $data = [];
        foreach ($module['fields'] as $f) {
            if (($f['type'] ?? '') === 'file' || !empty($f['auto']) || !empty($f['readonly'])) {
                continue;
            }
            $value = $this->input($f['name']);
            if ($f['type'] === 'relation' || $f['type'] === 'polymorphic_location') {
                $data[$f['name']] = $value !== '' && $value !== null ? (int) $value : null;
            } else {
                $data[$f['name']] = $value !== null ? trim((string) $value) : null;
            }
        }
        return $data;
    }

    private function castTypes(array $module, array $data): array
    {
        foreach ($module['fields'] as $f) {
            if (!array_key_exists($f['name'], $data)) {
                continue;
            }
            $v = $data[$f['name']];
            if ($v === '' || $v === null) {
                $data[$f['name']] = $f['default'] ?? null;
                if (($data[$f['name']] ?? null) === 'today') {
                    $data[$f['name']] = date('Y-m-d');
                }
                continue;
            }
            if (in_array($f['type'], ['number'], true)) {
                $data[$f['name']] = (int) $v;
            } elseif ($f['type'] === 'decimal') {
                $data[$f['name']] = (float) $v;
            }
        }
        return $data;
    }

    private function rulesFor(array $module, ?int $exceptId): array
    {
        $rules = [];
        foreach ($module['fields'] as $f) {
            if (($f['type'] ?? '') === 'file' || !empty($f['auto']) || !empty($f['readonly'])) {
                continue;
            }
            $r = [];
            if (!empty($f['required'])) {
                $r[] = 'required';
            }
            if ($f['type'] === 'number' || $f['type'] === 'decimal') {
                $r[] = 'numeric';
            }
            if ($f['type'] === 'date') {
                $r[] = 'date';
            }
            if ($f['type'] === 'select' && !empty($f['options'])) {
                $r[] = 'in:' . implode(',', array_keys($f['options']));
            }
            if ($r) {
                $rules[$f['name']] = implode('|', $r);
            }
        }
        return $rules;
    }

    private function handleFileUploads(array $module, array $data, ?array $old): array
    {
        foreach ($module['fields'] as $f) {
            if (($f['type'] ?? '') !== 'file') {
                continue;
            }
            $name = $f['name'];
            if (!empty($_FILES[$name]['name']) && $_FILES[$name]['error'] === UPLOAD_ERR_OK) {
                $dir = base_path('public/uploads/' . $module['key']);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $ext = pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
                $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
                $filename = $module['key'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $safeExt;
                move_uploaded_file($_FILES[$name]['tmp_name'], $dir . '/' . $filename);
                $data[$name] = 'uploads/' . $module['key'] . '/' . $filename;
            } else {
                $data[$name] = $old[$name] ?? null;
            }
        }
        return $data;
    }

    private function maybeRefreshComputed(string $moduleKey): void
    {
        if (!in_array($moduleKey, ['projects', 'activities'], true)) {
            return;
        }
        $last = $_SESSION['_last_progress_refresh'] ?? 0;
        if (time() - $last < 30) {
            return;
        }
        $_SESSION['_last_progress_refresh'] = time();
        ProgressCalculator::refreshAll(Database::connection());
    }

    // ------------------------------------------------------------------
    // EXPORT / IMPORT
    // ------------------------------------------------------------------
    public function export(string $slug, string $format): void
    {
        $module = $this->resolveModule($slug);
        Permission::requireCapability($module['key'], 'export');

        $pdo = Database::connection();
        [$selectSql, $joinSql] = $this->buildSelectAndJoins($module);
        $where = ["{$module['table']}.deleted_at IS NULL"];
        $params = [];
        $this->applyScope($module, $where, $params);

        $q = trim((string) $this->input('q', ''));
        if ($q !== '') {
            $searchable = array_filter($module['fields'], fn ($f) => !empty($f['searchable']));
            $clauses = [];
            foreach ($searchable as $f) {
                $clauses[] = "{$module['table']}.{$f['name']} LIKE ?";
                $params[] = "%{$q}%";
            }
            if ($clauses) {
                $where[] = '(' . implode(' OR ', $clauses) . ')';
            }
        }
        foreach ($module['fields'] as $f) {
            if (!empty($f['filterable']) && $this->input($f['name']) !== null && $this->input($f['name']) !== '') {
                $where[] = "{$module['table']}.{$f['name']} = ?";
                $params[] = $this->input($f['name']);
            }
        }

        $sql = "SELECT {$selectSql} FROM {$module['table']} {$joinSql} WHERE " . implode(' AND ', $where)
             . " ORDER BY {$module['table']}.created_at DESC LIMIT 50000";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        Audit::log('exported', $module['key'], null, count($rows) . " records exported as {$format}");

        if ($format === 'xlsx') {
            \App\Core\ExportImport::streamXlsx($module, $rows);
        } else {
            \App\Core\ExportImport::streamCsv($module, $rows);
        }
    }

    public function template(string $slug): void
    {
        $module = $this->resolveModule($slug);
        Permission::requireCapability($module['key'], 'import');
        \App\Core\ExportImport::streamXlsx($module, [], true);
    }

    public function import(string $slug): void
    {
        $module = $this->resolveModule($slug);
        Permission::requireCapability($module['key'], 'import');
        $this->verifyCsrf();

        if (empty($_FILES['import_file']['name']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Please choose a valid file to import.');
            redirect('/m/' . $slug);
        }

        $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xlsx'], true)) {
            flash('error', 'Only CSV and XLSX files are supported.');
            redirect('/m/' . $slug);
        }

        $report = \App\Core\ExportImport::import($module, $_FILES['import_file']['tmp_name'], $_FILES['import_file']['name'], current_user()['id']);

        if ($report['success'] > 0) {
            flash('success', "Imported {$report['success']} of {$report['total']} rows successfully.");
        }
        if ($report['errors']) {
            $preview = array_slice($report['errors'], 0, 5);
            $msg = count($report['errors']) . ' row(s) had errors: ';
            $msg .= implode(' | ', array_map(fn ($e) => "Row {$e['row']}: {$e['message']}", $preview));
            flash('error', $msg);
        }
        redirect('/m/' . $slug);
    }

    /** Used by ReportController to fetch filtered rows for a module without pagination (capped for safety). */
    public function queryModuleRows(array $module, array $get, int $limit = 5000): array
    {
        $pdo = Database::connection();
        [$selectSql, $joinSql] = $this->buildSelectAndJoins($module);
        $where = ["{$module['table']}.deleted_at IS NULL"];
        $params = [];
        $this->applyScope($module, $where, $params);

        $q = trim((string) ($get['q'] ?? ''));
        if ($q !== '') {
            $searchable = array_filter($module['fields'], fn ($f) => !empty($f['searchable']));
            $clauses = [];
            foreach ($searchable as $f) {
                $clauses[] = "{$module['table']}.{$f['name']} LIKE ?";
                $params[] = "%{$q}%";
            }
            if ($clauses) {
                $where[] = '(' . implode(' OR ', $clauses) . ')';
            }
        }
        foreach ($module['fields'] as $f) {
            if (!empty($f['filterable']) && !empty($get[$f['name']])) {
                $where[] = "{$module['table']}.{$f['name']} = ?";
                $params[] = $get[$f['name']];
            }
        }
        if (!empty($get['date_from']) && in_array('registration_date', array_column($module['fields'], 'name'), true)) {
            $where[] = "{$module['table']}.registration_date >= ?";
            $params[] = $get['date_from'];
        }
        if (!empty($get['date_to']) && in_array('registration_date', array_column($module['fields'], 'name'), true)) {
            $where[] = "{$module['table']}.registration_date <= ?";
            $params[] = $get['date_to'];
        }

        $sql = "SELECT {$selectSql} FROM {$module['table']} {$joinSql} WHERE " . implode(' AND ', $where)
             . " ORDER BY {$module['table']}.created_at DESC LIMIT {$limit}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function relationOptions(array $field): array
    {
        $pdo = Database::connection();
        $sql = "SELECT id, {$field['relation_display']} AS label FROM {$field['relation_table']}";
        $conditions = [];
        if (!empty($field['relation_where'])) {
            $conditions[] = $field['relation_where'];
        }
        // most relation tables use soft deletes except categories, users, roles, sections(soft)
        $hasDeletedAt = !in_array($field['relation_table'], ['categories', 'users', 'roles'], true);
        if ($hasDeletedAt) {
            $conditions[] = 'deleted_at IS NULL';
        }
        if ($field['relation_table'] === 'users') {
            $conditions[] = "status = 'approved'";
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY label";
        return $pdo->query($sql)->fetchAll();
    }
}
