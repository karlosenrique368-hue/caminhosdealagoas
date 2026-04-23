<?php
require_once __DIR__ . '/../../src/bootstrap.php';

if (!isPost()) jsonResponse(['ok' => false, 'msg' => 'Método inválido.'], 405);
if (!csrfVerify()) jsonResponse(['ok' => false, 'msg' => 'Token inválido.'], 403);

$name  = trim($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = trim($_POST['phone'] ?? '');
$doc   = preg_replace('/\D/', '', $_POST['cpf'] ?? $_POST['document'] ?? '');
$rg    = trim($_POST['rg'] ?? '');
$birth = trim($_POST['birth_date'] ?? '');

$entityType = $_POST['entity_type'] ?? '';
$entityId   = (int) ($_POST['entity_id'] ?? 0);
$travelDate = $_POST['travel_date'] ?? null;
$adults     = max(1, (int)($_POST['adults'] ?? $_POST['people'] ?? 1));
$children   = max(0, (int)($_POST['children'] ?? 0));
$notes      = trim($_POST['notes'] ?? '');
$couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));
$pm         = $_POST['payment_method'] ?? 'pix';

// Campos tipo Google Forms
$comorbidity   = trim($_POST['comorbidity'] ?? '');
$hasComorbid   = ($_POST['has_comorbidity'] ?? 'nao') === 'sim';
$source        = in_array($_POST['source'] ?? '', ['instagram','whatsapp','indicacao','google','outro']) ? $_POST['source'] : null;
$sourceDetail  = trim($_POST['source_detail'] ?? '');
$acceptTerms   = !empty($_POST['accept_terms']);
$priceOption   = $_POST['price_option'] ?? null;

// Modo grupo (checkout institucional)
$bookingMode   = $_POST['booking_mode'] ?? 'individual';
if (!in_array($bookingMode, ['individual','grupo_instituicao'], true)) $bookingMode = 'individual';
$participantsRaw = $_POST['participants'] ?? null;
$participants  = null;
if ($bookingMode === 'grupo_instituicao' && $participantsRaw) {
    $dec = json_decode($participantsRaw, true);
    if (is_array($dec) && count($dec) > 0) {
        $participants = $dec;
        $adults = count($dec);
        $children = 0;
    }
}
$respName  = trim($_POST['responsible_name'] ?? '');
$respCpf   = preg_replace('/\D/', '', $_POST['responsible_cpf'] ?? '');
$respPhone = trim($_POST['responsible_phone'] ?? '');
$instPartnerId = (int)($_POST['institution_partner_id'] ?? 0) ?: null;

// Resposta completa do formulario (para guardar como evidencia)
$answers = [
    'nome' => $name, 'cpf' => $doc, 'rg' => $rg, 'birth' => $birth, 'telefone' => $phone,
    'comorbidade' => $hasComorbid ? $comorbidity : null,
    'como_conheceu' => $source, 'indicado_por' => $sourceDetail,
    'opcao_preco' => $priceOption, 'aceite_desistencia' => $acceptTerms,
    'submitted_at' => date('c'),
];

$pmMap = ['pix' => 'pix', 'card' => 'credit_card', 'credit_card' => 'credit_card', 'boleto' => 'boleto'];
$paymentMethod = $pmMap[$pm] ?? null;

if (!$name || !$email || !$phone) jsonResponse(['ok' => false, 'msg' => 'Preencha nome, email e telefone.']);
if (!in_array($entityType, ['roteiro','pacote'])) jsonResponse(['ok' => false, 'msg' => 'Produto inválido.']);
if (!$entityId) jsonResponse(['ok' => false, 'msg' => 'Produto não informado.']);
if (!$paymentMethod) jsonResponse(['ok' => false, 'msg' => 'Método de pagamento inválido.']);
if (!$acceptTerms) jsonResponse(['ok' => false, 'msg' => 'É preciso concordar com a política de desistência.']);

$table = $entityType === 'roteiro' ? 'roteiros' : 'pacotes';
$entity = dbOne("SELECT * FROM {$table} WHERE id = ? AND status = 'published'", [$entityId]);
if (!$entity) jsonResponse(['ok' => false, 'msg' => 'Produto indisponível.']);

