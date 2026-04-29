<?php
require_once __DIR__ . '/../../src/bootstrap.php';

if (!isPost()) jsonResponse(['ok' => false, 'msg' => 'Método inválido.'], 405);
if (!csrfVerify()) jsonResponse(['ok' => false, 'msg' => 'Token inválido.'], 403);
if (sessionRateLimited('contact', 6, 600)) jsonResponse(['ok' => false, 'msg' => 'Muitas mensagens. Tente novamente em alguns minutos.'], 429);

$name    = trim($_POST['name'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone   = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$name || !$email || !$message) {
    jsonResponse(['ok' => false, 'msg' => 'Preencha nome, email e mensagem.']);
}
if (strlen($message) < 10) {
    jsonResponse(['ok' => false, 'msg' => 'Mensagem muito curta.']);
}

dbInsert(
    "INSERT INTO contact_messages (name, email, phone, subject, message, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)",
    [$name, $email, $phone ?: null, $subject ?: null, $message, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]
);

jsonResponse(['ok' => true, 'msg' => 'Mensagem enviada! Em breve entraremos em contato.']);
