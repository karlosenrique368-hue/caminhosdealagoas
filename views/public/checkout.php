<?php
$pageTitle = 'Reserva segura · Caminhos de Alagoas';
$solidNav  = true;
$macaiokMode = !empty($macaiokMode) || !empty($GLOBALS['macaiokMode']) || str_starts_with(currentPath(), '/macaiok');
if ($macaiokMode) $GLOBALS['macaiokMode'] = true;
$cartCheckoutBase = $macaiokMode ? '/macaiok/checkout' : '/checkout';

$roteiroId  = (int)($_GET['roteiro'] ?? 0);
$pacoteId   = (int)($_GET['pacote'] ?? 0);
$transferId = (int)($_GET['transfer'] ?? 0);
$cartKey = trim($_GET['cart_key'] ?? '');
$cartItem = ($cartKey !== '' && !empty($_SESSION['cart'][$cartKey])) ? $_SESSION['cart'][$cartKey] : null;
$item = null; $type = null;

if (!$roteiroId && !$pacoteId && !$transferId && $cartItem) {
    if (($cartItem['type'] ?? '') === 'roteiro') $roteiroId = (int)$cartItem['id'];
    elseif (($cartItem['type'] ?? '') === 'pacote') $pacoteId = (int)$cartItem['id'];
    elseif (($cartItem['type'] ?? '') === 'transfer') $transferId = (int)$cartItem['id'];
}

if (!$roteiroId && !$pacoteId && !$transferId && ($_GET['cart'] ?? '') === '1' && !empty($_SESSION['cart']) && count($_SESSION['cart']) > 1) {
    include VIEWS_DIR . '/partials/public_head.php';
    $cartRows = [];
    foreach ($_SESSION['cart'] as $key => $ci) {
        $row = null;
        if (($ci['type'] ?? '') === 'roteiro') $row = dbOne("SELECT id,title,slug,cover_image,price,price_pix,location FROM roteiros WHERE id=? AND status='published'", [(int)$ci['id']]);
        elseif (($ci['type'] ?? '') === 'pacote') $row = dbOne("SELECT id,title,slug,cover_image,price,price_pix,destination AS location FROM pacotes WHERE id=? AND status='published'", [(int)$ci['id']]);
        elseif (($ci['type'] ?? '') === 'transfer') $row = dbOne("SELECT id,title,slug,cover_image,price,price_pix,location_to AS location FROM transfers WHERE id=? AND status='published'", [(int)$ci['id']]);
        if (!$row) continue;
        $dates = !empty($ci['travel_dates']) && is_array($ci['travel_dates']) ? array_values($ci['travel_dates']) : (!empty($ci['travel_date']) ? [$ci['travel_date']] : []);
        $qty = max(1, (int)($ci['qty'] ?? 1));
        $dateCount = max(1, count($dates));
        $unitPrice = (float)($row['price_pix'] ?: $row['price']);
        $subtotal = $unitPrice * $qty * $dateCount;
        $query = ['cart_key' => $key, $ci['type'] => (int)$row['id']];
        if ($dates) $query['dates'] = implode(',', $dates);
        $cartRows[] = ['key'=>$key, 'type'=>$ci['type'], 'row'=>$row, 'dates'=>$dates, 'qty'=>$qty, 'date_count'=>$dateCount, 'subtotal'=>$subtotal, 'checkout'=>url($cartCheckoutBase . '?' . http_build_query($query))];
    }
    ?>
    <section class="pt-32 pb-16" style="background:var(--bg-surface)">
        <div class="max-w-5xl mx-auto px-6">
            <div class="mb-8 text-center">
                <span class="text-xs font-bold uppercase tracking-[0.24em]" style="color:var(--terracota)">Carrinho</span>
                <h1 class="font-display text-4xl sm:text-5xl font-bold mt-2" style="color:var(--sepia)">Escolha o item da reserva</h1>
                <p class="mt-3 text-sm" style="color:var(--text-secondary)">Cada passeio, pacote ou transfer precisa ser confirmado com suas próprias datas e dados.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-5">
                <?php foreach ($cartRows as $cart): $row = $cart['row']; ?>
                    <a href="<?= e($cart['checkout']) ?>" class="admin-card p-4 flex gap-4 hover:-translate-y-1 transition group">
                        <div class="w-28 sm:w-32 aspect-[4/3] rounded-xl overflow-hidden flex-shrink-0" style="background:var(--bg-surface)">
                            <?php if (!empty($row['cover_image'])): ?><img src="<?= e(storageUrl($row['cover_image'])) ?>" class="w-full h-full object-cover" alt="<?= e($row['title']) ?>"><?php endif; ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--terracota)"><?= $cart['type'] === 'roteiro' ? 'Passeio' : ($cart['type'] === 'pacote' ? 'Pacote' : 'Transfer') ?></div>
                            <h2 class="font-display text-lg font-bold line-clamp-2" style="color:var(--sepia)"><?= e($row['title']) ?></h2>
                            <?php if ($cart['dates']): ?><div class="text-xs mt-2 font-semibold" style="color:var(--horizonte)"><i data-lucide="calendar" class="w-3.5 h-3.5 inline -mt-0.5"></i> <?= e(implode(', ', array_map(fn($d) => date('d/m/Y', strtotime($d)), $cart['dates']))) ?></div><?php endif; ?>
                            <div class="text-[11px] mt-2" style="color:var(--text-muted)"><?= (int)$cart['qty'] ?> <?= (int)$cart['qty'] === 1 ? 'item' : 'itens' ?><?= (int)$cart['date_count'] > 1 ? ' · ' . (int)$cart['date_count'] . ' datas' : '' ?></div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted)">Total do item</div>
                                    <strong class="font-display text-xl" style="color:var(--terracota)"><?= formatPrice($cart['subtotal']) ?></strong>
                                </div>
                                <span class="w-10 h-10 rounded-full flex items-center justify-center transition group-hover:text-white" style="background:var(--bg-surface);color:var(--terracota)"><i data-lucide="arrow-right" class="w-4 h-4"></i></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php include VIEWS_DIR . '/partials/public_foot.php'; return;
}

// Fallback: pega o primeiro item do carrinho
if (!$roteiroId && !$pacoteId && !$transferId && !empty($_SESSION['cart'])) {
    $first = reset($_SESSION['cart']);
    if ($first['type'] === 'roteiro') $roteiroId = (int)$first['id'];
    elseif ($first['type'] === 'pacote') $pacoteId = (int)$first['id'];
    elseif ($first['type'] === 'transfer') $transferId = (int)$first['id'];
}
if ($roteiroId)       { $item = dbOne("SELECT * FROM roteiros  WHERE id=? AND status='published'", [$roteiroId]);  $type = 'roteiro';  }
elseif ($pacoteId)    { $item = dbOne("SELECT * FROM pacotes   WHERE id=? AND status='published'", [$pacoteId]);   $type = 'pacote';   }
elseif ($transferId)  { $item = dbOne("SELECT * FROM transfers WHERE id=? AND status='published'", [$transferId]); $type = 'transfer'; }
if (!$item) { redirect($macaiokMode ? '/macaiok' : '/passeios'); }
$typeLabel = $type === 'roteiro' ? 'Passeio' : ($type === 'pacote' ? 'Pacote' : 'Transfer');

