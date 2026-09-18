<?php

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;

class RoleController extends Controller
{
    public function index(): void
    {
        Permission::requireCapability('roles', 'manage');
        $pdo = Database::connection();

        $roles = $pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();
        $permissions = $pdo->query('SELECT * FROM permissions ORDER BY module_key, capability')->fetchAll();

        $grouped = [];
        foreach ($permissions as $p) {
            $grouped[$p['module_key']][] = $p;
        }

        $assignments = [];
        $rows = $pdo->query('SELECT role_id, permission_id FROM role_permissions')->fetchAll();
        foreach ($rows as $r) {
            $assignments[$r['role_id']][$r['permission_id']] = true;
        }

        $this->view('roles/index', [
            'title' => 'Roles & Permissions',
            'roles' => $roles,
            'grouped' => $grouped,
            'assignments' => $assignments,
        ]);
    }

    public function update(string $id): void
    {
        Permission::requireCapability('roles', 'manage');
        $this->verifyCsrf();

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM roles WHERE id = ?');
        $stmt->execute([$id]);
        $role = $stmt->fetch();
        if (!$role) {
            redirect('/roles');
        }
        if ($role['slug'] === 'super_admin') {
            flash('error', 'The Super Admin role always has full access and cannot be changed.');
            redirect('/roles');
        }

        $permissionIds = array_map('intval', $this->input('permissions', []));

        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$id]);
        $stmt = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
        foreach ($permissionIds as $pid) {
            $stmt->execute([$id, $pid]);
        }
        $pdo->commit();

        Audit::log('updated', 'roles', (int) $id, "Permissions updated for role \"{$role['name']}\"");
        flash('success', "Permissions updated for {$role['name']}.");
        redirect('/roles');
    }
}
