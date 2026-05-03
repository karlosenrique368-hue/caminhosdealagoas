<?php

function integrationSetting(string $key, string $default = ''): string {
    $value = getSetting($key, $default);
    return is_string($value) ? $value : (string)$value;
}

function integrationEnabled(string $key, bool $default = false): bool {
    $value = integrationSetting($key, $default ? '1' : '0');
    return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
}

function integrationAppUrl(): string {
    $configured = rtrim(integrationSetting('app_url', defined('APP_URL') ? APP_URL : ''), '/');
    if ($configured !== '') return $configured;
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return rtrim($scheme . '://' . $host . BASE_PATH, '/');
}

function paymentWebhookUrl(): string {
    return integrationAppUrl() . '/api/payment-webhook';
}

function paymentGatewaySecretForProvider(string $provider): string {
    if ($provider === 'mercadopago') {
        $sandbox = integrationEnabled('payment_sandbox', false);
        if ($sandbox) {
            $test = integrationSetting('payment_test_secret_key', '');
            if ($test !== '') return $test;
        }
        return integrationSetting('payment_secret_key', '');
    }
    if ($provider === 'pagseguro') return integrationSetting('payment_pagseguro_token', integrationSetting('payment_secret_key', ''));
    return integrationSetting('payment_secret_key', '');
}

function mercadoPagoActivePublicKey(): string {
    if (integrationEnabled('payment_sandbox', false)) {
        $test = integrationSetting('payment_test_public_key', '');
        if ($test !== '') return $test;
    }
    return integrationSetting('payment_public_key', '');
}

function logActivity(?int $adminId, string $action, ?string $entity = null, ?int $entityId = null, ?string $description = null): void {
    try {
        dbExec(
            'INSERT INTO activity_log (admin_id, action, entity, entity_id, description, ip) VALUES (?, ?, ?, ?, ?, ?)',
            [$adminId, mb_substr($action, 0, 80), $entity, $entityId, $description ? mb_substr($description, 0, 500) : null, $_SERVER['REMOTE_ADDR'] ?? null]
        );
    } catch (Throwable $error) {
    }
}

