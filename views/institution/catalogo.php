<?php
requireInstitution();
$i = currentInstitution();
$isMacaiok = institutionPortalProgram($i) === 'macaiok';
$pageTitle = $isMacaiok ? 'Vivências disponíveis' : 'Catálogo exclusivo';
$partner = dbOne('SELECT referral_code FROM institutions WHERE id=?', [$i['id']]);
$roteiroWhere = "status='published'" . ($isMacaiok ? " AND macaiok_featured=1" : "");
$pacoteWhere = "status='published'" . ($isMacaiok ? " AND macaiok_featured=1" : "");
$roteiroPag = paginate(
    "SELECT COUNT(*) c FROM roteiros WHERE " . $roteiroWhere,
    "SELECT * FROM roteiros WHERE " . $roteiroWhere . " ORDER BY featured DESC, title",
    [],
    ['allowed' => [6, 12, 24], 'default' => 6, 'page_param' => 'page_roteiros', 'per_param' => 'per_roteiros']
);
$pacotePag = paginate(
    "SELECT COUNT(*) c FROM pacotes WHERE " . $pacoteWhere,
    "SELECT * FROM pacotes WHERE " . $pacoteWhere . " ORDER BY featured DESC, title",
    [],
    ['allowed' => [6, 12, 24], 'default' => 6, 'page_param' => 'page_pacotes', 'per_param' => 'per_pacotes']
);
$roteiros = $roteiroPag['rows'];
$pacotes  = $pacotePag['rows'];

include VIEWS_DIR . '/partials/institution_head.php';
?>
<p class="text-sm mb-6" style="color:var(--text-secondary)"><?= $isMacaiok ? 'Escolha uma vivência e abra o checkout para gerar o fluxo que os responsáveis vão preencher e pagar.' : 'Catálogo completo com desconto automático de <b>' . number_format($i['discount'],0) . '%</b> para ' . e($i['name']) . '.' ?></p>

<h2 class="font-display text-xl font-bold mb-4 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="compass" class="w-5 h-5" style="color:var(--terracota)"></i>Passeios</h2>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-10">
    <?php foreach ($roteiros as $r): $pf = $r['price_pix'] ?: $r['price']; $pfDisc = $pf * (1 - $i['discount']/100); $href = $isMacaiok ? referralShareUrl($partner['referral_code'] ?? '', '/macaiok/checkout?roteiro='.$r['id']) : url('/passeios/'.$r['slug'].'?parceiro='.$i['id']); ?>
    <a href="<?= e($href) ?>" target="_blank" class="admin-card p-4 hover:shadow-lg transition">
        <?php if ($r['cover_image']): ?>
            <img src="<?= storageUrl($r['cover_image']) ?>" class="w-full aspect-[4/3] object-cover rounded-lg mb-3">
        <?php else: ?>
            <div class="w-full aspect-[4/3] rounded-lg mb-3 img-placeholder"><span><?= e(mb_substr($r['title'],0,1)) ?></span></div>
        <?php endif; ?>
        <div class="font-display font-bold text-sm leading-snug mb-1 line-clamp-2" style="color:var(--sepia)"><?= e($r['title']) ?></div>
        <div class="text-xs mb-2" style="color:var(--text-muted)"><?= e($r['location'] ?: '—') ?></div>
        <div class="flex items-end justify-between pt-2 border-t" style="border-color:var(--border-default)">
            <div>
                <div class="text-[10px] uppercase font-semibold" style="color:var(--text-muted)">Seu preço</div>
                <div class="font-display text-lg font-bold" style="color:var(--terracota)"><?= formatBRL($pfDisc) ?></div>
                <?php if ($i['discount']>0): ?><div class="text-[10px] line-through" style="color:var(--text-muted)"><?= formatBRL($pf) ?></div><?php endif; ?>
            </div>
            <i data-lucide="arrow-up-right" class="w-4 h-4" style="color:var(--terracota)"></i>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php $pag = $roteiroPag; include VIEWS_DIR . '/partials/pagination.php'; unset($pag); ?>

<h2 class="font-display text-xl font-bold mb-4 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="package" class="w-5 h-5" style="color:var(--horizonte)"></i>Pacotes</h2>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($pacotes as $p): $pf = $p['price_pix'] ?: $p['price']; $pfDisc = $pf * (1 - $i['discount']/100); $href = $isMacaiok ? referralShareUrl($partner['referral_code'] ?? '', '/macaiok/checkout?pacote='.$p['id']) : url('/pacotes/'.$p['slug'].'?parceiro='.$i['id']); ?>
    <a href="<?= e($href) ?>" target="_blank" class="admin-card p-4 hover:shadow-lg transition">
        <?php if ($p['cover_image']): ?>
            <img src="<?= storageUrl($p['cover_image']) ?>" class="w-full aspect-[4/3] object-cover rounded-lg mb-3">
        <?php else: ?>
            <div class="w-full aspect-[4/3] rounded-lg mb-3 img-placeholder"><span><?= e(mb_substr($p['title'],0,1)) ?></span></div>
        <?php endif; ?>
        <div class="font-display font-bold text-sm leading-snug mb-1 line-clamp-2" style="color:var(--sepia)"><?= e($p['title']) ?></div>
        <div class="text-xs mb-2" style="color:var(--text-muted)"><?= e($p['destination'] ?: '—') ?> · <?= (int)$p['duration_days'] ?>D<?= (int)$p['duration_nights'] ?>N</div>
        <div class="flex items-end justify-between pt-2 border-t" style="border-color:var(--border-default)">
            <div>
                <div class="text-[10px] uppercase font-semibold" style="color:var(--text-muted)">Seu preço</div>
                <div class="font-display text-lg font-bold" style="color:var(--terracota)"><?= formatBRL($pfDisc) ?></div>
                <?php if ($i['discount']>0): ?><div class="text-[10px] line-through" style="color:var(--text-muted)"><?= formatBRL($pf) ?></div><?php endif; ?>
            </div>
            <i data-lucide="arrow-up-right" class="w-4 h-4" style="color:var(--terracota)"></i>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php $pag = $pacotePag; include VIEWS_DIR . '/partials/pagination.php'; unset($pag); ?>

<?php include VIEWS_DIR . '/partials/institution_foot.php';
