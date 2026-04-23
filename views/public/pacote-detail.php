<?php
$slug = $_GET['slug'] ?? '';
$p = dbOne("SELECT * FROM pacotes WHERE slug=? AND status='published'", [$slug]);
if (!$p) { http_response_code(404); require VIEWS_DIR . '/public/404.php'; return; }
dbExec("UPDATE pacotes SET views=views+1 WHERE id=?", [$p['id']]);

// saídas
$departuresAll = dbAll("SELECT * FROM departures WHERE entity_type='pacote' AND entity_id=? AND departure_date>=CURDATE() ORDER BY departure_date", [$p['id']]);
$departures    = array_values(array_filter($departuresAll, fn($d) => $d['status'] === 'open'));
$related       = dbAll("SELECT * FROM pacotes WHERE status='published' AND id<>? ORDER BY RAND() LIMIT 4", [$p['id']]);

// Avaliações destacadas (filtrar por roteiro_id ou pacote no futuro; por enquanto exibir featured)
$reviews    = dbAll("SELECT * FROM testimonials WHERE active=1 ORDER BY featured DESC, created_at DESC LIMIT 9");
$reviewsAvg = $reviews ? round(array_sum(array_column($reviews,'rating')) / count($reviews), 1) : 0;

$availabilityMap = [];
foreach ($departuresAll as $d) {
    $availabilityMap[$d['departure_date']] = [
        'status' => $d['status'],
        'seats'  => max(0, (int)$d['seats_total'] - (int)$d['seats_sold']),
        'price'  => $d['price_override'] !== null ? (float)$d['price_override'] : (float)($p['price_pix'] ?: $p['price']),
        'time'   => $d['departure_time'],
    ];
}

// galeria
$gallery = [];
if ($p['cover_image']) $gallery[] = storageUrl($p['cover_image']);
if (!empty($p['gallery'])) {
    $dec = json_decode($p['gallery'], true);
    if (is_array($dec)) foreach ($dec as $g) if ($g) $gallery[] = storageUrl($g);
}
$gallery = array_values(array_unique($gallery));

$pageTitle = $p['title'];
$pageDesc  = $p['short_desc'];
include VIEWS_DIR . '/partials/public_head.php';
?>

<!-- HERO -->
<section class="relative h-[55vh] sm:h-[60vh] min-h-[360px] overflow-hidden" style="margin-top:-80px">
    <?php if ($p['cover_image']): ?>
        <img src="<?= storageUrl($p['cover_image']) ?>" class="absolute inset-0 w-full h-full object-cover" alt="<?= e($p['title']) ?>">
    <?php else: ?>
        <div class="img-placeholder absolute inset-0"></div>
    <?php endif; ?>
    <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(30,58,82,0.25) 0%, rgba(201,107,74,0.78) 100%)"></div>
    <img src="<?= asset('brand/selo-branco.png') ?>" class="seal-rotate absolute hidden md:block" style="top:120px;right:40px;width:100px;opacity:0.3;z-index:5" alt="">

    <div class="relative z-10 h-full flex items-end pb-10 sm:pb-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 w-full text-white">
            <a href="<?= url('/pacotes') ?>" class="inline-flex items-center gap-1 text-sm text-white/85 hover:text-white mb-3">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> <?= t('nav.packages') ?>
            </a>
            <div class="flex flex-wrap gap-2 mb-3">
                <span class="inline-block text-[10px] uppercase tracking-widest font-bold px-3 py-1 rounded-full bg-white" style="color:var(--terracota)">
                    <?= (int)$p['duration_days'] ?> dias / <?= (int)$p['duration_nights'] ?> noites
                </span>
                <?php if (!empty($p['featured'])): ?>
                    <span class="inline-block text-[10px] uppercase tracking-widest font-bold px-3 py-1 rounded-full" style="background:var(--terracota);color:white">Destaque</span>
                <?php endif; ?>
            </div>
            <h1 class="font-display text-3xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-4 max-w-4xl"><?= e(tAuto($p['title'])) ?></h1>
            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-white/90">
                <?php if ($p['destination']): ?>
                    <div class="flex items-center gap-2"><i data-lucide="map-pin" class="w-4 h-4"></i><?= e(tAuto($p['destination'])) ?></div>
                <?php endif; ?>
                <div class="flex items-center gap-2"><i data-lucide="calendar" class="w-4 h-4"></i><?= (int)$p['duration_days'] ?> dias de viagem</div>
                <?php if (!empty($p['min_people'])): ?>
                    <div class="flex items-center gap-2"><i data-lucide="users" class="w-4 h-4"></i>De <?= (int)$p['min_people'] ?> a <?= (int)$p['max_people'] ?> pessoas</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if (count($gallery) > 1): ?>
