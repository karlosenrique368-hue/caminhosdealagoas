<?php
/**
 * API: Esqueci minha senha (cliente)
 *  - POST: { email } → cria token e envia e-mail (resposta sempre genérica)
 */
require_once __DIR__ . '/../../src/bootstrap.php';

if (!isPost())     jsonResponse(['ok' => false, 'msg' => 'Método inválido.'], 405);
if (!csrfVerify()) jsonResponse(['ok' => false, 'msg' => 'Token CSRF inválido.'], 403);

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['ok' => false, 'msg' => 'Informe um e-mail válido.']);
}

// Throttle simples por IP+email (5 tentativas / 30 min)
$throttleKey = 'pwd_reset_' . md5(($_SERVER['REMOTE_ADDR'] ?? '0') . '|' . $email);
$attempts = (int)(getSetting($throttleKey, '0'));
if ($attempts >= 5) {
    jsonResponse(['ok' => true, 'msg' => 'Se o e-mail estiver cadastrado, você receberá um link em instantes.']);
}

try {
    $user = dbOne('SELECT id, email, name FROM customers WHERE LOWER(email)=? LIMIT 1', [$email]);
    if ($user) {
        $token = passwordResetCreate('customer', (int)$user['id'], $user['email']);
        $scheme = isSecureRequest() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $resetUrl = $scheme . '://' . $host . url('/conta/esqueci-senha?token=' . urlencode($token));
        passwordResetSendEmail('customer', $user['email'], $token, $resetUrl);
    }
    setSetting($throttleKey, (string)($attempts + 1));
    // Resposta SEMPRE genérica para não vazar quais e-mails existem
    jsonResponse(['ok' => true, 'msg' => 'Se o e-mail estiver cadastrado, você receberá um link em instantes.']);
} catch (\Throwable $e) {
    error_log('[account-forgot] ' . $e->getMessage());
    jsonResponse(['ok' => false, 'msg' => 'Erro interno. Tente novamente.'], 500);
}
