<?php
$slug = $_GET['slug'] ?? '';
$r = dbOne("SELECT r.*, c.name AS category_name FROM roteiros r LEFT JOIN categories c ON r.category_id=c.id WHERE r.slug=? AND r.status='published'", [$slug]);
if (!$r) {
    http_response_code(404);
    require VIEWS_DIR . '/public/404.php';
    return;
}
dbExec("UPDATE roteiros SET views = views + 1 WHERE id = ?", [$r['id']]);
$departures = dbAll("SELECT * FROM departures WHERE entity_type='roteiro' AND entity_id=? AND status='open' AND departure_date>=CURDATE() ORDER BY departure_date", [$r['id']]);
$related = dbAll("SELECT * FROM roteiros WHERE status='published' AND id<>? ORDER BY RAND() LIMIT 3", [$r['id']]);

// Galeria
$gallery = [];
if ($r['cover_image']) $gallery[] = storageUrl($r['cover_image']);
if (!empty($r['gallery'])) {
    $decg = json_decode($r['gallery'], true);
    if (is_array($decg)) foreach ($decg as $g) if ($g) $gallery[] = storageUrl($g);
}
$gallery = array_values(array_unique($gallery));

$pageTitle = $r['title'];
$pageDesc = $r['short_desc'] ?? '';

include VIEWS_DIR . '/partials/public_head.php';
?>

<!-- Hero image -->
<section class="relative h-[60vh] min-h-[400px] overflow-hidden" style="margin-top:-80px">
    <?php if ($r['cover_image']): ?>
        <img src="<?= storageUrl($r['cover_image']) ?>" class="absolute inset-0 w-full h-full object-cover" alt="<?= e($r['title']) ?>">
    <?php else: ?>
        <div class="img-placeholder absolute inset-0"></div>
    <?php endif; ?>
    <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(30,58,82,0.2) 0%, rgba(30,58,82,0.85) 100%)"></div>
    <img src="<?= asset('brand/selo-branco.png') ?>" class="seal-rotate absolute hidden md:block" style="top:120px;right:40px;width:100px;opacity:0.35;z-index:5" alt="">

    <div class="relative z-10 h-full flex items-end pb-16">
        <div class="max-w-7xl mx-auto px-6 w-full text-white">
            <a href="<?= url('/roteiros') ?>" class="inline-flex items-center gap-1 text-sm text-white/80 hover:text-white mb-4">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Todos os passeios
            </a>
            <?php if ($r['category_name']): ?>
                <span class="inline-block text-[10px] uppercase tracking-widest font-bold px-3 py-1 rounded-full mb-3" style="background:var(--terracota);color:white"><?= e($r['category_name']) ?></span>
            <?php endif; ?>
            <h1 class="font-display text-4xl md:text-6xl font-bold leading-tight mb-4 max-w-4xl"><?= e($r['title']) ?></h1>
            <div class="flex flex-wrap gap-5 text-sm text-white/85">
                <?php if ($r['location']): ?>
                <div class="flex items-center gap-2"><i data-lucide="map-pin" class="w-4 h-4"></i><?= e($r['location']) ?></div>
                <?php endif; ?>
                <?php if ($r['duration_hours']): ?>
                <div class="flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4"></i><?= $r['duration_hours'] ?>h de duração</div>
                <?php endif; ?>
                <div class="flex items-center gap-2"><i data-lucide="users" class="w-4 h-4"></i>De <?= $r['min_people'] ?> a <?= $r['max_people'] ?> pessoas</div>
            </div>
        </div>
    </div>
</section>

