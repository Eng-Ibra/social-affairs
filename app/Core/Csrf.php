<?php

namespace App\Core;

class Csrf
{
    public static function verify(): bool
    {
        $token = $_POST['_token'] ?? '';
        $valid = !empty($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
        if (!$valid) {
            http_response_code(419);
            flash('error', 'Your session has expired. Please try again.');
            $ref = $_SERVER['HTTP_REFERER'] ?? url('/');
            header('Location: ' . $ref);
            exit;
        }
        return true;
    }
}