function ensureMigrations(): void {
    static $ran = false;
    if ($ran) return;
    $ran = true;
    try {
        $current = (int) (getSetting('db_migration_version', '0'));
        if ($current >= 15) return;
        $colExists = function(string $t, string $c): bool {
            try {
                $r = dbOne('SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', [$t, $c]);
                return (int)($r['c'] ?? 0) > 0;
            } catch (Throwable $e) { return false; }
        };
        $tabExists = function(string $t): bool {
            try {
                $r = dbOne('SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$t]);
                return (int)($r['c'] ?? 0) > 0;
            } catch (Throwable $e) { return false; }
        };
        $exec = function(string $sql) { try { db()->exec($sql); } catch (Throwable $e) { error_log('[ensureMigrations] '.$e->getMessage()); } };
        // Migration 014: program/parent_share_note on institutions
        if (!$colExists('institutions', 'program')) $exec("ALTER TABLE institutions ADD COLUMN program ENUM('parceiros','macaiok') NOT NULL DEFAULT 'parceiros' AFTER partner_type");
        if (!$colExists('institutions', 'parent_share_note')) $exec('ALTER TABLE institutions ADD COLUMN parent_share_note TEXT NULL AFTER notes');
        if (!$colExists('institutions', 'allow_group_checkout')) $exec('ALTER TABLE institutions ADD COLUMN allow_group_checkout TINYINT(1) NOT NULL DEFAULT 0');
        // Migration 015: avatars + macaiok_featured + password_resets
        if (!$colExists('customers', 'avatar')) $exec('ALTER TABLE customers ADD COLUMN avatar VARCHAR(255) NULL AFTER phone');
        if (!$colExists('customers', 'address_number')) $exec('ALTER TABLE customers ADD COLUMN address_number VARCHAR(20) NULL');
        if (!$colExists('customers', 'address_complement')) $exec('ALTER TABLE customers ADD COLUMN address_complement VARCHAR(120) NULL');
        if (!$colExists('customers', 'neighborhood')) $exec('ALTER TABLE customers ADD COLUMN neighborhood VARCHAR(120) NULL');
        if (!$colExists('institution_users', 'avatar')) $exec('ALTER TABLE institution_users ADD COLUMN avatar VARCHAR(255) NULL AFTER email');
        if (!$colExists('roteiros', 'macaiok_featured')) $exec('ALTER TABLE roteiros ADD COLUMN macaiok_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER featured');
        if (!$colExists('pacotes', 'macaiok_featured')) $exec('ALTER TABLE pacotes ADD COLUMN macaiok_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER featured');
        if (!$colExists('transfers', 'macaiok_featured')) $exec('ALTER TABLE transfers ADD COLUMN macaiok_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER featured');
        if (!$tabExists('password_resets')) $exec("CREATE TABLE password_resets (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, scope ENUM('customer','institution','admin') NOT NULL, user_id INT UNSIGNED NOT NULL, email VARCHAR(160) NOT NULL, token_hash CHAR(64) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL, ip VARCHAR(45) NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_token (token_hash), INDEX idx_scope_user (scope, user_id), INDEX idx_expires (expires_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        setSetting('db_migration_version', '15');
    } catch (Throwable $e) {
        error_log('[ensureMigrations] fatal: ' . $e->getMessage());
    }
}

function applyProductionRuntimeSettings(): void {
    if (integrationEnabled('production_mode')) {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
    }
    applyCorsPolicy();
    if (!integrationEnabled('security_headers_enabled', true) || headers_sent()) return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('X-Download-Options: noopen');
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
    header('Origin-Agent-Cluster: ?1');
    if (integrationEnabled('hsts_enabled') && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function applyCorsPolicy(): void {
    if (headers_sent()) return;
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (!str_starts_with($path, rtrim(BASE_PATH, '/') . '/api') && !str_starts_with($path, '/api')) return;
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = array_filter(array_map('trim', explode(',', integrationSetting('cors_allowed_origins', ''))));
    $appUrl = integrationAppUrl();
    if ($appUrl !== '') $allowed[] = $appUrl;
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host !== '') {
        $allowed[] = 'https://' . $host;
        $allowed[] = 'http://' . $host;
    }
    if ($origin !== '' && in_array(rtrim($origin, '/'), array_map(fn($o) => rtrim((string)$o, '/'), $allowed), true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Requested-With, X-Caminhos-Signature, X-Signature, X-Request-Id');
    header('Access-Control-Max-Age: 600');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function integrationCleanId(string $value): string {
    return preg_replace('/[^A-Za-z0-9_\-]/', '', trim($value));
}

function renderAnalyticsHead(): string {
    $gaId = integrationCleanId(integrationSetting('analytics_ga4_id', integrationSetting('ga_id', '')));
    $gtmId = integrationCleanId(integrationSetting('analytics_gtm_id', ''));
    $metaId = integrationCleanId(integrationSetting('analytics_meta_pixel_id', integrationSetting('fb_pixel_id', '')));
    $tiktokId = integrationCleanId(integrationSetting('analytics_tiktok_pixel_id', ''));
    $hotjarId = preg_replace('/[^0-9]/', '', integrationSetting('analytics_hotjar_id', ''));
    $utmifyId = integrationCleanId(integrationSetting('analytics_utmify_id', ''));
    $out = [];

    if ($gtmId !== '') {
        $out[] = '<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!==\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);})(window,document,\'script\',\'dataLayer\',' . json_encode($gtmId) . ');</script>';
    }

    if ($gaId !== '') {
        $out[] = '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($gaId) . '"></script>';
        $out[] = '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag(\'js\',new Date());gtag(\'config\',' . json_encode($gaId) . ');</script>';
    }

    if ($metaId !== '') {
        $out[] = '<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version=\'2.0\';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,\'script\',\'https://connect.facebook.net/en_US/fbevents.js\');fbq(\'init\',' . json_encode($metaId) . ');fbq(\'track\',\'PageView\');</script>';
    }

    if ($tiktokId !== '') {
        $out[] = '<script>!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=[\'page\',\'track\',\'identify\',\'instances\',\'debug\',\'on\',\'off\',\'once\',\'ready\',\'alias\',\'group\',\'enableCookie\',\'disableCookie\'],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.load=function(e){var i=\'https://analytics.tiktok.com/i18n/pixel/events.js\',n=d.createElement(\'script\');n.type=\'text/javascript\',n.async=!0,n.src=i+\'?sdkid=\'+e+\'&lib=\'+t;var a=d.getElementsByTagName(\'script\')[0];a.parentNode.insertBefore(n,a)};ttq.load(' . json_encode($tiktokId) . ');ttq.page();}(window,document,\'ttq\');</script>';
    }

    if ($hotjarId !== '') {
        $out[] = '<script>(function(h,o,t,j,a,r){h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};h._hjSettings={hjid:' . $hotjarId . ',hjsv:6};a=o.getElementsByTagName(\'head\')[0];r=o.createElement(\'script\');r.async=1;r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;a.appendChild(r);})(window,document,\'https://static.hotjar.com/c/hotjar-\',\'.js?sv=\');</script>';
    }

    if ($utmifyId !== '') {
        $out[] = '<script>window.pixelId=' . json_encode($utmifyId) . ';(function(){var script=document.createElement(\'script\');script.async=true;script.defer=true;script.src=\'https://cdn.utmify.com.br/scripts/pixel/pixel.js\';document.head.appendChild(script);})();</script>';
    }

    return $out ? "\n" . implode("\n", $out) . "\n" : '';
}

function renderAnalyticsConversion(): string {
    $code = strtoupper(trim($_GET['booking'] ?? ''));
    if ($code === '' || !preg_match('/^[A-Z0-9\-]+$/', $code)) return '';
    $booking = dbOne('SELECT code, total, currency, payment_status FROM bookings WHERE code = ? LIMIT 1', [$code]);
    if (!$booking) return '';

    $isPaid = $booking['payment_status'] === 'paid';
    $payload = [
        'transaction_id' => $booking['code'],
        'value' => (float)$booking['total'],
        'currency' => $booking['currency'] ?: 'BRL',
        'status' => $booking['payment_status'],
    ];

    return '<script>window.addEventListener(\'load\',function(){var data=' . json_encode($payload, JSON_UNESCAPED_SLASHES) . ';if(window.gtag)gtag(\'event\',' . json_encode($isPaid ? 'purchase' : 'generate_lead') . ',data);if(window.fbq)fbq(\'track\',' . json_encode($isPaid ? 'Purchase' : 'Lead') . ',{value:data.value,currency:data.currency},{eventID:data.transaction_id});if(window.ttq)ttq.track(' . json_encode($isPaid ? 'CompletePayment' : 'SubmitForm') . ',{value:data.value,currency:data.currency,content_id:data.transaction_id});});</script>';
}

function paymentWebhookUrlWithSecret(): string {
    $url = paymentWebhookUrl();
    $secret = integrationSetting('payment_webhook_secret', '');
    if ($secret === '') return $url;
    return $url . (str_contains($url, '?') ? '&' : '?') . 'secret=' . rawurlencode($secret);
}

function integrationHttpJson(string $method, string $url, ?array $payload = null, array $headers = [], int $timeout = 8): array {
    $method = strtoupper($method);
    $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
    $headerLines = $payload !== null ? array_merge(['Content-Type: application/json'], $headers) : $headers;

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_TIMEOUT => $timeout,
        ];
        if ($payload !== null) $opts[CURLOPT_POSTFIELDS] = $body;
        curl_setopt_array($curl, $opts);
        $response = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'body' => (string)$response, 'error' => $error];
    }

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headerLines),
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
    ]);
    if ($payload !== null) $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headerLines),
            'content' => $body,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    $status = 0;
    foreach (($http_response_header ?? []) as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) { $status = (int)$m[1]; break; }
    }
    return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'body' => (string)$response, 'error' => $response === false ? 'Falha HTTP' : ''];
}

