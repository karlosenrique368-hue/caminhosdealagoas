<?php
$accountTitle = 'Minhas reservas';
$accountTab = 'reservas';
include VIEWS_DIR . '/partials/account_layout.php';

$cid = currentCustomerId();
$macaiokBookingExists = "EXISTS (SELECT 1 FROM institutions mi WHERE mi.program='macaiok' AND (mi.id=b.institution_id OR mi.referral_code=b.referral_code))";
$bookingScopeWhere = "(b.customer_id=? OR b.customer_user_id=?) AND " . ($macaiokAccount ? $macaiokBookingExists : "NOT " . $macaiokBookingExists);
$exploreUrl = $macaiokAccount ? '/macaiok#vivencias' : '/passeios';
$pag = paginate(
    'SELECT COUNT(*) c FROM bookings b WHERE ' . $bookingScopeWhere,
    "
    SELECT b.*, r.slug AS roteiro_slug, p.slug AS pacote_slug, t.slug AS transfer_slug,
        COALESCE(r.cover_image, p.cover_image, t.cover_image) AS cover_image
    FROM bookings b
    LEFT JOIN roteiros r ON b.entity_type='roteiro' AND b.entity_id=r.id
    LEFT JOIN pacotes p ON b.entity_type='pacote' AND b.entity_id=p.id
    LEFT JOIN transfers t ON b.entity_type='transfer' AND b.entity_id=t.id
    WHERE " . $bookingScopeWhere . "
    ORDER BY b.created_at DESC",
    [$cid, $cid],
    ['allowed' => [5, 10, 20], 'default' => 10]
);
$bookings = $pag['rows'];
?>

