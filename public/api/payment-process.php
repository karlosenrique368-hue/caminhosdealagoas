<?php
require_once __DIR__ . '/../../src/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($action !== 'status' && !csrfVerify()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Token CSRF invalido. Recarregue a pagina.']);
    exit;
}

function pp_json($d, int $code = 200): void {
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

function pp_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) return $_POST;
    $j = json_decode($raw, true);
    return is_array($j) ? $j : $_POST;
}

function pp_get_booking(string $code): ?array {
    $code = strtoupper(preg_replace('/[^A-Z0-9-]/i', '', $code));
    if ($code === '') return null;
    try {
        $b = dbOne('SELECT * FROM bookings WHERE code = ? LIMIT 1', [$code]);
        if (!$b) return null;
        $c = null;
        if (!empty($b['customer_id'])) {
            try { $c = dbOne('SELECT * FROM customers WHERE id = ? LIMIT 1', [(int)$b['customer_id']]); }
            catch (Throwable $e) { error_log('[pp.customer] ' . $e->getMessage()); }
        }
        $b['customer_name'] = $c['name'] ?? '';
        $b['customer_email'] = $c['email'] ?? '';
        $b['customer_phone'] = $c['phone'] ?? '';
        $b['customer_document'] = $c['document'] ?? '';
        return $b;
    } catch (Throwable $e) {
        error_log('[pp.booking] ' . $e->getMessage());
        return null;
    }
}

if ($action === 'status') {
    $b = pp_get_booking($_GET['booking_code'] ?? '');
    if (!$b) pp_json(['ok' => false, 'msg' => 'Reserva nao encontrada'], 404);
    pp_json(['ok' => true, 'payment_status' => $b['payment_status'] ?? 'pending', 'status' => $b['status'] ?? 'pending']);
}

$in = pp_input();
$code = (string)($in['booking_code'] ?? '');
$booking = pp_get_booking($code);
if (!$booking) pp_json(['ok' => false, 'msg' => 'Reserva nao encontrada.'], 404);
if ((string)$booking['payment_status'] === 'paid') pp_json(['ok' => true, 'status' => 'approved', 'msg' => 'Reserva ja paga.']);

$provider = strtolower((string) integrationSetting('payment_provider', 'manual'));
if ($provider !== 'mercadopago' || !integrationEnabled('payment_enabled')) {
    pp_json(['ok' => false, 'msg' => 'Pagamento online indisponivel.'], 400);
}

if ($action === 'card') {
    $r = mercadoPagoChargeCard($booking, $in);
    if (!$r['ok']) pp_json(['ok' => false, 'msg' => $r['msg'] ?? 'Pagamento recusado.'], 200);
    logActivity(null, 'payment_card_attempt', 'booking', (int)$booking['id'], 'MP card status=' . ($r['status'] ?? '?'));
    pp_json(['ok' => true, 'status' => $r['status'], 'status_detail' => $r['status_detail'] ?? '', 'mp_id' => $r['mp_id'] ?? null]);
}

if ($action === 'pix') {
    $r = mercadoPagoCreatePix($booking);
    if (!$r['ok']) pp_json(['ok' => false, 'msg' => $r['msg'] ?? 'Falha ao gerar PIX.'], 200);
    logActivity(null, 'payment_pix_created', 'booking', (int)$booking['id'], 'PIX gerado MP id=' . ($r['mp_id'] ?? '?'));
    pp_json(['ok' => true, 'qr_code' => $r['qr_code'], 'qr_code_base64' => $r['qr_code_base64'], 'ticket_url' => $r['ticket_url'], 'expires_at' => $r['expires_at']]);
}

if ($action === 'boleto') {
    $r = mercadoPagoCreateBoleto($booking);
    if (!$r['ok']) pp_json(['ok' => false, 'msg' => $r['msg'] ?? 'Falha ao gerar boleto.'], 200);
    logActivity(null, 'payment_boleto_created', 'booking', (int)$booking['id'], 'Boleto gerado MP id=' . ($r['mp_id'] ?? '?'));
    pp_json(['ok' => true, 'ticket_url' => $r['ticket_url'], 'barcode' => $r['barcode'], 'expires_at' => $r['expires_at']]);
}

pp_json(['ok' => false, 'msg' => 'Acao invalida.'], 400);