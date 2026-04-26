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
$travelDates = [];
$rawTravelDates = $_POST['travel_dates'] ?? null;
if (is_string($rawTravelDates) && $rawTravelDates !== '') {
    $decoded = json_decode($rawTravelDates, true);
    $rawTravelDates = is_array($decoded) ? $decoded : preg_split('/[,;\s]+/', $rawTravelDates);
}
if (is_array($rawTravelDates)) {
    foreach ($rawTravelDates as $dt) {
        $dt = trim((string)$dt);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) $travelDates[] = $dt;
    }
}
if ($travelDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $travelDate)) $travelDates[] = $travelDate;
$travelDates = array_values(array_unique($travelDates));
sort($travelDates);
$travelDate = $travelDates[0] ?? null;
$adults     = max(1, (int)($_POST['adults'] ?? $_POST['people'] ?? 1));
$children   = max(0, (int)($_POST['children'] ?? 0));
$infants    = max(0, (int)($_POST['infants']  ?? 0));
$notes      = trim($_POST['notes'] ?? '');
$couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));
$pm         = $_POST['payment_method'] ?? 'pix';
$installments = max(0, (int)($_POST['installments'] ?? 0));
$currencyCode = strtoupper(trim($_POST['currency'] ?? 'BRL')) ?: 'BRL';

// Campos tipo Google Forms
$comorbidity   = trim($_POST['comorbidity'] ?? '');
$hasComorbid   = ($_POST['has_comorbidity'] ?? 'nao') === 'sim';
$source        = in_array($_POST['source'] ?? '', ['instagram','whatsapp','indicacao','google','outro']) ? $_POST['source'] : null;
$sourceDetail  = trim($_POST['source_detail'] ?? '');
$acceptTerms   = !empty($_POST['accept_terms']);
$priceOption   = $_POST['price_option'] ?? null;
$cartKey       = trim($_POST['cart_key'] ?? '');

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

$pmMap = ['pix' => 'pix', 'pix_installments' => 'pix', 'card' => 'credit_card', 'credit_card' => 'credit_card', 'boleto' => 'boleto'];
$paymentMethod = $pmMap[$pm] ?? null;

if (!$name || !$email || !$phone) jsonResponse(['ok' => false, 'msg' => 'Preencha nome, email e telefone.']);
if (!in_array($entityType, ['roteiro','pacote','transfer'], true)) jsonResponse(['ok' => false, 'msg' => 'Produto inválido.']);
if (!$entityId) jsonResponse(['ok' => false, 'msg' => 'Produto não informado.']);
if (!$paymentMethod) jsonResponse(['ok' => false, 'msg' => 'Método de pagamento inválido.']);
if (!$acceptTerms) jsonResponse(['ok' => false, 'msg' => 'É preciso concordar com a política de desistência.']);
if (!$travelDates) jsonResponse(['ok' => false, 'msg' => 'Escolha pelo menos uma data disponível.']);

$table = $entityType === 'roteiro' ? 'roteiros' : ($entityType === 'pacote' ? 'pacotes' : 'transfers');
$entity = dbOne("SELECT * FROM {$table} WHERE id = ? AND status = 'published'", [$entityId]);
if (!$entity) jsonResponse(['ok' => false, 'msg' => 'Produto indisponível.']);

$availabilityMode = $entityType === 'transfer' ? 'open' : ($entity['availability_mode'] ?? 'fixed');
if (!in_array($availabilityMode, ['fixed','open','on_request'], true)) $availabilityMode = 'fixed';
if ($availabilityMode === 'on_request') jsonResponse(['ok'=>false,'msg'=>'Esta experiência está sob consulta. Fale com a equipe para reservar.']);
$peopleTotal = $adults + $children + $infants;
if ($entityType === 'transfer' && $peopleTotal > (int)($entity['capacity'] ?? 0)) {
    jsonResponse(['ok'=>false,'msg'=>'A quantidade de passageiros excede a capacidade do veículo.']);
}
foreach ($travelDates as $dt) {
    if (strtotime($dt) < strtotime(date('Y-m-d'))) jsonResponse(['ok'=>false,'msg'=>'A data selecionada já passou.']);
    $dep = dbOne("SELECT * FROM departures WHERE entity_type=? AND entity_id=? AND departure_date=? LIMIT 1", [$entityType, $entityId, $dt]);
    if ($dep) {
        $free = max(0, (int)$dep['seats_total'] - (int)$dep['seats_sold']);
        if ($dep['status'] !== 'open' || $free < $peopleTotal) jsonResponse(['ok'=>false,'msg'=>'Uma das datas selecionadas não tem vagas suficientes.']);
    } elseif ($availabilityMode === 'fixed') {
        jsonResponse(['ok'=>false,'msg'=>'Escolha apenas datas disponíveis no calendário.']);
    }
}

// Faixas etárias e preço PIX promocional
$factorChild  = (float) getSetting('price_factor_child',  '0.5');
$factorInfant = (float) getSetting('price_factor_infant', '0');
$priceAdult   = (float) $entity['price'];
$pricePixVal  = (float) ($entity['price_pix'] ?? $entity['price']);
$priceChild   = isset($entity['price_children']) && $entity['price_children'] !== null ? (float)$entity['price_children'] : round($priceAdult * $factorChild, 2);
$priceInfant  = isset($entity['price_infant'])   && $entity['price_infant']   !== null ? (float)$entity['price_infant']   : round($priceAdult * $factorInfant, 2);

