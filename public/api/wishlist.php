<?php
require_once dirname(__DIR__,2) . '/src/bootstrap.php';
if (!csrfVerify()) jsonResponse(['ok'=>false,'msg'=>'CSRF inválido.'], 403);
if (!isCustomerLoggedIn()) jsonResponse(['ok'=>false,'msg'=>'Faça login.'], 401);
$cid = currentCustomerId();
$action = $_GET['action'] ?? 'toggle';
$type = $_POST['entity_type'] ?? $_POST['type'] ?? '';
$eid = (int)($_POST['entity_id'] ?? $_POST['id'] ?? 0);

if ($action === 'remove' && $eid) {
    // id in this branch is the wishlist row id
    dbExec('DELETE FROM wishlist WHERE id=? AND customer_id=?', [$eid, $cid]);
    jsonResponse(['ok'=>true]);
}

if (!in_array($type, ['roteiro','pacote'], true) || !$eid) {
    jsonResponse(['ok'=>false,'msg'=>'Parâmetros inválidos.'], 400);
}

if ($action === 'add') {
    try { dbExec('INSERT IGNORE INTO wishlist (customer_id,entity_type,entity_id) VALUES (?,?,?)', [$cid,$type,$eid]); } catch (\Throwable $e) {}
    jsonResponse(['ok'=>true,'added'=>true]);
}
if ($action === 'toggle') {
    $ex = dbOne('SELECT id FROM wishlist WHERE customer_id=? AND entity_type=? AND entity_id=?', [$cid,$type,$eid]);
    if ($ex) { dbExec('DELETE FROM wishlist WHERE id=?', [$ex['id']]); jsonResponse(['ok'=>true,'added'=>false]); }
    dbExec('INSERT INTO wishlist (customer_id,entity_type,entity_id) VALUES (?,?,?)', [$cid,$type,$eid]);
    jsonResponse(['ok'=>true,'added'=>true]);
}
jsonResponse(['ok'=>false,'msg'=>'Ação inválida.'], 400);
