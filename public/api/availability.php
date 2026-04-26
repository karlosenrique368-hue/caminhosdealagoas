<?php
/**
 * Disponibilidade pública por produto.
 * Usada pelo checkout, modal de carrinho e páginas de detalhe.
 */
require_once __DIR__ . '/../../src/bootstrap.php';

$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['roteiro', 'pacote', 'transfer'], true) || $id <= 0) {
    jsonResponse(['ok' => false, 'msg' => 'Produto inválido.'], 400);
}

$table = $type === 'roteiro' ? 'roteiros' : ($type === 'pacote' ? 'pacotes' : 'transfers');
$row = dbOne("SELECT * FROM {$table} WHERE id=? AND status='published'", [$id]);
if (!$row) jsonResponse(['ok' => false, 'msg' => 'Produto indisponível.'], 404);

$mode = $type === 'transfer' ? 'open' : ($row['availability_mode'] ?? 'fixed');
if (!in_array($mode, ['fixed', 'open', 'on_request'], true)) $mode = 'fixed';

$departures = dbAll(
    "SELECT * FROM departures WHERE entity_type=? AND entity_id=? AND departure_date>=CURDATE() ORDER BY departure_date",
    [$type, $id]
);

$map = [];
foreach ($departures as $d) {
    $map[$d['departure_date']] = [
        'status' => $d['status'],
        'seats'  => max(0, (int)$d['seats_total'] - (int)$d['seats_sold']),
        'price'  => $d['price_override'] !== null ? (float)$d['price_override'] : (float)($row['price_pix'] ?: $row['price']),
        'time'   => $d['departure_time'],
    ];
}

jsonResponse([
    'ok' => true,
    'type' => $type,
    'id' => $id,
    'title' => $row['title'],
    'mode' => $mode,
    'basePrice' => (float)($row['price_pix'] ?: $row['price']),
    'map' => $map,
]);
