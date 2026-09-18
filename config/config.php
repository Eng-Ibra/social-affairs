<?php
/**
 * Loads .env into a simple config array. No external dependency required.
 */

function env_load(string $path): array
{
    $vars = [];
    if (!is_file($path)) {
        return $vars;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
        }
        $vars[$key] = $value;
    }
    return $vars;
}

$root = dirname(__DIR__);
$env = array_merge(env_load($root . '/.env.example'), env_load($root . '/.env'));

foreach ($env as $k => $v) {
    if (getenv($k) === false) {
        putenv("$k=$v");
    }
}

function env(string $key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    $map = ['true' => true, 'false' => false, 'null' => null, '(empty)' => ''];
    return array_key_exists(strtolower($value), $map) ? $map[strtolower($value)] : $value;
}

return [
    'app' => [
        'name' => env('APP_NAME', 'Social Affairs Management System'),
        'env' => env('APP_ENV', 'production'),
        'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
        'url' => rtrim((string) env('APP_URL', ''), '/'),
        'key' => env('APP_KEY', ''),
        'root' => $root,
    ],
    'db' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'social_affairs'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],
    'session' => [
        'name' => env('SESSION_NAME', 'sas_session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 120),
    ],
    'mail' => [
        'host' => env('MAIL_HOST', ''),
        'port' => (int) env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@localhost'),
        'from_name' => env('MAIL_FROM_NAME', 'Social Affairs Management System'),
    ],
    'upload' => [
        'max_mb' => (int) env('UPLOAD_MAX_MB', 10),
    ],
];
