<?php
require_once dirname(__DIR__,2) . '/src/bootstrap.php';
if (!csrfVerify()) jsonResponse(['ok'=>false,'msg'=>'CSRF inválido.'], 403);
if (!isCustomerLoggedIn()) jsonResponse(['ok'=>false,'msg'=>'Faça login.'], 401);

$cid = currentCustomerId();
$type = $_POST['entity_type'] ?? '';
$eid = (int)($_POST['entity_id'] ?? 0);
$rating = max(1, min(5, (int)($_POST['rating'] ?? 0)));
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

if (!in_array($type, ['roteiro','pacote'], true) || !$eid || !$rating || !$content) {
    jsonResponse(['ok'=>false,'msg'=>'Preencha nota e comentário.'], 400);
}

// verified if customer has a completed booking for this entity
$verified = 0;
$field = $type === 'roteiro' ? 'roteiro_id' : 'pacote_id';
$b = dbOne("SELECT id FROM bookings WHERE customer_user_id=? AND $field=? AND status IN ('confirmed','completed') LIMIT 1", [$cid,$eid]);
$bookingId = $b['id'] ?? null;
if ($b) $verified = 1;

dbExec('INSERT INTO reviews (customer_id,booking_id,entity_type,entity_id,rating,title,content,verified,status) VALUES (?,?,?,?,?,?,?,?,?)',
    [$cid, $bookingId, $type, $eid, $rating, $title, $content, $verified, 'pending']);

// Recompute avg (approved only)
$table = $type === 'roteiro' ? 'roteiros' : 'pacotes';
$stats = dbOne("SELECT AVG(rating) a, COUNT(*) c FROM reviews WHERE entity_type=? AND entity_id=? AND status='approved'", [$type,$eid]);
dbExec("UPDATE $table SET rating_avg=?, rating_count=? WHERE id=?", [(float)($stats['a'] ?? 0), (int)($stats['c'] ?? 0), $eid]);

jsonResponse(['ok'=>true,'msg'=>'Avaliação enviada! Será publicada após moderação.']);
