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
$bookingIdInput = (int)($_POST['booking_id'] ?? 0);
$bookingId = null;
if ($bookingIdInput > 0) {
    $b = dbOne("SELECT id FROM bookings WHERE id=? AND customer_user_id=? AND entity_type=? AND entity_id=? AND payment_status='paid' LIMIT 1", [$bookingIdInput,$cid,$type,$eid]);
    if ($b) { $bookingId = (int)$b['id']; $verified = 1; }
}
if (!$bookingId) {
    $b = dbOne("SELECT id FROM bookings WHERE customer_user_id=? AND entity_type=? AND entity_id=? AND payment_status='paid' LIMIT 1", [$cid,$type,$eid]);
    if ($b) { $bookingId = (int)$b['id']; $verified = 1; }
}

// Evita duplicada
if ($bookingId) {
    $dup = dbOne('SELECT id FROM reviews WHERE booking_id=? AND customer_id=? LIMIT 1', [$bookingId, $cid]);
    if ($dup) jsonResponse(['ok'=>false,'msg'=>'Você já avaliou esta reserva.'], 400);
}

$photos = [];
if (!empty($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
    $uploadDir = UPLOADS_DIR . '/reviews';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        jsonResponse(['ok'=>false,'msg'=>'Não foi possível preparar o upload.'], 500);
    }
    $count = min(4, count($_FILES['photos']['name']));
    $mimeToExt = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    for ($i=0; $i<$count; $i++) {
        if (($_FILES['photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($_FILES['photos']['error'][$i] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) jsonResponse(['ok'=>false,'msg'=>'Falha ao enviar uma das fotos.'], 400);
        if (($_FILES['photos']['size'][$i] ?? 0) > MAX_UPLOAD_SIZE) jsonResponse(['ok'=>false,'msg'=>'Cada foto deve ter até 5MB.'], 400);
        $tmp = $_FILES['photos']['tmp_name'][$i] ?? '';
        if (!$tmp || !is_uploaded_file($tmp)) continue;
        $mime = mime_content_type($tmp) ?: '';
        if (!isset($mimeToExt[$mime])) jsonResponse(['ok'=>false,'msg'=>'Envie fotos em JPG, PNG ou WebP.'], 400);
        $fileName = 'review_' . $cid . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $mimeToExt[$mime];
        $target = $uploadDir . '/' . $fileName;
        if (!move_uploaded_file($tmp, $target)) jsonResponse(['ok'=>false,'msg'=>'Não foi possível salvar a foto.'], 500);
        $photos[] = '/storage/uploads/reviews/' . $fileName;
    }
}

dbExec('INSERT INTO reviews (customer_id,booking_id,entity_type,entity_id,rating,title,content,photos,verified,status) VALUES (?,?,?,?,?,?,?,?,?,?)',
    [$cid, $bookingId, $type, $eid, $rating, $title, $content, $photos ? json_encode($photos, JSON_UNESCAPED_SLASHES) : null, $verified, 'pending']);

// Recompute avg (approved only)
$table = $type === 'roteiro' ? 'roteiros' : 'pacotes';
$stats = dbOne("SELECT AVG(rating) a, COUNT(*) c FROM reviews WHERE entity_type=? AND entity_id=? AND status='approved'", [$type,$eid]);
dbExec("UPDATE $table SET rating_avg=?, rating_count=? WHERE id=?", [(float)($stats['a'] ?? 0), (int)($stats['c'] ?? 0), $eid]);

jsonResponse(['ok'=>true,'msg'=>'Avaliação enviada! Será publicada após moderação.']);
