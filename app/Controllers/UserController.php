<?php

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Notification;
use App\Core\Permission;

class UserController extends Controller
{
    public function index(): void
    {
        Permission::requireCapability('users', 'view');
        $pdo = Database::connection();

        $status = $this->input('status', '');
        $q = trim((string) $this->input('q', ''));

        $where = ['1=1'];
        $params = [];
        if ($status !== '') {
            $where[] = 'u.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ?)';
            $params[] = "%{$q}%";
            $params[] = "%{$q}%";
        }

        $sql = 'SELECT u.*, r.name AS role_name, d.name AS department_name, s.section_name
                FROM users u
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN departments d ON d.id = u.department_id
                LEFT JOIN sections s ON s.id = u.section_id
                WHERE ' . implode(' AND ', $where) . ' ORDER BY u.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        $roles = $pdo->query('SELECT id, name FROM roles ORDER BY id')->fetchAll();
        $sections = $pdo->query('SELECT id, section_name FROM sections ORDER BY section_name')->fetchAll();

        $this->view('users/index', [
            'title' => 'User Management',
            'users' => $users,
            'roles' => $roles,
            'sections' => $sections,
            'status' => $status,
            'q' => $q,
        ]);
    }

    public function approve(string $id): void
    {
        Permission::requireCapability('users', 'approve');
        $this->verifyCsrf();
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            redirect('/users');
        }

        $pdo->prepare('UPDATE users SET status = "approved", approved_by = ?, approved_at = NOW() WHERE id = ?')
            ->execute([current_user()['id'], $id]);

        Audit::log('approved', 'users', (int) $id, "{$user['name']} account approved");
        Notification::toUser((int) $id, 'Account Approved', 'Your account has been approved. You can now log in.', 'account', '/login');

        flash('success', "{$user['name']}'s account has been approved.");
        redirect('/users');
    }

    public function reject(string $id): void
    {
        Permission::requireCapability('users', 'approve');
        $this->verifyCsrf();
        $reason = trim((string) $this->input('reason', 'Not specified'));
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            redirect('/users');
        }

        $pdo->prepare('UPDATE users SET status = "rejected", rejection_reason = ?, approved_by = ?, approved_at = NOW() WHERE id = ?')
            ->execute([$reason, current_user()['id'], $id]);

        Audit::log('rejected', 'users', (int) $id, "{$user['name']} account rejected: {$reason}");
        flash('success', "{$user['name']}'s account has been rejected.");
        redirect('/users');
    }

    public function suspend(string $id): void
    {
        Permission::requireCapability('users', 'edit');
        $this->verifyCsrf();
        $pdo = Database::connection();
        $pdo->prepare('UPDATE users SET status = "suspended" WHERE id = ?')->execute([$id]);
        Audit::log('suspended', 'users', (int) $id, 'Account suspended');
        flash('success', 'Account suspended.');
        redirect('/users');
    }

    public function reactivate(string $id): void
    {
        Permission::requireCapability('users', 'edit');
        $this->verifyCsrf();
        $pdo = Database::connection();
        $pdo->prepare('UPDATE users SET status = "approved" WHERE id = ?')->execute([$id]);
        Audit::log('reactivated', 'users', (int) $id, 'Account reactivated');
        flash('success', 'Account reactivated.');
        redirect('/users');
    }

    public function updateRole(string $id): void
    {
        Permission::requireCapability('users', 'edit');
        $this->verifyCsrf();
        $roleId = (int) $this->input('role_id');
        $sectionId = $this->input('section_id') ?: null;
        $departmentId = $this->input('department_id') ?: null;

        $pdo = Database::connection();
        $pdo->prepare('UPDATE users SET role_id = ?, section_id = ?, department_id = ? WHERE id = ?')
            ->execute([$roleId, $sectionId, $departmentId, $id]);

        Audit::log('updated', 'users', (int) $id, 'User role/section/department updated by admin');
        flash('success', 'User updated successfully.');
        redirect('/users');
    }
}