$incomingPartner = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)($_GET['parceiro'] ?? '')));
$refCode    = currentReferralCode();
$refPartner = $refCode ? partnerByCode($refCode) : null;
if (!$macaiokMode && $refPartner && ($refPartner['program'] ?? '') === 'macaiok') {
    $refCode = '';
    $refPartner = null;
}
if ($incomingPartner !== '') {
    $incoming = partnerByCode($incomingPartner);
    if (!$incoming && ctype_digit($incomingPartner)) {
        $incoming = dbOne('SELECT * FROM institutions WHERE id=? AND active=1 LIMIT 1', [(int)$incomingPartner]);
    }
    if ($incoming) {
        $incomingProgram = (string)($incoming['program'] ?? 'parceiros');
        if ($macaiokMode ? $incomingProgram === 'macaiok' : $incomingProgram !== 'macaiok') {
            $refPartner = $incoming;
            $refCode = (string)$incoming['referral_code'];
            if ($refCode !== '') trackReferral($refCode);
        }
    }
}

// Pré-seleção (vem do calendário ou do carrinho)
$preDate     = $_GET['date']     ?? '';
$preDatesRaw = $_GET['dates']    ?? '';
$preDates    = [];
if ($preDatesRaw) {
    $decoded = json_decode($preDatesRaw, true);
    $rawList = is_array($decoded) ? $decoded : preg_split('/[,;\s]+/', $preDatesRaw);
    foreach ($rawList as $dt) {
        $dt = trim((string)$dt);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) $preDates[] = $dt;
    }
}
if ($preDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $preDate)) $preDates[] = $preDate;
$preAdults   = max(1, (int)($_GET['adults']   ?? 1));
$preChildren = max(0, (int)($_GET['children'] ?? 0));
$preInfants  = max(0, (int)($_GET['infants']  ?? 0));

// Se não veio data via GET, tenta puxar do item correto do carrinho
if (!$preDates && $cartItem) {
    if (!empty($cartItem['travel_dates']) && is_array($cartItem['travel_dates'])) $preDates = $cartItem['travel_dates'];
    elseif (!empty($cartItem['travel_date'])) $preDates = [$cartItem['travel_date']];
}
if (!$preDates && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $ci) {
        if (($ci['type'] ?? '') !== $type || (int)($ci['id'] ?? 0) !== (int)$item['id']) continue;
        if (!empty($ci['travel_dates']) && is_array($ci['travel_dates'])) { $preDates = $ci['travel_dates']; break; }
        if (!empty($ci['travel_date'])) { $preDates = [$ci['travel_date']]; break; }
    }
}
$preDates = array_values(array_unique(array_filter($preDates, fn($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d))));
sort($preDates);
$preDate = $preDates[0] ?? '';

// Moeda escolhida pelo usuário (sessão/cookie) + faixas etárias
$currencyCode   = currentCurrency();
$currencyMeta   = I18N_SUPPORTED_CURRENCIES[$currencyCode] ?? I18N_SUPPORTED_CURRENCIES['BRL'];
$currencySymbol = $currencyMeta['symbol'];
$currencyLocale = in_array($currencyCode, ['BRL','EUR']) ? 'pt-BR' : 'en-US';
$currencyRate   = currencyRates()[$currencyCode] ?? 1.0;
$factorChild    = (float) getSetting('price_factor_child',  '0.5');
$factorInfant   = (float) getSetting('price_factor_infant', '0');

// Preço cheio + PIX + faixas (sempre em BRL na DB; converte para a moeda do user)
$priceAdult  = convertPrice((float)$item['price'],  $currencyCode);
$pricePix    = convertPrice((float)($item['price_pix'] ?? $item['price']), $currencyCode);
$priceChildBRL  = isset($item['price_children']) && $item['price_children'] !== null
                ? (float)$item['price_children'] : round((float)$item['price'] * $factorChild, 2);
$priceInfantBRL = isset($item['price_infant']) && $item['price_infant'] !== null
                ? (float)$item['price_infant']   : round((float)$item['price'] * $factorInfant, 2);
$priceChild  = convertPrice($priceChildBRL,  $currencyCode);
$priceInfant = convertPrice($priceInfantBRL, $currencyCode);

$priceChildPix  = $pricePix > 0 && $priceAdult > 0 ? round($priceChild  * ($pricePix / $priceAdult), 2) : $priceChild;
$priceInfantPix = $pricePix > 0 && $priceAdult > 0 ? round($priceInfant * ($pricePix / $priceAdult), 2) : $priceInfant;

// PIX parcelado: calcular meses entre hoje e (viagem - 7 dias)
$pixInstallEnabled = getSetting('pix_installments_enabled', '1') === '1';
$pixInstallMinDays = (int) getSetting('pix_installments_min_days', '7');
$pixInstallMax     = max(1, (int) getSetting('pix_installments_max', '12'));

// Disponibilidade para mini-calendário do checkout
$departuresAll = dbAll("SELECT * FROM departures WHERE entity_type=? AND entity_id=? AND departure_date>=CURDATE() ORDER BY departure_date", [$type, $item['id']]);
$availabilityMap = [];
foreach ($departuresAll as $d) {
    $availabilityMap[$d['departure_date']] = [
        'status' => $d['status'],
        'seats'  => max(0, (int)$d['seats_total'] - (int)$d['seats_sold']),
        'price'  => $d['price_override'] !== null ? (float)$d['price_override'] : (float)($item['price_pix'] ?: $item['price']),
        'time'   => $d['departure_time'],
    ];
}
$availabilityMode = $type === 'transfer' ? 'open' : ($item['availability_mode'] ?? 'fixed');
if (!in_array($availabilityMode, ['fixed','open','on_request'], true)) $availabilityMode = 'fixed';

include VIEWS_DIR . '/partials/public_head.php';
?>

<?php if (!empty($macaiokMode)): ?>
<div class="pt-24 pb-2" style="background:linear-gradient(180deg,#324500 0%, #2F1607 100%)">
    <div class="max-w-6xl mx-auto px-6 flex items-center gap-3 flex-wrap">
        <img src="<?= asset('img/macaiok/VerdeEscuro_Horizontal.png') ?>" alt="Macaiok" class="h-8" style="filter:brightness(0) invert(1)">
        <span class="text-[11px] font-bold uppercase tracking-[0.24em] text-white/90">Vivencias pedagogicas - Reserva pelo responsavel</span>
        <span class="ml-auto text-[11px] text-white/70">Pagamento processado por <strong>Caminhos de Alagoas</strong></span>
    </div>
</div>
<?php endif; ?>

