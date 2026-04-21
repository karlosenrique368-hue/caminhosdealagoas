<?php
$accountTitle = 'Minhas reservas';
$accountTab = 'reservas';
include VIEWS_DIR . '/partials/account_layout.php';

$cid = currentCustomerId();
$bookings = dbAll("SELECT b.*, r.slug AS roteiro_slug, p.slug AS pacote_slug FROM bookings b LEFT JOIN roteiros r ON b.entity_type='roteiro' AND b.entity_id=r.id LEFT JOIN pacotes p ON b.entity_type='pacote' AND b.entity_id=p.id WHERE b.customer_user_id=? ORDER BY b.created_at DESC", [$cid]);
?>

<div class="rounded-2xl border p-6" style="background:#fff;border-color:var(--border-default)">
    <h2 class="font-display text-2xl font-bold mb-6" style="color:var(--sepia)">Suas viagens</h2>

    <?php if (empty($bookings)): ?>
        <div class="text-center py-12">
            <i data-lucide="calendar-x" class="w-14 h-14 mx-auto mb-4" style="color:var(--text-muted)"></i>
            <p class="mb-4 text-lg" style="color:var(--text-secondary)">Nenhuma reserva encontrada</p>
            <a href="<?= url('/roteiros') ?>" class="btn-primary inline-flex">Explorar roteiros</a>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($bookings as $b): $title = $b['entity_title']; $slug = $b['roteiro_slug'] ?: $b['pacote_slug']; $type = $b['entity_type']==='roteiro'?'roteiros':'pacotes'; ?>
                <div class="p-5 rounded-2xl border flex flex-col md:flex-row md:items-center gap-4" style="background:var(--areia-light);border-color:var(--border-default)">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" style="background:var(--terracota);color:#fff"><?= e($b['entity_type']) ?></span>
                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" style="background:var(--azul-profundo);color:#fff"><?= e($b['payment_status']) ?></span>
                        </div>
                        <h3 class="font-display font-bold text-lg" style="color:var(--sepia)"><?= e($title) ?></h3>
                        <p class="text-xs mt-1" style="color:var(--text-muted)">
                            Reservado em <?= date('d/m/Y', strtotime($b['created_at'])) ?>
                            <?php if (!empty($b['travel_date'])): ?> · Viagem: <?= date('d/m/Y', strtotime($b['travel_date'])) ?><?php endif; ?>
                            · Código: <code class="text-xs"><?= e($b['code']) ?></code>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <p class="font-display font-bold text-xl mr-2" style="color:var(--sepia)"><?= formatPrice((float)$b['total']) ?></p>
                        <?php if ($slug): ?><a href="<?= url('/' . $type . '/' . $slug) ?>" class="btn-secondary text-sm">Ver</a><?php endif; ?>
                        <?php if ($b['payment_status'] === 'paid'): ?>
                            <a href="<?= url('/conta/reembolso?booking=' . $b['id']) ?>" class="btn-secondary text-sm">Reembolso</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include VIEWS_DIR . '/partials/account_layout_end.php'; ?>
