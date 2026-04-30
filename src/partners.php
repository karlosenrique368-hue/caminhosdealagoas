<?php
/**
 * Partners — sistema de indicacao e comissao.
 * Discreto no site publico ("benefícios exclusivos"), detalhado na area do parceiro.
 */

/** Gera codigo unico de 8 chars (A-Z0-9, sem 0/O/1/I para legibilidade). */
function generateReferralCode(): string {
    $alpha = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($tries = 0; $tries < 10; $tries++) {
        $code = '';
        for ($i = 0; $i < 8; $i++) $code .= $alpha[random_int(0, strlen($alpha) - 1)];
        if (!dbOne('SELECT id FROM institutions WHERE referral_code = ?', [$code])) return $code;
    }
    return strtoupper(bin2hex(random_bytes(4)));
}

/** Busca parceiro pelo codigo de indicacao. */
function partnerByCode(?string $code): ?array {
    if (!$code) return null;
    $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));
    if (!$code) return null;
    $p = dbOne('SELECT * FROM institutions WHERE referral_code = ? AND active = 1', [$code]);
    return $p ?: null;
}

/** Le o codigo de indicacao ativo (session -> cookie). */
function currentReferralCode(): ?string {
    if (!empty($_SESSION['ref_code'])) return $_SESSION['ref_code'];
    if (!empty($_COOKIE['ref_code'])) return (string)$_COOKIE['ref_code'];
    return null;
}

/** Registra codigo em session+cookie (30 dias). */
function trackReferral(string $code): bool {
    $p = partnerByCode($code);
    if (!$p) return false;
    $_SESSION['ref_code'] = $p['referral_code'];
    $_SESSION['ref_partner_id'] = (int)$p['id'];
    if (!headers_sent()) {
        setcookie('ref_code', $p['referral_code'], [
            'expires'  => time() + 86400 * 30,
            'path'     => '/',
            'secure'   => isSecureRequest(),
            'samesite' => 'Lax',
            'httponly' => true,
        ]);
    }
    return true;
}

function clearReferral(): void {
    unset($_SESSION['ref_code'], $_SESSION['ref_partner_id']);
    if (!headers_sent()) setcookie('ref_code', '', time() - 3600, '/');
}

/** URL publica de indicacao para compartilhar. */
function referralShareUrl(string $code, string $path = '/'): string {
    $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $sep = (strpos($path, '?') !== false) ? '&' : '?';
    return $base . url($path) . $sep . 'ref=' . urlencode($code);
}

/** Cadastra um parceiro novo (retorna [partner_id, user_id, code] ou lanca Exception). */
function createPartner(array $data, string $password): array {
    $name   = trim($data['name'] ?? '');
    $email  = strtolower(trim($data['email'] ?? ''));
    $type   = $data['partner_type'] ?? 'individual';
    $cpf    = preg_replace('/\D/', '', $data['cpf'] ?? '');
    $phone  = trim($data['phone'] ?? $data['whatsapp'] ?? '');
    $cidade = trim($data['city'] ?? '');
    if (!$name || !$email || !$phone) throw new RuntimeException('Preencha nome, email e telefone.');
    if (strlen($password) < PASSWORD_MIN_LENGTH) throw new RuntimeException('Senha muito curta (mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres).');
    $validTypes = ['individual','familia','grupo','instituicao','revendedor'];
    if (!in_array($type, $validTypes, true)) $type = 'individual';
    if (dbOne('SELECT id FROM institution_users WHERE email=?', [$email])) {
        throw new RuntimeException('Email já cadastrado. Faça login.');
    }

    $slug = slugify($name) . '-' . substr(md5($email.microtime()), 0, 6);
    $code = generateReferralCode();

    // Institution tipo legacy: mapeia partner_type -> type
    $typeMap = ['individual'=>'outro','familia'=>'outro','grupo'=>'outro','instituicao'=>'empresa','revendedor'=>'outro'];

    $pid = dbInsert(
        "INSERT INTO institutions (name, type, partner_type, cpf, contact_name, contact_email, contact_phone, whatsapp, referral_code, slug, address, active, discount_percent, commission_percent, bookings_threshold, allow_group_checkout)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 0, 0, ?)",
        [
            $name, $typeMap[$type], $type, $cpf ?: null, $name, $email, $phone, $phone, $code, $slug, $cidade ?: null,
            $type === 'instituicao' ? 1 : 0,
        ]
    );

    $uid = dbInsert(
        "INSERT INTO institution_users (institution_id, name, email, password_hash, role, active)
         VALUES (?, ?, ?, ?, 'owner', 1)",
        [$pid, $name, $email, password_hash($password, PASSWORD_DEFAULT)]
    );

    return ['partner_id' => $pid, 'user_id' => $uid, 'code' => $code];
}

