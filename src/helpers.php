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
    $base = BASE_PATH;
    if ($path === '/') {
        return $base === '' ? '/' : $base;
    }
    return $base . $path;
}

function isSecureRequest(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return true;
    if (strtolower($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on') return true;
    return false;
}

function safeRedirectPath(?string $target, string $fallback = '/'): string {
    $target = trim((string)$target);
    if ($target === '' || preg_match('/[\r\n]/', $target) || str_starts_with($target, '//')) return $fallback;
    $parts = parse_url($target);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) return $fallback;
    $path = $parts['path'] ?? '/';
    $base = rtrim(BASE_PATH, '/');
    if ($base !== '' && strpos($path, $base) === 0) $path = substr($path, strlen($base)) ?: '/';
    if ($path === '' || $path[0] !== '/') $path = '/' . $path;
    return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
}

function publicErrorMessage(string $fallback = 'Erro interno. Tente novamente em instantes.'): string {
    return IS_PRODUCTION ? $fallback : $fallback;
}

function asset(string $path): string {
    $rel = ltrim($path, '/');
    $abs = __DIR__ . '/../public/assets/' . $rel;
    $v = is_file($abs) ? '?v=' . filemtime($abs) : '';
    return url('assets/' . $rel) . $v;
}

/**
 * Constrói URL preservando a query string atual e substituindo um parâmetro.
 * Útil para seletores de idioma/moeda que devem manter contexto da página.
 */
function urlWithParam(string $key, string $value, ?string $path = null): string {
    $reqUri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts  = parse_url($reqUri);
    $qs     = [];
    if (!empty($parts['query'])) parse_str($parts['query'], $qs);
    $qs[$key] = $value;
    $p = $path !== null ? $path : ($parts['path'] ?? '/');
    return $p . '?' . http_build_query($qs);
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

function jsonException(Throwable $e, string $message = 'Erro interno. Tente novamente em instantes.', int $status = 500): void {
    error_log($e->getMessage());
    jsonResponse(['ok' => false, 'msg' => IS_PRODUCTION ? $message : $message . ' (' . $e->getMessage() . ')'], $status);
}

function loginThrottleKey(string $scope, string $identifier): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return $scope . ':' . hash('sha256', strtolower(trim($identifier)) . '|' . $ip);
}

function loginThrottleBlocked(string $key, int $maxAttempts = 8, int $windowSeconds = 900): bool {
    $now = time();
    $bucket = $_SESSION['_login_throttle'][$key] ?? ['count' => 0, 'first' => $now];
    if (($now - (int)$bucket['first']) > $windowSeconds) {
        $_SESSION['_login_throttle'][$key] = ['count' => 0, 'first' => $now];
        return false;
    }
    return (int)$bucket['count'] >= $maxAttempts;
}

function loginThrottleFail(string $key): void {
    $now = time();
    $bucket = $_SESSION['_login_throttle'][$key] ?? ['count' => 0, 'first' => $now];
    if (($now - (int)$bucket['first']) > 900) $bucket = ['count' => 0, 'first' => $now];
    $bucket['count'] = (int)$bucket['count'] + 1;
    $_SESSION['_login_throttle'][$key] = $bucket;
}

function loginThrottleClear(string $key): void {
    unset($_SESSION['_login_throttle'][$key]);
}

function sessionRateLimited(string $scope, int $maxAttempts, int $windowSeconds): bool {
    $now = time();
    $key = $scope . ':' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $bucket = $_SESSION['_rate_limit'][$key] ?? ['count' => 0, 'first' => $now];
    if (($now - (int)$bucket['first']) > $windowSeconds) $bucket = ['count' => 0, 'first' => $now];
    $bucket['count'] = (int)$bucket['count'] + 1;
    $_SESSION['_rate_limit'][$key] = $bucket;
    return $bucket['count'] > $maxAttempts;
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

/** Formata datas em PT-BR sem depender de locale do servidor. */
function dateBR(?string $date, string $style = 'long'): string {
    if (!$date) return '';
    $ts = strtotime($date);
    if (!$ts) return '';
    static $months = [1=>'janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
    static $monthsShort = [1=>'jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];
    $d = (int)date('j', $ts);
    $m = (int)date('n', $ts);
    $y = date('Y', $ts);
    switch ($style) {
        case 'short':       return sprintf('%02d/%02d/%s', $d, $m, $y);                       // 24/04/2026
        case 'shortMonth':  return sprintf('%02d %s · %s', $d, $monthsShort[$m], $y);          // 24 abr · 2026
        case 'monthYear':   return ucfirst($monthsShort[$m]) . ' · ' . $y;                     // Abr · 2026
        case 'dayMonth':    return sprintf('%02d de %s', $d, $months[$m]);                     // 24 de abril
        case 'dayMonthY':   return sprintf('%02d de %s de %s', $d, $months[$m], $y);           // 24 de abril de 2026
        case 'long':
        default:            return sprintf('%02d de %s de %s', $d, $months[$m], $y);
    }
}

/** Dia da semana em PT-BR. */
function weekdayBR(?string $date, bool $short = false): string {
    if (!$date) return '';
    $ts = strtotime($date);
    if (!$ts) return '';
    $w = (int)date('w', $ts);
    $full  = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
    $shortW = ['dom','seg','ter','qua','qui','sex','sáb'];
    return $short ? $shortW[$w] : $full[$w];
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
    $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        error_log('[upload] erro codigo=' . $uploadError . ' nome=' . (string)($file['name'] ?? ''));
        return null;
    }
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        error_log('[upload] tmp_name invalido nome=' . (string)($file['name'] ?? ''));
        return null;
    }
    if (($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        error_log('[upload] excede limite nome=' . (string)($file['name'] ?? '') . ' size=' . (string)($file['size'] ?? 0));
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_IMAGE_TYPES, true)) {
        error_log('[upload] mime nao permitido nome=' . (string)($file['name'] ?? '') . ' mime=' . (string)$mime);
        return null;
    }

    $extMap = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];
    if (!isset($extMap[$mime])) return null;
    $ext = $extMap[$mime];
    $dir = UPLOADS_DIR . '/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        error_log('[upload] falha ao criar dir=' . $dir);
        return null;
    }
    if (!is_writable($dir)) {
        @chmod($dir, 0777);
        if (!is_writable($dir)) {
            error_log('[upload] dir sem permissao de escrita=' . $dir);
            return null;
        }
    }

    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        error_log('[upload] move_uploaded_file falhou para=' . $dest);
        return null;
    }
    return 'uploads/' . $subdir . '/' . $name;
}