function integrationPostJson(string $url, array $payload, array $headers = [], int $timeout = 8): array {
    return integrationHttpJson('POST', $url, $payload, $headers, $timeout);
}

function integrationGetJson(string $url, array $headers = [], int $timeout = 8): array {
    return integrationHttpJson('GET', $url, null, $headers, $timeout);
}

function mercadoPagoApiHeaders(): array {
    $token = paymentGatewaySecretForProvider('mercadopago');
    return $token !== '' ? ['Authorization: Bearer ' . $token] : [];
}

function mercadoPagoGatewayMessage(?array $body, string $fallback): string {
    if (!$body) return $fallback;
    $parts = [];
    foreach (['message', 'error', 'status_detail'] as $key) {
        if (!empty($body[$key]) && is_scalar($body[$key])) $parts[] = trim((string)$body[$key]);
    }
    if (!empty($body['cause']) && is_array($body['cause'])) {
        foreach ($body['cause'] as $cause) {
            if (is_array($cause) && !empty($cause['description'])) $parts[] = trim((string)$cause['description']);
        }
    }
    $msg = trim(implode(' | ', array_unique(array_filter($parts))));
    return $msg !== '' ? 'Mercado Pago: ' . mb_substr($msg, 0, 220) : $fallback;
}


function mercadoPagoChargeCard(array $booking, array $cardData): array {
    $headers = mercadoPagoApiHeaders();
    if (!$headers) return ['ok' => false, 'msg' => 'Access Token Mercado Pago nao configurado.'];
    $bookingCode = (string)$booking['code'];
    $amount = round(max(0.01, (float)$booking['total']), 2);
    $token = trim((string)($cardData['token'] ?? ''));
    $paymentMethodId = trim((string)($cardData['payment_method_id'] ?? ''));
    $issuerId = (string)($cardData['issuer_id'] ?? '');
    $installments = max(1, (int)($cardData['installments'] ?? 1));
    $payerEmail = trim((string)($cardData['payer_email'] ?? $booking['customer_email'] ?? ''));
    $payerDocType = strtoupper((string)($cardData['payer_doc_type'] ?? 'CPF'));
    $payerDocNumber = preg_replace('/\D/', '', (string)($cardData['payer_doc_number'] ?? $booking['customer_document'] ?? ''));
    if ($token === '' || $paymentMethodId === '') {
        return ['ok' => false, 'msg' => 'Dados do cartao incompletos.'];
    }
    $payload = [
        'transaction_amount' => $amount,
        'token' => $token,
        'description' => mb_substr((string)($booking['entity_title'] ?: 'Reserva ' . $bookingCode), 0, 250),
        'installments' => $installments,
        'payment_method_id' => $paymentMethodId,
        'external_reference' => $bookingCode,
        'notification_url' => paymentWebhookUrlWithSecret(),
        'statement_descriptor' => 'CAMINHOSALAGOAS',
        'payer' => [
            'email' => $payerEmail !== '' ? $payerEmail : 'sem-email@caminhosdealagoas.com.br',
            'first_name' => (string)($booking['customer_name'] ?? ''),
            'identification' => ['type' => $payerDocType, 'number' => $payerDocNumber],
        ],
        'metadata' => [
            'booking_code' => $bookingCode,
            'booking_id' => (int)$booking['id'],
        ],
    ];
    if ($issuerId !== '') $payload['issuer_id'] = $issuerId;
    $headers[] = 'X-Idempotency-Key: mp-card-' . $bookingCode . '-' . substr(md5($token), 0, 10);
    $resp = integrationPostJson('https://api.mercadopago.com/v1/payments', $payload, $headers, 25);
    $body = json_decode($resp['body'] ?? '', true);
    if (!$resp['ok'] || !is_array($body) || empty($body['id'])) {
        $msg = mercadoPagoGatewayMessage(is_array($body) ? $body : null, 'Pagamento recusado pelo gateway.');
        error_log('[mp.card] booking=' . $bookingCode . ' status=' . (int)($resp['status'] ?? 0) . ' body=' . mb_substr((string)($resp['body'] ?? ''), 0, 500));
        return ['ok' => false, 'msg' => $msg, 'mp_status' => is_array($body) ? ($body['status'] ?? null) : null];
    }
    $status = (string)($body['status'] ?? 'pending');
    $statusDetail = (string)($body['status_detail'] ?? '');
    $newBookingStatus = $status === 'approved' ? 'paid' : ($status === 'rejected' ? 'failed' : 'pending');
    dbExec('UPDATE bookings SET payment_gateway = ?, gateway_tx_id = ?, payment_status = ? WHERE id = ?', ['mercadopago', (string)$body['id'], $newBookingStatus, (int)$booking['id']]);
    return ['ok' => true, 'mp_id' => $body['id'], 'status' => $status, 'status_detail' => $statusDetail, 'booking_status' => $newBookingStatus];
}

