<?php
$accountTitle = 'Favoritos';
$accountTab = 'favoritos';
include VIEWS_DIR . '/partials/account_layout.php';

$cid = currentCustomerId();
$items = dbAll("
    SELECT w.id AS wid, w.entity_type, w.entity_id,
        COALESCE(r.title, p.title) AS title,
        COALESCE(r.slug, p.slug) AS slug,
        COALESCE(r.cover_image, p.cover_image) AS cover,
        COALESCE(r.price, p.price) AS price,
        COALESCE(r.short_desc, p.short_desc) AS short_desc,
        COALESCE(r.location, p.destination) AS location
    FROM wishlist w
    LEFT JOIN roteiros r ON w.entity_type='roteiro' AND w.entity_id=r.id
    LEFT JOIN pacotes p ON w.entity_type='pacote' AND w.entity_id=p.id
    WHERE w.customer_id=?
    ORDER BY w.created_at DESC
", [$cid]);
?>

<div class="glass-card p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="font-display text-2xl font-bold" style="color:var(--sepia)">Seus favoritos</h2>
            <p class="text-xs" style="color:var(--text-muted)"><?= count($items) ?> item<?= count($items)===1?'':'s' ?> salvo<?= count($items)===1?'':'s' ?></p>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i data-lucide="heart-crack" class="w-7 h-7"></i></div>
            <div class="empty-state-title">Sua lista está vazia</div>
            <div class="empty-state-desc">Salve roteiros e pacotes que te inspiram para voltar depois.</div>
            <a href="<?= url('/roteiros') ?>" class="btn-primary inline-flex"><i data-lucide="compass" class="w-4 h-4"></i> Descobrir roteiros</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" id="fav-grid">
            <?php foreach ($items as $it):
                $link = ($it['entity_type']==='roteiro'?'/roteiros/':'/pacotes/') . $it['slug'];
                $cover = $it['cover'] ? storageUrl($it['cover']) : '';
            ?>
                <div class="fav-card" data-wid="<?= (int)$it['wid'] ?>">
                    <a href="<?= url($link) ?>" class="block">
                        <div class="fav-card-image" style="<?= $cover ? 'background-image:url(\'' . e($cover) . '\')' : '' ?>">
                            <?php if (!$cover): ?>
                                <div class="w-full h-full flex items-center justify-center font-display text-4xl font-bold" style="color:var(--terracota);opacity:0.3"><?= e(mb_substr($it['title'],0,1)) ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <button type="button" class="fav-card-unheart" onclick="removeFavorite(<?= (int)$it['wid'] ?>, this)" aria-label="Remover dos favoritos">
                        <i data-lucide="heart" class="w-4 h-4" style="fill:currentColor"></i>
                    </button>
                    <a href="<?= url($link) ?>" class="block p-5">
                        <span class="pill pill-primary mb-2"><?= e($it['entity_type']) ?></span>
                        <h3 class="font-display font-bold text-lg mt-2 mb-1 line-clamp-2" style="color:var(--sepia)"><?= e($it['title']) ?></h3>
                        <?php if ($it['location']): ?>
                            <div class="flex items-center gap-1 text-xs mb-2" style="color:var(--horizonte)">
                                <i data-lucide="map-pin" class="w-3 h-3"></i> <?= e($it['location']) ?>
                            </div>
                        <?php endif; ?>
                        <p class="text-sm line-clamp-2 mb-3" style="color:var(--text-secondary)"><?= e($it['short_desc']) ?></p>
                        <div class="flex items-end justify-between pt-3 border-t" style="border-color:var(--border-default)">
                            <div>
                                <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:var(--text-muted)">A partir de</div>
                                <div class="font-display font-bold text-xl" style="color:var(--terracota)"><?= formatPrice((float)$it['price']) ?></div>
                            </div>
                            <span class="pill pill-info"><i data-lucide="arrow-right" class="w-3 h-3"></i></span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
async function removeFavorite(wid, btn) {
    const card = btn.closest('.fav-card');
    if (!card || !confirm('Remover dos favoritos?')) return;
    card.style.opacity = '0.5';
    try {
        const r = await caminhosApi('<?= url('/api/wishlist') ?>?action=remove', {
            method: 'POST',
            data: new URLSearchParams({ id: wid })
        });
        if (r.ok) {
            card.style.transition = 'all 300ms';
            card.style.transform = 'scale(0.9)';
            card.style.opacity = '0';
            setTimeout(() => {
                card.remove();
                showToast('Removido dos favoritos', 'info');
                const grid = document.getElementById('fav-grid');
                if (grid && !grid.children.length) location.reload();
            }, 300);
        } else {
            card.style.opacity = '1';
            showToast(r.msg || 'Erro ao remover', 'error');
        }
    } catch (e) {
        card.style.opacity = '1';
        showToast('Erro de rede', 'error');
    }
}
</script>

<?php include VIEWS_DIR . '/partials/account_layout_end.php'; ?>