function storageUrl(string $path): string {
    $clean = trim($path);
    if ($clean === '') return url('storage/uploads/');
    if (preg_match('#^https?://#i', $clean)) return $clean;
    $clean = ltrim($clean, '/');
    if (!str_starts_with($clean, 'uploads/')) $clean = 'uploads/' . $clean;
    return url('storage/' . $clean);
}

// ============== Upload multiplo (galeria) ==============
function handleMultipleImageUpload(array $files, string $subdir = 'general'): array {
    $out = [];
    if (empty($files['tmp_name']) || !is_array($files['tmp_name'])) return $out;
    $count = count($files['tmp_name']);
    for ($i = 0; $i < $count; $i++) {
        if (!($files['tmp_name'][$i] ?? null)) continue;
        $single = [
            'name'     => $files['name'][$i]     ?? '',
            'type'     => $files['type'][$i]     ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error'    => $files['error'][$i]    ?? UPLOAD_ERR_NO_FILE,
            'size'     => $files['size'][$i]     ?? 0,
        ];
        if ($single['error'] !== UPLOAD_ERR_OK) continue;
        $p = handleImageUpload($single, $subdir);
        if ($p) $out[] = $p;
    }
    return $out;
}

// ============== Paginacao (admin lists) ==============
function paginate(string $countSql, string $dataSql, array $params = [], array $options = []): array {
    $allowed   = $options['allowed']  ?? [5, 10, 20, 50];
    $default   = $options['default']  ?? 10;
    $perParam  = $options['per_param']?? 'per';
    $pageParam = $options['page_param']?? 'page';

    $per = (int) ($_GET[$perParam] ?? $default);
    if (!in_array($per, $allowed, true)) $per = $default;
    $page = max(1, (int) ($_GET[$pageParam] ?? 1));

    $total = (int) (dbOne($countSql, $params)['c'] ?? 0);
    $pages = max(1, (int) ceil($total / $per));
    if ($page > $pages) $page = $pages;
    $offset = ($page - 1) * $per;

    // LIMIT/OFFSET sao validados como inteiros mas usar concatenacao apenas apos cast explicito
    $rows = dbAll($dataSql . " LIMIT " . (int)$per . " OFFSET " . (int)$offset, $params);

    $qs = $_GET;
    unset($qs[$pageParam], $qs[$perParam]);
    $baseQs = http_build_query($qs);

    return [
        'rows'       => $rows,
        'total'      => $total,
        'per'        => $per,
        'page'       => $page,
        'pages'      => $pages,
        'offset'     => $offset,
        'allowed'    => $allowed,
        'per_param'  => $perParam,
        'page_param' => $pageParam,
        'base_qs'    => $baseQs,
    ];
}


