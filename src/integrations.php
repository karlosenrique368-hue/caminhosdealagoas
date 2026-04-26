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

function logActivity(?int $adminId, string $action, ?string $entity = null, ?int $entityId = null, ?string $description = null): void {
    try {
        dbExec(
            'INSERT INTO activity_log (admin_id, action, entity, entity_id, description, ip) VALUES (?, ?, ?, ?, ?, ?)',
            [$adminId, mb_substr($action, 0, 80), $entity, $entityId, $description ? mb_substr($description, 0, 500) : null, $_SERVER['REMOTE_ADDR'] ?? null]
        );
    } catch (Throwable $error) {
    }
}

function applyProductionRuntimeSettings(): void {
    if (integrationEnabled('production_mode')) {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
    }
    if (!integrationEnabled('security_headers_enabled') || headers_sent()) return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(self)');
    if (integrationEnabled('hsts_enabled') && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
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

function integrationPostJson(string $url, array $payload, array $headers = [], int $timeout = 8): array {
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $headerLines = array_merge(['Content-Type: application/json'], $headers);

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_TIMEOUT => $timeout,
        ]);
        $response = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'body' => (string)$response, 'error' => $error];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headerLines),
            'content' => $body,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    return ['ok' => $response !== false, 'status' => 0, 'body' => (string)$response, 'error' => $response === false ? 'Falha HTTP' : ''];
}

function bookingWithCustomer(int $bookingId): ?array {
    return dbOne('SELECT b.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone FROM bookings b JOIN customers c ON c.id = b.customer_id WHERE b.id = ? LIMIT 1', [$bookingId]);
}

function prepareBookingPayment(int $bookingId): array {
    $booking = bookingWithCustomer($bookingId);
    if (!$booking) return ['ok' => false, 'mode' => 'missing_booking'];

    $provider = strtolower(preg_replace('/[^a-z0-9_\-]/i', '', integrationSetting('payment_provider', 'manual')) ?: 'manual');
    if (!integrationEnabled('payment_enabled')) {
        logActivity(null, 'payment_skipped', 'booking', $bookingId, 'Pagamento externo desativado para reserva ' . $booking['code']);
        return ['ok' => true, 'mode' => 'disabled', 'provider' => $provider];
    }

    $transactionId = $booking['gateway_tx_id'] ?: strtoupper(substr($provider, 0, 4)) . '-' . $booking['code'] . '-' . strtoupper(substr(hash('sha256', $booking['code'] . microtime(true)), 0, 8));
    dbExec('UPDATE bookings SET payment_gateway = ?, gateway_tx_id = COALESCE(gateway_tx_id, ?) WHERE id = ?', [$provider, $transactionId, $bookingId]);

    $secret = integrationSetting('payment_secret_key', '');
    $mode = in_array($provider, ['manual', 'sandbox'], true) ? 'sandbox_ready' : ($secret !== '' ? 'credentials_ready' : 'missing_credentials');
    logActivity(null, 'payment_prepared', 'booking', $bookingId, 'Gateway ' . $provider . ' preparado para reserva ' . $booking['code']);

    return ['ok' => true, 'mode' => $mode, 'provider' => $provider, 'transaction_id' => $transactionId, 'webhook_url' => paymentWebhookUrl()];
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
