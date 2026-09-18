<?php

require_once __DIR__ . '/Core/helpers.php';

if (is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$cfg = config('app');
error_reporting(E_ALL);
ini_set('display_errors', $cfg['debug'] ? '1' : '0');
date_default_timezone_set('Africa/Mogadishu');

session_name(config('session.name'));
session_set_cookie_params([
    'lifetime' => config('session.lifetime') * 60,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

\App\Core\Auth::refreshSessionUser();
