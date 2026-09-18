<?php

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Database;

class CalendarController extends Controller
{
    public function index(): void
    {
        $this->view('calendar/index', ['title' => 'Calendar']);
    }

    public function feed(): void
    {
        $pdo = Database::connection();
        $events = [];

        foreach ($pdo->query("SELECT id, project_name, start_date, end_date FROM projects WHERE deleted_at IS NULL")->fetchAll() as $p) {
            $events[] = ['title' => 'Project: ' . $p['project_name'], 'start' => $p['start_date'], 'end' => $p['end_date'], 'color' => '#0d6efd', 'url' => url('/m/projects/' . $p['id'])];
        }
        foreach ($pdo->query("SELECT id, activity_title, due_date, status FROM activities WHERE deleted_at IS NULL")->fetchAll() as $a) {
            $events[] = ['title' => 'Activity Due: ' . $a['activity_title'], 'start' => $a['due_date'], 'color' => $a['status'] === 'overdue' ? '#dc3545' : '#fd7e14', 'url' => url('/m/activities/' . $a['id'])];
        }
        foreach ($pdo->query("SELECT id, event_name, start_date, end_date FROM events WHERE deleted_at IS NULL")->fetchAll() as $e) {
            $events[] = ['title' => 'Event: ' . $e['event_name'], 'start' => $e['start_date'], 'end' => $e['end_date'], 'color' => '#20c997', 'url' => url('/m/events/' . $e['id'])];
        }
        foreach ($pdo->query("SELECT id, complainant_name, follow_up_date FROM complaints WHERE deleted_at IS NULL AND follow_up_date IS NOT NULL")->fetchAll() as $c) {
            $events[] = ['title' => 'Follow-up: ' . $c['complainant_name'], 'start' => $c['follow_up_date'], 'color' => '#d63384', 'url' => url('/m/complaints/' . $c['id'])];
        }
        foreach ($pdo->query('SELECT id, title, item_date, end_date, item_type FROM calendar_items')->fetchAll() as $c) {
            $events[] = ['title' => ucfirst($c['item_type']) . ': ' . $c['title'], 'start' => $c['item_date'], 'end' => $c['end_date'], 'color' => '#6f42c1'];
        }

        json_response($events);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO calendar_items (title, description, item_date, end_date, item_type, created_by) VALUES (?,?,?,?,?,?)');
        $stmt->execute([
            trim((string) $this->input('title')),
            trim((string) $this->input('description')),
            $this->input('item_date'),
            $this->input('end_date') ?: null,
            $this->input('item_type', 'meeting'),
            current_user()['id'],
        ]);
        Audit::log('created', 'calendar', (int) $pdo->lastInsertId(), 'Calendar item created');
        flash('success', 'Calendar item added.');
        redirect('/calendar');
    }
}