<style>
.wiz-step-dot{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;transition:all .35s cubic-bezier(.4,0,.2,1);flex-shrink:0;border:2px solid var(--border-default);background:var(--bg-card);color:var(--text-muted)}
.wiz-step-dot.done{background:var(--maresia);border-color:var(--maresia);color:#fff}
.wiz-step-dot.active{background:var(--terracota);border-color:var(--terracota);color:#fff;box-shadow:0 0 0 6px rgba(201,107,74,.15);transform:scale(1.08)}
.wiz-step-bar{flex:1;height:3px;background:var(--border-default);position:relative;border-radius:3px;overflow:hidden}
.wiz-step-bar::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,var(--maresia),var(--terracota));transform:scaleX(0);transform-origin:left;transition:transform .5s ease}
.wiz-step-bar.filled::after{transform:scaleX(1)}
.wiz-radio{display:block;cursor:pointer;transition:all .2s}
.wiz-radio-inner{padding:16px;border-radius:12px;border:2px solid var(--border-default);background:var(--bg-card);transition:all .2s;display:flex;align-items:center;gap:12px}
.wiz-radio.active .wiz-radio-inner{border-color:var(--terracota);background:linear-gradient(135deg,rgba(201,107,74,.06),rgba(201,107,74,.02));box-shadow:0 6px 20px rgba(201,107,74,.12)}
.wiz-radio .wiz-radio-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--bg-surface);color:var(--terracota);flex-shrink:0;transition:all .2s}
.wiz-radio.active .wiz-radio-icon{background:var(--terracota);color:#fff}
.wiz-chip{padding:10px 14px;border-radius:10px;border:2px solid var(--border-default);background:var(--bg-card);cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px;font-weight:600;font-size:13px;color:var(--text-secondary)}
.wiz-chip.active{border-color:var(--terracota);background:rgba(201,107,74,.08);color:var(--terracota)}
.wiz-chip:hover{border-color:var(--text-muted)}
.wiz-card{animation:wizFade .4s ease}
@keyframes wizFade{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.qty-stepper{display:flex;align-items:center;gap:0;border:1.5px solid var(--border-default);border-radius:12px;background:var(--bg-card);overflow:hidden}
.qty-stepper button{width:42px;height:42px;font-size:18px;font-weight:700;color:var(--terracota);transition:all .15s}
.qty-stepper button:hover:not(:disabled){background:rgba(201,107,74,.08)}
.qty-stepper button:disabled{opacity:.3;cursor:not-allowed}
.qty-stepper input{flex:1;text-align:center;border:0;background:transparent;font-weight:700;font-size:15px;color:var(--sepia);outline:none;min-width:42px}
.tier-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px;border-radius:12px;border:1.5px solid var(--border-default);background:var(--bg-card)}
.lot-card{position:relative;padding:18px;border-radius:14px;border:2px solid var(--border-default);background:var(--bg-card);cursor:pointer;transition:all .25s}
.lot-card.active{border-color:var(--terracota);background:linear-gradient(135deg,#fff,rgba(201,107,74,.04));box-shadow:0 10px 30px rgba(201,107,74,.15)}
.lot-ribbon{position:absolute;top:-10px;right:14px;background:linear-gradient(135deg,var(--maresia),var(--maresia-dark));color:#fff;padding:4px 12px;border-radius:999px;font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase}
.installment-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;margin-top:14px}
.installment-pill{padding:10px 12px;border-radius:10px;border:2px solid var(--border-default);background:var(--bg-card);cursor:pointer;text-align:center;transition:all .2s;font-size:12px;color:var(--text-secondary);font-weight:600}
.installment-pill.active{border-color:var(--terracota);background:rgba(201,107,74,.08);color:var(--terracota)}
.installment-pill .ip-num{font-family:var(--font-display);font-size:16px;font-weight:800;display:block;color:inherit}
.summary-drawer{transition:all .3s}
@media (max-width:1023px){.summary-drawer{position:fixed;bottom:0;left:0;right:0;z-index:30;border-radius:20px 20px 0 0;max-height:80vh;overflow-y:auto;box-shadow:0 -8px 40px rgba(0,0,0,.15)}}
</style>

<section class="pt-28 pb-16" style="background:linear-gradient(180deg,var(--bg-surface) 0%,var(--bg-page) 100%);min-height:100vh">
<div class="max-w-6xl mx-auto px-4 sm:px-6" x-data="checkoutWizard()" x-init="init()">

    <a href="<?= url($macaiokMode ? '/macaiok' : ($type==='roteiro' ? '/passeios' : ($type==='pacote' ? '/pacotes' : '/transfers'))) ?>" class="inline-flex items-center gap-1 text-sm mb-4" style="color:var(--horizonte)">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Voltar ao catálogo
    </a>

    <div class="mb-8">
        <h1 class="font-display text-3xl sm:text-4xl font-bold" style="color:var(--sepia)">Vamos garantir sua vaga</h1>
        <p class="text-sm sm:text-base mt-1" style="color:var(--text-secondary)">Em <b>4 passos</b> você finaliza sua reserva. Seus dados ficam seguros.</p>
    </div>

    <div class="flex items-center gap-2 sm:gap-3 mb-8">
        <template x-for="(s, idx) in steps" :key="idx">
            <div class="flex items-center flex-1 last:flex-initial gap-2 sm:gap-3">
                <div class="wiz-step-dot" :class="idx < step ? 'done' : idx === step ? 'active' : ''">
                    <i x-show="idx < step" data-lucide="check" class="w-5 h-5"></i>
                    <span x-show="idx >= step" x-text="idx + 1"></span>
                </div>
                <div class="hidden sm:block flex-1">
                    <div class="text-[10px] uppercase tracking-wider font-bold" :style="idx <= step ? 'color:var(--terracota)' : 'color:var(--text-muted)'" x-text="'Passo ' + (idx+1)"></div>
                    <div class="text-xs font-semibold" :style="idx <= step ? 'color:var(--sepia)' : 'color:var(--text-muted)'" x-text="s"></div>
                </div>
                <div class="wiz-step-bar" :class="idx < step ? 'filled' : ''" x-show="idx < steps.length - 1"></div>
            </div>
        </template>
    </div>

    <div class="grid lg:grid-cols-[1fr_380px] gap-6">
        <div class="admin-card p-6 sm:p-8">

            <!-- STEP 0: Seus dados -->
            <div class="wiz-card" x-show="step === 0" x-cloak>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:rgba(201,107,74,.1);color:var(--terracota)"><i data-lucide="user-circle-2" class="w-6 h-6"></i></div>
                    <div><div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Passo 1 de 4</div><h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Seus dados</h2></div>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2"><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">Nome completo *</label><input x-model="form.name" class="admin-input w-full" placeholder="Como está no documento"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">CPF *</label><input x-model="form.document" @input="form.document = maskCPF($event.target.value)" maxlength="14" class="admin-input w-full" placeholder="000.000.000-00"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">RG *</label><input x-model="form.rg" @input="form.rg = maskRG($event.target.value)" maxlength="14" class="admin-input w-full" placeholder="00.000.000-0"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">Data de nascimento *</label><?php $dobModel='form.birth_date'; include __DIR__.'/../partials/dob_picker.php'; ?></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">E-mail *</label><input type="email" x-model="form.email" class="admin-input w-full" placeholder="voce@email.com"></div>
                    <div class="sm:col-span-2"><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">WhatsApp *</label><input type="tel" x-model="form.phone" @input="form.phone = maskPhone($event.target.value)" maxlength="15" class="admin-input w-full" placeholder="(00) 00000-0000"></div>
                </div>
                <div class="mt-4 p-3 rounded-xl flex items-start gap-2" style="background:rgba(122,157,110,.08);border:1px solid rgba(122,157,110,.2)">
                    <i data-lucide="shield-check" class="w-4 h-4 mt-0.5" style="color:var(--maresia-dark)"></i>
                    <span class="text-xs" style="color:var(--maresia-dark)">Criptografia SSL. Seus dados nunca são compartilhados.</span>
                </div>
            </div>

            <!-- STEP 1: Sua viagem -->
            <div class="wiz-card" x-show="step === 1" x-cloak>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:rgba(58,107,138,.1);color:var(--horizonte)"><i data-lucide="map-pin" class="w-6 h-6"></i></div>
                    <div><div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Passo 2 de 4</div><h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Sua viagem</h2></div>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider" style="color:var(--text-secondary)">Datas disponíveis *</label>
                            <p class="text-[11px] mt-1" style="color:var(--text-muted)">Escolha uma ou várias datas. O total atualiza pelo número de dias selecionados.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click.stop="prevYear()" class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)" aria-label="Ano anterior"><i data-lucide="chevrons-left" class="w-4 h-4"></i></button>
                            <button type="button" @click="prevMonth()" class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                            <div class="min-w-[118px] sm:min-w-[150px] text-center font-display font-bold text-sm" style="color:var(--sepia)" x-text="monthLabel"></div>
                            <button type="button" @click="nextMonth()" class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                            <button type="button" @click.stop="nextYear()" class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)" aria-label="Próximo ano"><i data-lucide="chevrons-right" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mb-4 text-xs" style="color:var(--text-muted)">
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background:var(--maresia)"></span> Disponível</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background:#F59E0B"></span> Últimas vagas</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background:var(--terracota)"></span> Selecionado</span>
                    </div>
                    <div class="calendar-grid">
                        <template x-for="dow in ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb']" :key="dow">
                            <div class="text-center text-[11px] font-bold uppercase tracking-wider py-2" style="color:var(--text-muted)" x-text="dow"></div>
                        </template>
                        <template x-for="cell in cells" :key="cell.key">
                            <button type="button" :disabled="!cell.available" @click="toggleDate(cell)" class="calendar-cell" :class="{'empty':cell.empty,'available':cell.available&&!cell.lowSeats,'low':cell.available&&cell.lowSeats,'blocked':cell.blocked,'selected':form.travel_dates.includes(cell.iso)}">
                                <span class="cal-day" x-text="cell.day"></span>
                                <span class="cal-price" x-show="cell.available" x-text="cell.seats !== null ? cell.seats + ' vagas' : 'Livre'"></span>
                            </button>
                        </template>
                    </div>
                    <div x-show="form.travel_dates.length" x-cloak class="mt-4 p-4 rounded-xl" style="background:rgba(201,107,74,.08);border:1px solid rgba(201,107,74,.25)">
                        <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:var(--terracota)" x-text="selectedDatesCountLabel()"></div>
                        <div class="text-sm font-semibold" style="color:var(--sepia)" x-text="selectedDatesText()"></div>
                        <p class="text-[11px] mt-1" style="color:var(--text-muted)" x-show="form.travel_date"><i data-lucide="info" class="w-3 h-3 inline -mt-0.5"></i> <span x-text="datePreviewLabel()"></span></p>
                    </div>
                    <div x-show="!cells.some(c => c.available) && availabilityMode !== 'on_request'" class="mt-4 p-4 rounded-xl text-center text-sm" style="background:var(--bg-surface);color:var(--text-muted)">Sem datas disponíveis neste mês. Avance para o próximo mês.</div>
                    <div x-show="availabilityMode === 'on_request'" class="mt-4 p-4 rounded-xl text-center text-sm" style="background:rgba(58,107,138,.08);color:var(--horizonte)">Esta experiência está sob consulta. Fale com a equipe para combinar a data.</div>
                </div>

                <label class="block text-xs font-bold uppercase tracking-wider mb-2" style="color:var(--text-secondary)">Quantas pessoas? *</label>
                <div class="space-y-3 mb-6">
                    <div class="tier-row">
                        <div>
                            <div class="font-semibold text-sm" style="color:var(--sepia)">Adultos</div>
                            <div class="text-[11px]" style="color:var(--text-muted)" x-text="isTransfer ? formatBRL(adultUnit()) + ' por veículo' : formatBRL(adultUnit()) + ' por pessoa'"></div>
                        </div>
                        <div class="qty-stepper">
                            <button type="button" @click="form.adults = Math.max(1, form.adults-1)" :disabled="form.adults <= 1">−</button>
                            <input type="number" min="1" max="20" x-model.number="form.adults">
                            <button type="button" @click="form.adults = Math.min(20, form.adults+1)" :disabled="form.adults >= 20">+</button>
                        </div>
                    </div>
                    <div class="tier-row">
                        <div>
                            <div class="font-semibold text-sm" style="color:var(--sepia)">Crianças <span class="font-normal" style="color:var(--text-muted)">(6 a 12 anos)</span></div>
                            <div class="text-[11px]" style="color:var(--text-muted)" x-text="isTransfer ? 'Não altera o valor' : (priceChild > 0 ? formatBRL(childUnit()) + ' por criança' : 'Cortesia')"></div>
                        </div>
                        <div class="qty-stepper">
                            <button type="button" @click="form.children = Math.max(0, form.children-1)" :disabled="form.children <= 0">−</button>
                            <input type="number" min="0" max="20" x-model.number="form.children">
                            <button type="button" @click="form.children = Math.min(20, form.children+1)" :disabled="form.children >= 20">+</button>
                        </div>
                    </div>
                    <div class="tier-row">
                        <div>
                            <div class="font-semibold text-sm" style="color:var(--sepia)">Bebês <span class="font-normal" style="color:var(--text-muted)">(0 a 5 anos)</span></div>
                            <div class="text-[11px]" style="color:var(--text-muted)" x-text="isTransfer ? 'Não altera o valor' : (priceInfant > 0 ? formatBRL(infantUnit()) + ' por bebê' : 'Cortesia')"></div>
                        </div>
                        <div class="qty-stepper">
                            <button type="button" @click="form.infants = Math.max(0, form.infants-1)" :disabled="form.infants <= 0">−</button>
                            <input type="number" min="0" max="10" x-model.number="form.infants">
                            <button type="button" @click="form.infants = Math.min(10, form.infants+1)" :disabled="form.infants >= 10">+</button>
                        </div>
                    </div>
                </div>
                <div x-show="isTransfer && peopleTotal() > maxPeople" x-cloak class="mb-6 p-3 rounded-xl text-sm" style="background:rgba(239,68,68,.08);color:#B91C1C;border:1px solid rgba(239,68,68,.2)">
                    Capacidade máxima deste veículo: <b x-text="maxPeople"></b> passageiros.
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2" style="color:var(--text-secondary)">Alguém do grupo tem comorbidade? *</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="form.has_comorbidity='nao'; form.comorbidity=''" class="wiz-chip justify-center" :class="form.has_comorbidity==='nao'?'active':''"><i data-lucide="check-circle" class="w-4 h-4"></i>Não</button>
                        <button type="button" @click="form.has_comorbidity='sim'" class="wiz-chip justify-center" :class="form.has_comorbidity==='sim'?'active':''"><i data-lucide="heart-pulse" class="w-4 h-4"></i>Sim</button>
                    </div>
                    <div x-show="form.has_comorbidity==='sim'" x-transition class="mt-3" x-cloak>
                        <textarea x-model="form.comorbidity" rows="2" class="admin-input w-full" placeholder="Descreva brevemente para cuidarmos melhor de você."></textarea>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2" style="color:var(--text-secondary)">Como você nos conheceu?</label>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                        <template x-for="s in sources" :key="s.id">
                            <button type="button" @click="form.source=s.id" class="wiz-chip justify-center flex-col py-3" :class="form.source===s.id?'active':''">
                                <i :data-lucide="s.icon" class="w-4 h-4"></i>
                                <span x-text="s.label" class="text-[11px]"></span>
                            </button>
                        </template>
                    </div>
                    <div x-show="form.source" x-transition class="mt-3" x-cloak>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)" x-text="sourceDetailLabel()"></label>
                        <input x-model="form.source_detail" class="admin-input w-full" :placeholder="sourceDetailPlaceholder()">
                        <?php if ($refPartner): ?>
                            <p class="text-xs mt-1 flex items-center gap-1" style="color:var(--maresia-dark)"><i data-lucide="check-circle-2" class="w-3 h-3"></i> Indicação registrada automaticamente via <b><?= e($refPartner['name']) ?></b>.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Pagamento -->
            <div class="wiz-card" x-show="step === 2" x-cloak>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:rgba(122,157,110,.1);color:var(--maresia-dark)"><i data-lucide="wallet" class="w-6 h-6"></i></div>
                    <div><div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Passo 3 de 4</div><h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Como prefere pagar?</h2></div>
                </div>

                <div class="grid sm:grid-cols-2 gap-3 mb-4">
                    <template x-for="pm in paymentMethods" :key="pm.id">
                        <label class="wiz-radio" :class="form.payment_method===pm.id?'active':''" x-show="!pm.requiresInstallment || canInstallment()">
                            <input type="radio" x-model="form.payment_method" :value="pm.id" class="sr-only">
                            <div class="wiz-radio-inner">
                                <div class="wiz-radio-icon"><i :data-lucide="pm.icon" class="w-5 h-5"></i></div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm flex items-center gap-2" style="color:var(--sepia)"><span x-text="pm.label"></span><span x-show="pm.badge" class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:var(--maresia);color:#fff" x-text="pm.badge"></span></div>
                                    <div class="text-[11px]" style="color:var(--text-muted)" x-text="pm.hint"></div>
                                </div>
                            </div>
                        </label>
                    </template>
                </div>

                <?php if (!IS_PRODUCTION): ?>
                <div class="mb-4 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="background:rgba(0,113,206,.07);border:1px solid rgba(0,113,206,.18)">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#fff;color:#0071CE"><i data-lucide="radio" class="w-5 h-5"></i></div>
                        <div>
                            <div class="font-semibold text-sm" style="color:var(--sepia)">Teste local do webhook Mercado Pago</div>
                            <div class="text-[11px]" style="color:var(--text-muted)">Envia um evento aprovado para a última reserva pendente.</div>
                        </div>
                    </div>
                    <button type="button" @click="testWebhook()" class="admin-btn admin-btn-secondary justify-center" :disabled="webhookTesting" :class="webhookTesting && 'opacity-60'">
                        <i data-lucide="send-horizontal" class="w-4 h-4"></i><span x-text="webhookTesting ? 'Enviando...' : 'Enviar webhook'"></span>
                    </button>
                </div>
                <?php endif; ?>

                <!-- PIX à vista: lotes -->
                <div x-show="form.payment_method==='pix'" x-transition>
                    <div class="text-[10px] uppercase tracking-widest font-bold mt-5 mb-3" style="color:var(--terracota)">Selecione o lote</div>
                    <div class="grid gap-3">
                        <label class="lot-card" :class="form.price_option==='promo'?'active':''" x-show="pricePix > 0 && pricePix < priceAdult">
                            <span class="lot-ribbon">Econômico · PIX</span>
                            <input type="radio" x-model="form.price_option" value="promo" class="sr-only">
                            <div class="flex items-center justify-between gap-3">
                                <div><div class="font-semibold" style="color:var(--sepia)">Lote promocional</div><div class="text-xs mt-1" style="color:var(--text-muted)">Pagamento PIX instantâneo, hoje.</div></div>
                                <div class="text-right"><div class="font-display text-2xl font-bold" style="color:var(--terracota)" x-text="formatBRL(pricePix)"></div><div class="text-[10px]" style="color:var(--text-muted)">por adulto</div></div>
                            </div>
                        </label>
                        <label class="lot-card" :class="form.price_option==='regular'?'active':''">
                            <input type="radio" x-model="form.price_option" value="regular" class="sr-only">
                            <div class="flex items-center justify-between gap-3">
                                <div><div class="font-semibold" style="color:var(--sepia)">Lote regular</div><div class="text-xs mt-1" style="color:var(--text-muted)">Preço cheio sem desconto.</div></div>
                                <div class="text-right"><div class="font-display text-2xl font-bold" style="color:var(--sepia)" x-text="formatBRL(priceAdult)"></div><div class="text-[10px]" style="color:var(--text-muted)">por adulto</div></div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- PIX parcelado -->
                <div x-show="form.payment_method==='pix_installments'" x-transition>
                    <div class="mt-5 p-4 rounded-xl" style="background:rgba(58,107,138,.06);border:1px solid rgba(58,107,138,.18)">
                        <div class="flex items-start gap-3 mb-3">
                            <i data-lucide="calendar-clock" class="w-5 h-5 mt-0.5" style="color:var(--horizonte)"></i>
                            <div>
                                <div class="font-semibold text-sm" style="color:var(--horizonte)">PIX parcelado mensal</div>
                                <div class="text-[12px]" style="color:var(--text-secondary)">Quitação total <b>até <?= (int)$pixInstallMinDays ?> dias antes</b> da viagem. Parcelamos conforme a margem disponível.</div>
                            </div>
                        </div>
                        <div x-show="canInstallment()">
                            <div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--terracota)" x-text="'Quantas parcelas? (até ' + maxInstallments() + 'x disponíveis)'"></div>
                            <div class="installment-grid">
                                <template x-for="n in installmentOptions()" :key="n">
                                    <button type="button" @click="form.installments=n" class="installment-pill" :class="form.installments===n?'active':''">
                                        <span class="ip-num" x-text="n + 'x'"></span>
                                        <span x-text="formatBRL(installmentValue(n))"></span>
                                    </button>
                                </template>
                            </div>
                            <div class="mt-3 text-[11px] flex items-center gap-1.5" style="color:var(--text-muted)">
                                <i data-lucide="info" class="w-3 h-3"></i>
                                <span>Sem juros · primeira parcela hoje · próximas no mesmo dia de cada mês até <span x-text="installmentDeadlineLabel()"></span>.</span>
                            </div>
                        </div>
                        <div x-show="!canInstallment()" class="text-xs p-3 rounded-lg" style="background:#FEF3C7;color:#92400E">
                            <i data-lucide="alert-triangle" class="w-4 h-4 inline -mt-0.5"></i>
                            Sua viagem está muito próxima. Para parcelar precisamos de pelo menos 1 mês de margem antes do limite de quitação. Escolha PIX à vista ou cartão.
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Revisão -->
            <div class="wiz-card" x-show="step === 3" x-cloak>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:rgba(201,107,74,.1);color:var(--terracota)"><i data-lucide="clipboard-check" class="w-6 h-6"></i></div>
                    <div><div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Passo 4 de 4</div><h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Revisar e confirmar</h2></div>
                </div>

                <div class="space-y-3 mb-5">
                    <div class="p-4 rounded-xl flex justify-between items-start gap-3" style="background:var(--bg-surface)">
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-bold mb-1" style="color:var(--text-muted)">Titular</div>
                            <div class="font-semibold text-sm" style="color:var(--sepia)" x-text="form.name || '—'"></div>
                            <div class="text-xs" style="color:var(--text-secondary)" x-text="(form.email || '—') + ' · ' + (form.phone || '—')"></div>
                            <div class="text-[11px] mt-1" style="color:var(--text-muted)" x-text="'CPF ' + (form.document || '—') + ' · RG ' + (form.rg || '—')"></div>
                        </div>
                        <button type="button" @click="step=0" class="text-xs font-semibold" style="color:var(--horizonte)">Editar</button>
                    </div>
                    <div class="p-4 rounded-xl flex justify-between items-start gap-3" style="background:var(--bg-surface)">
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-bold mb-1" style="color:var(--text-muted)">Viagem</div>
                            <div class="font-semibold text-sm" style="color:var(--sepia)" x-text="formatDate(form.travel_date)"></div>
                            <div class="text-xs" style="color:var(--text-secondary)" x-text="peopleSummary()"></div>
                            <div class="text-[11px] mt-1" style="color:var(--text-muted)" x-show="form.has_comorbidity==='sim'" x-text="'Comorbidade: ' + form.comorbidity"></div>
                            <div class="text-[11px] mt-1" style="color:var(--text-muted)" x-show="form.source" x-text="'Conheceu via: ' + sourceLabelById(form.source) + (form.source_detail ? ' — ' + form.source_detail : '')"></div>
                        </div>
                        <button type="button" @click="step=1" class="text-xs font-semibold" style="color:var(--horizonte)">Editar</button>
                    </div>
                    <div class="p-4 rounded-xl flex justify-between items-start gap-3" style="background:var(--bg-surface)">
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-bold mb-1" style="color:var(--text-muted)">Pagamento</div>
                            <div class="font-semibold text-sm" style="color:var(--sepia)" x-text="labelPayment()"></div>
                            <div class="text-xs" style="color:var(--text-secondary)" x-show="form.payment_method==='pix_installments' && form.installments" x-text="form.installments + 'x de ' + formatBRL(installmentValue(form.installments))"></div>
                        </div>
                        <button type="button" @click="step=2" class="text-xs font-semibold" style="color:var(--horizonte)">Editar</button>
                    </div>
                </div>

                <label class="flex items-start gap-3 p-4 rounded-xl cursor-pointer" :style="form.accept_terms ? 'background:rgba(122,157,110,.08);border:1.5px solid rgba(122,157,110,.4)' : 'background:var(--bg-surface);border:1.5px solid var(--border-default)'">
                    <input type="checkbox" x-model="form.accept_terms" class="mt-1 w-5 h-5" style="accent-color:var(--terracota)">
                    <span class="text-sm" style="color:var(--text-secondary)">
                        Li e concordo com a <a href="<?= url('/politica-desistencia') ?>" target="_blank" class="font-bold underline" style="color:var(--sepia)">política de desistência</a>: em caso de cancelamento, aplicam-se as condições de contrato. *
                    </span>
                </label>
            </div>

            <!-- NAV BUTTONS -->
            <div class="mt-8 pt-6 border-t flex items-center justify-between gap-3" style="border-color:var(--border-default)">
                <button type="button" @click="prev()" x-show="step > 0" class="admin-btn admin-btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i>Voltar</button>
                <div x-show="step === 0"></div>
                <button type="button" @click="next()" x-show="step < steps.length - 1" class="btn-primary" :disabled="!canProceed()" :class="!canProceed()&&'opacity-60 cursor-not-allowed'">
                    <span>Continuar</span><i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
                <button type="button" @click="submit()" x-show="step === steps.length - 1" class="btn-primary" :disabled="loading || !form.accept_terms" :class="(loading||!form.accept_terms)&&'opacity-60'">
                    <i data-lucide="lock" class="w-4 h-4"></i><span x-text="loading ? 'Processando...' : 'Confirmar reserva'"></span>
                </button>
            </div>
        </div>

        <!-- ========== SUMMARY ========== -->
        <aside class="summary-drawer">
            <div class="admin-card p-5 sm:p-6 lg:sticky lg:top-24">
                <div class="flex gap-3 mb-4">
                    <?php if ($item['cover_image']): ?>
                        <img src="<?= storageUrl($item['cover_image']) ?>" class="w-20 h-20 rounded-xl object-cover">
                    <?php else: ?>
                        <div class="w-20 h-20 rounded-xl img-placeholder"><span class="text-2xl"><?= e(mb_substr($item['title'],0,1)) ?></span></div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--terracota)"><?= e($typeLabel) ?></div>
                        <div class="font-semibold leading-snug" style="color:var(--sepia)"><?= e($item['title']) ?></div>
                        <?php if (!empty($item['duration'])): ?><div class="text-xs flex items-center gap-1 mt-1" style="color:var(--text-muted)"><i data-lucide="clock" class="w-3 h-3"></i><?= e($item['duration']) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="text-xs mb-3 p-2 rounded-lg" style="background:var(--bg-surface);color:var(--text-secondary)" x-show="form.travel_dates.length">
                    <i data-lucide="calendar" class="w-3 h-3 inline -mt-0.5"></i>
                    <span x-text="(form.travel_dates.length > 1 ? 'Datas: ' : 'Data: ') + selectedDatesText()"></span>
                </div>
                <div class="py-4 border-t border-b space-y-2" style="border-color:var(--border-default)">
                    <div class="flex justify-between text-sm" x-show="isTransfer"><span style="color:var(--text-secondary)" x-text="'Transfer' + (selectedDateCount()>1 ? ' × ' + selectedDateCount() + ' datas' : '')"></span><span style="color:var(--sepia)" x-text="formatBRL(adultUnit() * selectedDateCount())"></span></div>
                    <div class="flex justify-between text-sm" x-show="isTransfer"><span style="color:var(--text-secondary)">Passageiros</span><span style="color:var(--sepia)" x-text="peopleTotal()"></span></div>
                    <div class="flex justify-between text-sm" x-show="!isTransfer"><span style="color:var(--text-secondary)" x-text="'Adultos × ' + form.adults + (selectedDateCount()>1 ? ' × ' + selectedDateCount() + ' datas' : '')"></span><span style="color:var(--sepia)" x-text="formatBRL(adultUnit() * form.adults * selectedDateCount())"></span></div>
                    <div class="flex justify-between text-sm" x-show="!isTransfer && form.children > 0"><span style="color:var(--text-secondary)" x-text="'Crianças × ' + form.children + (selectedDateCount()>1 ? ' × ' + selectedDateCount() + ' datas' : '')"></span><span style="color:var(--sepia)" x-text="formatBRL(childUnit() * form.children * selectedDateCount())"></span></div>
                    <div class="flex justify-between text-sm" x-show="!isTransfer && form.infants > 0"><span style="color:var(--text-secondary)" x-text="'Bebês × ' + form.infants + (selectedDateCount()>1 ? ' × ' + selectedDateCount() + ' datas' : '')"></span><span style="color:var(--sepia)" x-text="form.infants && infantUnit()===0 ? 'Cortesia' : formatBRL(infantUnit() * form.infants * selectedDateCount())"></span></div>
                    <div class="flex justify-between text-sm" x-show="form.payment_method==='pix' && form.price_option==='promo' && pricePix < priceAdult"><span style="color:var(--maresia-dark)"><i data-lucide="zap" class="w-3 h-3 inline"></i> Desconto PIX</span><span class="font-semibold" style="color:var(--maresia-dark)" x-text="'− ' + formatBRL(discount())"></span></div>
                </div>
                <div class="flex justify-between items-end pt-4">
                    <span class="text-sm font-semibold" style="color:var(--text-secondary)">Total</span>
                    <span class="font-display text-3xl font-bold" style="color:var(--terracota)" x-text="formatBRL(total())"></span>
                </div>
                <!-- Destaque grande das parcelas PIX -->
                <div class="mt-3 p-3 rounded-xl" style="background:linear-gradient(135deg,rgba(58,107,138,.08),rgba(122,157,110,.06));border:1.5px solid rgba(58,107,138,.25)" x-show="form.payment_method==='pix_installments' && form.installments && canInstallment()" x-cloak>
                    <div class="flex items-center gap-2 mb-1">
                        <i data-lucide="calendar-clock" class="w-4 h-4" style="color:var(--horizonte)"></i>
                        <span class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--horizonte)">Você paga em</span>
                    </div>
                    <div class="font-display text-2xl font-bold leading-tight" style="color:var(--horizonte)">
                        <span x-text="form.installments"></span>×
                        <span x-text="formatBRL(installmentValue(form.installments))"></span>
                    </div>
                    <div class="text-[11px] mt-1" style="color:var(--text-muted)">Sem juros · 1ª hoje · última até <span x-text="installmentDeadlineLabel()"></span></div>
                </div>
                <?php if ($refPartner): ?>
                <div class="mt-4 p-3 rounded-lg text-xs flex items-start gap-2" style="background:rgba(201,107,74,.06);border:1px solid rgba(201,107,74,.2)">
                    <i data-lucide="handshake" class="w-4 h-4 mt-0.5" style="color:var(--terracota)"></i>
                    <div style="color:var(--text-secondary)">Reserva indicada por <b style="color:var(--sepia)"><?= e($refPartner['name']) ?></b>.</div>
                </div>
                <?php endif; ?>
                <div class="mt-4 text-[11px] flex items-center gap-1.5" style="color:var(--text-muted)">
                    <i data-lucide="lock" class="w-3 h-3"></i> Pagamento 100% seguro · ambiente criptografado
                </div>
            </div>
        </aside>
    </div>
