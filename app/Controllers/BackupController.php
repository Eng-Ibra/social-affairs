<?php

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Permission;

class BackupController extends Controller
{
    private function backupDir(): string
    {
        $dir = base_path('storage/backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public function index(): void
    {
        Permission::requireCapability('settings', 'manage');
        $files = glob($this->backupDir() . '/*.sql') ?: [];
        rsort($files);
        $backups = array_map(fn ($f) => [
            'name' => basename($f),
            'size' => filesize($f),
            'created_at' => date('Y-m-d H:i:s', filemtime($f)),
        ], $files);

        $this->view('backup/index', ['title' => 'Backup & Data Management', 'backups' => $backups]);
    }

    public function create(): void
    {
        Permission::requireCapability('settings', 'manage');
        $this->verifyCsrf();

        $cfg = config('db');
        $filename = 'backup_' . date('Ymd_His') . '.sql';
        $path = $this->backupDir() . '/' . $filename;

        $mysqldump = $this->findMysqldump();
        if ($mysqldump) {
            $cmd = sprintf(
                '%s --host=%s --port=%s --user=%s %s %s > %s 2>&1',
                escapeshellarg($mysqldump),
                escapeshellarg($cfg['host']),
                escapeshellarg($cfg['port']),
                escapeshellarg($cfg['username']),
                $cfg['password'] !== '' ? '--password=' . escapeshellarg($cfg['password']) : '',
                escapeshellarg($cfg['database']),
                escapeshellarg($path)
            );
            exec($cmd, $output, $exitCode);
        } else {
            $exitCode = 1;
        }

        if ($exitCode !== 0 || !is_file($path) || filesize($path) === 0) {
            // Fallback: pure-PHP logical backup so the feature still works when mysqldump isn't on PATH.
            $this->phpFallbackBackup($path);
        }

        Audit::log('exported', 'settings', null, "Database backup created: {$filename}");
        flash('success', 'Backup created successfully: ' . $filename);
        redirect('/backup');
    }

    public function download(string $filename): void
    {
        Permission::requireCapability('settings', 'manage');
        $safe = basename($filename);
        $path = $this->backupDir() . '/' . $safe;
        if (!is_file($path)) {
            http_response_code(404);
            exit('Backup not found.');
        }
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment;filename="' . $safe . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    private function findMysqldump(): ?string
    {
        foreach (['mysqldump', '/usr/bin/mysqldump', 'C:\\xampp\\mysql\\bin\\mysqldump.exe'] as $candidate) {
            $which = @shell_exec('command -v ' . escapeshellarg($candidate) . ' 2>/dev/null');
            if ($which || is_file($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    private function phpFallbackBackup(string $path): void
    {
        $pdo = \App\Core\Database::connection();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        $sql = "-- Social Affairs Management System logical backup\n-- Generated " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n" . $create['Create Table'] . ";\n\n";
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll();
            foreach ($rows as $row) {
                $cols = array_map(fn ($c) => '`' . $c . '`', array_keys($row));
                $vals = array_map(function ($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote((string) $v);
                }, array_values($row));
                $sql .= "INSERT INTO `{$table}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
            }
            $sql .= "\n";
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($path, $sql);
    }
}
