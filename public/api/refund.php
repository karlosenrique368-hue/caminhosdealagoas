<?php
/**
 * Customer refund request API.
 */
require_once __DIR__ . '/../../src/bootstrap.php';

if (!isCustomerLoggedIn()) {
    jsonResponse(['ok' => false, 'msg' => 'Não autenticado.'], 401);
}
if (!isPost()) {
    jsonResponse(['ok' => false, 'msg' => 'Método inválido.'], 405);
}
if (!csrfVerify()) {
    jsonResponse(['ok' => false, 'msg' => 'Token CSRF inválido.'], 403);
}

$cid = currentCustomerId();
$bid = (int)($_POST['booking_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if ($bid <= 0 || $reason === '') {
    jsonResponse(['ok' => false, 'msg' => 'Reserva e motivo são obrigatórios.'], 422);
}

$b = dbOne('SELECT * FROM bookings WHERE id=? AND customer_user_id=?', [$bid, $cid]);
if (!$b) {
    jsonResponse(['ok' => false, 'msg' => 'Reserva não encontrada.'], 404);
}
if ($b['payment_status'] !== 'paid') {
    jsonResponse(['ok' => false, 'msg' => 'Apenas reservas pagas podem ser reembolsadas.'], 422);
}

// Check no duplicate pending request
$exists = dbOne('SELECT id FROM refund_requests WHERE booking_id=? AND customer_id=?', [$bid, $cid]);
if ($exists) {
    jsonResponse(['ok' => false, 'msg' => 'Já existe uma solicitação para esta reserva.'], 409);
}

try {
    dbExec(
        'INSERT INTO refund_requests (booking_id, customer_id, reason, amount, status) VALUES (?, ?, ?, ?, ?)',
        [$bid, $cid, $reason, (float)$b['total'], 'pending']
    );
    jsonResponse(['ok' => true, 'msg' => 'Solicitação enviada! Nossa equipe analisará em breve.']);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'msg' => 'Erro ao processar: ' . $e->getMessage()], 500);
}