$unitPrice = ($pm === 'pix' && !empty($entity['price_pix'])) ? (float)$entity['price_pix'] : (float)$entity['price'];
$totalPeople = $adults + $children;
$subtotal = $unitPrice * $totalPeople;

$discount = 0.0;
$couponIdToIncrement = null;
if ($couponCode) {
    $cp = dbOne("SELECT * FROM coupons WHERE code = ? AND active = 1", [$couponCode]);
    if ($cp) {
        $now = date('Y-m-d H:i:s');
        $validFrom  = !$cp['valid_from']  || $cp['valid_from']  <= $now;
        $validUntil = !$cp['valid_until'] || $cp['valid_until'] >= $now;
        $validUses  = !$cp['max_uses']    || $cp['used_count'] < $cp['max_uses'];
        $validMin   = !$cp['min_purchase']|| $subtotal >= (float)$cp['min_purchase'];
        if ($validFrom && $validUntil && $validUses && $validMin) {
            $discount = $cp['type'] === 'percent' ? $subtotal * ((float)$cp['value'] / 100) : (float)$cp['value'];
            $discount = min($discount, $subtotal);
            $couponIdToIncrement = (int)$cp['id'];
        }
    }
}

$total = max(0, $subtotal - $discount);

$customer = dbOne("SELECT * FROM customers WHERE email = ?", [$email]);
if ($customer) {
    dbExec("UPDATE customers SET name = ?, phone = ?, document = ?, rg = COALESCE(?, rg), birth_date = COALESCE(?, birth_date) WHERE id = ?",
        [$name, $phone, $doc ?: $customer['document'], $rg ?: null, $birth ?: null, $customer['id']]);
    $customerId = (int)$customer['id'];
} else {
    $customerId = dbInsert("INSERT INTO customers (name, email, phone, document, rg, birth_date) VALUES (?, ?, ?, ?, ?, ?)",
        [$name, $email, $phone, $doc ?: null, $rg ?: null, $birth ?: null]);
}

// Codigo de indicacao: parametro explicito (source=indicacao + source_detail=codigo) OU cookie/session
$refCode = null; $partnerId = null;
$refFromForm = strtoupper(preg_replace('/[^A-Z0-9]/', '', $_POST['ref_code'] ?? ''));
if ($refFromForm) {
    $refPartner = partnerByCode($refFromForm);
    if ($refPartner) { $refCode = $refPartner['referral_code']; $partnerId = (int)$refPartner['id']; }
}
if (!$refCode) {
    $tracked = currentReferralCode();
    if ($tracked) {
        $rp = partnerByCode($tracked);
        if ($rp) { $refCode = $rp['referral_code']; $partnerId = (int)$rp['id']; }
    }
}

$year = date('Y');
$code = '';
for ($i = 0; $i < 5; $i++) {
    $rand = strtoupper(bin2hex(random_bytes(2)));
    $candidate = "CA-{$year}-{$rand}";
    if (!dbOne("SELECT id FROM bookings WHERE code = ?", [$candidate])) { $code = $candidate; break; }
}
if (!$code) $code = "CA-{$year}-" . strtoupper(uniqid());

$bookingId = dbInsert(
    "INSERT INTO bookings (code, customer_id, entity_type, entity_id, booking_mode, entity_title, adults, children, travel_date, subtotal, discount, total, payment_method, payment_status, notes, institution_id, referral_code, source, source_detail, comorbidity, booking_answers, participants, responsible_name, responsible_cpf, responsible_phone)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    [$code, $customerId, $entityType, $entityId, $bookingMode, $entity['title'], $adults, $children, $travelDate ?: null, $subtotal, $discount, $total, $paymentMethod, $notes ?: null,
     $instPartnerId ?: $partnerId, $refCode, $source, $sourceDetail ?: null, $hasComorbid ? $comorbidity : null, json_encode($answers, JSON_UNESCAPED_UNICODE),
     $participants ? json_encode($participants, JSON_UNESCAPED_UNICODE) : null,
     $respName ?: null, $respCpf ?: null, $respPhone ?: null]
);

if ($couponIdToIncrement) dbExec("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?", [$couponIdToIncrement]);

jsonResponse([
    'ok' => true,
    'msg' => 'Reserva criada com sucesso!',
    'booking' => ['id' => $bookingId, 'code' => $code, 'total' => $total],
    'redirect' => url('/?booking=' . urlencode($code)),
]);
