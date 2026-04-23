<?php
$slug = $_GET['slug'] ?? '';
$r = dbOne("SELECT * FROM transfers WHERE slug=? AND status='published'", [$slug]);
if (!$r) { http_response_code(404); require VIEWS_DIR . '/public/404.php'; return; }
dbExec("UPDATE transfers SET views=views+1 WHERE id=?", [$r['id']]);

$related = dbAll("SELECT * FROM transfers WHERE status='published' AND id<>? ORDER BY RAND() LIMIT 4", [$r['id']]);
$includesArr = !empty($r['includes']) ? (json_decode($r['includes'], true) ?: []) : [];

$gallery = [];
if (!empty($r['cover_image'])) $gallery[] = storageUrl($r['cover_image']);
if (!empty($r['gallery'])) {
    $dec = json_decode($r['gallery'], true);
    if (is_array($dec)) foreach ($dec as $g) if ($g) $gallery[] = storageUrl($g);
}
$gallery = array_values(array_unique($gallery));

$pageTitle = $r['title'];
$pageDesc = $r['short_desc'] ?? '';

include VIEWS_DIR . '/partials/public_head.php';
?>

<section class="relative h-[55vh] min-h-[380px] overflow-hidden" style="margin-top:-80px">
    <?php if (!empty($r['cover_image'])): ?>
        <img src="<?= storageUrl($r['cover_image']) ?>" class="absolute inset-0 w-full h-full object-cover" alt="<?= e($r['title']) ?>">
    <?php else: ?>
        <div class="absolute inset-0" style="background:linear-gradient(135deg,var(--horizonte),var(--horizonte-dark))"></div>
    <?php endif; ?>
    <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(30,58,82,0.25) 0%, rgba(30,58,82,0.85) 100%)"></div>
    <div class="relative z-10 h-full flex items-end pb-14">
        <div class="max-w-7xl mx-auto px-6 w-full text-white">
            <a href="<?= url('/transfers') ?>" class="inline-flex items-center gap-1 text-sm text-white/80 hover:text-white mb-4"><i data-lucide="arrow-left" class="w-4 h-4"></i> Todos os transfers</a>
            <span class="inline-block text-[10px] uppercase tracking-widest font-bold px-3 py-1 rounded-full mb-3" style="background:var(--horizonte);color:#fff"><i data-lucide="car" class="w-3 h-3 inline"></i> Transfer privativo</span>
            <h1 class="font-display text-4xl md:text-6xl font-bold leading-tight mb-4 max-w-4xl"><?= e(tAuto($r['title'])) ?></h1>
            <div class="flex flex-wrap gap-5 text-sm text-white/85">
                <?php if ($r['location_from']): ?><div class="flex items-center gap-2"><i data-lucide="map-pin" class="w-4 h-4"></i><?= e(tAuto($r['location_from'])) ?> → <?= e(tAuto($r['location_to'])) ?></div><?php endif; ?>
                <?php if ($r['duration_minutes']): ?><div class="flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4"></i><?= (int)$r['duration_minutes'] ?> min</div><?php endif; ?>
                <div class="flex items-center gap-2"><i data-lucide="users" class="w-4 h-4"></i>Até <?= (int)$r['capacity'] ?> passageiros</div>
            </div>
        </div>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2 space-y-8">
                <div class="admin-card p-6 sm:p-8">
                    <h2 class="font-display text-2xl font-bold mb-4" style="color:var(--sepia)">Sobre este transfer</h2>
                    <div class="text-[15px] leading-relaxed" style="color:var(--text-secondary)"><?= nl2br(e(tAuto($r['description'] ?? $r['short_desc'] ?? ''))) ?></div>
                </div>

                <?php if ($includesArr): ?>
                <div class="admin-card p-6 sm:p-8">
                    <h2 class="font-display text-2xl font-bold mb-4" style="color:var(--sepia)">O que está incluído</h2>
                    <ul class="space-y-2">
                        <?php foreach ($includesArr as $inc): ?>
                        <li class="flex items-start gap-2 text-sm" style="color:var(--text-secondary)"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:var(--maresia)"></i><?= e(tAuto($inc)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (count($gallery) > 1): ?>
                <div class="admin-card p-6 sm:p-8">
                    <h2 class="font-display text-2xl font-bold mb-4" style="color:var(--sepia)">Galeria</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <?php foreach ($gallery as $g): ?>
                        <img src="<?= e($g) ?>" class="w-full h-32 object-cover rounded-lg" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <aside id="reservar" class="lg:sticky lg:top-28 lg:self-start space-y-5">
                <div class="admin-card p-6">
                    <div class="text-xs uppercase tracking-wider font-semibold mb-1" style="color:var(--text-muted)">A partir de</div>
                    <div class="font-display text-4xl font-bold mb-1" style="color:var(--terracota)"><?= formatPrice($r['price_pix'] ?: $r['price']) ?></div>
                    <?php if ($r['price_pix']): ?><div class="text-xs" style="color:var(--text-muted)">PIX · ou <?= formatPrice($r['price']) ?> no cartão</div><?php endif; ?>

                    <hr class="my-5" style="border-color:var(--border-default)">

                    <div class="space-y-3 text-sm mb-5" style="color:var(--text-secondary)">
                        <div class="flex items-center justify-between"><span class="inline-flex items-center gap-1.5" style="color:var(--text-muted)"><i data-lucide="car" class="w-4 h-4"></i>Veículo</span><strong style="color:var(--sepia)"><?= e(tAuto($r['vehicle_type'] ?? '—')) ?></strong></div>
                        <div class="flex items-center justify-between"><span class="inline-flex items-center gap-1.5" style="color:var(--text-muted)"><i data-lucide="users" class="w-4 h-4"></i>Capacidade</span><strong style="color:var(--sepia)">Até <?= (int)$r['capacity'] ?></strong></div>
                        <?php if ($r['distance_km']): ?>
                        <div class="flex items-center justify-between"><span class="inline-flex items-center gap-1.5" style="color:var(--text-muted)"><i data-lucide="route" class="w-4 h-4"></i>Distância</span><strong style="color:var(--sepia)"><?= (int)$r['distance_km'] ?> km</strong></div>
                        <?php endif; ?>
                        <div class="flex items-center justify-between"><span class="inline-flex items-center gap-1.5" style="color:var(--text-muted)"><i data-lucide="repeat" class="w-4 h-4"></i>Tipo</span><strong style="color:var(--sepia)"><?= !empty($r['one_way']) ? 'Apenas ida' : 'Ida e volta' ?></strong></div>
                    </div>

                    <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>?text=Ol%C3%A1!%20Quero%20reservar%20o%20transfer%20<?= urlencode($r['title']) ?>" target="_blank" class="btn-primary w-full"><i data-lucide="message-circle" class="w-5 h-5"></i> Reservar via WhatsApp</a>
                    <a href="<?= url('/contato') ?>" class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 font-semibold text-sm" style="color:var(--horizonte);border-color:var(--horizonte);background:rgba(58,107,138,0.05)"><i data-lucide="mail" class="w-4 h-4"></i> Solicitar orçamento</a>
                </div>
            </aside>
        </div>

        <?php if ($related): ?>
        <div class="mt-20">
            <h2 class="font-display text-3xl font-bold mb-8 text-center" style="color:var(--sepia)">Outros transfers</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <?php foreach ($related as $rel): ?>
                <a href="<?= url('/transfers/'.$rel['slug']) ?>" class="roteiro-card group">
                    <div class="img-wrap" style="aspect-ratio:4/3">
                        <?php if (!empty($rel['cover_image'])): ?>
                            <div class="slide active" style="background-image:url('<?= e(storageUrl($rel['cover_image'])) ?>')"></div>
                        <?php else: ?>
                            <div class="img-placeholder w-full h-full"><span><?= e(mb_substr($rel['title'],0,1)) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <div class="p-5">
                        <h3 class="font-display text-base font-bold leading-snug mb-2 line-clamp-2" style="color:var(--sepia)"><?= e(tAuto($rel['title'])) ?></h3>
                        <div class="font-display text-lg font-bold" style="color:var(--terracota)"><?= formatPrice($rel['price_pix'] ?: $rel['price']) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Sticky bottom bar mobile -->
<div class="mobile-book-bar md:hidden">
    <div class="flex-1 min-w-0">
        <div class="text-[10px] uppercase tracking-wider font-bold opacity-70">A partir de</div>
        <div class="font-display text-xl font-bold leading-none" style="color:var(--terracota)"><?= formatPrice($r['price_pix'] ?: $r['price']) ?></div>
    </div>
    <a href="#reservar" onclick="event.preventDefault();document.getElementById('reservar').scrollIntoView({behavior:'smooth',block:'start'})" class="btn-primary" style="white-space:nowrap"><i data-lucide="calendar-check" class="w-4 h-4"></i> Reservar</a>
</div>
<style>
.mobile-book-bar{position:fixed;bottom:0;left:0;right:0;z-index:60;background:var(--bg-card);border-top:1px solid var(--border-default);padding:12px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 -8px 24px -8px rgba(0,0,0,.15);padding-bottom:calc(12px + env(safe-area-inset-bottom))}
@media(min-width:768px){.mobile-book-bar{display:none !important}}
@media(max-width:767px){body{padding-bottom:88px}}
</style>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
