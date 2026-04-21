<?php
/**
 * Helpers: escape, URLs, format, CSRF, flash, JSON
 */

// ============== HTML / URLs ==============
function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string {
    $path = '/' . ltrim($path, '/');
    return BASE_PATH . ($path === '/' ? '' : $path);
}

function asset(string $path): string {
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $to): void {
    if (!preg_match('#^https?://#i', $to)) {
        $to = url($to);
    }
    header('Location: ' . $to);
    exit;
}

function currentPath(): string {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    $base = rtrim(BASE_PATH, '/');
    if ($base && strpos($uri, $base) === 0) $uri = substr($uri, strlen($base));
    return '/' . ltrim($uri, '/');
}

function isPost(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function isAjax(): bool {
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

// ============== CSRF ==============
function csrfToken(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function csrfField(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(csrfToken()) . '">';
}

function csrfVerify(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($token) && hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

// ============== JSON ==============
function jsonResponse($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ============== Flash ==============
function flash(string $key, $value = null) {
    if ($value === null) {
        $v = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $v;
    }
    $_SESSION['_flash'][$key] = $value;
}

// ============== Format ==============
function formatBRL(float $value): string {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function parseBRL(string $value): float {
    $clean = preg_replace('/[^\d,.-]/', '', $value);
    $clean = str_replace(['.', ','], ['', '.'], $clean);
    return (float) $clean;
}

function formatDate(?string $date, string $fmt = 'd/m/Y'): string {
    if (!$date) return '';
    $ts = strtotime($date);
    return $ts ? date($fmt, $ts) : '';
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    return $text ?: 'item-' . uniqid();
}

function truncate(string $text, int $limit = 150): string {
    if (mb_strlen($text) <= $limit) return $text;
    return mb_substr($text, 0, $limit) . '…';
}

// ============== Settings ==============
function getSetting(string $key, $default = null) {
    $row = dbOne('SELECT value FROM settings WHERE `key` = ?', [$key]);
    return $row ? $row['value'] : $default;
}

function setSetting(string $key, $value): void {
    dbExec(
        'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
        [$key, (string) $value]
    );
}

// ============== Upload ==============
function handleImageUpload(array $file, string $subdir = 'general'): ?string {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
    if ($file['size'] > MAX_UPLOAD_SIZE) return null;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) return null;

    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
    $dir = UPLOADS_DIR . '/' . $subdir;
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    return 'uploads/' . $subdir . '/' . $name;
}

function storageUrl(string $path): string {
    return url('storage/' . ltrim($path, '/'));
}
