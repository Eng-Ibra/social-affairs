<?php
/**
 * Global helper functions used across controllers and views.
 */

function config(?string $key = null, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
    }
    if ($key === null) {
        return $config;
    }
    $segments = explode('.', $key);
    $value = $config;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function base_path(string $path = ''): string
{
    return config('app.root') . ($path ? '/' . ltrim($path, '/') : '');
}

/** Base URL path the app is served under, e.g. /social-affairs/public or empty for the built-in server. */
function base_url(string $path = ''): string
{
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
    if ($scriptDir === '/' || $scriptDir === '.') {
        $scriptDir = '';
    }
    return $scriptDir . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function url(string $path = ''): string
{
    return base_url($path);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function old(string $key, $default = '')
{
    $old = $_SESSION['_old_input'][$key] ?? $default;
    return $old;
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][$type][] = $message;
}

function get_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function can(string $moduleKey, string $capability): bool
{
    return \App\Core\Permission::check(current_user(), $moduleKey, $capability);
}

function format_date(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '-';
}

function format_datetime(?string $date): string
{
    return format_date($date, 'd M Y H:i');
}

function view(string $view, array $data = [], ?string $layout = 'layout/app'): void
{
    $viewFile = base_path('app/Views/' . $view . '.php');
    if (!is_file($viewFile)) {
        throw new RuntimeException("View not found: {$view}");
    }
    if ($layout === null) {
        extract($data, EXTR_SKIP);
        require $viewFile;
        return;
    }

    // Render the child view first, in its own scope, capturing both its HTML
    // output and any extra variables it defines (e.g. $extraScripts) so the
    // layout below can see them too — a plain closure capture only exposes
    // variables that existed *before* the view ran, not ones it creates.
    $renderChild = static function () use ($viewFile, $data) {
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $html = ob_get_clean();
        $vars = get_defined_vars();
        unset($vars['viewFile'], $vars['data'], $vars['html']);
        return [$html, $vars];
    };
    [$contentHtml, $extraVars] = $renderChild();

    $data = array_merge($data, $extraVars);
    extract($data, EXTR_SKIP);
    $content = static function () use ($contentHtml) {
        echo $contentHtml;
    };
    require base_path('app/Views/' . $layout . '.php');
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function str_slug_upper(string $prefix): string
{
    return strtoupper($prefix) . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

function generate_code(string $prefix, \PDO $pdo, string $table, string $column): string
{
    do {
        $code = str_slug_upper($prefix);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$code]);
        $exists = (int) $stmt->fetchColumn() > 0;
    } while ($exists);
    return $code;
}

function request_input(string $key, $default = null)
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function active_nav(string $segment): string
{
    $current = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $scriptDir = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($scriptDir && str_starts_with($current, $scriptDir)) {
        $current = trim(substr($current, strlen($scriptDir)), '/');
    }
    return str_starts_with($current, $segment) ? 'active' : '';
}