function mercadoPagoCreatePix(array $booking): array {
    $headers = mercadoPagoApiHeaders();
    if (!$headers) return ['ok' => false, 'msg' => 'Access Token Mercado Pago nao configurado.'];
    $bookingCode = (string)$booking['code'];
    $amount = round(max(0.01, (float)$booking['total']), 2);
    $payerEmail = trim((string)($booking['customer_email'] ?? '')) ?: 'sem-email@caminhosdealagoas.com.br';
    $payerDoc = preg_replace('/\D/', '', (string)($booking['customer_document'] ?? ''));
    $payload = [
        'transaction_amount' => $amount,
        'description' => mb_substr((string)($booking['entity_title'] ?: 'Reserva ' . $bookingCode), 0, 250),
        'payment_method_id' => 'pix',
        'external_reference' => $bookingCode,
        'notification_url' => paymentWebhookUrlWithSecret(),
        'date_of_expiration' => date('Y-m-d\TH:i:s.000P', time() + 3600 * 24),
        'payer' => [
            'email' => $payerEmail,
            'first_name' => (string)($booking['customer_name'] ?? 'Cliente'),
        ],
        'metadata' => ['booking_code' => $bookingCode, 'booking_id' => (int)$booking['id']],
    ];
    if (strlen($payerDoc) === 11) $payload['payer']['identification'] = ['type' => 'CPF', 'number' => $payerDoc];
    $headers[] = 'X-Idempotency-Key: mp-pix-' . $bookingCode . '-' . bin2hex(random_bytes(4));
    $resp = integrationPostJson('https://api.mercadopago.com/v1/payments', $payload, $headers, 25);
    $body = json_decode($resp['body'] ?? '', true);
    if (!$resp['ok'] || !is_array($body) || empty($body['id'])) {
        error_log('[mp.pix] booking=' . $bookingCode . ' status=' . (int)($resp['status'] ?? 0) . ' body=' . mb_substr((string)($resp['body'] ?? ''), 0, 500));
        return ['ok' => false, 'msg' => mercadoPagoGatewayMessage(is_array($body) ? $body : null, 'Nao foi possivel gerar o PIX agora.')];
    }
    $tx = $body['point_of_interaction']['transaction_data'] ?? [];
    dbExec('UPDATE bookings SET payment_gateway = ?, gateway_tx_id = ?, payment_status = ? WHERE id = ?', ['mercadopago', (string)$body['id'], 'pending', (int)$booking['id']]);
    return [
        'ok' => true,
        'mp_id' => $body['id'],
        'qr_code' => (string)($tx['qr_code'] ?? ''),
        'qr_code_base64' => (string)($tx['qr_code_base64'] ?? ''),
        'ticket_url' => (string)($tx['ticket_url'] ?? ''),
        'expires_at' => date('Y-m-d H:i:s', time() + 3600 * 24),
    ];
}

