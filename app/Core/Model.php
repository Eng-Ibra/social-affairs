<?php

namespace App\Core;

use PDO;

/**
 * Lightweight table gateway used by the generic CRUD engine.
 * Not a full ORM by design — the modules are metadata-driven (see app/Modules),
 * so a thin, explicit query layer keeps behaviour easy to audit.
 */
class Model
{
    protected PDO $pdo;

    public function __construct(
        protected string $table,
        protected string $primaryKey = 'id',
        protected bool $softDeletes = true
    ) {
        $this->pdo = Database::connection();
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        if ($this->softDeletes) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findWithTrashed(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $assignments = implode(', ', array_map(fn ($c) => "{$c} = ?", array_keys($data)));
        $sql = "UPDATE {$this->table} SET {$assignments} WHERE {$this->primaryKey} = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([...array_values($data), $id]);
    }

    public function softDelete(int $id): bool
    {
        if (!$this->softDeletes) {
            return $this->hardDelete($id);
        }
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET deleted_at = NOW() WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE {$this->table} SET deleted_at = NULL WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    public function hardDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    public function count(string $whereSql = '1=1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$whereSql}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function all(string $whereSql = '1=1', array $params = [], string $orderBy = ''): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$whereSql}" . ($orderBy ? " ORDER BY {$orderBy}" : '');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function table(): string
    {
        return $this->table;
    }
}
