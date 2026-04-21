<?php
$accountTitle = 'Visão geral';
$accountTab = 'dashboard';
include VIEWS_DIR . '/partials/account_layout.php';

$cid = currentCustomerId();
$totalBookings = (int)(dbOne('SELECT COUNT(*) c FROM bookings WHERE customer_user_id=?', [$cid])['c'] ?? 0);
$totalWishlist = (int)(dbOne('SELECT COUNT(*) c FROM wishlist WHERE customer_id=?', [$cid])['c'] ?? 0);
$totalSpent = (float)(dbOne("SELECT COALESCE(SUM(total),0) s FROM bookings WHERE customer_user_id=? AND payment_status='paid'", [$cid])['s'] ?? 0);
$recent = dbAll("SELECT b.*, b.entity_title AS title FROM bookings b WHERE b.customer_user_id=? ORDER BY b.created_at DESC LIMIT 5", [$cid]);
?>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="p-6 rounded-2xl border relative overflow-hidden" style="background:#fff;border-color:var(--border-default)">
        <img src="<?= asset('brand/selo-terracota.png') ?>" class="seal-watermark sm" style="top:-20px;right:-20px" alt="">
        <div class="flex items-center gap-3 mb-3 relative z-10">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:var(--terracota);color:#fff">
                <i data-lucide="calendar-check" class="w-5 h-5"></i>
            </div>
            <span class="text-xs uppercase tracking-wider font-semibold" style="color:var(--text-muted)">Reservas</span>
        </div>
        <p class="font-display text-4xl font-bold relative z-10" style="color:var(--sepia)"><?= $totalBookings ?></p>
    </div>
    <div class="p-6 rounded-2xl border relative overflow-hidden" style="background:#fff;border-color:var(--border-default)">
        <img src="<?= asset('brand/selo-azul.png') ?>" class="seal-watermark sm" style="top:-20px;right:-20px" alt="">
        <div class="flex items-center gap-3 mb-3 relative z-10">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:var(--azul-profundo);color:#fff">
                <i data-lucide="heart" class="w-5 h-5"></i>
            </div>
            <span class="text-xs uppercase tracking-wider font-semibold" style="color:var(--text-muted)">Favoritos</span>
        </div>
        <p class="font-display text-4xl font-bold relative z-10" style="color:var(--sepia)"><?= $totalWishlist ?></p>
    </div>
    <div class="p-6 rounded-2xl border relative overflow-hidden" style="background:#fff;border-color:var(--border-default)">
        <img src="<?= asset('brand/selo-areia.png') ?>" class="seal-watermark sm reverse" style="top:-20px;right:-20px" alt="">
        <div class="flex items-center gap-3 mb-3 relative z-10">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:var(--areia-dark);color:#fff">
                <i data-lucide="banknote" class="w-5 h-5"></i>
            </div>
            <span class="text-xs uppercase tracking-wider font-semibold" style="color:var(--text-muted)">Investido</span>
        </div>
        <p class="font-display text-3xl font-bold relative z-10" style="color:var(--sepia)"><?= formatPrice($totalSpent) ?></p>
    </div>
</div>

<div class="rounded-2xl border p-6" style="background:#fff;border-color:var(--border-default)">
    <div class="flex items-center justify-between mb-5">
        <h2 class="font-display text-2xl font-bold" style="color:var(--sepia)">Reservas recentes</h2>
        <a href="<?= url('/conta/reservas') ?>" class="text-sm font-semibold" style="color:var(--terracota)">Ver todas →</a>
    </div>
    <?php if (empty($recent)): ?>
        <div class="text-center py-10">
            <i data-lucide="map" class="w-12 h-12 mx-auto mb-3" style="color:var(--text-muted)"></i>
            <p class="mb-4" style="color:var(--text-secondary)">Você ainda não tem nenhuma reserva.</p>
            <a href="<?= url('/roteiros') ?>" class="btn-primary inline-flex">Explorar roteiros</a>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($recent as $b): $title = $b['title'] ?: $b['entity_title']; ?>
                <div class="flex items-center gap-4 p-4 rounded-xl transition" style="background:var(--areia-light)">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:var(--terracota);color:#fff">
                        <i data-lucide="<?= $b['entity_type']==='roteiro' ? 'mountain' : ($b['entity_type']==='pacote'?'package':'car') ?>" class="w-5 h-5"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold truncate" style="color:var(--text-primary)"><?= e($title ?: 'Reserva') ?></p>
                        <p class="text-xs" style="color:var(--text-muted)"><?= date('d/m/Y', strtotime($b['created_at'])) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold" style="color:var(--sepia)"><?= formatPrice((float)$b['total']) ?></p>
                        <span class="text-xs font-semibold"><?= e($b['payment_status']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include VIEWS_DIR . '/partials/account_layout_end.php'; ?>
