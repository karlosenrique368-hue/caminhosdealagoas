<?php
/**
 * Caminhos de Alagoas — Configuração
 */

function envValue(string $key, $default = null) {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : $value;
}

function envBool(string $key, bool $default = false): bool {
    $value = envValue($key, $default ? '1' : '0');
    return in_array(strtolower((string)$value), ['1', 'true', 'yes', 'on'], true);
}

function envRaw(string $key, $default = null) {
    if (array_key_exists($key, $_ENV)) return $_ENV[$key];
    if (array_key_exists($key, $_SERVER)) return $_SERVER[$key];
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// Paths
define('ROOT_DIR', dirname(__DIR__));
define('SRC_DIR', ROOT_DIR . '/src');
define('VIEWS_DIR', ROOT_DIR . '/views');
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('STORAGE_DIR', ROOT_DIR . '/storage');
define('UPLOADS_DIR', STORAGE_DIR . '/uploads');

// Ambiente
$defaultEnv = envValue('RAILWAY_ENVIRONMENT') ? 'production' : 'local';
define('APP_ENV', strtolower((string)envValue('APP_ENV', $defaultEnv)));
define('IS_PRODUCTION', in_array(APP_ENV, ['production', 'prod'], true));

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', IS_PRODUCTION ? '0' : (envBool('APP_DEBUG', true) ? '1' : '0'));
ini_set('log_errors', '1');
if (!is_dir(STORAGE_DIR . '/logs')) @mkdir(STORAGE_DIR . '/logs', 0775, true);
ini_set('error_log', STORAGE_DIR . '/logs/php-error.log');

// Timezone
date_default_timezone_set((string)envValue('APP_TIMEZONE', 'America/Sao_Paulo'));

// App path: local XAMPP usa subpasta; produção/domínio próprio usa raiz.
$defaultBasePath = IS_PRODUCTION || envValue('RAILWAY_ENVIRONMENT') ? '' : '/caminhosdealagoas/public';
define('BASE_PATH', rtrim((string)envRaw('APP_BASE_PATH', $defaultBasePath), '/'));

// Session
if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = envBool('SESSION_SECURE', IS_PRODUCTION || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'));
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name((string)envValue('SESSION_NAME', 'CAMINHOS_SESSION'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Database
$databaseUrl = envValue('DATABASE_URL');
$dbFromUrl = $databaseUrl ? parse_url((string)$databaseUrl) : [];
define('DB_HOST', (string)envValue('DB_HOST', envValue('MYSQLHOST', $dbFromUrl['host'] ?? '127.0.0.1')));
define('DB_PORT', (string)envValue('DB_PORT', envValue('MYSQLPORT', isset($dbFromUrl['port']) ? (string)$dbFromUrl['port'] : '3306')));
define('DB_NAME', (string)envValue('DB_NAME', envValue('MYSQLDATABASE', isset($dbFromUrl['path']) ? ltrim($dbFromUrl['path'], '/') : 'caminhosdealagoas')));
define('DB_USER', (string)envValue('DB_USER', envValue('MYSQLUSER', $dbFromUrl['user'] ?? 'root')));
define('DB_PASS', (string)envValue('DB_PASS', envValue('MYSQLPASSWORD', isset($dbFromUrl['pass']) ? urldecode($dbFromUrl['pass']) : '')));
define('DB_CHARSET', 'utf8mb4');

// App
define('APP_NAME', 'Caminhos de Alagoas');
define('APP_URL', rtrim((string)envValue('APP_URL', envValue('RAILWAY_PUBLIC_DOMAIN') ? 'https://' . envValue('RAILWAY_PUBLIC_DOMAIN') : 'http://localhost/caminhosdealagoas/public'), '/'));
define('APP_EMAIL', (string)envValue('APP_EMAIL', 'contato@caminhosdealagoas.com'));
define('APP_PHONE', (string)envValue('APP_PHONE', '82 98822-0546'));

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', (int)envValue('PASSWORD_MIN_LENGTH', 8));

// Uploads
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Locale
setlocale(LC_TIME, 'pt_BR.UTF-8', 'Portuguese_Brazil.UTF-8');
