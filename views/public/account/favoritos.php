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
        COALESCE(r.short_desc, p.short_desc) AS short_desc
    FROM wishlist w
    LEFT JOIN roteiros r ON w.entity_type='roteiro' AND w.entity_id=r.id
    LEFT JOIN pacotes p ON w.entity_type='pacote' AND w.entity_id=p.id
    WHERE w.customer_id=?
    ORDER BY w.created_at DESC
", [$cid]);
?>

<div class="rounded-2xl border p-6" style="background:#fff;border-color:var(--border-default)">
    <h2 class="font-display text-2xl font-bold mb-6" style="color:var(--sepia)">Seus favoritos</h2>

    <?php if (empty($items)): ?>
        <div class="text-center py-12">
            <i data-lucide="heart-crack" class="w-14 h-14 mx-auto mb-4" style="color:var(--text-muted)"></i>
            <p class="mb-4" style="color:var(--text-secondary)">Sua lista de favoritos está vazia.</p>
            <a href="<?= url('/roteiros') ?>" class="btn-primary inline-flex">Descobrir roteiros</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($items as $it): $link = ($it['entity_type']==='roteiro'?'/roteiros/':'/pacotes/') . $it['slug']; ?>
                <a href="<?= url($link) ?>" class="group rounded-2xl overflow-hidden border transition hover:shadow-xl" style="background:var(--areia-light);border-color:var(--border-default)">
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <?php if ($it['cover']): ?>
                            <img src="<?= asset($it['cover']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition" alt="">
                        <?php endif; ?>
                        <button type="button" onclick="event.preventDefault();toggleWish(<?= $it['wid'] ?>,this)" class="absolute top-3 right-3 w-10 h-10 rounded-full flex items-center justify-center transition" style="background:rgba(255,255,255,0.95)">
                            <i data-lucide="heart" class="w-5 h-5" style="fill:var(--terracota);color:var(--terracota)"></i>
                        </button>
                    </div>
                    <div class="p-5">
                        <span class="text-[10px] font-bold uppercase tracking-wider" style="color:var(--terracota)"><?= $it['entity_type'] ?></span>
                        <h3 class="font-display font-bold text-lg mt-1 mb-2" style="color:var(--sepia)"><?= e($it['title']) ?></h3>
                        <p class="text-sm line-clamp-2 mb-3" style="color:var(--text-secondary)"><?= e($it['short_desc']) ?></p>
                        <p class="font-display font-bold text-xl" style="color:var(--sepia)"><?= formatPrice((float)$it['price']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
async function toggleWish(id, btn) {
    const r = await fetch('<?= url('/api/wishlist') ?>?action=remove', {
        method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'},
        body: new URLSearchParams({id, csrf_token:'<?= csrfToken() ?>'})
    }).then(r=>r.json());
    if (r.ok) btn.closest('a').remove();
}
</script>

<?php include VIEWS_DIR . '/partials/account_layout_end.php'; ?>
