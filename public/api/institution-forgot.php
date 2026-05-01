<?php
/**
 * API: Esqueci minha senha (institution / parceiro / macaiok)
 */
require_once __DIR__ . '/../../src/bootstrap.php';

if (!isPost())     jsonResponse(['ok' => false, 'msg' => 'Método inválido.'], 405);
if (!csrfVerify()) jsonResponse(['ok' => false, 'msg' => 'Token CSRF inválido.'], 403);

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$origin = $_POST['origin'] === 'macaiok' ? '/macaiok' : '/parceiro';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['ok' => false, 'msg' => 'Informe um e-mail válido.']);
}

$throttleKey = 'pwd_reset_inst_' . md5(($_SERVER['REMOTE_ADDR'] ?? '0') . '|' . $email);
$attempts = (int)(getSetting($throttleKey, '0'));
if ($attempts >= 5) {
    jsonResponse(['ok' => true, 'msg' => 'Se o e-mail estiver cadastrado, você receberá um link em instantes.']);
}

try {
    $user = dbOne('SELECT id, email FROM institution_users WHERE LOWER(email)=? LIMIT 1', [$email]);
    if ($user) {
        $token = passwordResetCreate('institution', (int)$user['id'], $user['email']);
        $scheme = isSecureRequest() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $resetUrl = $scheme . '://' . $host . url($origin . '/esqueci-senha?token=' . urlencode($token));
        passwordResetSendEmail('institution', $user['email'], $token, $resetUrl);
    }
    setSetting($throttleKey, (string)($attempts + 1));
    jsonResponse(['ok' => true, 'msg' => 'Se o e-mail estiver cadastrado, você receberá um link em instantes.']);
} catch (\Throwable $e) {
    error_log('[institution-forgot] ' . $e->getMessage());
    jsonResponse(['ok' => false, 'msg' => 'Erro interno.'], 500);
}
