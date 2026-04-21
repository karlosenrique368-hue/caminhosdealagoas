<?php
/**
 * Customer profile API.
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

$action = $_GET['action'] ?? ($_POST['action'] ?? 'update');
$cid = currentCustomerId();

try {
    if ($action === 'password') {
        $new = (string)($_POST['new_password'] ?? '');
        if (strlen($new) < 6) {
            jsonResponse(['ok' => false, 'msg' => 'Senha precisa ter no mínimo 6 caracteres.'], 422);
        }
        dbExec('UPDATE customers SET password_hash=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $cid]);
        jsonResponse(['ok' => true, 'msg' => 'Senha atualizada com sucesso!']);
    }

    // update profile
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        jsonResponse(['ok' => false, 'msg' => 'Nome é obrigatório.'], 422);
    }
    dbExec('UPDATE customers SET name=?, phone=?, document=?, city=?, state=?, country=? WHERE id=?', [
        $name,
        trim($_POST['phone'] ?? ''),
        trim($_POST['document'] ?? ''),
        trim($_POST['city'] ?? ''),
        trim($_POST['state'] ?? ''),
        trim($_POST['country'] ?? ''),
        $cid,
    ]);
    $_SESSION['customer_name'] = $name;
    jsonResponse(['ok' => true, 'msg' => 'Perfil atualizado!', 'data' => ['name' => $name]]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'msg' => 'Erro ao processar: ' . $e->getMessage()], 500);
}