/**
 * Hook chamado ao trocar payment_status para 'paid'.
 * Calcula comissao, credita no parceiro, adiciona gratuidade se bater threshold.
 */
function creditCommissionOnPaid(int $bookingId): void {
    $b = dbOne('SELECT * FROM bookings WHERE id = ?', [$bookingId]);
    if (!$b || $b['payment_status'] !== 'paid') return;
    if ((int)$b['commission_credited'] === 1) return;
    if (empty($b['institution_id']) && empty($b['referral_code'])) return;

    // Acha parceiro
    $partner = null;
    if (!empty($b['institution_id'])) {
        $partner = dbOne('SELECT * FROM institutions WHERE id = ?', [$b['institution_id']]);
    } elseif (!empty($b['referral_code'])) {
        $partner = dbOne('SELECT * FROM institutions WHERE referral_code = ?', [$b['referral_code']]);
    }
    if (!$partner) return;

    // Comissao: PADRAO = regra do passeio/pacote. SE o parceiro tiver valor definido (>0), o parceiro SOBREPOE.
    $itemPct = null;
    $itemThreshold = null;
    if ($b['entity_type'] === 'roteiro') {
        $it = dbOne('SELECT commission_percent, bookings_threshold FROM roteiros WHERE id=?', [$b['entity_id']]);
    } elseif ($b['entity_type'] === 'pacote') {
        $it = dbOne('SELECT commission_percent, bookings_threshold FROM pacotes WHERE id=?', [$b['entity_id']]);
    } else { $it = null; }
    if ($it) {
        if ($it['commission_percent'] !== null && $it['commission_percent'] !== '') $itemPct = (float)$it['commission_percent'];
        if ($it['bookings_threshold'] !== null && $it['bookings_threshold'] !== '') $itemThreshold = (int)$it['bookings_threshold'];
    }
    $partnerPct       = (float)($partner['commission_percent'] ?? 0);
    $partnerThreshold = (int)($partner['bookings_threshold'] ?? 0);
    $pctToUse   = $partnerPct > 0 ? $partnerPct : (float)($itemPct ?? 0);
    $commission = round((float)$b['total'] * ($pctToUse / 100), 2);

    // Atualizacao atomica: SO procede se conseguiu virar credited=0 -> 1 (previne race condition de webhook duplicado)
    $rows = dbExec('UPDATE bookings SET commission_value=?, commission_credited=1, institution_id=COALESCE(institution_id,?) WHERE id=? AND commission_credited=0',
        [$commission, $partner['id'], $bookingId]);
    if ($rows < 1) return; // ja foi creditado por outra requisicao

    // Atualiza parceiro
    $newCount = (int)$partner['bookings_count_paid'] + 1;
    $newPending = (float)$partner['commission_pending'] + $commission;

    // Gratuidade: parceiro sobrepoe item se tiver meta definida (>0). Se nenhum dos dois, gratuidade desabilitada.
    $thresholdUse = $partnerThreshold > 0 ? $partnerThreshold : (int)($itemThreshold ?? 0);
    if ($thresholdUse > 0) {
        $priorBlocks = intdiv((int)$partner['bookings_count_paid'], $thresholdUse);
        $newBlocks   = intdiv($newCount, $thresholdUse);
        $newFree     = (int)$partner['free_spots_earned'] + max(0, $newBlocks - $priorBlocks);
    } else {
        $newFree = (int)$partner['free_spots_earned'];
    }

    dbExec('UPDATE institutions SET commission_pending=?, bookings_count_paid=?, free_spots_earned=? WHERE id=?',
        [$newPending, $newCount, $newFree, $partner['id']]);
    logActivity(null, 'commission_credited', 'booking', $bookingId, "Comissao R$ {$commission} creditada para parceiro #{$partner['id']}");
}

