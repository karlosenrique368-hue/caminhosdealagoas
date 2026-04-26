<?php
$accountTitle = 'Visão geral';
$accountTab = 'dashboard';
include VIEWS_DIR . '/partials/account_layout.php';

$cid = currentCustomerId();
$totalBookings = (int)(dbOne('SELECT COUNT(*) c FROM bookings WHERE customer_user_id=?', [$cid])['c'] ?? 0);
$totalWishlist = (int)(dbOne('SELECT COUNT(*) c FROM wishlist WHERE customer_id=?', [$cid])['c'] ?? 0);
$totalSpent = (float)(dbOne("SELECT COALESCE(SUM(total),0) s FROM bookings WHERE customer_user_id=? AND payment_status='paid'", [$cid])['s'] ?? 0);
$paidCount = (int)(dbOne("SELECT COUNT(*) c FROM bookings WHERE customer_user_id=? AND payment_status='paid'", [$cid])['c'] ?? 0);
$pendingCount = (int)(dbOne("SELECT COUNT(*) c FROM bookings WHERE customer_user_id=? AND payment_status='pending'", [$cid])['c'] ?? 0);
$recent = dbAll("SELECT b.*, b.entity_title AS title FROM bookings b WHERE b.customer_user_id=? ORDER BY b.created_at DESC LIMIT 5", [$cid]);
?>

<!-- Premium stat grid -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="stat-card-premium" data-reveal
         style="--accent:var(--terracota); --tint:rgba(201,107,74,0.1); --accent-shadow:rgba(201,107,74,0.4)">
        <img src="<?= asset('brand/selo-terracota.png') ?>" class="stat-seal" alt="">
        <div class="stat-head">
            <div class="stat-icon-box"><i data-lucide="calendar-check" class="w-5 h-5"></i></div>
            <span class="stat-label">Reservas</span>
        </div>
        <div class="stat-value" data-counter="<?= $totalBookings ?>"><?= $totalBookings ?></div>
        <div class="stat-sub"><?= $paidCount ?> paga<?= $paidCount===1?'':'s' ?> · <?= $pendingCount ?> pendente<?= $pendingCount===1?'':'s' ?></div>
    </div>

    <div class="stat-card-premium" data-reveal style="animation-delay:80ms;
         --accent:var(--horizonte); --tint:rgba(58,107,138,0.1); --accent-shadow:rgba(58,107,138,0.4)">
        <img src="<?= asset('brand/selo-azul.png') ?>" class="stat-seal" alt="">
        <div class="stat-head">
            <div class="stat-icon-box"><i data-lucide="heart" class="w-5 h-5"></i></div>
            <span class="stat-label">Favoritos</span>
        </div>
        <div class="stat-value" data-counter="<?= $totalWishlist ?>"><?= $totalWishlist ?></div>
        <div class="stat-sub">Itens salvos para voltar depois</div>
    </div>

    <div class="stat-card-premium" data-reveal style="animation-delay:160ms;
         --accent:var(--maresia-dark); --tint:rgba(122,157,110,0.12); --accent-shadow:rgba(122,157,110,0.4)">
        <img src="<?= asset('brand/selo-azul.png') ?>" class="stat-seal" alt="">
        <div class="stat-head">
            <div class="stat-icon-box"><i data-lucide="wallet" class="w-5 h-5"></i></div>
            <span class="stat-label">Investido</span>
        </div>
        <div class="stat-value" style="font-size:28px"><?= formatPrice($totalSpent) ?></div>
        <div class="stat-sub">Total em experiências pagas</div>
    </div>
</div>

<!-- Recent bookings -->
<div class="glass-card p-6" data-reveal>
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="font-display text-2xl font-bold" style="color:var(--sepia)">Reservas recentes</h2>
            <p class="text-xs" style="color:var(--text-muted)">Últimas 5 atividades da sua conta</p>
        </div>
        <a href="<?= url('/conta/reservas') ?>" class="text-sm font-bold flex items-center gap-1" style="color:var(--terracota)">
            Ver todas <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
    </div>
    <?php if (empty($recent)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="map" class="w-7 h-7"></i></div>
            <div class="empty-state-title">Nenhuma viagem ainda</div>
            <div class="empty-state-desc">Comece explorando nossos passeios curados em Alagoas.</div>
            <a href="<?= url('/passeios') ?>" class="btn-primary inline-flex"><i data-lucide="compass" class="w-4 h-4"></i> Explorar passeios</a>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($recent as $b): $title = $b['title'] ?: ($b['entity_title'] ?? 'Reserva');
                $iconName = $b['entity_type']==='roteiro' ? 'mountain' : ($b['entity_type']==='pacote' ? 'package' : 'car');
                $statusPill = ['paid'=>'pill-success','pending'=>'pill-warning','failed'=>'pill-danger','refunded'=>'pill-info'][$b['payment_status']] ?? 'pill-info';
            ?>
                <div class="booking-row">
                    <div class="booking-icon"><i data-lucide="<?= $iconName ?>" class="w-5 h-5"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="pill pill-primary"><?= e($b['entity_type']) ?></span>
                            <span class="pill <?= $statusPill ?>"><?= e($b['payment_status']) ?></span>
                        </div>
                        <p class="font-bold truncate" style="color:var(--sepia)"><?= e($title) ?></p>
                        <p class="text-xs mt-1" style="color:var(--text-muted)">
                            <?= date('d/m/Y', strtotime($b['created_at'])) ?>
                            <?php if (!empty($b['code'])): ?> · <code style="font-size:11px"><?= e($b['code']) ?></code><?php endif; ?>
                        </p>
                    </div>
                    <div class="text-right md:ml-auto">
                        <p class="font-display font-bold text-lg" style="color:var(--terracota)"><?= formatPrice((float)$b['total']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include VIEWS_DIR . '/partials/account_layout_end.php'; ?>
