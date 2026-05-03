<?php
/**
 * Cart API — session-based cart
 * GET  ?action=get       → returns cart
 * POST ?action=add       { type, id, travel_date|travel_dates }
 * POST ?action=remove    { key }
 * POST ?action=update    { key, qty }
 * POST ?action=clear
 */
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

function cartResponse(): void {
    $cartContext = (string)($_GET['context'] ?? $_POST['context'] ?? '');
    $macaiokCart = $cartContext === 'macaiok';
    $checkoutBase = $macaiokCart ? '/macaiok/checkout' : '/checkout';
    $items = [];
    $total = 0.0;
    $count = 0;
    foreach ($_SESSION['cart'] as $key => $it) {
        $row = null;
        if ($it['type'] === 'roteiro') {
            $row = dbOne("SELECT id,title,slug,short_desc,cover_image,price,price_pix,location,duration_hours FROM roteiros WHERE id=? AND status='published'" . ($macaiokCart ? " AND macaiok_featured=1" : ""), [$it['id']]);
        } elseif ($it['type'] === 'pacote') {
            $row = dbOne("SELECT id,title,slug,short_desc,cover_image,price,price_pix,destination AS location,duration_days FROM pacotes WHERE id=? AND status='published'" . ($macaiokCart ? " AND macaiok_featured=1" : ""), [$it['id']]);
        } elseif ($it['type'] === 'transfer') {
            $row = dbOne("SELECT id,title,slug,short_desc,cover_image,price,price_pix,location_to AS location,duration_minutes FROM transfers WHERE id=? AND status='published'" . ($macaiokCart ? " AND macaiok_featured=1" : ""), [$it['id']]);
        }
        if (!$row) { unset($_SESSION['cart'][$key]); continue; }
        $qty   = max(1, (int)($it['qty'] ?? 1));
        $price = (float)($row['price_pix'] ?: $row['price']);
        $dateCount = !empty($it['travel_dates']) && is_array($it['travel_dates']) ? count($it['travel_dates']) : (!empty($it['travel_date']) ? 1 : 1);
        $travelDates = !empty($it['travel_dates']) && is_array($it['travel_dates']) ? array_values($it['travel_dates']) : (!empty($it['travel_date']) ? [$it['travel_date']] : []);
        $sub   = $price * $qty * max(1, $dateCount);
        $total += $sub;
        $count += $qty;
        $query = ['cart_key' => $key, $it['type'] => (int)$row['id']];
        if ($qty > 1) $query['qty'] = $qty;
        if ($travelDates) $query['dates'] = implode(',', $travelDates);
        $checkoutUrl = url($checkoutBase . '?' . http_build_query($query));
        $items[] = [
            'key'        => $key,
            'type'       => $it['type'],
            'type_label' => $it['type'] === 'roteiro' ? 'Passeio' : ($it['type'] === 'pacote' ? 'Pacote' : 'Transfer'),
            'id'         => (int)$row['id'],
            'title'      => $row['title'],
            'slug'       => $row['slug'],
            'short_desc' => $row['short_desc'] ?? '',
            'cover'      => $row['cover_image'] ? storageUrl($row['cover_image']) : null,
            'location'   => $row['location'] ?? '',
            'price'      => $price,
            'qty'        => $qty,
            'travel_date'=> $travelDates[0] ?? null,
            'travel_dates'=> $travelDates,
            'subtotal'   => $sub,
            'url'        => url('/' . ($it['type'] === 'roteiro' ? 'passeios' : ($it['type'] === 'pacote' ? 'pacotes' : 'transfers')) . '/' . $row['slug']),
            'checkout_url'=> $checkoutUrl,
        ];
    }
    $checkoutUrl = url($checkoutBase);
    if (count($items) === 1) {
        $checkoutUrl = $items[0]['checkout_url'];
    } elseif (count($items) > 1) {
        $checkoutUrl = url($checkoutBase . '?cart=1');
    }
    echo json_encode([
        'ok'    => true,
        'count' => $count,
        'total' => $total,
        'total_fmt' => 'R$ ' . number_format($total, 2, ',', '.'),
        'items' => $items,
        'checkout_url' => $checkoutUrl,
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
        $travelDates = [];
        $rawDates = $data['travel_dates'] ?? null;
        if (is_string($rawDates) && $rawDates !== '') {
            $decoded = json_decode($rawDates, true);
            $rawDates = is_array($decoded) ? $decoded : preg_split('/[,;\s]+/', $rawDates);
        }
        if (is_array($rawDates)) {
            foreach ($rawDates as $dt) {
                $dt = trim((string)$dt);
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) $travelDates[] = $dt;
            }
        }
        $travelDate = $data['travel_date'] ?? null;
        if ($travelDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $travelDate)) $travelDates[] = $travelDate;
        $travelDates = array_values(array_unique($travelDates));
        sort($travelDates);
        $travelDate = $travelDates[0] ?? null;
        if (!in_array($type, ['roteiro','pacote','transfer'], true) || $id <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Dados inválidos.']);
            exit;
        }
        // Chave inclui datas para permitir a mesma experiência em seleções diferentes
        $key = $type . ':' . $id . ($travelDates ? ':' . implode('|', $travelDates) : '');
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['qty'] = ($_SESSION['cart'][$key]['qty'] ?? 1) + 1;
        } else {
            $_SESSION['cart'][$key] = ['type' => $type, 'id' => $id, 'qty' => 1, 'travel_date' => $travelDate, 'travel_dates' => $travelDates];
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