/** Reverte comissao (ex: reembolso ou cancelamento de booking paga). */
function revokeCommissionOnUnpaid(int $bookingId): void {
    $b = dbOne('SELECT * FROM bookings WHERE id = ?', [$bookingId]);
    if (!$b) return;
    if ((int)$b['commission_credited'] !== 1) return;
    if (empty($b['institution_id'])) return;

    $partner = dbOne('SELECT * FROM institutions WHERE id = ?', [$b['institution_id']]);
    if (!$partner) return;

    $commission = (float)$b['commission_value'];
    $newPending = max(0, (float)$partner['commission_pending'] - $commission);
    $newCount   = max(0, (int)$partner['bookings_count_paid'] - 1);

    $itemThreshold = null;
    if ($b['entity_type'] === 'roteiro') {
        $it = dbOne('SELECT bookings_threshold FROM roteiros WHERE id=?', [$b['entity_id']]);
    } elseif ($b['entity_type'] === 'pacote') {
        $it = dbOne('SELECT bookings_threshold FROM pacotes WHERE id=?', [$b['entity_id']]);
    } else { $it = null; }
    if ($it && $it['bookings_threshold'] !== null && $it['bookings_threshold'] !== '') $itemThreshold = (int)$it['bookings_threshold'];
    $partnerThreshold = (int)($partner['bookings_threshold'] ?? 0);
    $thresholdUse = $partnerThreshold > 0 ? $partnerThreshold : (int)($itemThreshold ?? 0);
    $newFreeEarned = (int)$partner['free_spots_earned'];
    if ($thresholdUse > 0) {
        $oldBlocks = intdiv((int)$partner['bookings_count_paid'], $thresholdUse);
        $newBlocks = intdiv($newCount, $thresholdUse);
        $newFreeEarned = max((int)$partner['free_spots_used'], $newFreeEarned - max(0, $oldBlocks - $newBlocks));
    }

    dbExec('UPDATE bookings SET commission_credited=0 WHERE id=?', [$bookingId]);
    dbExec('UPDATE institutions SET commission_pending=?, bookings_count_paid=?, free_spots_earned=? WHERE id=?',
        [$newPending, $newCount, $newFreeEarned, $partner['id']]);
}

/** KPIs do parceiro (area logada). */
function partnerStats(int $partnerId): array {
    $p = dbOne('SELECT * FROM institutions WHERE id = ?', [$partnerId]);
    if (!$p) return [];
    $threshold = max(1, (int)$p['bookings_threshold']);
    $count     = (int)$p['bookings_count_paid'];
    $toNextFree = $threshold - ($count % $threshold);
    if ($toNextFree === $threshold && $count > 0) $toNextFree = 0; // acabou de ganhar

    $totalBookings = (int)(dbOne('SELECT COUNT(*) AS c FROM bookings WHERE institution_id=? OR referral_code=?', [$partnerId, $p['referral_code']])['c'] ?? 0);
    $paidBookings  = (int)(dbOne("SELECT COUNT(*) AS c FROM bookings WHERE (institution_id=? OR referral_code=?) AND payment_status='paid'", [$partnerId, $p['referral_code']])['c'] ?? 0);

    return [
        'partner'          => $p,
        'threshold'        => $threshold,
        'paid_bookings'    => $paidBookings,
        'total_bookings'   => $totalBookings,
        'to_next_free'     => $toNextFree,
        'free_available'   => max(0, (int)$p['free_spots_earned'] - (int)$p['free_spots_used']),
        'progress_pct'     => $threshold > 0 ? round((($count % $threshold) / $threshold) * 100) : 0,
        'commission_pending' => (float)$p['commission_pending'],
        'commission_paid'    => (float)$p['commission_paid'],
    ];
}