function mercadoPagoCreatePreference(array $booking): array {
    $headers = mercadoPagoApiHeaders();
    if (!$headers) return ['ok' => false, 'mode' => 'missing_credentials', 'provider' => 'mercadopago', 'msg' => 'Access Token Mercado Pago não configurado.'];

    $baseUrl = integrationAppUrl();
    $bookingCode = (string)$booking['code'];
    $amount = round(max(0.01, (float)$booking['total']), 2);
    $description = mb_substr((string)($booking['entity_title'] ?: 'Reserva ' . $bookingCode), 0, 250);
    $payer = [
        'name' => (string)($booking['customer_name'] ?? ''),
        'email' => (string)($booking['customer_email'] ?? ''),
    ];
    $document = preg_replace('/\D/', '', (string)($booking['customer_document'] ?? ''));
    if (strlen($document) === 11) $payer['identification'] = ['type' => 'CPF', 'number' => $document];

    $payload = [
        'items' => [[
            'id' => $bookingCode,
            'title' => $description,
            'description' => 'Caminhos de Alagoas',
            'quantity' => 1,
            'currency_id' => 'BRL',
            'unit_price' => $amount,
        ]],
        'payer' => $payer,
        'external_reference' => $bookingCode,
        'notification_url' => paymentWebhookUrlWithSecret(),
        'back_urls' => [
            'success' => $baseUrl . '/?booking=' . rawurlencode($bookingCode) . '&payment=success',
            'pending' => $baseUrl . '/?booking=' . rawurlencode($bookingCode) . '&payment=pending',
            'failure' => $baseUrl . '/?booking=' . rawurlencode($bookingCode) . '&payment=failure',
        ],
        'statement_descriptor' => 'CAMINHOSALAGOAS',
        'metadata' => [
            'booking_code' => $bookingCode,
            'booking_id' => (int)$booking['id'],
            'entity_type' => (string)$booking['entity_type'],
        ],
    ];

    if (!preg_match('#^https?://(localhost|127\.0\.0\.1|0\.0\.0\.0|10\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)#i', $baseUrl)) {
        $payload['auto_return'] = 'approved';
    }

    $headers[] = 'X-Idempotency-Key: mp-pref-' . $bookingCode;
    $response = integrationPostJson('https://api.mercadopago.com/checkout/preferences', $payload, $headers, 20);
    $body = json_decode($response['body'] ?? '', true);
    if (!$response['ok'] || !is_array($body) || empty($body['id'])) {
        error_log('[mercadopago] falha ao criar preferencia booking=' . $bookingCode . ' status=' . (int)($response['status'] ?? 0) . ' body=' . mb_substr((string)($response['body'] ?? ''), 0, 500));
        return ['ok' => false, 'mode' => 'preference_failed', 'provider' => 'mercadopago', 'msg' => 'Não foi possível criar o pagamento Mercado Pago agora.'];
    }

    $sandbox = integrationEnabled('payment_sandbox', true);
    $checkoutUrl = $sandbox ? ($body['sandbox_init_point'] ?? '') : ($body['init_point'] ?? '');
    if ($checkoutUrl === '') $checkoutUrl = $body['init_point'] ?? ($body['sandbox_init_point'] ?? '');
    dbExec('UPDATE bookings SET payment_gateway = ?, gateway_tx_id = ? WHERE id = ?', ['mercadopago', $body['id'], $booking['id']]);

    return [
        'ok' => true,
        'mode' => $sandbox ? 'sandbox_checkout' : 'production_checkout',
        'provider' => 'mercadopago',
        'transaction_id' => $body['id'],
        'preference_id' => $body['id'],
        'checkout_url' => $checkoutUrl,
        'webhook_url' => paymentWebhookUrl(),
    ];
}

function mercadoPagoPaymentDetails(string $paymentId): array {
    $paymentId = preg_replace('/[^0-9]/', '', $paymentId);
    if ($paymentId === '') return ['ok' => false, 'data' => null];
    $headers = mercadoPagoApiHeaders();
    if (!$headers) return ['ok' => false, 'data' => null];
    $response = integrationGetJson('https://api.mercadopago.com/v1/payments/' . rawurlencode($paymentId), $headers, 15);
    $body = json_decode($response['body'] ?? '', true);
    return ['ok' => $response['ok'] && is_array($body), 'data' => is_array($body) ? $body : null, 'status' => $response['status'] ?? 0];
}

function mercadoPagoPayloadValue(array $payload, string $path): string {
    $value = $payload;
    foreach (explode('.', $path) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) return '';
        $value = $value[$part];
    }
    return is_scalar($value) ? trim((string)$value) : '';
}

