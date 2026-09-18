<?php

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;

class SettingsController extends Controller
{
    private const CATEGORY_TYPES = [
        'need' => 'Need Categories',
        'disability' => 'Disability Categories',
        'complaint' => 'Complaint Categories',
        'sector' => 'Sectors',
        'organization_type' => 'Organization Types',
        'facility_type' => 'Health Facility Types',
        'event_type' => 'Event Types',
    ];

    public function index(): void
    {
        Permission::requireCapability('settings', 'manage');
        $pdo = Database::connection();
        $settings = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll(\PDO::FETCH_KEY_PAIR);

        $categories = [];
        foreach (array_keys(self::CATEGORY_TYPES) as $type) {
            $stmt = $pdo->prepare('SELECT * FROM categories WHERE type = ? ORDER BY name');
            $stmt->execute([$type]);
            $categories[$type] = $stmt->fetchAll();
        }

        $this->view('settings/index', [
            'title' => 'Settings',
            'settings' => $settings,
            'categoryTypes' => self::CATEGORY_TYPES,
            'categories' => $categories,
        ]);
    }

    public function update(): void
    {
        Permission::requireCapability('settings', 'manage');
        $this->verifyCsrf();
        $pdo = Database::connection();

        $keys = ['system_name', 'organization_name', 'district_name', 'contact_email', 'contact_phone',
                 'project_deadline_warning_days', 'activity_deadline_warning_days', 'max_login_attempts', 'login_lockout_minutes'];

        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($keys as $key) {
            $stmt->execute([$key, trim((string) $this->input($key, ''))]);
        }

        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $dir = base_path('public/uploads/branding');
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . time() . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
            move_uploaded_file($_FILES['logo']['tmp_name'], $dir . '/' . $filename);
            $stmt->execute(['logo_path', 'uploads/branding/' . $filename]);
        }

        Audit::log('updated', 'settings', null, 'System settings updated');
        flash('success', 'Settings updated successfully.');
        redirect('/settings');
    }

    public function addCategory(): void
    {
        Permission::requireCapability('settings', 'manage');
        $this->verifyCsrf();
        $type = $this->input('type');
        $name = trim((string) $this->input('name'));

        if (!isset(self::CATEGORY_TYPES[$type]) || $name === '') {
            flash('error', 'Please provide a valid category name.');
            redirect('/settings');
        }

        $pdo = Database::connection();
        try {
            $pdo->prepare('INSERT INTO categories (type, name) VALUES (?, ?)')->execute([$type, $name]);
            Audit::log('created', 'settings', null, "Category \"{$name}\" added to {$type}");
            flash('success', 'Category added.');
        } catch (\PDOException $e) {
            flash('error', 'That category already exists.');
        }
        redirect('/settings');
    }

    public function toggleCategory(string $id): void
    {
        Permission::requireCapability('settings', 'manage');
        $this->verifyCsrf();
        $pdo = Database::connection();
        $pdo->prepare("UPDATE categories SET status = IF(status='active','inactive','active') WHERE id = ?")->execute([$id]);
        Audit::log('updated', 'settings', (int) $id, 'Category status toggled');
        redirect('/settings');
    }

    public function deleteCategory(string $id): void
    {
        Permission::requireCapability('settings', 'manage');
        $this->verifyCsrf();
        $pdo = Database::connection();
        try {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            Audit::log('deleted', 'settings', (int) $id, 'Category deleted');
            flash('success', 'Category deleted.');
        } catch (\PDOException $e) {
            flash('error', 'This category is in use and cannot be deleted. Deactivate it instead.');
        }
        redirect('/settings');
    }
}
