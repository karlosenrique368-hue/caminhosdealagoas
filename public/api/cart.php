<?php
/**
 * Cart API — session-based cart
 * GET  ?action=get       → returns cart
 * POST ?action=add       { type, id }
 * POST ?action=remove    { key }
 * POST ?action=update    { key, qty }
 * POST ?action=clear
 */
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

function cartResponse(): void {
    $items = [];
    $total = 0.0;
    $count = 0;
    foreach ($_SESSION['cart'] as $key => $it) {
        $row = null;
        if ($it['type'] === 'roteiro') {
            $row = dbOne("SELECT id,title,slug,short_desc,cover_image,price,price_pix,location,duration_hours FROM roteiros WHERE id=? AND status='published'", [$it['id']]);
        } elseif ($it['type'] === 'pacote') {
            $row = dbOne("SELECT id,title,slug,short_desc,cover_image,price,price_pix,destination AS location,duration_days FROM pacotes WHERE id=? AND status='published'", [$it['id']]);
        }
        if (!$row) { unset($_SESSION['cart'][$key]); continue; }
        $qty   = max(1, (int)($it['qty'] ?? 1));
        $price = (float)($row['price_pix'] ?: $row['price']);
        $sub   = $price * $qty;
        $total += $sub;
        $count += $qty;
        $items[] = [
            'key'        => $key,
            'type'       => $it['type'],
            'id'         => (int)$row['id'],
            'title'      => $row['title'],
            'slug'       => $row['slug'],
            'short_desc' => $row['short_desc'] ?? '',
            'cover'      => $row['cover_image'] ? storageUrl($row['cover_image']) : null,
            'location'   => $row['location'] ?? '',
            'price'      => $price,
            'qty'        => $qty,
            'travel_date'=> $it['travel_date'] ?? null,
            'subtotal'   => $sub,
            'url'        => url('/' . ($it['type'] === 'roteiro' ? 'roteiros' : 'pacotes') . '/' . $row['slug']),
        ];
    }
    echo json_encode([
        'ok'    => true,
        'count' => $count,
        'total' => $total,
        'total_fmt' => 'R$ ' . number_format($total, 2, ',', '.'),
        'items' => $items,
    ]);
    exit;
}

$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    cartResponse();
}

if (!csrfVerify()) {
    echo json_encode(['ok' => false, 'msg' => 'Token inválido.']);
    exit;
}

$data = $_POST;
if (empty($data)) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) $data = array_merge($data, $json);
}

switch ($action) {
    case 'add':
        $type = $data['type'] ?? '';
        $id   = (int)($data['id'] ?? 0);
        $travelDate = $data['travel_date'] ?? null;
        if ($travelDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $travelDate)) $travelDate = null;
        if (!in_array($type, ['roteiro','pacote'], true) || $id <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Dados inválidos.']);
            exit;
        }
        // Chave inclui data para permitir mesma viagem em dias diferentes
        $key = $type . ':' . $id . ($travelDate ? ':' . $travelDate : '');
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['qty'] = ($_SESSION['cart'][$key]['qty'] ?? 1) + 1;
        } else {
            $_SESSION['cart'][$key] = ['type' => $type, 'id' => $id, 'qty' => 1, 'travel_date' => $travelDate];
        }
        cartResponse();
        break;
    case 'remove':
        $key = $data['key'] ?? '';
        unset($_SESSION['cart'][$key]);
        cartResponse();
        break;
    case 'update':
        $key = $data['key'] ?? '';
        $qty = max(1, (int)($data['qty'] ?? 1));
        if (isset($_SESSION['cart'][$key])) $_SESSION['cart'][$key]['qty'] = $qty;
        cartResponse();
        break;
    case 'clear':
        $_SESSION['cart'] = [];
        cartResponse();
        break;
    default:
        echo json_encode(['ok' => false, 'msg' => 'Ação inválida.']);
}
