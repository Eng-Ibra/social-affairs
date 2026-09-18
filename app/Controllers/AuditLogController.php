<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;

class AuditLogController extends Controller
{
    private const PER_PAGE = 25;

    public function index(): void
    {
        Permission::requireCapability('audit_logs', 'view');
        $pdo = Database::connection();

        $where = ['1=1'];
        $params = [];

        if ($module = $this->input('module')) {
            $where[] = 'a.module = ?';
            $params[] = $module;
        }
        if ($action = $this->input('action')) {
            $where[] = 'a.action = ?';
            $params[] = $action;
        }
        if ($userId = $this->input('user_id')) {
            $where[] = 'a.user_id = ?';
            $params[] = $userId;
        }
        if ($from = $this->input('from')) {
            $where[] = 'a.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to = $this->input('to')) {
            $where[] = 'a.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }

        $whereSql = implode(' AND ', $where);
        $count = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $page = max(1, (int) $this->input('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $stmt = $pdo->prepare(
            "SELECT a.*, u.name AS user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
             WHERE {$whereSql} ORDER BY a.created_at DESC LIMIT " . self::PER_PAGE . " OFFSET {$offset}"
        );
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $modules = $pdo->query('SELECT DISTINCT module FROM audit_logs ORDER BY module')->fetchAll(\PDO::FETCH_COLUMN);
        $actions = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(\PDO::FETCH_COLUMN);

        $this->view('audit/index', [
            'title' => 'Audit Log',
            'logs' => $logs,
            'modules' => $modules,
            'actions' => $actions,
            'total' => $total,
            'page' => $page,
            'lastPage' => max(1, (int) ceil($total / self::PER_PAGE)),
        ]);
    }
}