function mercadoPagoWebhookSignatureValid(array $payload, string $secret): bool {
    $signatureHeader = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
    if ($signatureHeader === '' || $requestId === '') return false;
    $parts = [];
    foreach (explode(',', $signatureHeader) as $part) {
        [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($key !== '') $parts[$key] = $value;
    }
    $ts = $parts['ts'] ?? '';
    $signature = $parts['v1'] ?? '';
    $dataId = $_GET['data.id'] ?? $_GET['id'] ?? mercadoPagoPayloadValue($payload, 'data.id');
    if ($dataId === '') $dataId = mercadoPagoPayloadValue($payload, 'id');
    if ($ts === '' || $signature === '' || $dataId === '') return false;
    $manifest = 'id:' . $dataId . ';request-id:' . $requestId . ';ts:' . $ts . ';';
    return hash_equals(hash_hmac('sha256', $manifest, $secret), $signature);
}

function bookingWithCustomer(int $bookingId): ?array {
    return dbOne('SELECT b.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.document AS customer_document FROM bookings b JOIN customers c ON c.id = b.customer_id WHERE b.id = ? LIMIT 1', [$bookingId]);
}

function prepareBookingPayment(int $bookingId): array {
    $booking = bookingWithCustomer($bookingId);
    if (!$booking) return ['ok' => false, 'mode' => 'missing_booking'];

    $provider = strtolower(preg_replace('/[^a-z0-9_\-]/i', '', integrationSetting('payment_provider', 'manual')) ?: 'manual');
    if (!integrationEnabled('payment_enabled')) {
        logActivity(null, 'payment_skipped', 'booking', $bookingId, 'Pagamento externo desativado para reserva ' . $booking['code']);
        return ['ok' => true, 'mode' => 'disabled', 'provider' => $provider];
    }

    if ($provider === 'mercadopago') {
        // Checkout transparente: pagamento acontece na pagina /pagamento/CODE via Bricks
        if (integrationEnabled('payment_use_redirect_checkout', false)) {
            return mercadoPagoCreatePreference($booking);
        }
        dbExec('UPDATE bookings SET payment_gateway = ? WHERE id = ?', ['mercadopago', (int)$booking['id']]);
        return [
            'ok' => true,
            'mode' => 'transparent_checkout',
            'provider' => 'mercadopago',
            'checkout_url' => url('/pagamento/' . rawurlencode($booking['code'])),
            'webhook_url' => paymentWebhookUrl(),
        ];
    }

    $transactionId = $booking['gateway_tx_id'] ?: strtoupper(substr($provider, 0, 4)) . '-' . $booking['code'] . '-' . strtoupper(substr(hash('sha256', $booking['code'] . microtime(true)), 0, 8));
    dbExec('UPDATE bookings SET payment_gateway = ?, gateway_tx_id = COALESCE(gateway_tx_id, ?) WHERE id = ?', [$provider, $transactionId, $bookingId]);

    $secret = paymentGatewaySecretForProvider($provider);
    $mode = in_array($provider, ['manual', 'sandbox'], true) ? 'sandbox_ready' : ($secret !== '' ? 'credentials_ready' : 'missing_credentials');
    logActivity(null, 'payment_prepared', 'booking', $bookingId, 'Gateway ' . $provider . ' preparado para reserva ' . $booking['code']);

    return ['ok' => true, 'mode' => $mode, 'provider' => $provider, 'transaction_id' => $transactionId, 'webhook_url' => paymentWebhookUrl()];
}

function sendMercadoPagoTestWebhook(?string $bookingCode = null): array {
    $bookingCode = preg_replace('/[^A-Z0-9\-]/', '', strtoupper((string)$bookingCode));
    if ($bookingCode !== '') {
        $booking = dbOne('SELECT * FROM bookings WHERE code = ? LIMIT 1', [$bookingCode]);
    } else {
        $booking = dbOne("SELECT * FROM bookings WHERE payment_status='pending' ORDER BY id DESC LIMIT 1");
    }
    if (!$booking) return ['ok' => false, 'msg' => 'Nenhuma reserva pendente encontrada para testar.'];

    $transactionId = $booking['gateway_tx_id'] ?: 'MP-TEST-' . $booking['code'];
    $payload = [
        'provider' => 'mercadopago',
        'type' => 'payment',
        'action' => 'payment.updated',
        'external_reference' => $booking['code'],
        'payment_id' => $transactionId,
        'status' => 'approved',
        'data' => ['id' => $transactionId, 'status' => 'approved'],
        'metadata' => ['booking_code' => $booking['code'], 'test' => true],
    ];

    $secret = integrationSetting('payment_webhook_secret', '');
    $headers = [];
    if ($secret !== '') {
        $raw = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers[] = 'X-Caminhos-Signature: ' . hash_hmac('sha256', $raw, $secret);
    }

    $result = integrationPostJson(paymentWebhookUrl(), $payload, $headers, 10);
    $body = json_decode($result['body'] ?? '', true);
    if (!empty($result['ok']) && (!is_array($body) || !empty($body['ok']))) {
        return ['ok' => true, 'msg' => 'Webhook Mercado Pago enviado e processado.', 'booking' => $booking['code'], 'data' => $body ?: $result];
    }

    $fresh = dbOne('SELECT * FROM bookings WHERE id = ? LIMIT 1', [$booking['id']]);
    if (!$fresh) return ['ok' => false, 'msg' => 'Reserva não encontrada após o teste.'];
    $previousStatus = $fresh['payment_status'];
    dbExec("UPDATE bookings SET payment_status='paid', payment_gateway='mercadopago', gateway_tx_id=COALESCE(NULLIF(gateway_tx_id, ''), ?), paid_at=COALESCE(paid_at, NOW()) WHERE id=?", [$transactionId, $fresh['id']]);
    handleBookingPaymentStatusChanged((int)$fresh['id'], $previousStatus, 'paid', 'payment_webhook_test');
    logActivity(null, 'payment_webhook_test', 'booking', (int)$fresh['id'], 'Webhook Mercado Pago de teste confirmou ' . $fresh['code']);

    return ['ok' => true, 'msg' => 'Webhook Mercado Pago simulado localmente e reserva marcada como paga.', 'booking' => $fresh['code'], 'data' => ['http' => $result]];
}

function sendTransactionalEmail(string $to, string $subject, string $html, string $text = '', array $meta = []): array {
    if (!integrationEnabled('email_enabled')) {
        logActivity(null, 'email_skipped', 'email', null, 'Email desativado: ' . $subject);
        return ['ok' => true, 'skipped' => true, 'msg' => 'Email desativado.'];
    }

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'msg' => 'Destinatário inválido.'];

    $provider = strtolower(integrationSetting('email_provider', 'log'));
    $fromEmail = integrationSetting('email_from', APP_EMAIL);
    $fromName = integrationSetting('email_from_name', APP_NAME);
    $apiKey = integrationSetting('email_api_key', '');
    $result = ['ok' => false, 'msg' => 'Provedor não configurado.'];

    if ($provider === 'resend' && $apiKey !== '') {
        $result = integrationPostJson('https://api.resend.com/emails', [
            'from' => $fromName . ' <' . $fromEmail . '>',
            'to' => [$to],
            'subject' => $subject,
            'html' => $html,
            'text' => $text ?: strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)),
        ], ['Authorization: Bearer ' . $apiKey]);
    } elseif ($provider === 'sendgrid' && $apiKey !== '') {
        $result = integrationPostJson('https://api.sendgrid.com/v3/mail/send', [
            'personalizations' => [['to' => [['email' => $to]]]],
            'from' => ['email' => $fromEmail, 'name' => $fromName],
            'subject' => $subject,
            'content' => [['type' => 'text/html', 'value' => $html]],
        ], ['Authorization: Bearer ' . $apiKey]);
    } elseif ($provider === 'mail') {
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: " . $fromName . ' <' . $fromEmail . '>';
        $sent = @mail($to, $subject, $html, $headers);
        $result = ['ok' => $sent, 'msg' => $sent ? 'Email enviado via mail().' : 'Falha no mail().'];
    } elseif ($provider === 'smtp') {
        $result = ['ok' => false, 'msg' => 'SMTP salvo para ativação com biblioteca de envio. Use Resend, SendGrid ou mail() para envio imediato.'];
    } else {
        $result = ['ok' => true, 'skipped' => true, 'msg' => 'Email registrado em modo log.'];
    }

    logActivity(null, !empty($result['ok']) ? 'email_sent' : 'email_failed', 'email', null, $subject . ' -> ' . $to . ' (' . ($result['msg'] ?? $result['status'] ?? '') . ')');
    return $result;
}

