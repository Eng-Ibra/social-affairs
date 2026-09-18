<?php

namespace App\Core;

class Controller
{
    public function __construct()
    {
        $this->requireAuth();
    }

    protected function guestOnlyRoutes(): bool
    {
        return false;
    }

    protected function requireAuth(): void
    {
        if ($this->guestOnlyRoutes()) {
            return;
        }
        if (!is_logged_in()) {
            flash('error', 'Please log in to continue.');
            redirect('/login');
        }
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function all(): array
    {
        return $_POST;
    }

    protected function validate(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            $_SESSION['_old_input'] = $data;
            $_SESSION['_errors'] = $validator->errors();
            return $validator->errors();
        }
        unset($_SESSION['_errors'], $_SESSION['_old_input']);
        return [];
    }

    protected function verifyCsrf(): void
    {
        Csrf::verify();
    }

    protected function view(string $view, array $data = [], ?string $layout = 'layout/app'): void
    {
        view($view, $data, $layout);
    }

    protected function back(): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? url('/');
        header('Location: ' . $ref);
        exit;
    }
}
