<?php

namespace App\Core;

/**
 * Resolves the polymorphic (location_type, location_id) pairs used by
 * Projects, Complaints and Needs Assessments to whichever concrete table
 * that type refers to.
 */
class LocationResolver
{
    public const MAP = [
        'camp' => ['table' => 'camps', 'label' => 'camp_name', 'icon' => 'fa-campground'],
        'village' => ['table' => 'villages', 'label' => 'village_name', 'icon' => 'fa-house-chimney'],
        'host_community' => ['table' => 'host_communities', 'label' => 'neighborhood_name', 'icon' => 'fa-people-group'],
        'beneficiary' => ['table' => 'beneficiaries', 'label' => 'full_name', 'icon' => 'fa-hand-holding-heart'],
        'pwd' => ['table' => 'persons_with_disabilities', 'label' => 'full_name', 'icon' => 'fa-wheelchair'],
        'refugee' => ['table' => 'refugees', 'label' => 'full_name', 'icon' => 'fa-person-walking-luggage'],
    ];

    public static function options(string $type, string $q = ''): array
    {
        if (!isset(self::MAP[$type])) {
            return [];
        }
        $meta = self::MAP[$type];
        $pdo = Database::connection();
        $sql = "SELECT id, {$meta['label']} AS label FROM {$meta['table']} WHERE deleted_at IS NULL";
        $params = [];
        if ($q !== '') {
            $sql .= " AND {$meta['label']} LIKE ?";
            $params[] = "%{$q}%";
        }
        $sql .= " ORDER BY {$meta['label']} LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function resolve(?string $type, ?int $id): ?string
    {
        if (!$type || !$id || !isset(self::MAP[$type])) {
            return null;
        }
        $meta = self::MAP[$type];
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT {$meta['label']} FROM {$meta['table']} WHERE id = ?");
        $stmt->execute([$id]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : null;
    }

    public static function typeLabel(string $type): string
    {
        return ucwords(str_replace('_', ' ', $type));
    }
}