function bookingEmailContent(array $booking, string $event): array {
    $labels = [
        'booking_created' => ['Reserva recebida', 'Recebemos sua reserva e ela está aguardando confirmação de pagamento.'],
        'payment_paid' => ['Pagamento confirmado', 'Seu pagamento foi confirmado. A reserva está garantida.'],
        'payment_refunded' => ['Reembolso registrado', 'Registramos o reembolso da sua reserva.'],
        'payment_failed' => ['Pagamento não aprovado', 'O pagamento não foi aprovado. Fale com a equipe para tentar novamente.'],
        'booking_cancelled' => ['Reserva cancelada', 'Sua reserva foi marcada como cancelada.'],
    ];
    $label = $labels[$event] ?? $labels['booking_created'];
    $subject = $label[0] . ' · ' . $booking['code'];
    $html = '<div style="font-family:Inter,Arial,sans-serif;color:#3E2E1F;line-height:1.55">'
        . '<h2 style="margin:0 0 12px">' . e($label[0]) . '</h2>'
        . '<p>' . e($label[1]) . '</p>'
        . '<div style="padding:16px;border:1px solid #eadfca;border-radius:12px;background:#fffaf0">'
        . '<strong>Código:</strong> ' . e($booking['code']) . '<br>'
        . '<strong>Experiência:</strong> ' . e($booking['entity_title']) . '<br>'
        . '<strong>Data:</strong> ' . e(formatDate($booking['travel_date'])) . '<br>'
        . '<strong>Total:</strong> ' . e(formatBRL((float)$booking['total']))
        . '</div><p>Equipe Caminhos de Alagoas</p></div>';
    return [$subject, $html];
}

