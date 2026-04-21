<?php
$slug = $_GET['slug'] ?? '';
$p = dbOne("SELECT * FROM pacotes WHERE slug=? AND status='published'", [$slug]);
if (!$p) { http_response_code(404); require VIEWS_DIR . '/public/404.php'; return; }
dbExec("UPDATE pacotes SET views=views+1 WHERE id=?", [$p['id']]);

$pageTitle = $p['title'];
$pageDesc = $p['short_desc'];
include VIEWS_DIR . '/partials/public_head.php';
?>

<section class="relative h-[55vh] min-h-[400px] overflow-hidden" style="margin-top:-80px">
    <?php if ($p['cover_image']): ?><img src="<?= storageUrl($p['cover_image']) ?>" class="absolute inset-0 w-full h-full object-cover"><?php else: ?><div class="img-placeholder absolute inset-0"></div><?php endif; ?>
    <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(30,58,82,0.2) 0%, rgba(201,107,74,0.8) 100%)"></div>
    <div class="relative z-10 h-full flex items-end pb-16">
        <div class="max-w-7xl mx-auto px-6 w-full text-white">
            <a href="<?= url('/pacotes') ?>" class="inline-flex items-center gap-1 text-sm text-white/80 hover:text-white mb-4"><i data-lucide="arrow-left" class="w-4 h-4"></i>Todos os pacotes</a>
            <span class="inline-block text-[10px] uppercase tracking-widest font-bold px-3 py-1 rounded-full mb-3 bg-white" style="color:var(--terracota)"><?= $p['duration_days'] ?> dias / <?= $p['duration_nights'] ?> noites</span>
            <h1 class="font-display text-4xl md:text-6xl font-bold leading-tight mb-4 max-w-4xl"><?= e($p['title']) ?></h1>
            <div class="flex items-center gap-2 text-sm text-white/85"><i data-lucide="map-pin" class="w-4 h-4"></i><?= e($p['destination']) ?></div>
        </div>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2 space-y-8">
                <div class="admin-card p-8">
                    <h2 class="font-display text-2xl font-bold mb-5" style="color:var(--sepia)">Sobre o pacote</h2>
                    <div class="text-[15px] leading-relaxed" style="color:var(--text-secondary)"><?= nl2br(e($p['description'] ?? $p['short_desc'] ?? '')) ?></div>
                </div>
            </div>
            <aside class="lg:sticky lg:top-28 lg:self-start">
                <div class="admin-card p-6">
                    <div class="text-xs uppercase tracking-wider font-semibold mb-1" style="color:var(--text-muted)">A partir de</div>
                    <div class="font-display text-4xl font-bold mb-1" style="color:var(--terracota)"><?= formatBRL($p['price_pix'] ?: $p['price']) ?></div>
                    <?php if ($p['installments']>1): ?><div class="text-xs mb-5" style="color:var(--text-muted)">ou <?= $p['installments'] ?>x sem juros de <?= formatBRL($p['price']/$p['installments']) ?></div><?php endif; ?>
                    <a href="<?= url('/checkout?pacote='.$p['id']) ?>" class="btn-primary w-full mt-4"><i data-lucide="calendar-check" class="w-5 h-5"></i>Reservar agora</a>
                    <button type="button" onclick="window.cart.add('pacote', <?= (int)$p['id'] ?>)" class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 font-semibold text-sm transition hover:scale-[1.02]" style="color:var(--horizonte);border-color:var(--horizonte);background:rgba(58,107,138,0.05)"><i data-lucide="shopping-bag" class="w-4 h-4"></i>Adicionar ao carrinho</button>
                    <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>?text=Ol%C3%A1!%20Tenho%20interesse%20no%20pacote%20<?= urlencode($p['title']) ?>" target="_blank" class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 font-semibold text-sm" style="color:var(--maresia-dark);border-color:var(--maresia)"><i data-lucide="message-circle" class="w-4 h-4"></i>Consultar</a>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
