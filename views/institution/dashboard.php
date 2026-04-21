<?php
requireInstitution();
$i = currentInstitution();
$pageTitle = 'Visão geral';

$bookings = dbAll("SELECT b.*, c.name AS customer_name FROM bookings b LEFT JOIN customers c ON c.id=b.customer_id WHERE b.institution_id=? ORDER BY b.created_at DESC LIMIT 50", [$i['id']]);
$totalBookings = dbOne("SELECT COUNT(*) AS c FROM bookings WHERE institution_id=?", [$i['id']])['c'] ?? 0;
$paidRevenue = (float) (dbOne("SELECT COALESCE(SUM(total),0) AS t FROM bookings WHERE institution_id=? AND payment_status='paid'", [$i['id']])['t'] ?? 0);
$pendingCount = (int) (dbOne("SELECT COUNT(*) AS c FROM bookings WHERE institution_id=? AND payment_status='pending'", [$i['id']])['c'] ?? 0);
$peopleTraveled = (int) (dbOne("SELECT COALESCE(SUM(adults+children),0) AS p FROM bookings WHERE institution_id=? AND payment_status='paid'", [$i['id']])['p'] ?? 0);
$commission = $paidRevenue * ((float)(dbOne("SELECT commission_percent FROM institutions WHERE id=?", [$i['id']])['commission_percent'] ?? 0) / 100);

$groupRequests = dbAll("SELECT * FROM group_requests WHERE institution_id=? ORDER BY created_at DESC LIMIT 5", [$i['id']]);

include VIEWS_DIR . '/partials/institution_head.php';
?>
<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="admin-card p-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Reservas totais</span>
            <i data-lucide="calendar-check" class="w-5 h-5" style="color:var(--horizonte)"></i>
        </div>
        <div class="font-display text-3xl font-bold" style="color:var(--sepia)"><?= $totalBookings ?></div>
    </div>
    <div class="admin-card p-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Receita paga</span>
            <i data-lucide="trending-up" class="w-5 h-5" style="color:var(--maresia-dark)"></i>
        </div>
        <div class="font-display text-3xl font-bold" style="color:var(--sepia)"><?= formatBRL($paidRevenue) ?></div>
    </div>
    <div class="admin-card p-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Pendentes</span>
            <i data-lucide="clock" class="w-5 h-5" style="color:#F59E0B"></i>
        </div>
        <div class="font-display text-3xl font-bold" style="color:var(--sepia)"><?= $pendingCount ?></div>
    </div>
    <div class="admin-card p-6">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Comissão acumulada</span>
            <i data-lucide="badge-percent" class="w-5 h-5" style="color:var(--terracota)"></i>
        </div>
        <div class="font-display text-3xl font-bold" style="color:var(--terracota)"><?= formatBRL($commission) ?></div>
        <p class="text-xs mt-1" style="color:var(--text-muted)"><?= $peopleTraveled ?> pessoas já viajaram</p>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 admin-card p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="font-display text-lg font-bold" style="color:var(--sepia)">Reservas recentes</h2>
            <a href="<?= url('/instituicao/reservas') ?>" class="text-sm font-semibold" style="color:var(--horizonte)">Ver todas →</a>
        </div>
        <?php if (!$bookings): ?>
            <div class="py-8 text-center">
                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3" style="color:var(--text-muted)"></i>
                <p class="text-sm" style="color:var(--text-muted)">Ainda sem reservas. Envie o link da sua página parceira para começar.</p>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead><tr><th>Código</th><th>Produto</th><th>Cliente</th><th>Data</th><th>Pessoas</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($bookings, 0, 10) as $b): ?>
                <tr>
                    <td class="font-mono text-xs"><?= e($b['code']) ?></td>
                    <td><span class="text-xs uppercase font-semibold" style="color:var(--terracota)"><?= e($b['entity_type']) ?></span><br><span class="text-sm"><?= e($b['entity_title']) ?></span></td>
                    <td class="text-sm"><?= e($b['customer_name'] ?? '—') ?></td>
                    <td class="text-sm"><?= $b['travel_date'] ? formatDate($b['travel_date'], 'd/m/Y') : '—' ?></td>
                    <td class="text-sm"><?= (int)$b['adults'] + (int)$b['children'] ?></td>
                    <td class="text-sm font-semibold" style="color:var(--sepia)"><?= formatBRL($b['total']) ?></td>
                    <td><?php
                        $badgeMap = ['paid'=>['success','Pago'],'pending'=>['warning','Pendente'],'cancelled'=>['danger','Cancelada'],'refunded'=>['info','Reembolsada'],'failed'=>['danger','Falhou']];
                        $bm = $badgeMap[$b['payment_status']] ?? ['muted', $b['payment_status']];
                    ?><span class="badge badge-<?= $bm[0] ?>"><?= $bm[1] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="space-y-5">
        <div class="admin-card p-6">
            <h3 class="font-display text-base font-bold mb-3" style="color:var(--sepia)">Pedidos de cotação</h3>
            <?php if (!$groupRequests): ?>
                <p class="text-sm" style="color:var(--text-muted)">Nenhum pedido ainda.</p>
                <a href="<?= url('/instituicao/cotacao') ?>" class="admin-btn admin-btn-primary w-full justify-center mt-4"><i data-lucide="file-plus" class="w-4 h-4"></i>Pedir cotação em grupo</a>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($groupRequests as $gr): ?>
                    <div class="p-3 rounded-lg" style="background:var(--bg-surface)">
                        <div class="text-sm font-semibold" style="color:var(--sepia)"><?= e($gr['title']) ?></div>
                        <div class="text-xs" style="color:var(--text-muted)"><?= (int)$gr['people'] ?> pessoas · <span class="badge badge-info"><?= e($gr['status']) ?></span></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <a href="<?= url('/instituicao/cotacao') ?>" class="admin-btn admin-btn-secondary w-full justify-center mt-4"><i data-lucide="plus" class="w-4 h-4"></i>Novo pedido</a>
            <?php endif; ?>
        </div>

        <div class="admin-card p-6">
            <h3 class="font-display text-base font-bold mb-3" style="color:var(--sepia)">Seu link parceiro</h3>
            <?php
            $slug = dbOne("SELECT slug FROM institutions WHERE id=?", [$i['id']])['slug'] ?? null;
            $link = $slug ? (($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . url('/?parceiro='.$slug) : null;
            ?>
            <?php if ($link): ?>
                <div class="flex gap-2">
                    <input type="text" readonly value="<?= e($link) ?>" class="admin-input flex-1 text-xs font-mono" onclick="this.select()">
                    <button onclick="navigator.clipboard.writeText('<?= e($link) ?>');this.innerHTML='<i data-lucide=&quot;check&quot; class=&quot;w-4 h-4&quot;></i>';window.lucide&&window.lucide.createIcons()" class="admin-btn admin-btn-secondary"><i data-lucide="copy" class="w-4 h-4"></i></button>
                </div>
                <p class="text-xs mt-2" style="color:var(--text-muted)">Quem comprar por este link ganha <?= number_format($i['discount'],0) ?>% de desconto automático.</p>
            <?php else: ?>
                <p class="text-sm" style="color:var(--text-muted)">Solicite seu slug exclusivo em <a href="<?= url('/instituicao/perfil') ?>" class="font-semibold" style="color:var(--horizonte)">Conta</a>.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/institution_foot.php';
