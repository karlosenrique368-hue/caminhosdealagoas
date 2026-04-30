<?php
require_once __DIR__ . '/../../src/bootstrap.php';

if (IS_PRODUCTION) jsonResponse(['ok' => false, 'msg' => 'Teste de webhook desativado em produção.'], 403);
if (!isPost()) jsonResponse(['ok' => false, 'msg' => 'Método inválido.'], 405);
if (!csrfVerify()) jsonResponse(['ok' => false, 'msg' => 'Token inválido.'], 403);

$result = sendMercadoPagoTestWebhook($_POST['booking_code'] ?? null);
jsonResponse($result, !empty($result['ok']) ? 200 : 404);