<section class="py-6 sm:py-8" x-data="galleryLightbox(<?= htmlspecialchars(json_encode($gallery), ENT_QUOTES) ?>)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="hero-gallery-grid">
            <?php $show = array_slice($gallery, 0, 5); foreach ($show as $idx => $img): ?>
            <div @click="open(<?= $idx ?>)">
                <img src="<?= e($img) ?>" alt="Foto <?= $idx+1 ?> de <?= e($p['title']) ?>" loading="<?= $idx===0?'eager':'lazy' ?>">
                <?php if ($idx === 4 && count($gallery) > 5): ?>
                    <div class="hero-gallery-more"><i data-lucide="images" class="w-6 h-6"></i>+<?= count($gallery) - 5 ?> fotos</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
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
if (typeof galleryLightbox === 'undefined') {
    function galleryLightbox(images) {
        return { images, isOpen:false, current:0,
            open(i){ this.current=i; this.isOpen=true; document.body.style.overflow='hidden'; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
            close(){ this.isOpen=false; document.body.style.overflow=''; },
            prev(){ this.current=(this.current-1+this.images.length)%this.images.length; },
            next(){ this.current=(this.current+1)%this.images.length; },
        };
    }
}
</script>
<?php endif; ?>

<section class="py-10 sm:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid lg:grid-cols-3 gap-6 lg:gap-10">
            <div class="lg:col-span-2 space-y-6 sm:space-y-8">
                <!-- Sobre -->
                <div class="admin-card p-6 sm:p-8">
                    <h2 class="font-display text-2xl font-bold mb-4" style="color:var(--sepia)"><?= t('detail.about') ?></h2>
                    <div class="text-[15px] leading-relaxed" style="color:var(--text-secondary)"><?= nl2br(e(tAuto($p['description'] ?? $p['short_desc'] ?? ''))) ?></div>
                </div>

                <?php
                $highlights = !empty($p['highlights']) ? json_decode($p['highlights'], true) : null;
                if (is_array($highlights) && $highlights):
                ?>
                <div class="admin-card p-6 sm:p-8">
                    <h2 class="font-display text-2xl font-bold mb-5 flex items-center gap-3" style="color:var(--sepia)"><i data-lucide="sparkles" class="w-6 h-6" style="color:var(--terracota)"></i> <?= t('detail.highlights') ?></h2>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <?php foreach ($highlights as $h): ?>
                            <div class="flex items-start gap-3 p-4 rounded-xl" style="background:rgba(201,107,74,0.06);border:1px solid rgba(201,107,74,0.12)">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:var(--terracota);color:#fff"><i data-lucide="star" class="w-4 h-4"></i></div>
                                <span class="text-sm leading-relaxed" style="color:var(--text-primary)"><?= e($h) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                $itin = !empty($p['itinerary']) ? json_decode($p['itinerary'], true) : null;
                if (is_array($itin) && $itin):
                ?>
                <div class="admin-card p-6 sm:p-8">
                    <h2 class="font-display text-2xl font-bold mb-5 flex items-center gap-3" style="color:var(--sepia)"><i data-lucide="route" class="w-6 h-6" style="color:var(--horizonte)"></i> <?= t('detail.itinerary') ?></h2>
                    <div class="space-y-3">
                        <?php foreach ($itin as $idx => $item):
                            $title = is_array($item) ? ($item['title'] ?? '') : '';
                            $desc  = is_array($item) ? ($item['description'] ?? $item['desc'] ?? '') : (string)$item;
                        ?>
                        <div class="flex gap-4" x-data="{open: <?= $idx===0?'true':'false' ?>}">
                            <div class="flex flex-col items-center flex-shrink-0">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-display font-bold text-sm text-white" style="background:linear-gradient(135deg,var(--horizonte),var(--horizonte-light))"><?= $idx + 1 ?></div>
                                <?php if ($idx < count($itin) - 1): ?><div class="flex-1 w-0.5 mt-2" style="background:var(--border-default);min-height:32px"></div><?php endif; ?>
                            </div>
                            <div class="flex-1 pb-2">
                                <button type="button" @click="open=!open" class="w-full text-left flex items-start justify-between gap-3">
                                    <?php if ($title): ?><div class="font-display font-bold text-base" style="color:var(--sepia)"><?= e($title) ?></div><?php endif; ?>
                                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform mt-1" :class="open && 'rotate-180'" style="color:var(--text-muted)"></i>
                                </button>
                                <div x-show="open" x-collapse class="mt-2 text-sm leading-relaxed" style="color:var(--text-secondary)"><?= nl2br(e($desc)) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                $inc = $p['includes'] ? json_decode($p['includes'], true) : null;
                $exc = $p['excludes'] ? json_decode($p['excludes'], true) : null;
                if ($inc || $exc):
                ?>
                <div class="grid md:grid-cols-2 gap-5">
                    <?php if ($inc): ?>
                    <div class="admin-card p-6">
                        <h3 class="font-display text-lg font-bold mb-4 flex items-center gap-2" style="color:var(--maresia-dark)"><i data-lucide="check-circle" class="w-5 h-5"></i> <?= t('detail.included') ?></h3>
                        <ul class="space-y-2 text-sm">
                            <?php foreach ($inc as $it): ?>
                                <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:var(--maresia)"></i><span style="color:var(--text-secondary)"><?= e($it) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php if ($exc): ?>
                    <div class="admin-card p-6">
                        <h3 class="font-display text-lg font-bold mb-4 flex items-center gap-2" style="color:var(--terracota-dark)"><i data-lucide="x-circle" class="w-5 h-5"></i> <?= t('detail.excluded') ?></h3>
                        <ul class="space-y-2 text-sm">
                            <?php foreach ($exc as $it): ?>
                                <li class="flex items-start gap-2"><i data-lucide="x" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:var(--terracota)"></i><span style="color:var(--text-secondary)"><?= e($it) ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($p['meeting_point'])): ?>
                <div class="admin-card p-6">
                    <h3 class="font-display text-lg font-bold mb-3" style="color:var(--sepia)"><?= t('detail.meeting') ?></h3>
                    <div class="flex items-start gap-3 text-sm" style="color:var(--text-secondary)">
                        <i data-lucide="map-pin" class="w-5 h-5 flex-shrink-0 mt-0.5" style="color:var(--terracota)"></i><?= e($p['meeting_point']) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Calendário -->
                <div id="calendario" class="admin-card p-6 sm:p-8" x-data="availabilityCalendar(<?= htmlspecialchars(json_encode([
                    'mode' => $p['availability_mode'] ?? 'fixed',
                    'map' => $availabilityMap,
                    'basePrice' => (float)($p['price_pix'] ?: $p['price']),
                    'checkoutBase' => url('/checkout?pacote=' . $p['id']),
                ]), ENT_QUOTES) ?>)">
                    <div class="flex items-start justify-between flex-wrap gap-4 mb-5">
                        <div>
                            <h2 class="font-display text-2xl font-bold flex items-center gap-3" style="color:var(--sepia)"><i data-lucide="calendar-days" class="w-6 h-6" style="color:var(--terracota)"></i> <?= t('detail.availability') ?></h2>
                            <p class="text-sm mt-1" style="color:var(--text-muted)" x-text="modeLabel"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="prevMonth()" class="w-10 h-10 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                            <div class="min-w-[160px] text-center font-display font-bold text-base" style="color:var(--sepia)" x-text="monthLabel"></div>
                            <button type="button" @click="nextMonth()" class="w-10 h-10 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 mb-5 text-xs" style="color:var(--text-muted)">
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background:var(--maresia)"></span> Disponível</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background:#F59E0B"></span> Últimas vagas</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background:#E5E7EB"></span> Indisponível</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded" style="background:var(--terracota)"></span> Selecionado</span>
                    </div>

                    <div class="calendar-grid">
                        <template x-for="dow in ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb']" :key="dow">
                            <div class="text-center text-[11px] font-bold uppercase tracking-wider py-2" style="color:var(--text-muted)" x-text="dow"></div>
                        </template>
                        <template x-for="cell in cells" :key="cell.key">
                            <button type="button" :disabled="!cell.available" @click="cell.available && select(cell)"
                                class="calendar-cell"
                                :class="{ 'empty':cell.empty, 'past':cell.past, 'available':cell.available&&!cell.lowSeats, 'low':cell.available&&cell.lowSeats, 'blocked':cell.blocked, 'selected':cell.iso&&cell.iso===selectedIso }">
                                <span class="cal-day" x-text="cell.day"></span>
                                <span class="cal-price" x-show="cell.available" x-text="cell.priceLabel"></span>
                            </button>
                        </template>
                    </div>

                    <div x-show="selectedIso" x-cloak class="mt-6 p-5 rounded-xl flex items-center justify-between flex-wrap gap-4" style="background:rgba(201,107,74,0.08);border:1px solid rgba(201,107,74,0.25)">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:var(--terracota)">Data selecionada</div>
                            <div class="font-display font-bold text-lg" style="color:var(--sepia)" x-text="selectedLabel"></div>
                            <div class="text-xs mt-0.5" style="color:var(--text-secondary)" x-text="selectedDetail"></div>
                        </div>
                        <a :href="selectedCheckoutUrl" class="btn-primary"><i data-lucide="calendar-check" class="w-5 h-5"></i> Reservar esta data</a>
                    </div>
                    <div x-show="!selectedIso && cells.some(c => c.available)" class="mt-6 text-sm text-center" style="color:var(--text-muted)">Clique em uma data disponível para reservar.</div>
                    <div x-show="!cells.some(c => c.available) && mode !== 'on_request'" class="mt-6 p-5 rounded-xl text-center" style="background:var(--bg-surface)">
                        <div class="text-sm font-semibold mb-1" style="color:var(--sepia)">Sem datas disponíveis neste mês</div>
                        <div class="text-xs" style="color:var(--text-muted)">Tente o próximo mês ou fale com a gente no WhatsApp.</div>
                    </div>
                    <div x-show="mode === 'on_request'" class="mt-6 p-5 rounded-xl text-center" style="background:rgba(58,107,138,0.08);border:1px solid rgba(58,107,138,0.25)">
                        <div class="text-sm font-semibold mb-2" style="color:var(--horizonte)">Pacote sob consulta</div>
                        <div class="text-xs mb-3" style="color:var(--text-secondary)">Combine a data diretamente com nossa equipe.</div>
                        <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>?text=Ol%C3%A1!%20Tenho%20interesse%20no%20pacote%20<?= urlencode($p['title']) ?>" target="_blank" class="btn-secondary"><i data-lucide="message-circle" class="w-4 h-4"></i> Falar no WhatsApp</a>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="lg:sticky lg:top-28 lg:self-start space-y-5">
                <div class="admin-card p-6">
                    <div class="text-xs uppercase tracking-wider font-semibold mb-1" style="color:var(--text-muted)"><?= t('price.from') ?></div>
                    <div class="font-display text-4xl font-bold mb-1" style="color:var(--terracota)"><?= formatPrice($p['price_pix'] ?: $p['price']) ?></div>
                    <?php if ($p['price_pix']): ?>
                        <div class="text-xs" style="color:var(--text-muted)"><?= t('price.per_person') ?> · PIX · cartão <?= formatPrice($p['price']) ?></div>
                    <?php else: ?>
                        <div class="text-xs" style="color:var(--text-muted)"><?= t('price.per_person') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($p['installments']) && $p['installments']>1): ?>
                        <div class="text-xs mt-1 font-semibold" style="color:var(--maresia-dark)">
                            <?= t('price.installments', ['n'=>(int)$p['installments']]) ?> de <?= formatPrice($p['price']/$p['installments']) ?>
                        </div>
                    <?php endif; ?>

                    <hr class="my-5" style="border-color:var(--border-default)">

                    <?php if ($departures): ?>
                        <div class="text-sm font-semibold mb-3" style="color:var(--sepia)">Próximas saídas</div>
                        <div class="space-y-2 mb-5" x-data="{ open:false }">
                            <?php foreach ($departures as $i => $d): ?>
                                <div class="flex items-center justify-between p-3 rounded-lg" style="background:var(--bg-surface)" <?= $i >= 4 ? 'x-show="open" x-collapse' : '' ?>>
                                    <div>
                                        <div class="text-sm font-semibold" style="color:var(--sepia)"><?= e(dateBR($d['departure_date'], 'dayMonth')) ?></div>
                                        <?php if ($d['departure_time']): ?><div class="text-xs" style="color:var(--text-muted)">Saída às <?= date('H:i', strtotime($d['departure_time'])) ?></div><?php endif; ?>
                                    </div>
                                    <div class="text-xs font-semibold" style="color:var(--maresia-dark)"><?= max(0, $d['seats_total']-$d['seats_sold']) ?> vagas</div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($departures) > 4): ?>
                                <button type="button" @click="open=!open" class="w-full text-center text-xs font-semibold py-2 rounded-lg" style="color:var(--horizonte);background:rgba(58,107,138,0.06)">
                                    <span x-show="!open">Ver mais <?= count($departures) - 4 ?> datas</span>
                                    <span x-show="open" x-cloak>Ver menos</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <a href="#calendario" onclick="event.preventDefault();document.getElementById('calendario').scrollIntoView({behavior:'smooth',block:'start'})" class="btn-primary w-full"><i data-lucide="calendar-check" class="w-5 h-5"></i> <?= t('nav.book_now') ?></a>
                    <button type="button" onclick="window.cart.add('pacote', <?= (int)$p['id'] ?>)" class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 font-semibold text-sm transition hover:scale-[1.02]" style="color:var(--horizonte);border-color:var(--horizonte);background:rgba(58,107,138,0.05)"><i data-lucide="shopping-bag" class="w-4 h-4"></i> Adicionar ao carrinho</button>
                    <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>?text=Ol%C3%A1!%20Tenho%20interesse%20no%20pacote%20<?= urlencode($p['title']) ?>" target="_blank" class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 font-semibold text-sm" style="color:var(--maresia-dark);border-color:var(--maresia)"><i data-lucide="message-circle" class="w-4 h-4"></i> <?= t('book.whatsapp') ?></a>
                </div>

                <div class="admin-card p-5 flex items-start gap-3">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0 mt-1" style="color:var(--maresia)"></i>
                    <div>
                        <div class="text-sm font-semibold" style="color:var(--sepia)">Reserva garantida</div>
                        <div class="text-xs" style="color:var(--text-secondary)">Cancelamento grátis até 7 dias antes da viagem.</div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Avaliações -->
        <?php if ($reviews): ?>
        <div class="mt-16 sm:mt-20" x-data="{ open:false }">
            <div class="flex items-end justify-between flex-wrap gap-4 mb-8">
                <div>
                    <h2 class="font-display text-2xl sm:text-3xl font-bold mb-1" style="color:var(--sepia)">Avaliações de viajantes</h2>
                    <div class="flex items-center gap-2 text-sm" style="color:var(--text-muted)">
                        <span class="inline-flex items-center gap-1 font-display font-bold text-lg" style="color:var(--terracota)"><?= number_format($reviewsAvg,1,',','.') ?><i data-lucide="star" class="w-4 h-4 fill-current"></i></span>
                        · <?= count($reviews) ?> avaliações
                    </div>
                </div>
                <a href="<?= url('/depoimentos') ?>" class="text-sm font-semibold inline-flex items-center gap-1" style="color:var(--horizonte)">Ver todas <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($reviews as $i => $rv): ?>
                <div class="admin-card p-6" <?= $i >= 3 ? 'x-show="open" x-collapse' : '' ?>>
                    <div class="flex items-center gap-1 mb-3">
                        <?php for ($s=0;$s<(int)$rv['rating'];$s++): ?><i data-lucide="star" class="w-3.5 h-3.5 fill-current" style="color:#F59E0B"></i><?php endfor; ?>
                    </div>
                    <p class="text-sm leading-relaxed mb-4 italic" style="color:var(--text-secondary)">“<?= e(tAuto($rv['content'])) ?>”</p>
                    <div class="flex items-center gap-3 pt-4 border-t" style="border-color:var(--border-default)">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-display font-bold text-white" style="background:linear-gradient(135deg,var(--horizonte),var(--terracota))"><?= e(mb_substr($rv['name'],0,1)) ?></div>
                        <div>
                            <div class="font-bold text-sm" style="color:var(--sepia)"><?= e($rv['name']) ?></div>
                            <?php if (!empty($rv['location'])): ?><div class="text-xs" style="color:var(--text-muted)"><?= e(tAuto($rv['location'])) ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($reviews) > 3): ?>
                <div class="text-center mt-6">
                    <button type="button" @click="open=!open" class="btn-secondary">
                        <span x-show="!open">Ver mais <?= count($reviews) - 3 ?> avaliações</span>
                        <span x-show="open" x-cloak>Ver menos</span>
                        <i data-lucide="chevron-down" class="w-4 h-4" :style="open ? 'transform:rotate(180deg)' : ''"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($related): ?>
        <div class="mt-16 sm:mt-20">
            <h2 class="font-display text-2xl sm:text-3xl font-bold mb-8 text-center" style="color:var(--sepia)"><?= t('detail.related') ?></h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6">
                <?php foreach ($related as $rel): ?>
                <a href="<?= url('/pacotes/'.$rel['slug']) ?>" class="roteiro-card group">
                    <div class="img-wrap" style="aspect-ratio:4/3">
                        <?php if ($rel['cover_image']): ?>
                            <div class="slide active" style="background-image:url('<?= e(storageUrl($rel['cover_image'])) ?>')"></div>
                        <?php else: ?>
                            <div class="img-placeholder w-full h-full"><span><?= e(mb_substr($rel['title'],0,1)) ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($rel['featured'])): ?><div class="badge-featured">Destaque</div><?php endif; ?>
                    </div>
                    <div class="p-5">
                        <?php if ($rel['destination']): ?>
                            <div class="flex items-center gap-1.5 text-xs font-semibold mb-2" style="color:var(--horizonte)"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i><?= e($rel['destination']) ?> · <?= (int)$rel['duration_days'] ?>D<?= (int)$rel['duration_nights'] ?>N</div>
                        <?php endif; ?>
                        <h3 class="font-display text-lg font-bold leading-snug mb-2 line-clamp-2" style="color:var(--sepia)"><?= e($rel['title']) ?></h3>
                        <div class="flex items-end justify-between pt-3 border-t" style="border-color:var(--border-default)">
                            <div>
                                <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:var(--text-muted)"><?= t('price.from') ?></div>
                                <div class="font-display text-xl font-bold" style="color:var(--terracota)"><?= formatPrice($rel['price_pix'] ?: $rel['price']) ?></div>
                            </div>
                            <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background:var(--bg-surface);color:var(--terracota)"><i data-lucide="arrow-right" class="w-4 h-4"></i></div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
