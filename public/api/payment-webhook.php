<?php
require_once __DIR__ . '/../../src/bootstrap.php';

if (!isPost()) jsonResponse(['ok' => false, 'msg' => 'Método inválido.'], 405);

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);
if (!is_array($payload)) $payload = $_POST ?: [];

$secret = integrationSetting('payment_webhook_secret', '');
if ($secret !== '') {
    $givenSecret = $_GET['secret'] ?? $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
    $givenSignature = $_SERVER['HTTP_X_CAMINHOS_SIGNATURE'] ?? $_SERVER['HTTP_X_SIGNATURE'] ?? '';
    $expectedSignature = hash_hmac('sha256', $raw, $secret);
    $validSecret = is_string($givenSecret) && hash_equals($secret, $givenSecret);
    $validSignature = is_string($givenSignature) && hash_equals($expectedSignature, $givenSignature);
    if (!$validSecret && !$validSignature) jsonResponse(['ok' => false, 'msg' => 'Assinatura inválida.'], 401);
} elseif (integrationEnabled('production_mode')) {
    jsonResponse(['ok' => false, 'msg' => 'Webhook sem segredo configurado.'], 401);
}

function webhookValue(array $payload, array $keys): string {
    foreach ($keys as $key) {
        $value = $payload;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) { $value = null; break; }
            $value = $value[$part];
        }
        if (is_scalar($value) && trim((string)$value) !== '') return trim((string)$value);
    }
    return '';
}

$provider = strtolower(preg_replace('/[^a-z0-9_\-]/i', '', webhookValue($payload, ['provider', 'gateway', 'type'])) ?: integrationSetting('payment_provider', 'manual'));
$reference = strtoupper(webhookValue($payload, ['booking_code', 'code', 'external_reference', 'reference', 'data.external_reference', 'metadata.booking_code']));
$transactionId = webhookValue($payload, ['transaction_id', 'payment_id', 'id', 'data.id', 'payment.id']);
$rawStatus = strtolower(webhookValue($payload, ['status', 'payment_status', 'data.status', 'payment.status']));
$statusMap = [
    'approved' => 'paid', 'paid' => 'paid', 'succeeded' => 'paid', 'completed' => 'paid', 'confirmed' => 'paid',
    'refunded' => 'refunded', 'chargeback' => 'refunded',
    'cancelled' => 'cancelled', 'canceled' => 'cancelled',
    'failed' => 'failed', 'rejected' => 'failed', 'declined' => 'failed',
    'pending' => 'pending', 'in_process' => 'pending', 'processing' => 'pending',
];
$nextStatus = $statusMap[$rawStatus] ?? '';
if ($nextStatus === '') jsonResponse(['ok' => false, 'msg' => 'Status do pagamento não reconhecido.'], 422);

$booking = null;
if ($reference !== '') $booking = dbOne('SELECT * FROM bookings WHERE code = ? LIMIT 1', [$reference]);
if (!$booking && $transactionId !== '') $booking = dbOne('SELECT * FROM bookings WHERE gateway_tx_id = ? LIMIT 1', [$transactionId]);
if (!$booking) jsonResponse(['ok' => false, 'msg' => 'Reserva não encontrada.'], 404);

$previousStatus = $booking['payment_status'];
$paidSql = $nextStatus === 'paid' ? ', paid_at = COALESCE(paid_at, NOW())' : '';
$cancelledSql = $nextStatus === 'cancelled' ? ', cancelled_at = COALESCE(cancelled_at, NOW())' : '';
dbExec(
    "UPDATE bookings SET payment_status = ?, payment_gateway = ?, gateway_tx_id = COALESCE(NULLIF(gateway_tx_id, ''), ?) $paidSql $cancelledSql WHERE id = ?",
    [$nextStatus, $provider, $transactionId ?: null, $booking['id']]
);

handleBookingPaymentStatusChanged((int)$booking['id'], $previousStatus, $nextStatus, 'payment_webhook');
logActivity(null, 'payment_webhook', 'booking', (int)$booking['id'], 'Webhook ' . $provider . ' atualizou ' . $booking['code'] . ' para ' . $nextStatus);

jsonResponse(['ok' => true, 'msg' => 'Webhook processado.', 'booking' => $booking['code'], 'status' => $nextStatus]);
