<?php
/**
 * Caminhos de Alagoas — Configuração
 */

// Error reporting (desenvolvimento)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_name('CAMINHOS_SESSION');
    session_start();
}

// Paths
define('BASE_PATH', '/caminhosdealagoas/public');
define('ROOT_DIR', dirname(__DIR__));
define('SRC_DIR', ROOT_DIR . '/src');
define('VIEWS_DIR', ROOT_DIR . '/views');
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('STORAGE_DIR', ROOT_DIR . '/storage');
define('UPLOADS_DIR', STORAGE_DIR . '/uploads');

// Database
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'caminhosdealagoas');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App
define('APP_NAME', 'Caminhos de Alagoas');
define('APP_URL', 'http://localhost/caminhosdealagoas/public');
define('APP_EMAIL', 'contato@caminhosdealagoas.com');
define('APP_PHONE', '82 98822-0546');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

// Uploads
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Locale
setlocale(LC_TIME, 'pt_BR.UTF-8', 'Portuguese_Brazil.UTF-8');