<?php if (count($gallery) > 1): ?>
<!-- Gallery premium -->
<section class="py-8" x-data="galleryLightbox(<?= htmlspecialchars(json_encode($gallery), ENT_QUOTES) ?>)">
    <div class="max-w-7xl mx-auto px-6">
        <div class="hero-gallery-grid">
            <?php $show = array_slice($gallery, 0, 5); foreach ($show as $idx => $img): ?>
            <div @click="open(<?= $idx ?>)">
                <img src="<?= e($img) ?>" alt="Foto <?= $idx+1 ?> de <?= e($r['title']) ?>" loading="<?= $idx===0?'eager':'lazy' ?>">
                <?php if ($idx === 4 && count($gallery) > 5): ?>
                <div class="hero-gallery-more">
                    <i data-lucide="images" class="w-6 h-6"></i>
                    +<?= count($gallery) - 5 ?> fotos
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Lightbox -->
        <template x-teleport="body">
            <div x-show="isOpen" x-cloak class="gallery-lightbox-backdrop" @keydown.escape.window="close()" @keydown.arrow-left.window="prev()" @keydown.arrow-right.window="next()">
                <button class="gallery-lightbox-close" @click="close()"><i data-lucide="x" class="w-5 h-5"></i></button>
                <button class="gallery-lightbox-arrow prev" @click="prev()"><i data-lucide="chevron-left" class="w-6 h-6"></i></button>
                <img :src="images[current]" :alt="'Foto ' + (current+1)" class="gallery-lightbox-image">
                <button class="gallery-lightbox-arrow next" @click="next()"><i data-lucide="chevron-right" class="w-6 h-6"></i></button>
                <div class="gallery-lightbox-counter"><span x-text="current+1"></span> / <span x-text="images.length"></span></div>
            </div>
        </template>
    </div>