function sendBookingEmail(int $bookingId, string $event): void {
    $booking = bookingWithCustomer($bookingId);
    if (!$booking || empty($booking['customer_email'])) return;
    [$subject, $html] = bookingEmailContent($booking, $event);
    sendTransactionalEmail($booking['customer_email'], $subject, $html, strip_tags($html), ['booking_id' => $bookingId, 'event' => $event]);
}

function notifyOperations(string $event, array $payload = []): array {
    $bookingId = isset($payload['booking_id']) ? (int)$payload['booking_id'] : null;
    $description = $payload['message'] ?? ('Evento operacional: ' . $event);
    logActivity(null, 'ops_' . mb_substr($event, 0, 60), $bookingId ? 'booking' : 'system', $bookingId, $description);

    $results = [];
    $message = '[' . APP_NAME . '] ' . $description;
    if (integrationEnabled('ops_webhook_enabled') && integrationSetting('ops_webhook_url', '') !== '') {
        $webhookUrl = integrationSetting('ops_webhook_url');
        $webhookPayload = ['event' => $event, 'message' => $message, 'payload' => $payload, 'sent_at' => date('c')];
        $outboundPayload = $webhookPayload;
        if (str_contains($webhookUrl, 'hooks.slack.com')) {
            $outboundPayload = ['text' => $message];
        } elseif (str_contains($webhookUrl, 'discord.com/api/webhooks') || str_contains($webhookUrl, 'discordapp.com/api/webhooks')) {
            $outboundPayload = ['content' => $message];
        }
        $secret = integrationSetting('ops_webhook_secret', '');
        $headers = $secret !== '' ? ['X-Caminhos-Signature: ' . hash_hmac('sha256', json_encode($outboundPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $secret)] : [];
        $results['webhook'] = integrationPostJson($webhookUrl, $outboundPayload, $headers);
    }

    if (integrationEnabled('whatsapp_api_enabled')) {
        $results['whatsapp'] = sendWhatsAppOperationNotification($message);
    }

    return $results;
}

function sendWhatsAppOperationNotification(string $message): array {
    $token = integrationSetting('whatsapp_token', '');
    $phoneId = integrationSetting('whatsapp_phone_id', '');
    $to = preg_replace('/\D/', '', integrationSetting('whatsapp_admin_phone', ''));
    if ($token === '' || $phoneId === '' || $to === '') return ['ok' => false, 'msg' => 'WhatsApp Cloud API incompleto.'];

    return integrationPostJson('https://graph.facebook.com/v19.0/' . rawurlencode($phoneId) . '/messages', [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'text',
        'text' => ['preview_url' => false, 'body' => $message],
    ], ['Authorization: Bearer ' . $token]);
}

function notifyBookingEvent(int $bookingId, string $event, array $extra = []): void {
    $booking = bookingWithCustomer($bookingId);
    if (!$booking) return;
    $messages = [
        'booking_created' => 'Nova reserva ' . $booking['code'] . ' de ' . $booking['customer_name'] . ' · ' . formatBRL((float)$booking['total']),
        'payment_paid' => 'Pagamento confirmado na reserva ' . $booking['code'] . ' · ' . formatBRL((float)$booking['total']),
        'payment_refunded' => 'Reserva reembolsada ' . $booking['code'],
        'payment_failed' => 'Pagamento falhou na reserva ' . $booking['code'],
        'booking_cancelled' => 'Reserva cancelada ' . $booking['code'],
    ];
    notifyOperations($event, array_merge($extra, [
        'booking_id' => (int)$booking['id'],
        'booking_code' => $booking['code'],
        'customer' => $booking['customer_name'],
        'total' => (float)$booking['total'],
        'message' => $messages[$event] ?? ('Reserva ' . $booking['code'] . ': ' . $event),
    ]));
}

function handleBookingPaymentStatusChanged(int $bookingId, string $previousStatus, string $nextStatus, string $source = 'system'): void {
    if ($previousStatus === $nextStatus) return;
    if ($nextStatus === 'paid' && $previousStatus !== 'paid') {
        creditCommissionOnPaid($bookingId);
        sendBookingEmail($bookingId, 'payment_paid');
        notifyBookingEvent($bookingId, 'payment_paid', ['source' => $source, 'previous_status' => $previousStatus]);
    } elseif (in_array($nextStatus, ['refunded', 'cancelled', 'failed'], true)) {
        if ($previousStatus === 'paid') revokeCommissionOnUnpaid($bookingId);
        $event = $nextStatus === 'refunded' ? 'payment_refunded' : ($nextStatus === 'failed' ? 'payment_failed' : 'booking_cancelled');
        sendBookingEmail($bookingId, $event);
        notifyBookingEvent($bookingId, $event, ['source' => $source, 'previous_status' => $previousStatus]);
    }
}