function availabilityCalendar(config) {
    const today = new Date(); today.setHours(0,0,0,0);
    return {
        mode: config.mode, map: config.map || {},
        basePrice: config.basePrice || 0, checkoutBase: config.checkoutBase,
        viewYear: today.getFullYear(), viewMonth: today.getMonth(),
        selectedIso: null,
        get modeLabel() {
            if (this.mode === 'open') return 'Datas abertas — escolha quando quiser ir';
            if (this.mode === 'on_request') return 'Pacote sob consulta — combine pelo WhatsApp';
            return 'Apenas datas listadas abaixo';
        },
        get monthLabel() {
            const n = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
            return n[this.viewMonth] + ' de ' + this.viewYear;
        },
        pad(n){ return n<10?'0'+n:''+n; },
        iso(y,m,d){ return y+'-'+this.pad(m+1)+'-'+this.pad(d); },
        brl(v){ return 'R$ ' + Number(v).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); },
        get cells() {
            const first = new Date(this.viewYear, this.viewMonth, 1);
            const startDow = first.getDay();
            const days = new Date(this.viewYear, this.viewMonth+1, 0).getDate();
            const cells = [];
            for (let i=0;i<startDow;i++) cells.push({ key:'e'+i, empty:true });
            for (let d=1; d<=days; d++) {
                const dateObj = new Date(this.viewYear, this.viewMonth, d);
                const isoStr = this.iso(this.viewYear, this.viewMonth, d);
                const past = dateObj < today;
                const info = this.map[isoStr];
                let available=false,lowSeats=false,blocked=false,price=this.basePrice;
                if (past) { available=false; }
                else if (this.mode==='on_request') { available=false; }
                else if (info) {
                    if (info.status==='open' && info.seats>0) { available=true; lowSeats=info.seats<=3; price=info.price; }
                    else { blocked=true; }
                } else if (this.mode==='open') { available=true; }
                cells.push({ key:isoStr, iso:isoStr, day:d, empty:false, past, available, lowSeats, blocked,
                    priceLabel: available?this.brl(price).replace('R$ ','R$'):'', seats:info?info.seats:null, price });
            }
            return cells;
        },
        prevMonth() { if (this.viewMonth===0) { this.viewMonth=11; this.viewYear--; } else this.viewMonth--; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        nextMonth() { if (this.viewMonth===11) { this.viewMonth=0; this.viewYear++; } else this.viewMonth++; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        select(c) { this.selectedIso = c.iso; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        get selectedLabel() {
            if (!this.selectedIso) return '';
            const [y,m,d] = this.selectedIso.split('-').map(Number);
            const names = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
            return d + ' de ' + names[m-1] + ' de ' + y;
        },
        get selectedDetail() {
            if (!this.selectedIso) return '';
            const c = this.cells.find(x => x.iso === this.selectedIso); if (!c) return '';
            const parts = [this.brl(c.price) + ' por pessoa'];
            if (c.seats !== null) parts.push(c.seats + ' vagas restantes');
            return parts.join(' · ');
        },
        get selectedCheckoutUrl() { return this.checkoutBase + '&date=' + (this.selectedIso || ''); },
    };
}
</script>

<!-- Sticky bottom bar mobile -->
<div class="mobile-book-bar md:hidden">
    <div class="flex-1 min-w-0">
        <div class="text-[10px] uppercase tracking-wider font-bold opacity-70">A partir de</div>
        <div class="font-display text-xl font-bold leading-none" style="color:var(--terracota)"><?= formatPrice($p['price_pix'] ?: $p['price']) ?></div>
    </div>
    <a href="#calendario" onclick="event.preventDefault();document.getElementById('calendario').scrollIntoView({behavior:'smooth',block:'start'})" class="btn-primary" style="white-space:nowrap">
        <i data-lucide="calendar-check" class="w-4 h-4"></i> Reservar
    </a>
</div>
<style>
.mobile-book-bar{position:fixed;bottom:0;left:0;right:0;z-index:60;background:var(--bg-card);border-top:1px solid var(--border-default);padding:12px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 -8px 24px -8px rgba(0,0,0,.15);padding-bottom:calc(12px + env(safe-area-inset-bottom))}
@media(min-width:768px){.mobile-book-bar{display:none !important}}
@media(max-width:767px){body{padding-bottom:88px}}
</style>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
