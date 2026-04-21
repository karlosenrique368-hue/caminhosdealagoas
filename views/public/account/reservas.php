<?php
$accountTitle = 'Minhas reservas';
$accountTab = 'reservas';
include VIEWS_DIR . '/partials/account_layout.php';

$cid = currentCustomerId();
$bookings = dbAll("
    SELECT b.*, r.slug AS roteiro_slug, p.slug AS pacote_slug
    FROM bookings b
    LEFT JOIN roteiros r ON b.entity_type='roteiro' AND b.entity_id=r.id
    LEFT JOIN pacotes p ON b.entity_type='pacote' AND b.entity_id=p.id
    WHERE b.customer_user_id=?
    ORDER BY b.created_at DESC", [$cid]);
?>

<div class="glass-card p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="font-display text-2xl font-bold" style="color:var(--sepia)">Suas viagens</h2>
            <p class="text-xs" style="color:var(--text-muted)"><?= count($bookings) ?> reserva<?= count($bookings)===1?'':'s' ?> no total</p>
        </div>
        <a href="<?= url('/roteiros') ?>" class="btn-primary" style="padding:10px 18px;font-size:13px">
            <i data-lucide="plus" class="w-4 h-4"></i> Nova reserva
        </a>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="calendar-x" class="w-7 h-7"></i></div>
            <div class="empty-state-title">Nenhuma reserva encontrada</div>
            <div class="empty-state-desc">Que tal começar uma aventura em Alagoas?</div>
            <a href="<?= url('/roteiros') ?>" class="btn-primary inline-flex"><i data-lucide="compass" class="w-4 h-4"></i> Explorar roteiros</a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($bookings as $b):
                $title = $b['entity_title'];
                $slug = $b['roteiro_slug'] ?: $b['pacote_slug'];
                $type = $b['entity_type']==='roteiro'?'roteiros':'pacotes';
                $iconName = $b['entity_type']==='roteiro' ? 'mountain' : ($b['entity_type']==='pacote' ? 'package' : 'car');
                $statusPill = ['paid'=>'pill-success','pending'=>'pill-warning','failed'=>'pill-danger','refunded'=>'pill-info'][$b['payment_status']] ?? 'pill-info';
            ?>
                <div class="booking-row">
                    <div class="booking-icon"><i data-lucide="<?= $iconName ?>" class="w-6 h-6"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <span class="pill pill-primary"><?= e($b['entity_type']) ?></span>
                            <span class="pill <?= $statusPill ?>"><?= e($b['payment_status']) ?></span>
                        </div>
                        <h3 class="font-display font-bold text-lg" style="color:var(--sepia)"><?= e($title) ?></h3>
                        <p class="text-xs mt-1" style="color:var(--text-muted)">
                            Reservado em <?= date('d/m/Y', strtotime($b['created_at'])) ?>
                            <?php if (!empty($b['travel_date'])): ?> · Viagem: <?= date('d/m/Y', strtotime($b['travel_date'])) ?><?php endif; ?>
                            · Código: <code style="font-size:11px;background:var(--areia-light);padding:1px 6px;border-radius:4px"><?= e($b['code']) ?></code>
                        </p>
                    </div>
                    <div class="flex items-center gap-3 md:flex-col md:items-end">
                        <p class="font-display font-bold text-xl" style="color:var(--terracota)"><?= formatPrice((float)$b['total']) ?></p>
                        <div class="flex items-center gap-2">
                            <?php if ($slug): ?>
                                <a href="<?= url('/' . $type . '/' . $slug) ?>" class="btn-secondary" style="padding:8px 14px;font-size:12px">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i> Ver
                                </a>
                            <?php endif; ?>
                            <?php if ($b['payment_status'] === 'paid'): ?>
                                <a href="<?= url('/conta/reembolso?booking=' . $b['id']) ?>" class="btn-refund">
                                    <i data-lucide="refresh-ccw" class="w-3.5 h-3.5"></i> Reembolso
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include VIEWS_DIR . '/partials/account_layout_end.php'; ?>
