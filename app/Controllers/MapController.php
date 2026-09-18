<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;

class MapController extends Controller
{
    private const GEO_MODULES = [
        'camps' => ['table' => 'camps', 'name' => 'camp_name', 'icon' => 'campground', 'color' => '#0d6efd'],
        'villages' => ['table' => 'villages', 'name' => 'village_name', 'icon' => 'house', 'color' => '#198754'],
        'host_communities' => ['table' => 'host_communities', 'name' => 'neighborhood_name', 'icon' => 'people', 'color' => '#6f42c1'],
        'schools' => ['table' => 'schools', 'name' => 'school_name', 'icon' => 'school', 'color' => '#0891b2'],
        'health_facilities' => ['table' => 'health_facilities', 'name' => 'facility_name', 'icon' => 'hospital', 'color' => '#be123c'],
    ];

    public function index(): void
    {
        $this->view('map/index', ['title' => 'Map View', 'module' => $this->input('module', '')]);
    }

    public function data(): void
    {
        $pdo = Database::connection();
        $filter = $this->input('module', '');
        $points = [];

        foreach (self::GEO_MODULES as $key => $meta) {
            if ($filter && $filter !== $key) {
                continue;
            }
            if (!Permission::check(current_user(), $key, 'view')) {
                continue;
            }
            $rows = $pdo->query(
                "SELECT id, {$meta['name']} AS name, gps_lat, gps_lng FROM {$meta['table']}
                 WHERE deleted_at IS NULL AND gps_lat IS NOT NULL AND gps_lng IS NOT NULL"
            )->fetchAll();
            foreach ($rows as $r) {
                $points[] = [
                    'module' => $key,
                    'name' => $r['name'],
                    'lat' => (float) $r['gps_lat'],
                    'lng' => (float) $r['gps_lng'],
                    'color' => $meta['color'],
                    'url' => url('/m/' . $key . '/' . $r['id']),
                ];
            }
        }

        json_response($points);
    }
}
