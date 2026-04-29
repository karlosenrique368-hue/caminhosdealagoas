<?php
require_once __DIR__ . '/../../src/bootstrap.php';

if (!isPost()) jsonResponse(['ok' => false, 'msg' => 'Método inválido.'], 405);
if (!csrfVerify()) jsonResponse(['ok' => false, 'msg' => 'Token inválido.'], 403);
if (sessionRateLimited('newsletter', 10, 600)) jsonResponse(['ok' => false, 'msg' => 'Muitas tentativas. Tente novamente em alguns minutos.'], 429);

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!$email) jsonResponse(['ok' => false, 'msg' => 'Email inválido.']);

// Insere (ignora duplicados)
try {
    dbInsert("INSERT IGNORE INTO newsletter (email, ip) VALUES (?, ?)", [$email, $_SERVER['REMOTE_ADDR'] ?? null]);
} catch (Throwable $e) {
    // schema pode não ter ip — tentar sem
    dbInsert("INSERT IGNORE INTO newsletter (email) VALUES (?)", [$email]);
}

jsonResponse(['ok' => true, 'msg' => 'Inscrição confirmada! Obrigado.']);