$useDesconto = ($pm === 'pix' && $priceOption === 'promo' && $pricePixVal > 0 && $pricePixVal < $priceAdult);
$ratio = ($useDesconto && $priceAdult > 0) ? ($pricePixVal / $priceAdult) : 1.0;
$unitAdult  = $useDesconto ? $pricePixVal : $priceAdult;
$unitChild  = $useDesconto ? round($priceChild  * $ratio, 2) : $priceChild;
$unitInfant = $useDesconto ? round($priceInfant * $ratio, 2) : $priceInfant;
$dateCount = max(1, count($travelDates));
if ($entityType === 'transfer') {
    $unitChild = 0.0;
    $unitInfant = 0.0;
    $subtotal = $unitAdult * $dateCount;
} else {
    $subtotal = ($unitAdult * $adults + $unitChild * $children + $unitInfant * $infants) * $dateCount;
}

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

// Validações de PIX parcelado: precisa de margem >= 1 mês até (data - N dias)
$installmentAmount = null;
if ($pm === 'pix_installments') {
    if (!$travelDate) jsonResponse(['ok'=>false,'msg'=>'Defina a data da viagem para parcelar.']);
    $minDays = (int) getSetting('pix_installments_min_days', '7');
    $maxInst = max(1, (int) getSetting('pix_installments_max', '12'));
    $deadline = strtotime($travelDate . ' -' . $minDays . ' days');
    $today = strtotime(date('Y-m-d'));
    $monthsAvailable = max(0, (int) floor(($deadline - $today) / 86400 / 30));
    if ($monthsAvailable < 1) jsonResponse(['ok'=>false,'msg'=>'Sem margem suficiente para PIX parcelado. Escolha PIX à vista.']);
    $installments = min($maxInst, max(1, $installments));
    if ($installments > $monthsAvailable + 1) $installments = $monthsAvailable + 1;
    $installmentAmount = round($total / $installments, 2);
}

// Resposta completa do formulário (para guardar como evidência)
$answers = [
    'nome' => $name, 'cpf' => $doc, 'rg' => $rg, 'birth' => $birth, 'telefone' => $phone,
    'comorbidade' => $hasComorbid ? $comorbidity : null,
    'como_conheceu' => $source, 'detalhe_origem' => $sourceDetail,
    'opcao_preco' => $priceOption, 'aceite_desistencia' => $acceptTerms,
    'datas_viagem' => $travelDates,
    'quantidade_datas' => $dateCount,
    'pessoas' => ['adultos'=>$adults,'criancas'=>$children,'bebes'=>$infants],
    'precos_unitarios' => ['adulto'=>$unitAdult,'crianca'=>$unitChild,'bebe'=>$unitInfant],
    'parcelamento' => $installments > 0 ? ['parcelas'=>$installments,'valor_parcela'=>$installmentAmount,'limite_quitacao'=>date('Y-m-d', strtotime(($travelDate ?: 'now') . ' -' . (int)getSetting('pix_installments_min_days','7') . ' days'))] : null,
    'moeda' => $currencyCode,
    'submitted_at' => date('c'),
];

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
    "INSERT INTO bookings (code, customer_id, entity_type, entity_id, booking_mode, entity_title, adults, children, infants, travel_date, subtotal, discount, total, currency, payment_method, installments, installment_amount, payment_status, notes, institution_id, referral_code, source, source_detail, comorbidity, booking_answers, participants, responsible_name, responsible_cpf, responsible_phone)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
    [$code, $customerId, $entityType, $entityId, $bookingMode, $entity['title'], $adults, $children, $infants, $travelDate ?: null, $subtotal, $discount, $total, $currencyCode, $paymentMethod, $installments ?: null, $installmentAmount, $notes ?: null,
     $instPartnerId ?: $partnerId, $refCode, $source, $sourceDetail ?: null, $hasComorbid ? $comorbidity : null, json_encode($answers, JSON_UNESCAPED_UNICODE),
     $participants ? json_encode($participants, JSON_UNESCAPED_UNICODE) : null,
     $respName ?: null, $respCpf ?: null, $respPhone ?: null]
);

if ($couponIdToIncrement) dbExec("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?", [$couponIdToIncrement]);
foreach ($travelDates as $dt) {
    dbExec("UPDATE departures SET seats_sold = LEAST(seats_total, seats_sold + ?) WHERE entity_type=? AND entity_id=? AND departure_date=? AND status='open'", [$peopleTotal, $entityType, $entityId, $dt]);
}
if ($cartKey !== '' && isset($_SESSION['cart'][$cartKey])) {
    unset($_SESSION['cart'][$cartKey]);
}

$payment = prepareBookingPayment($bookingId);
sendBookingEmail($bookingId, 'booking_created');
notifyBookingEvent($bookingId, 'booking_created', ['source' => 'checkout']);

jsonResponse([
    'ok' => true,
    'msg' => 'Reserva criada com sucesso!',
    'booking' => ['id' => $bookingId, 'code' => $code, 'total' => $total],
    'payment' => $payment,
    'redirect' => url('/?booking=' . urlencode($code)),
]);
