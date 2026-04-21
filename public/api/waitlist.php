<?php
require_once dirname(__DIR__,2) . '/src/bootstrap.php';
if (!csrfVerify()) jsonResponse(['ok'=>false,'msg'=>'CSRF inválido.'], 403);

$type = $_POST['entity_type'] ?? '';
$eid = (int)($_POST['entity_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$desired = $_POST['desired_date'] ?? null;

if (!in_array($type, ['roteiro','pacote'], true) || !$eid || !$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['ok'=>false,'msg'=>'Preencha todos os campos obrigatórios.'], 400);
}

dbExec('INSERT INTO waitlist (customer_id,name,email,phone,entity_type,entity_id,desired_date,notes) VALUES (?,?,?,?,?,?,?,?)',
    [currentCustomerId(), $name, $email, $phone, $type, $eid, $desired ?: null, $notes]);

jsonResponse(['ok'=>true, 'msg'=>'Você está na lista de espera! Avisaremos assim que houver vaga.']);