</div>
</section>

<script>
function checkoutWizard() {
    return {
        step: 0,
        loading: false,
        webhookTesting: false,
        today: new Date().toISOString().split('T')[0],
        steps: ['Seus dados', 'Sua viagem', 'Pagamento', 'Revisão'],
        priceAdult:  <?= json_encode($priceAdult) ?>,
        pricePix:    <?= json_encode($pricePix) ?>,
        priceChild:  <?= json_encode($priceChild) ?>,
        priceInfant: <?= json_encode($priceInfant) ?>,
        priceChildPix:  <?= json_encode($priceChildPix) ?>,
        priceInfantPix: <?= json_encode($priceInfantPix) ?>,
        currencySymbol: <?= json_encode($currencySymbol) ?>,
        currencyLocale: <?= json_encode($currencyLocale) ?>,
        currencyCode:   <?= json_encode($currencyCode) ?>,
        isTransfer:     <?= $type === 'transfer' ? 'true' : 'false' ?>,
        maxPeople:      <?= (int)($item['capacity'] ?? 20) ?>,
        installMinDays: <?= (int)$pixInstallMinDays ?>,
        installMax:     <?= (int)$pixInstallMax ?>,
        availabilityMode: <?= json_encode($availabilityMode) ?>,
        availabilityMap: <?= json_encode($availabilityMap, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
        viewYear: new Date().getFullYear(),
        viewMonth: new Date().getMonth(),
        form: {
            name:'', document:'', rg:'', birth_date:'', email:'', phone:'',
            travel_date: <?= json_encode($preDate) ?>,
            travel_dates: <?= json_encode($preDates, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,
            adults:   <?= (int)$preAdults ?>,
            children: <?= (int)$preChildren ?>,
            infants:  <?= (int)$preInfants ?>,
            has_comorbidity:'nao', comorbidity:'',
            source:'', source_detail: <?= json_encode($refPartner['name'] ?? '') ?>,
            payment_method:'pix', price_option:'promo', installments: 0,
            accept_terms:false,
            ref_code:    <?= json_encode($refCode ?? '') ?>,
            institution_partner_id: <?= (int)($refPartner['id'] ?? 0) ?>,
            entity_type: <?= json_encode($type) ?>,
            entity_id:   <?= (int)$item['id'] ?>,
            currency:    <?= json_encode($currencyCode) ?>,
            cart_key:    <?= json_encode($cartKey) ?>
        },
        sources: [
            {id:'instagram', label:'Instagram',  icon:'instagram',         detailLabel:'Qual perfil ou post?',     placeholder:'@perfil ou link do post'},
            {id:'whatsapp',  label:'WhatsApp',   icon:'message-circle',    detailLabel:'Quem te encaminhou?',      placeholder:'Nome ou número'},
            {id:'indicacao', label:'Indicação',  icon:'user-check',        detailLabel:'Quem indicou? *',          placeholder:'Nome de quem indicou'},
            {id:'google',    label:'Google',     icon:'search',            detailLabel:'O que pesquisou?',         placeholder:'Termo da busca'},
            {id:'outro',     label:'Outro',      icon:'more-horizontal',   detailLabel:'Conta pra gente:',         placeholder:'TikTok, blog, anúncio...'}
        ],
        paymentMethods: [
            {id:'pix',              label:'PIX',           hint:'Confirmação em segundos',                icon:'qr-code',         badge:'Recomendado'},
            {id:'pix_installments', label:'PIX parcelado', hint:'Mensal sem juros, até a viagem',         icon:'calendar-clock',  badge:'Sem juros',  requiresInstallment:true},
            {id:'credit_card',      label:'Cartão de crédito', hint:'Parcele em até 12×',                  icon:'credit-card',     badge:''},
            {id:'boleto',           label:'Boleto bancário', hint:'Compensação em 1-3 dias úteis',         icon:'file-text',       badge:''}
        ],
        init() {
            if (this.form.travel_dates.length && !this.form.travel_date) this.form.travel_date = this.form.travel_dates[0];
            const first = this.form.travel_date || Object.keys(this.availabilityMap).find(k => this.isAvailableIso(k));
            if (first) { const d = new Date(first+'T12:00:00'); this.viewYear=d.getFullYear(); this.viewMonth=d.getMonth(); }
            if (window.lucide) window.lucide.createIcons();
        },

        // ===== Máscaras =====
        maskCPF(v){ v=(v||'').replace(/\D/g,'').slice(0,11); return v.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2'); },
        maskRG(v){ v=(v||'').replace(/[^\dXx]/g,'').toUpperCase().slice(0,9); return v.replace(/(\w{2})(\w)/,'$1.$2').replace(/(\w{2}\.\w{3})(\w)/,'$1.$2').replace(/(\w{2}\.\w{3}\.\w{3})(\w)/,'$1-$2'); },
        maskPhone(v){ v=(v||'').replace(/\D/g,'').slice(0,11); if(v.length<=10) return v.replace(/(\d{2})(\d)/,'($1) $2').replace(/(\d{4})(\d)/,'$1-$2'); return v.replace(/(\d{2})(\d)/,'($1) $2').replace(/(\d{5})(\d)/,'$1-$2'); },

        // ===== Preço =====
        adultUnit(){  return (this.form.payment_method==='pix' && this.form.price_option==='promo') ? this.pricePix       : this.priceAdult; },
        childUnit(){  return (this.form.payment_method==='pix' && this.form.price_option==='promo') ? this.priceChildPix  : this.priceChild; },
        infantUnit(){ return (this.form.payment_method==='pix' && this.form.price_option==='promo') ? this.priceInfantPix : this.priceInfant; },
        peopleTotal(){ return Math.max(1,this.form.adults) + Math.max(0,this.form.children) + Math.max(0,this.form.infants); },
        selectedDateCount(){ return Math.max(1, this.form.travel_dates.length || (this.form.travel_date ? 1 : 0)); },
        subtotalPerDate(){ return this.isTransfer ? this.adultUnit() : (this.adultUnit()*Math.max(1,this.form.adults) + this.childUnit()*Math.max(0,this.form.children) + this.infantUnit()*Math.max(0,this.form.infants)); },
        subtotal(){ return this.subtotalPerDate() * this.selectedDateCount(); },
        discount(){
            if (this.form.payment_method!=='pix' || this.form.price_option!=='promo') return 0;
            if (this.isTransfer) return Math.max(0, this.priceAdult - this.pricePix) * this.selectedDateCount();
            const da = (this.priceAdult - this.pricePix) * this.form.adults;
            const dc = Math.max(0, this.priceChild - this.priceChildPix) * this.form.children;
            const di = Math.max(0, this.priceInfant - this.priceInfantPix) * this.form.infants;
            return (da + dc + di) * this.selectedDateCount();
        },
        total(){ return this.subtotal(); },
        formatBRL(v){
            const n = Number(v||0);
            try { return new Intl.NumberFormat(this.currencyLocale,{style:'currency',currency:this.currencyCode}).format(n); }
            catch(e){ return this.currencySymbol + ' ' + n.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }
        },
        formatDate(s){ if(!s) return '—'; const d=new Date(s+'T12:00:00'); return d.toLocaleDateString('pt-BR',{day:'2-digit',month:'long',year:'numeric'}); },
        selectedDatesCountLabel(){ return this.form.travel_dates.length === 1 ? '1 data selecionada' : this.form.travel_dates.length + ' datas selecionadas'; },
        selectedDatesText(){ return this.form.travel_dates.map(d => this.formatDate(d)).join(', '); },
        datePreviewLabel(){
            if(!this.form.travel_date) return '';
            const d = new Date(this.form.travel_date+'T12:00:00');
            const days = Math.ceil((d - new Date())/86400000);
            if (days < 0) return 'Data no passado.';
            if (days === 0) return 'Viagem é hoje!';
            if (days === 1) return 'Viagem é amanhã.';
            return 'Faltam ' + days + ' dias para a viagem.';
        },

        // ===== Mini-calendário de disponibilidade =====
        pad(n){ return n<10?'0'+n:''+n; },
        iso(y,m,d){ return y+'-'+this.pad(m+1)+'-'+this.pad(d); },
        get monthLabel(){ const n=['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro']; return n[this.viewMonth]+' de '+this.viewYear; },
        isAvailableIso(iso){
            const today = new Date(this.today+'T00:00:00');
            const dt = new Date(iso+'T00:00:00');
            if (dt < today || this.availabilityMode === 'on_request') return false;
            const info = this.availabilityMap[iso];
            if (info) return info.status === 'open' && Number(info.seats || 0) > 0;
            return this.availabilityMode === 'open';
        },
        get cells(){
            const first = new Date(this.viewYear,this.viewMonth,1);
            const start = first.getDay();
            const days = new Date(this.viewYear,this.viewMonth+1,0).getDate();
            const cells=[];
            for(let i=0;i<start;i++) cells.push({key:'e'+i,empty:true});
            for(let d=1;d<=days;d++){
                const iso=this.iso(this.viewYear,this.viewMonth,d), info=this.availabilityMap[iso], available=this.isAvailableIso(iso);
                cells.push({key:iso,iso,day:d,empty:false,available,lowSeats:available&&info&&Number(info.seats)<=3,blocked:!!info&&!available,price:(info&&info.price)||this.pricePix||this.priceAdult,seats:info?info.seats:null});
            }
            return cells;
        },
        prevYear(){ this.viewYear--; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        nextYear(){ this.viewYear++; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        prevMonth(){ if(this.viewMonth===0){this.viewMonth=11;this.viewYear--;}else this.viewMonth--; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        nextMonth(){ if(this.viewMonth===11){this.viewMonth=0;this.viewYear++;}else this.viewMonth++; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        toggleDate(cell){
            if(!cell.available) return;
            const i = this.form.travel_dates.indexOf(cell.iso);
            if(i>=0) this.form.travel_dates.splice(i,1); else this.form.travel_dates.push(cell.iso);
            this.form.travel_dates.sort();
            this.form.travel_date = this.form.travel_dates[0] || '';
            this.$nextTick(()=>window.lucide&&window.lucide.createIcons());
        },

        // ===== PIX parcelado =====
        deadlineDate(){
            if(!this.form.travel_date) return null;
            const d = new Date(this.form.travel_date+'T12:00:00');
            d.setDate(d.getDate() - this.installMinDays);
            return d;
        },
        canInstallment(){
            const dl = this.deadlineDate(); if(!dl) return false;
            const months = (dl.getFullYear() - new Date().getFullYear())*12 + (dl.getMonth() - new Date().getMonth());
            return months >= 1;
        },
        maxInstallments(){
            const dl = this.deadlineDate(); if(!dl) return 1;
            const today = new Date(); today.setHours(0,0,0,0);
            let months = (dl.getFullYear() - today.getFullYear())*12 + (dl.getMonth() - today.getMonth());
            if (dl.getDate() < today.getDate()) months--;
            return Math.max(1, Math.min(this.installMax, months + 1));
        },
        installmentOptions(){ const max=this.maxInstallments(); return Array.from({length:max},(_,i)=>i+1).filter(n=>n>=1); },
        installmentValue(n){ if(!n) return 0; return this.total() / n; },
        installmentDeadlineLabel(){ const d=this.deadlineDate(); if(!d) return '—'; return d.toLocaleDateString('pt-BR',{day:'2-digit',month:'long',year:'numeric'}); },

        // ===== Source =====
        sourceLabelById(id){ const s=this.sources.find(x=>x.id===id); return s?s.label:'—'; },
        sourceDetailLabel(){ const s=this.sources.find(x=>x.id===this.form.source); return s ? s.detailLabel : ''; },
        sourceDetailPlaceholder(){ const s=this.sources.find(x=>x.id===this.form.source); return s ? s.placeholder : ''; },

        // ===== Resumo =====
        peopleSummary(){
            const parts = [this.form.adults + ' adulto' + (this.form.adults>1?'s':'')];
            if (this.form.children > 0) parts.push(this.form.children + ' criança' + (this.form.children>1?'s':''));
            if (this.form.infants  > 0) parts.push(this.form.infants  + ' bebê'    + (this.form.infants>1?'s':''));
            return parts.join(' · ');
        },
        labelPayment(){
            const m = this.paymentMethods.find(p=>p.id===this.form.payment_method);
            let s = m ? m.label : '—';
            if (this.form.payment_method==='pix') s += ' · ' + (this.form.price_option==='promo' ? 'Lote promocional' : 'Lote regular');
            return s;
        },
        canProceed(){
            if (this.step === 0) return this.form.name.trim() && this.form.document.replace(/\D/g,'').length===11 && this.form.rg.trim() && this.form.birth_date && this.form.email.includes('@') && this.form.phone.replace(/\D/g,'').length>=10;
            if (this.step === 1) {
                const okCom = this.form.has_comorbidity!=='sim' || this.form.comorbidity.trim();
                return this.form.travel_dates.length > 0 && this.form.adults>=1 && (!this.isTransfer || this.peopleTotal() <= this.maxPeople) && okCom;
            }
            if (this.step === 2) {
                if (!this.form.payment_method) return false;
                if (this.form.payment_method==='pix_installments') return this.canInstallment() && this.form.installments >= 1;
                return true;
            }
            return true;
        },
        next(){ if(!this.canProceed()){ showToast('Complete os campos obrigatórios.', 'error'); return; } if(this.step<this.steps.length-1){ this.step++; window.scrollTo({top:0,behavior:'smooth'}); setTimeout(()=>window.lucide && window.lucide.createIcons(), 50); } },
        prev(){ if(this.step>0){ this.step--; window.scrollTo({top:0,behavior:'smooth'}); setTimeout(()=>window.lucide && window.lucide.createIcons(), 50); } },
        async testWebhook(){
            if (this.webhookTesting) return;
            this.webhookTesting = true;
            try {
                const res = await caminhosApi('<?= url('/api/payment-test-webhook') ?>', { method:'POST', data: { test:'1' } });
                showToast(res.msg || (res.ok ? 'Webhook enviado.' : 'Falha ao enviar webhook.'), res.ok ? 'success' : 'error');
            } catch (e) {
                showToast('Erro de rede ao testar webhook.', 'error');
            } finally {
                this.webhookTesting = false;
            }
        },
        async submit(){
            if(!this.form.accept_terms){ showToast('Aceite a política de desistência.', 'error'); return; }
            this.loading = true;
            const payload = { ...this.form, travel_date: this.form.travel_dates[0] || '', travel_dates: JSON.stringify(this.form.travel_dates), accept_terms:'1' };
            const res = await caminhosApi('<?= url('/api/booking') ?>', { method:'POST', data: payload });
            showToast(res.msg || (res.ok ? 'Reserva criada!' : 'Erro ao processar.'), res.ok ? 'success' : 'error');
            if (res.ok && res.redirect) window.location = res.redirect;
            this.loading = false;
        }
    }
}
</script>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
