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
    if ($action === 'avatar') {
        if (empty($_FILES['avatar']) || ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            jsonResponse(['ok' => false, 'msg' => 'Selecione uma imagem.'], 422);
        }
        $rel = handleImageUpload($_FILES['avatar'], 'avatars');
        if (!$rel) jsonResponse(['ok' => false, 'msg' => 'Falha ao enviar imagem.'], 422);
        // remove old avatar
        $old = dbOne('SELECT avatar FROM customers WHERE id=?', [$cid])['avatar'] ?? null;
        if ($old && str_starts_with($old, 'uploads/')) {
            $oldPath = UPLOADS_DIR . '/' . substr($old, strlen('uploads/'));
            if (is_file($oldPath)) @unlink($oldPath);
        }
        dbExec('UPDATE customers SET avatar=?, updated_at=NOW() WHERE id=?', [$rel, $cid]);
        jsonResponse(['ok' => true, 'msg' => 'Foto atualizada!', 'data' => ['avatar' => storageUrl($rel)]]);
    }

    if ($action === 'avatar_remove') {
        $old = dbOne('SELECT avatar FROM customers WHERE id=?', [$cid])['avatar'] ?? null;
        if ($old && str_starts_with($old, 'uploads/')) {
            $oldPath = UPLOADS_DIR . '/' . substr($old, strlen('uploads/'));
            if (is_file($oldPath)) @unlink($oldPath);
        }
        dbExec('UPDATE customers SET avatar=NULL, updated_at=NOW() WHERE id=?', [$cid]);
        jsonResponse(['ok' => true, 'msg' => 'Foto removida.']);
    }

    if ($action === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $customer = dbOne('SELECT password_hash FROM customers WHERE id=?', [$cid]);
        if (!empty($customer['password_hash']) && !password_verify($current, $customer['password_hash'])) {
            jsonResponse(['ok' => false, 'msg' => 'Senha atual incorreta.'], 422);
        }
        if (strlen($new) < PASSWORD_MIN_LENGTH) {
            jsonResponse(['ok' => false, 'msg' => 'Senha precisa ter no mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres.'], 422);
        }
        dbExec('UPDATE customers SET password_hash=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $cid]);
        jsonResponse(['ok' => true, 'msg' => 'Senha atualizada com sucesso!']);
    }

    // update profile
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        jsonResponse(['ok' => false, 'msg' => 'Nome é obrigatório.'], 422);
    }
    dbExec('UPDATE customers SET name=?, phone=?, document=?, postal_code=?, address=?, address_number=?, neighborhood=?, address_complement=?, city=?, state=?, country=? WHERE id=?', [
        $name,
        trim($_POST['phone'] ?? ''),
        trim($_POST['document'] ?? ''),
        trim($_POST['postal_code'] ?? ''),
        trim($_POST['address'] ?? ''),
        trim($_POST['address_number'] ?? ''),
        trim($_POST['neighborhood'] ?? ''),
        trim($_POST['address_complement'] ?? ''),
        trim($_POST['city'] ?? ''),
        trim($_POST['state'] ?? ''),
        trim($_POST['country'] ?? ''),
        $cid,
    ]);
    $_SESSION['customer_name'] = $name;
    jsonResponse(['ok' => true, 'msg' => 'Perfil atualizado!', 'data' => ['name' => $name]]);
} catch (Throwable $e) {
    jsonException($e, 'Erro ao processar o perfil.');
}