</section>
<script>
function galleryLightbox(images) {
    return {
        images, isOpen:false, current:0,
        open(i){ this.current=i; this.isOpen=true; document.body.style.overflow='hidden'; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        close(){ this.isOpen=false; document.body.style.overflow=''; },
        prev(){ this.current = (this.current - 1 + this.images.length) % this.images.length; },
        next(){ this.current = (this.current + 1) % this.images.length; },
    };
}
</script>
<?php endif; ?>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid lg:grid-cols-3 gap-10">
            <!-- Content -->
            <div class="lg:col-span-2 space-y-10">
                <!-- Description -->
                <div class="admin-card p-8">
                    <h2 class="font-display text-2xl font-bold mb-5" style="color:var(--sepia)">Sobre o passeio</h2>
                    <div class="prose max-w-none text-[15px] leading-relaxed" style="color:var(--text-secondary)">
                        <?= nl2br(e($r['description'] ?? $r['short_desc'] ?? '')) ?>
                    </div>
                </div>

                <?php
                $inc = $r['includes'] ? json_decode($r['includes'], true) : null;
                $exc = $r['excludes'] ? json_decode($r['excludes'], true) : null;
                ?>
                <?php if ($inc || $exc): ?>
                <div class="grid md:grid-cols-2 gap-6">
                    <?php if ($inc): ?>
                    <div class="admin-card p-6">
                        <h3 class="font-display text-lg font-bold mb-4 flex items-center gap-2" style="color:var(--maresia-dark)">
                            <i data-lucide="check-circle" class="w-5 h-5"></i> Incluso
                        </h3>
                        <ul class="space-y-2 text-sm">
                            <?php foreach ($inc as $it): ?>
                                <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:var(--maresia)"></i><?= e($it) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php if ($exc): ?>
                    <div class="admin-card p-6">
                        <h3 class="font-display text-lg font-bold mb-4 flex items-center gap-2" style="color:var(--terracota-dark)">
                            <i data-lucide="x-circle" class="w-5 h-5"></i> Não incluso
                        </h3>
                        <ul class="space-y-2 text-sm">
                            <?php foreach ($exc as $it): ?>
                                <li class="flex items-start gap-2"><i data-lucide="x" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:var(--terracota)"></i><?= e($it) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Meeting point -->
                <?php if ($r['meeting_point']): ?>
                <div class="admin-card p-6">
                    <h3 class="font-display text-lg font-bold mb-3" style="color:var(--sepia)">Ponto de encontro</h3>
                    <div class="flex items-start gap-3 text-sm" style="color:var(--text-secondary)">
                        <i data-lucide="map-pin" class="w-5 h-5 flex-shrink-0 mt-0.5" style="color:var(--terracota)"></i>
                        <?= e($r['meeting_point']) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar sticky -->
            <aside class="lg:sticky lg:top-28 lg:self-start space-y-5">
                <div class="admin-card p-6">
                    <div class="text-xs uppercase tracking-wider font-semibold mb-1" style="color:var(--text-muted)">A partir de</div>
                    <div class="font-display text-4xl font-bold mb-1" style="color:var(--terracota)"><?= formatBRL($r['price_pix'] ?: $r['price']) ?></div>
                    <?php if ($r['price_pix']): ?>
                        <div class="text-xs" style="color:var(--text-muted)">por pessoa · PIX · ou <?= formatBRL($r['price']) ?> no cartão</div>
                    <?php else: ?>
                        <div class="text-xs" style="color:var(--text-muted)">por pessoa</div>
                    <?php endif; ?>

                    <hr class="my-5" style="border-color:var(--border-default)">

                    <?php if ($departures): ?>
                        <div class="text-sm font-semibold mb-3" style="color:var(--sepia)">Próximas saídas</div>
                        <div class="space-y-2 mb-5">
                            <?php foreach ($departures as $d): ?>
                                <div class="flex items-center justify-between p-3 rounded-lg" style="background:var(--bg-surface)">
                                    <div>
                                        <div class="text-sm font-semibold" style="color:var(--sepia)"><?= formatDate($d['departure_date'], 'd \d\e F') ?></div>
                                        <?php if ($d['departure_time']): ?><div class="text-xs" style="color:var(--text-muted)">Saída às <?= date('H:i', strtotime($d['departure_time'])) ?></div><?php endif; ?>
                                    </div>
                                    <div class="text-xs font-semibold" style="color:var(--maresia-dark)"><?= max(0, $d['seats_total']-$d['seats_sold']) ?> vagas</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <a href="<?= url('/checkout?roteiro=' . $r['id']) ?>" class="btn-primary w-full">
                        <i data-lucide="calendar-check" class="w-5 h-5"></i> Reservar agora
                    </a>
                    <button type="button" onclick="window.cart.add('roteiro', <?= (int)$r['id'] ?>)" class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 font-semibold text-sm transition hover:scale-[1.02]" style="color:var(--horizonte);border-color:var(--horizonte);background:rgba(58,107,138,0.05)">
                        <i data-lucide="shopping-bag" class="w-4 h-4"></i> Adicionar ao carrinho
                    </button>
                    <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>?text=Ol%C3%A1!%20Tenho%20interesse%20no%20passeio%20<?= urlencode($r['title']) ?>" target="_blank" class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 font-semibold text-sm" style="color:var(--maresia-dark);border-color:var(--maresia)">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> Falar no WhatsApp
                    </a>
                </div>

                <div class="admin-card p-5 flex items-start gap-3">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0 mt-1" style="color:var(--maresia)"></i>
                    <div>
                        <div class="text-sm font-semibold" style="color:var(--sepia)">Reserva garantida</div>
                        <div class="text-xs" style="color:var(--text-secondary)">Cancelamento grátis até 48h antes da data.</div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Related -->
        <?php if ($related): ?>
        <div class="mt-20">
            <h2 class="font-display text-3xl font-bold mb-8 text-center" style="color:var(--sepia)">Você também vai gostar</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($related as $rel): ?>
                <a href="<?= url('/roteiros/'.$rel['slug']) ?>" class="roteiro-card group">
                    <div class="img-wrap">
                        <?php if ($rel['cover_image']): ?><img src="<?= storageUrl($rel['cover_image']) ?>" alt="<?= e($rel['title']) ?>">
                        <?php else: ?><div class="img-placeholder w-full h-full"><span><?= e(mb_substr($rel['title'],0,1)) ?></span></div><?php endif; ?>
                    </div>
                    <div class="p-5">
                        <h3 class="font-display text-lg font-bold mb-2 line-clamp-2" style="color:var(--sepia)"><?= e($rel['title']) ?></h3>
                        <div class="font-display text-lg font-bold" style="color:var(--terracota)"><?= formatBRL($rel['price_pix'] ?: $rel['price']) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