<div class="glass-card p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="font-display text-2xl font-bold" style="color:var(--sepia)"><?= $macaiokAccount ? 'Suas vivencias' : 'Suas viagens' ?></h2>
            <p class="text-xs" style="color:var(--text-muted)"><?= (int)$pag['total'] ?> reserva<?= (int)$pag['total']===1?'':'s' ?> no total</p>
        </div>
        <a href="<?= url($exploreUrl) ?>" class="btn-primary" style="padding:10px 18px;font-size:13px">
            <i data-lucide="plus" class="w-4 h-4"></i> <?= $macaiokAccount ? 'Nova vivencia' : 'Nova reserva' ?>
        </a>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="calendar-x" class="w-7 h-7"></i></div>
            <div class="empty-state-title"><?= $macaiokAccount ? 'Nenhuma vivencia encontrada' : 'Nenhuma reserva encontrada' ?></div>
            <div class="empty-state-desc">Que tal começar uma aventura em Alagoas?</div>
            <a href="<?= url($exploreUrl) ?>" class="btn-primary inline-flex"><i data-lucide="compass" class="w-4 h-4"></i> <?= $macaiokAccount ? 'Explorar vivencias' : 'Explorar passeios' ?></a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($bookings as $b):
                $title = $b['entity_title'];
                $slug = $b['roteiro_slug'] ?: ($b['pacote_slug'] ?: $b['transfer_slug']);
                $type = $b['entity_type']==='roteiro'?'passeios':($b['entity_type']==='pacote'?'pacotes':'transfers');
                $detailUrl = $slug ? url('/' . $type . '/' . $slug) : null;
                if ($macaiokAccount) $detailUrl = url('/macaiok/checkout?' . ($b['entity_type']==='roteiro' ? 'roteiro' : ($b['entity_type']==='pacote' ? 'pacote' : 'transfer')) . '=' . (int)$b['entity_id']);
                $typeLabel = $b['entity_type']==='roteiro' ? 'Passeio' : ($b['entity_type']==='pacote' ? 'Pacote' : 'Transfer');
                $iconName = $b['entity_type']==='roteiro' ? 'mountain' : ($b['entity_type']==='pacote' ? 'package' : 'car');
                $statusPill = ['paid'=>'pill-success','pending'=>'pill-warning','failed'=>'pill-danger','refunded'=>'pill-info'][$b['payment_status']] ?? 'pill-info';
                $answerData = !empty($b['booking_answers']) ? (json_decode($b['booking_answers'], true) ?: []) : [];
                $travelDates = !empty($answerData['datas_viagem']) && is_array($answerData['datas_viagem']) ? $answerData['datas_viagem'] : (!empty($b['travel_date']) ? [$b['travel_date']] : []);
                $travelDateLabel = implode(', ', array_map(fn($dt) => date('d/m/Y', strtotime($dt)), $travelDates));

                // Calcular parcelas restantes (PIX parcelado)
                $hasInstallments = !empty($b['installments']) && (int)$b['installments'] > 1;
                $installmentsTotal = (int)($b['installments'] ?? 0);
                $installmentsPaid  = 0;
                $installmentsLeft  = 0;
                $nextDueDate       = null;
                if ($hasInstallments) {
                    if ($b['payment_status'] === 'paid') {
                        $installmentsPaid = $installmentsTotal;
                    } else {
                        // 1ª parcela conta como paga após paid_at; demais ainda pendentes
                        $installmentsPaid = !empty($b['paid_at']) ? 1 : 0;
                        $base = !empty($b['paid_at']) ? $b['paid_at'] : $b['created_at'];
                        $baseTs = strtotime($base);
                        $today = time();
                        $monthsPassed = max(0, (int) floor(($today - $baseTs) / 86400 / 30));
                        $installmentsPaid = min($installmentsTotal, $installmentsPaid + $monthsPassed);
                        $installmentsLeft = max(0, $installmentsTotal - $installmentsPaid);
                        if ($installmentsLeft > 0) {
                            $nextDueDate = date('Y-m-d', strtotime($base . ' +' . ($installmentsPaid) . ' month'));
                        }
                    }
                }
            ?>
                <div class="booking-row">
                    <?php if (!empty($b['cover_image'])): ?>
                        <a href="<?= e($detailUrl ?: '#') ?>" class="block flex-shrink-0">
                            <img src="<?= e(storageUrl($b['cover_image'])) ?>" alt="" style="width:88px;height:88px;border-radius:14px;object-fit:cover">
                        </a>
                    <?php else: ?>
                        <div class="booking-icon"><i data-lucide="<?= $iconName ?>" class="w-6 h-6"></i></div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <span class="pill pill-primary"><?= e($typeLabel) ?></span>
                            <span class="pill <?= $statusPill ?>"><?= e($b['payment_status']) ?></span>
                            <?php if ($hasInstallments): ?>
                                <span class="pill" style="background:rgba(58,107,138,.1);color:var(--horizonte)"><i data-lucide="calendar-clock" class="w-3 h-3"></i> <?= $installmentsPaid ?>/<?= $installmentsTotal ?> parcelas</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="font-display font-bold text-lg" style="color:var(--sepia)">
                            <?php if ($detailUrl): ?>
                                <a href="<?= e($detailUrl) ?>" class="hover:underline" style="color:inherit"><?= e($title) ?></a>
                            <?php else: ?>
                                <?= e($title) ?>
                            <?php endif; ?>
                        </h3>
                        <p class="text-xs mt-1" style="color:var(--text-muted)">
                            Reservado em <?= date('d/m/Y', strtotime($b['created_at'])) ?>
                            <?php if ($travelDateLabel): ?> · <?= count($travelDates) > 1 ? 'Viagens' : 'Viagem' ?>: <?= e($travelDateLabel) ?><?php endif; ?>
                            · Código: <code style="font-size:11px;background:var(--areia-light);padding:1px 6px;border-radius:4px"><?= e($b['code']) ?></code>
                        </p>
                        <?php if ($hasInstallments && $installmentsLeft > 0): ?>
                            <div class="mt-3 p-3 rounded-xl" style="background:linear-gradient(135deg,rgba(58,107,138,.08),rgba(122,157,110,.06));border:1px solid rgba(58,107,138,.2)">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <i data-lucide="calendar-clock" class="w-4 h-4" style="color:var(--horizonte)"></i>
                                    <span class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--horizonte)">Parcelas restantes</span>
                                </div>
                                <div class="flex items-end gap-2 flex-wrap">
                                    <div class="font-display text-2xl font-bold leading-tight" style="color:var(--horizonte)"><?= $installmentsLeft ?>×</div>
                                    <div class="font-display text-xl font-semibold leading-tight" style="color:var(--horizonte)"><?= formatPrice((float)$b['installment_amount']) ?></div>
                                </div>
                                <?php if ($nextDueDate): ?>
                                    <div class="text-[11px] mt-1.5" style="color:var(--text-muted)"><i data-lucide="bell" class="w-3 h-3 inline -mt-0.5"></i> Próximo vencimento: <b style="color:var(--text-secondary)"><?= date('d/m/Y', strtotime($nextDueDate)) ?></b></div>
                                <?php endif; ?>
                                <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background:rgba(58,107,138,.15)">
                                    <div style="height:100%;background:linear-gradient(90deg,var(--maresia),var(--horizonte));width:<?= round(($installmentsPaid/max(1,$installmentsTotal))*100) ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-3 md:flex-col md:items-end">
                        <p class="font-display font-bold text-xl" style="color:var(--terracota)"><?= formatPrice((float)$b['total']) ?></p>
                        <div class="flex items-center gap-2">
                            <?php if ($detailUrl): ?>
                                <a href="<?= e($detailUrl) ?>" class="btn-secondary" style="padding:8px 14px;font-size:12px">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> Ver
                                </a>
                            <?php endif; ?>
                            <?php if ($b['payment_status'] === 'paid'): ?>
                                <a href="<?= url(($macaiokAccount ? '/macaiok/conta/reembolso' : '/conta/reembolso') . '?booking=' . $b['id']) ?>" class="btn-refund">
                                    <i data-lucide="refresh-ccw" class="w-3.5 h-3.5"></i> Reembolso
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php include VIEWS_DIR . '/partials/pagination.php'; ?>
    <?php endif; ?>
</div>

<?php include VIEWS_DIR . '/partials/account_layout_end.php'; ?>
