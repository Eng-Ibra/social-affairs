<?php
/** @var \App\Core\Router $router */

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\CrudController;
use App\Controllers\PriorityController;
use App\Controllers\AiAssistantController;
use App\Controllers\UserController;
use App\Controllers\RoleController;
use App\Controllers\SettingsController;
use App\Controllers\AuditLogController;
use App\Controllers\NotificationController;
use App\Controllers\SearchController;
use App\Controllers\CalendarController;
use App\Controllers\MapController;
use App\Controllers\ReportController;
use App\Controllers\BackupController;

$router->get('/', function () {
    redirect(is_logged_in() ? '/dashboard' : '/login');
});

// ---- Auth ----
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/signup', [AuthController::class, 'showSignup']);
$router->post('/signup', [AuthController::class, 'signup']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgot']);
$router->post('/forgot-password', [AuthController::class, 'forgot']);
$router->get('/reset-password', [AuthController::class, 'showReset']);
$router->post('/reset-password', [AuthController::class, 'reset']);
$router->get('/profile', [AuthController::class, 'profile']);
$router->post('/profile', [AuthController::class, 'updateProfile']);
$router->post('/profile/password', [AuthController::class, 'changePassword']);

// ---- Dashboard ----
$router->get('/dashboard', [DashboardController::class, 'index']);

// ---- Location AJAX (polymorphic location_type/location_id pickers) ----
$router->get('/location-options', function () {
    if (!is_logged_in()) { json_response(['error' => 'unauthenticated'], 401); }
    $type = $_GET['type'] ?? '';
    $q = $_GET['q'] ?? '';
    json_response(\App\Core\LocationResolver::options($type, $q));
});

// ---- Generic CRUD engine ----
$router->get('/m/{module}', [CrudController::class, 'index']);
$router->get('/m/{module}/create', [CrudController::class, 'create']);
$router->post('/m/{module}', [CrudController::class, 'store']);
$router->get('/m/{module}/export/{format}', [CrudController::class, 'export']);
$router->get('/m/{module}/import/template', [CrudController::class, 'template']);
$router->post('/m/{module}/import', [CrudController::class, 'import']);
$router->get('/m/{module}/{id}/edit', [CrudController::class, 'edit']);
$router->post('/m/{module}/{id}/delete', [CrudController::class, 'destroy']);
$router->post('/m/{module}/{id}/restore', [CrudController::class, 'restore']);
$router->get('/m/{module}/{id}', [CrudController::class, 'show']);
$router->put('/m/{module}/{id}', [CrudController::class, 'update']);

// ---- Priorities & AI Assistant ----
$router->get('/priorities', [PriorityController::class, 'index']);
$router->post('/priorities/{id}/override', [PriorityController::class, 'override']);
$router->get('/ai-assistant', [AiAssistantController::class, 'index']);

// ---- Users & Roles ----
$router->get('/users', [UserController::class, 'index']);
$router->post('/users/{id}/approve', [UserController::class, 'approve']);
$router->post('/users/{id}/reject', [UserController::class, 'reject']);
$router->post('/users/{id}/suspend', [UserController::class, 'suspend']);
$router->post('/users/{id}/reactivate', [UserController::class, 'reactivate']);
$router->post('/users/{id}/role', [UserController::class, 'updateRole']);
$router->get('/roles', [RoleController::class, 'index']);
$router->post('/roles/{id}', [RoleController::class, 'update']);

// ---- Settings ----
$router->get('/settings', [SettingsController::class, 'index']);
$router->post('/settings', [SettingsController::class, 'update']);
$router->post('/settings/categories', [SettingsController::class, 'addCategory']);
$router->post('/settings/categories/{id}/toggle', [SettingsController::class, 'toggleCategory']);
$router->post('/settings/categories/{id}/delete', [SettingsController::class, 'deleteCategory']);

// ---- Audit Log ----
$router->get('/audit-logs', [AuditLogController::class, 'index']);

// ---- Notifications ----
$router->get('/notifications/{id}/open', [NotificationController::class, 'open']);
$router->get('/notifications/read-all', [NotificationController::class, 'readAll']);

// ---- Search ----
$router->get('/search', [SearchController::class, 'index']);

// ---- Calendar ----
$router->get('/calendar', [CalendarController::class, 'index']);
$router->get('/calendar/feed', [CalendarController::class, 'feed']);
$router->post('/calendar', [CalendarController::class, 'store']);

// ---- Map ----
$router->get('/map', [MapController::class, 'index']);
$router->get('/map/data', [MapController::class, 'data']);

// ---- Reports ----
$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/{module}', [ReportController::class, 'show']);

// ---- Backup ----
$router->get('/backup', [BackupController::class, 'index']);
$router->post('/backup', [BackupController::class, 'create']);
$router->get('/backup/{filename}/download', [BackupController::class, 'download']);
