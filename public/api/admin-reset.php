<?php
require_once __DIR__ . '/../../src/bootstrap.php';
if (!isPost())     jsonResponse(['ok' => false, 'msg' => 'Método inválido.'], 405);
if (!csrfVerify()) jsonResponse(['ok' => false, 'msg' => 'Token CSRF inválido.'], 403);

$token = trim((string)($_POST['token'] ?? ''));
$pass  = (string)($_POST['password'] ?? '');
$conf  = (string)($_POST['password_confirm'] ?? '');

if (strlen($pass) < 8) jsonResponse(['ok' => false, 'msg' => 'Senha precisa ter ao menos 8 caracteres.']);
if ($pass !== $conf)   jsonResponse(['ok' => false, 'msg' => 'As senhas não coincidem.']);
if ($token === '')     jsonResponse(['ok' => false, 'msg' => 'Token inválido.']);

try {
    $row = passwordResetConsume('admin', $token, true);
    if (!$row) jsonResponse(['ok' => false, 'msg' => 'Link inválido ou expirado.']);
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    dbExec('UPDATE admin_users SET password_hash=?, updated_at=NOW() WHERE id=?', [$hash, (int)$row['user_id']]);
    jsonResponse(['ok' => true, 'msg' => 'Senha redefinida!', 'redirect' => url('/admin/login')]);
} catch (\Throwable $e) {
    error_log('[admin-reset] ' . $e->getMessage());
    jsonResponse(['ok' => false, 'msg' => 'Erro ao redefinir.'], 500);
}