// ============== Documentos BR (CPF, CNPJ) ==============
function formatCpf(?string $cpf): string {
    $d = preg_replace('/\D/', '', (string)$cpf);
    if (strlen($d) !== 11) return (string)$cpf;
    return substr($d,0,3).'.'.substr($d,3,3).'.'.substr($d,6,3).'-'.substr($d,9,2);
}

function formatCnpj(?string $cnpj): string {
    $d = preg_replace('/\D/', '', (string)$cnpj);
    if (strlen($d) !== 14) return (string)$cnpj;
    return substr($d,0,2).'.'.substr($d,2,3).'.'.substr($d,5,3).'/'.substr($d,8,4).'-'.substr($d,12,2);
}

function formatCpfCnpj(?string $doc): string {
    $d = preg_replace('/\D/', '', (string)$doc);
    if (strlen($d) === 11) return formatCpf($d);
    if (strlen($d) === 14) return formatCnpj($d);
    return (string)$doc;
}

function onlyDigits(?string $v): string {
    return preg_replace('/\D/', '', (string)$v);
}

// ============== Avatar / Initials ==============
function userInitials(?string $name): string {
    $n = trim((string)$name);
    if ($n === '') return '?';
    $parts = preg_split('/\s+/', $n);
    if (count($parts) >= 2) return mb_strtoupper(mb_substr($parts[0],0,1).mb_substr(end($parts),0,1));
    return mb_strtoupper(mb_substr($n,0,1));
}

function avatarUrl(?string $avatarPath): ?string {
    if (!$avatarPath) return null;
    return storageUrl($avatarPath);
}

// ============== Password reset (cliente / parceiro / admin) ==============
function passwordResetCreate(string $scope, int $userId, string $email): string {
    if (!in_array($scope, ['customer','institution','admin'], true)) throw new InvalidArgumentException('scope invalido');
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 60 * 60); // 1h
    dbExec('INSERT INTO password_resets (scope,user_id,email,token_hash,expires_at,ip) VALUES (?,?,?,?,?,?)',
        [$scope, $userId, strtolower(trim($email)), $hash, $expires, $_SERVER['REMOTE_ADDR'] ?? null]);
    return $token;
}

function passwordResetConsume(string $scope, string $token, bool $consume = true): ?array {
    $hash = hash('sha256', $token);
    $row = dbOne('SELECT * FROM password_resets WHERE scope=? AND token_hash=? AND used_at IS NULL AND expires_at > NOW() LIMIT 1', [$scope, $hash]);
    if (!$row) return null;
    if ($consume) dbExec('UPDATE password_resets SET used_at=NOW() WHERE id=?', [(int)$row['id']]);
    return $row;
}

function passwordResetSendEmail(string $scope, string $email, string $token, string $resetUrl): bool {
    if (!function_exists('sendTransactionalEmail')) return false;
    $brand = getSetting('platform_name', 'Caminhos de Alagoas');
    $subject = 'Redefinir senha · ' . $brand;
    $link = $resetUrl . (str_contains($resetUrl,'?') ? '&' : '?') . 'token=' . urlencode($token);
    $html = '<div style="font-family:Inter,Arial,sans-serif;max-width:560px;margin:0 auto;padding:24px;background:#fff;color:#3E2E1F">'
        . '<h2 style="font-family:Georgia,serif;color:#3E2E1F">Redefinir sua senha</h2>'
        . '<p>Recebemos um pedido para redefinir a sua senha. Clique no botao abaixo para criar uma nova. O link expira em 1 hora.</p>'
        . '<p style="text-align:center;margin:28px 0"><a href="' . htmlspecialchars($link, ENT_QUOTES) . '" style="display:inline-block;padding:14px 28px;background:#C96B4A;color:#fff;text-decoration:none;border-radius:12px;font-weight:700">Redefinir senha</a></p>'
        . '<p style="font-size:12px;color:#888">Se voce nao pediu, pode ignorar este e-mail. Sua senha atual permanece valida.</p>'
        . '</div>';
    $text = "Redefinir sua senha\n\nAcesse: " . $link . "\n\nSe voce nao pediu, ignore este e-mail.";
    $r = sendTransactionalEmail($email, $subject, $html, $text, ['kind' => 'password_reset', 'scope' => $scope]);
    return !empty($r['ok']);
}
