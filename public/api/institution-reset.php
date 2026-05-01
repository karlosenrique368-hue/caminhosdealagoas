<?php
/**
 * API: Redefinir senha (institution)
 */
require_once __DIR__ . '/../../src/bootstrap.php';

if (!isPost())     jsonResponse(['ok' => false, 'msg' => 'Método inválido.'], 405);
if (!csrfVerify()) jsonResponse(['ok' => false, 'msg' => 'Token CSRF inválido.'], 403);

$token = trim((string)($_POST['token'] ?? ''));
$pass  = (string)($_POST['password'] ?? '');
$conf  = (string)($_POST['password_confirm'] ?? '');

if (strlen($pass) < 8) jsonResponse(['ok' => false, 'msg' => 'A senha precisa ter ao menos 8 caracteres.']);
if ($pass !== $conf)   jsonResponse(['ok' => false, 'msg' => 'As senhas não coincidem.']);
if ($token === '')     jsonResponse(['ok' => false, 'msg' => 'Token inválido.']);

try {
    $row = passwordResetConsume('institution', $token, true);
    if (!$row) jsonResponse(['ok' => false, 'msg' => 'Link inválido ou expirado.']);
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    dbExec('UPDATE institution_users SET password_hash=?, updated_at=NOW() WHERE id=?', [$hash, (int)$row['user_id']]);

    // Detect if user is macaiok or parceiro for redirect
    $u = dbOne('SELECT i.program FROM institution_users iu JOIN institutions i ON iu.institution_id=i.id WHERE iu.id=? LIMIT 1', [(int)$row['user_id']]);
    $base = ($u && ($u['program'] ?? '') === 'macaiok') ? '/macaiok' : '/parceiro';
    jsonResponse(['ok' => true, 'msg' => 'Senha redefinida com sucesso!', 'redirect' => url($base . '/login')]);
} catch (\Throwable $e) {
    error_log('[institution-reset] ' . $e->getMessage());
    jsonResponse(['ok' => false, 'msg' => 'Erro ao redefinir senha.'], 500);
}
