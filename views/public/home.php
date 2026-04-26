<?php
$pageTitle = 'Turismo Premium em Alagoas';
$pageDesc  = 'Passeios autênticos, pacotes premium e experiências únicas em Alagoas. Reserve sua viagem dos sonhos.';

$featured = dbAll("SELECT * FROM roteiros WHERE status='published' AND featured=1 ORDER BY created_at DESC LIMIT 8");
$pacotesFeatured = dbAll("SELECT * FROM pacotes WHERE status='published' AND featured=1 ORDER BY created_at DESC LIMIT 4");
$testimonials = dbAll("SELECT * FROM testimonials WHERE active=1 ORDER BY featured DESC, created_at DESC LIMIT 4");

include VIEWS_DIR . '/partials/public_head.php';
?>

<!-- ================================================
     HERO — Premium Cinematic
================================================ -->
<section class="hero-premium flex items-center justify-center" style="margin-top:-80px;padding-top:80px">
    <!-- Background image layer with parallax zoom -->
    <div class="hero-bg" style="background-image:url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=2200&q=90')"></div>

    <!-- Multi-layer overlays for premium contrast -->
    <div class="hero-overlay"></div>
    <div class="hero-vignette"></div>
    <div class="hero-progress-overlay"></div>

    <!-- Floating decorative icons (thematic brand icons) -->
    <img src="<?= asset('icons/bussola.png') ?>" class="float-icon" style="top:14%;left:6%;width:72px">
    <img src="<?= asset('icons/aviao.png') ?>" class="float-icon f2" style="top:20%;right:8%;width:90px">
    <img src="<?= asset('icons/mapa.png') ?>" class="float-icon f3" style="bottom:22%;left:10%;width:80px">
    <img src="<?= asset('icons/passaporte.png') ?>" class="float-icon f2" style="bottom:18%;right:10%;width:68px">
    <img src="<?= asset('icons/camera.png') ?>" class="float-icon" style="top:40%;left:3%;width:56px;opacity:0.35">
    <img src="<?= asset('icons/coco.png') ?>" class="float-icon f3" style="bottom:35%;right:4%;width:60px;opacity:0.4">

    <!-- Rotating brand seal (top right corner) -->
    <div class="absolute top-28 right-8 lg:right-16 z-10 hidden md:block">
        <img src="<?= asset('brand/selo-branco.png') ?>" class="seal-rotate opacity-25" style="width:120px;height:120px" alt="">
    </div>

    <div class="relative z-10 max-w-6xl mx-auto px-6 py-24 text-center text-white">
        <!-- Small badge -->
        <div class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full glass-premium mb-8 fade-in-up">
            <span class="w-2 h-2 rounded-full animate-pulse" style="background:var(--terracota-light)"></span>
            <span class="text-xs font-bold tracking-[0.2em] uppercase"><?= e(t('home.hero.badge')) ?></span>
        </div>

        <!-- Main title -->
        <h1 class="font-brand text-5xl md:text-7xl lg:text-[7rem] mb-8 leading-[0.95] fade-in-up delay-100" style="text-shadow:0 4px 40px rgba(0,0,0,0.4)">
            <?= e(t('home.hero.t1')) ?>
            <span class="font-display italic font-normal" style="color:var(--areia-light)"><?= e(t('home.hero.visitor')) ?></span>.<br>
            <?= e(t('home.hero.t2')) ?>
            <span class="font-display italic font-normal" style="color:var(--terracota-light)"><?= e(t('home.hero.athome')) ?></span>.
        </h1>

        <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto mb-12 leading-relaxed fade-in-up delay-200" style="text-shadow:0 2px 20px rgba(0,0,0,0.3)">
            <?= e(t('home.hero.sub')) ?>
        </p>

        <!-- CTA buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16 fade-in-up delay-300">
            <a href="<?= url('/passeios') ?>" class="btn-hero-primary animate-glow">
                <i data-lucide="compass" class="w-5 h-5"></i>
                <?= e(t('home.hero.cta1')) ?>
            </a>
            <a href="#destaques" class="btn-hero-ghost">
                <i data-lucide="arrow-down" class="w-5 h-5"></i>
                <?= e(t('home.hero.cta2')) ?>
            </a>
        </div>

        <!-- Search bar (premium glass) -->
        <div class="max-w-4xl mx-auto glass-premium rounded-2xl p-4 md:p-5 fade-in-up delay-400">
            <div class="text-left mb-3">
                <span class="font-display text-lg sm:text-xl font-bold tracking-wide uppercase" style="color:var(--areia-light)"><?= e(t('home.search.title')) ?></span>
            </div>
            <form method="GET" action="<?= url('/passeios') ?>" class="grid md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold tracking-wider uppercase text-white/70 mb-1.5 text-left"><?= e(t('home.search.date')) ?></label>
                    <input type="date" name="date" class="w-full px-4 py-3 rounded-xl bg-white/95 text-sm outline-none focus:ring-2 focus:ring-terracota" style="color:var(--sepia)" placeholder="Escolher data">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold tracking-wider uppercase text-white/70 mb-1.5 text-left"><?= e(t('home.search.find')) ?></label>
                    <input type="text" name="q" placeholder="<?= e(t('home.search.ph')) ?>" class="w-full px-4 py-3 rounded-xl bg-white/95 text-sm outline-none focus:ring-2 focus:ring-terracota" style="color:var(--sepia)">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold tracking-wider uppercase text-white/70 mb-1.5 text-left"><?= e(t('home.search.type')) ?></label>
                    <select name="tipo" class="w-full px-4 py-3 rounded-xl bg-white/95 text-sm outline-none focus:ring-2 focus:ring-terracota" style="color:var(--sepia)">
                        <option value=""><?= e(t('home.search.all')) ?></option>
                        <option value="roteiro"><?= e(t('nav.tours')) ?></option>
                        <option value="pacote"><?= e(t('nav.packages')) ?></option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-5 py-3 rounded-xl font-semibold text-white transition hover:opacity-90"
                            style="background:linear-gradient(135deg,var(--terracota),var(--terracota-dark));box-shadow:0 6px 18px rgba(201,107,74,0.4)">
                        <i data-lucide="search" class="w-4 h-4 inline"></i> <?= e(t('home.search.find')) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bottom fade into page -->
    <div class="hero-fade-bottom"></div>

    <!-- Scroll indicator -->
    <a href="#destaques" class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 animate-bounce text-white/80 hover:text-white transition">
        <i data-lucide="chevron-down" class="w-7 h-7"></i>
    </a>
</section>

<!-- ================================================
     TRUST STRIP
================================================ -->
<?php
$trustItems = [
    ['icon'=>'shield-check','title'=>'Pagamento seguro','sub'=>'Pix ou cartão até 12x','bg'=>'rgba(201,107,74,0.1)','color'=>'var(--terracota)'],
    ['icon'=>'map-pin','title'=>'Curadoria local','sub'=>'Feito por alagoanos','bg'=>'rgba(58,107,138,0.1)','color'=>'var(--horizonte)'],
    ['icon'=>'headphones','title'=>'Suporte 24/7','sub'=>'Durante sua viagem','bg'=>'rgba(16,185,129,0.1)','color'=>'#10B981'],
    ['icon'=>'badge-check','title'=>'Melhor preço','sub'=>'Garantido ou devolvemos','bg'=>'rgba(245,158,11,0.1)','color'=>'#F59E0B'],
];
?>
<section class="trust-strip-section relative" style="background:var(--bg-surface);border-bottom:1px solid var(--border-default)">
    <div class="max-w-7xl mx-auto px-0 md:px-6">
        <div class="trust-viewport">
            <div class="trust-track">
                <?php foreach (array_merge($trustItems, $trustItems) as $i => $trust): ?>
                <div class="trust-item<?= $i >= count($trustItems) ? ' is-duplicate' : '' ?>" data-reveal style="animation-delay:<?= ($i % count($trustItems)) * 100 ?>ms">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:<?= $trust['bg'] ?>;color:<?= $trust['color'] ?>"><i data-lucide="<?= e($trust['icon']) ?>" class="w-5 h-5"></i></div>
                    <div><div class="text-sm font-bold" style="color:var(--sepia)"><?= e($trust['title']) ?></div><div class="text-xs" style="color:var(--text-muted)"><?= e($trust['sub']) ?></div></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ================================================
    DESTAQUES — Passeios
================================================ -->
<section id="destaques" class="py-24 relative overflow-hidden" style="background:var(--bg-page)">
    <div class="max-w-7xl mx-auto px-6 relative z-10">
        <div class="text-center mb-16" data-reveal>
            <div class="inline-block mb-3">
                <span class="text-xs font-bold tracking-[0.3em] uppercase" style="color:var(--terracota)">Experiências imperdíveis</span>
            </div>
            <h2 class="section-title">Passeios em <span class="ornament">destaque</span></h2>
            <p class="section-subtitle">Passeios selecionados por quem conhece cada canto de Alagoas — história, natureza e sabor.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featured as $i => $r):
                $gal = [];
                if (!empty($r['gallery'])) { $dec = json_decode($r['gallery'], true); if (is_array($dec)) $gal = $dec; }
                $slides = [];
                if ($r['cover_image']) $slides[] = storageUrl($r['cover_image']);
                foreach ($gal as $g) $slides[] = storageUrl($g);
                $slides = array_values(array_unique(array_filter($slides)));
            ?>
            <div class="card-premium group" data-reveal style="animation-delay: <?= $i * 80 ?>ms">
                <a href="<?= url('/passeios/' . $r['slug']) ?>" class="block">
                    <div class="slider-wrap" data-slider style="aspect-ratio:4/3;background:linear-gradient(135deg,var(--horizonte-light),var(--maresia-light))">
                        <?php if (!empty($slides)): ?>
                            <?php foreach ($slides as $sIdx => $src): ?>
                                <div class="slide<?= $sIdx===0?' active':'' ?>" style="background-image:url('<?= e($src) ?>')"></div>
                            <?php endforeach; ?>
                            <?php if (count($slides) > 1): ?>
                                <div class="slider-dots">
                                    <?php foreach ($slides as $sIdx => $_): ?>
                                        <span class="dot<?= $sIdx===0?' active':'' ?>"></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="img-placeholder w-full h-full absolute inset-0"><span><?= e(mb_substr($r['title'], 0, 1)) ?></span></div>
                        <?php endif; ?>

                        <?php if ($r['featured']): ?>
                            <div class="ribbon"><i data-lucide="star" class="w-3 h-3"></i> Destaque</div>
                        <?php endif; ?>
                        <button type="button" class="heart-btn" data-fav-type="roteiro" data-fav-id="<?= (int)$r['id'] ?>" aria-label="Favoritar">
                            <i data-lucide="heart" class="w-4 h-4"></i>
                        </button>
                        <?php if (count($slides) > 1): ?>
                            <button type="button" class="slider-arrow prev" aria-label="Anterior" tabindex="-1"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                            <button type="button" class="slider-arrow next" aria-label="Próximo" tabindex="-1"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                    </div>
                    <div class="p-5">
                        <?php if ($r['location']): ?>
                        <div class="flex items-center gap-1.5 text-xs font-semibold mb-2" style="color:var(--horizonte)">
                            <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                            <span><?= e($r['location']) ?></span>
                        </div>
                        <?php endif; ?>
                        <h3 class="font-brand text-lg leading-snug mb-2 line-clamp-2" style="color:var(--sepia)">
                            <?= e($r['title']) ?>
                        </h3>
                        <p class="text-sm line-clamp-2 mb-4" style="color:var(--text-secondary)">
                            <?= e($r['short_desc'] ?? '') ?>
                        </p>
                        <div class="flex items-end justify-between pt-3 border-t" style="border-color:var(--border-default)">
                            <div>
                                <?php if ($r['price_pix'] && $r['price_pix'] < $r['price']): ?>
                                    <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:var(--maresia)">PIX a partir de</div>
                                    <div class="font-brand text-xl" style="color:var(--terracota)"><?= formatPrice($r['price_pix']) ?></div>
                                <?php else: ?>
                                    <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:var(--text-muted)">A partir de</div>
                                    <div class="font-brand text-xl" style="color:var(--terracota)"><?= formatPrice($r['price']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="w-10 h-10 rounded-full flex items-center justify-center transition group-hover:scale-110"
                                 style="background:var(--terracota);color:#fff">
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>
                </a>
                <div class="px-5 pb-5">
                    <button type="button" onclick="window.cart.askDate('roteiro', <?= (int)$r['id'] ?>, '<?= e(addslashes($r['title'])) ?>')"
                            class="w-full inline-flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition hover:scale-[1.02]"
                            style="background:rgba(58,107,138,0.08);color:var(--horizonte);border:1px solid rgba(58,107,138,0.15)">
                        <i data-lucide="shopping-bag" class="w-4 h-4"></i> Adicionar ao carrinho
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="<?= url('/passeios') ?>" class="btn-outline">
                Ver todos os passeios
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</section>

<!-- ================================================
     POR QUE ESCOLHER
================================================ -->
<section class="py-24 relative overflow-hidden" style="background:var(--bg-surface)">
    <div class="max-w-7xl mx-auto px-6 relative z-10">
        <div class="text-center mb-16" data-reveal>
            <span class="text-xs font-bold tracking-[0.3em] uppercase" style="color:var(--terracota)">Diferenciais</span>
            <h2 class="section-title">Por que viajar <span class="ornament">com a gente</span></h2>
            <p class="section-subtitle">Quatro pilares que fazem da sua viagem uma experiência inesquecível.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <?php foreach ([
                ['icon'=>'gem','title'=>'Experiências autênticas','desc'=>'Passeios curados por quem vive Alagoas — histórias, sabores e lugares fora do comum.','color'=>'terracota'],
                ['icon'=>'users','title'=>'Grupos pequenos','desc'=>'Atendimento personalizado com turmas reduzidas para você aproveitar sem pressa.','color'=>'horizonte'],
                ['icon'=>'leaf','title'=>'Turismo responsável','desc'=>'Parcerias com comunidades locais, respeito ambiental e impacto positivo.','color'=>'maresia'],
                ['icon'=>'star','title'=>'Avaliação 4,9/5','desc'=>'Mais de 300 viajantes recomendam. Qualidade reconhecida em cada detalhe.','color'=>'areia'],
            ] as $i => $pil): ?>
            <div class="pillar-card" data-reveal style="animation-delay:<?= $i*80 ?>ms">
                <div class="pillar-icon" style="background:linear-gradient(135deg,var(--<?= $pil['color'] ?>),var(--<?= $pil['color'] ?>-dark))">
                    <i data-lucide="<?= $pil['icon'] ?>" class="w-6 h-6 text-white"></i>
                </div>
                <h3 class="font-display text-lg font-bold mb-2" style="color:var(--sepia)"><?= e($pil['title']) ?></h3>
                <p class="text-sm leading-relaxed" style="color:var(--text-secondary)"><?= e($pil['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================================================
     QUEM SOMOS
================================================ -->
<section class="py-24 relative overflow-hidden" style="background:var(--bg-surface)">
    <div class="max-w-7xl mx-auto px-6 relative z-10">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <!-- Imagem -->
            <div class="relative" data-reveal>
                <div class="relative z-10 rounded-3xl overflow-hidden shadow-2xl aspect-[4/5]">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1000&q=85" alt="Equipe Caminhos de Alagoas" class="w-full h-full object-cover">
                </div>
                <!-- Decorative elements -->
                <div class="absolute -top-6 -left-6 w-32 h-32 rounded-full opacity-20 -z-0" style="background:var(--terracota)"></div>
                <div class="absolute -bottom-8 -right-8 w-40 h-40 rounded-full opacity-20 -z-0" style="background:var(--horizonte)"></div>
                <!-- Float badge -->
                <div class="absolute -bottom-6 -left-6 lg:left-8 z-20 bg-white rounded-2xl p-5 shadow-xl flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background:linear-gradient(135deg,var(--terracota),var(--terracota-dark))">
                        <i data-lucide="award" class="w-7 h-7 text-white"></i>
                    </div>
                    <div>
                        <div class="font-display text-2xl font-bold" style="color:var(--sepia)">10+ anos</div>
                        <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold">de experiência</div>
                    </div>
                </div>
            </div>

            <!-- Texto -->
            <div data-reveal>
                <span class="text-xs font-bold tracking-[0.3em] uppercase" style="color:var(--terracota)">Quem somos</span>
                <h2 class="font-display text-4xl md:text-5xl font-bold mt-3 mb-6 leading-tight" style="color:var(--sepia)">
                    Viagens feitas por quem <span class="italic" style="color:var(--terracota)">vive</span> o território
                </h2>
                <div class="space-y-4 text-lg leading-relaxed" style="color:var(--text-secondary)">
                    <p>
                        O <strong style="color:var(--sepia)">Caminhos de Alagoas</strong> é um projeto da MaçaiOK — agência alagoana com mais de 10 anos de história, criada para oferecer viagens seguras, confortáveis e cheias de sentido.
                    </p>
                    <p>
                        Aqui, você viaja com quem vive o território e sabe o que realmente faz diferença: histórias, cultura, gente, sabores e lugares que não aparecem nos passeios prontos.
                    </p>
                    <p>
                        Nossa proposta é simples: <strong style="color:var(--sepia)">transformar passeios em vivências</strong> que aproximam, emocionam e valorizam o que Alagoas tem de mais genuíno.
                    </p>
                </div>
                <div class="flex flex-wrap gap-4 mt-8">
                    <a href="<?= url('/sobre') ?>" class="btn-outline">
                        Conheça nossa história
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================================================
     PACOTES EM DESTAQUE
================================================ -->
<div class="section-divider-seal" style="--seal-img: url('<?= asset('brand/selo-azul.png') ?>')" aria-hidden="true"></div>
<section class="py-24 relative overflow-hidden" style="background:var(--bg-page)">
    <div class="max-w-7xl mx-auto px-6 relative z-10">
        <div class="text-center mb-16" data-reveal>
            <span class="text-xs font-bold tracking-[0.3em] uppercase" style="color:var(--terracota)">Experiências completas</span>
            <h2 class="section-title">Pacotes em <span class="ornament">destaque</span></h2>
            <p class="section-subtitle">Viagens curadas com hospedagem, transporte e passeios incluídos.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($pacotesFeatured as $i => $p):
                $gal = [];
                if (!empty($p['gallery'])) { $dec = json_decode($p['gallery'], true); if (is_array($dec)) $gal = $dec; }
                $slides = [];
                if ($p['cover_image']) $slides[] = storageUrl($p['cover_image']);
                foreach ($gal as $g) $slides[] = storageUrl($g);
                $slides = array_values(array_unique(array_filter($slides)));
            ?>
            <a href="<?= url('/pacotes/' . $p['slug']) ?>" class="roteiro-card group" data-reveal style="animation-delay: <?= $i * 80 ?>ms">
                <div class="img-wrap slider-wrap" <?= count($slides)>1?'data-slider':'' ?> style="aspect-ratio:4/3;position:relative">
                    <?php if (!empty($slides)): ?>
                        <?php foreach ($slides as $sIdx => $src): ?>
                            <div class="slide<?= $sIdx===0?' active':'' ?>" style="background-image:url('<?= e($src) ?>')"></div>
                        <?php endforeach; ?>
                        <?php if (count($slides) > 1): ?>
                            <div class="slider-dots"><?php foreach ($slides as $sIdx => $_): ?><span class="dot<?= $sIdx===0?' active':'' ?>"></span><?php endforeach; ?></div>
                            <button type="button" class="slider-arrow prev" tabindex="-1" aria-label="Anterior"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                            <button type="button" class="slider-arrow next" tabindex="-1" aria-label="Próximo"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="img-placeholder w-full h-full"><span><?= e(mb_substr($p['title'], 0, 1)) ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($p['featured'])): ?><div class="badge-featured">Destaque</div><?php endif; ?>
                    <button type="button" class="heart-btn" data-fav-type="pacote" data-fav-id="<?= (int)$p['id'] ?>" aria-label="Favoritar"><i data-lucide="heart" class="w-4 h-4"></i></button>
                </div>
                <div class="p-5">
                    <?php if (!empty($p['destination'])): ?>
                    <div class="flex items-center gap-1.5 text-xs font-semibold mb-2" style="color:var(--horizonte)">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i><?= e($p['destination']) ?>
                    </div>
                    <?php endif; ?>
                    <h3 class="font-display text-lg font-bold leading-snug mb-2 line-clamp-2" style="color:var(--sepia)"><?= e($p['title']) ?></h3>
                    <p class="text-sm line-clamp-2 mb-4" style="color:var(--text-secondary)"><?= e($p['short_desc'] ?? '') ?></p>
                    <div class="flex items-end justify-between pt-3 border-t" style="border-color:var(--border-default)">
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:var(--text-muted)">A partir de</div>
                            <div class="font-display text-xl font-bold" style="color:var(--terracota)"><?= formatPrice($p['price_pix'] ?: $p['price']) ?></div>
                            <?php if ($p['installments'] > 1): ?><div class="text-[11px]" style="color:var(--text-muted)">ou <?= $p['installments'] ?>x sem juros</div><?php endif; ?>
                        </div>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center transition group-hover:bg-terracota group-hover:text-white" style="background:var(--bg-surface);color:var(--terracota)">
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================================================
     COMO FUNCIONA
================================================ -->
<section class="py-24 relative overflow-hidden" style="background:var(--bg-surface)">
    <div class="max-w-6xl mx-auto px-6 relative z-10">
        <div class="text-center mb-16" data-reveal>
            <span class="text-xs font-bold tracking-[0.3em] uppercase" style="color:var(--terracota)">Simples e seguro</span>
            <h2 class="section-title">Como <span class="ornament">funciona</span></h2>
            <p class="section-subtitle">Em três passos você está com tudo pronto para embarcar.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-6 md:gap-4 relative">
            <div class="timeline-line hidden md:block"></div>
            <?php foreach ([
                ['step'=>'01','icon'=>'search','title'=>'Escolha seu passeio','desc'=>'Navegue pelos passeios e pacotes curados. Filtre por data, destino ou estilo.'],
                ['step'=>'02','icon'=>'credit-card','title'=>'Reserve com segurança','desc'=>'Pague com Pix ou cartão em até 12x. Confirmação imediata e voucher por email.'],
                ['step'=>'03','icon'=>'plane-takeoff','title'=>'Viva a experiência','desc'=>'Nossa equipe cuida de tudo. Você só precisa aproveitar cada momento.'],
            ] as $i => $st): ?>
            <div class="step-card" data-reveal style="animation-delay:<?= $i*120 ?>ms">
                <div class="step-badge"><?= $st['step'] ?></div>
                <div class="step-icon"><i data-lucide="<?= $st['icon'] ?>" class="w-7 h-7"></i></div>
                <h3 class="font-display text-xl font-bold mb-2" style="color:var(--sepia)"><?= e($st['title']) ?></h3>
                <p class="text-sm leading-relaxed" style="color:var(--text-secondary)"><?= e($st['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================================================
     STATS
================================================ -->
<section class="py-20 relative overflow-hidden" style="background:var(--bg-page)">
    <div class="max-w-6xl mx-auto px-6 relative z-10">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
            <?php
            $stats = [
                ['icon' => 'users', 'num' => getSetting('stats_clients','300+'), 'label' => 'Clientes satisfeitos'],
                ['icon' => 'compass', 'num' => getSetting('stats_destinations','30+'), 'label' => 'Destinos incríveis'],
                ['icon' => 'map', 'num' => getSetting('stats_packages','20+'), 'label' => 'Pacotes personalizados'],
                ['icon' => 'award', 'num' => getSetting('stats_years','10+'), 'label' => 'Anos de atuação'],
            ];
            foreach ($stats as $i => $s): ?>
            <div class="stat-box" data-reveal style="animation-delay: <?= $i * 100 ?>ms">
                <div class="stat-icon">
                    <i data-lucide="<?= $s['icon'] ?>" class="w-6 h-6"></i>
                </div>
                <div class="counter-num" data-counter="<?= e($s['num']) ?>"><?= e($s['num']) ?></div>
                <div class="stat-label"><?= e($s['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================================================
     TESTIMONIALS
================================================ -->
<div class="section-divider-seal" style="--seal-img: url('<?= asset('brand/selo-terracota.png') ?>')" aria-hidden="true"></div>
<section class="py-24 relative overflow-hidden" style="background:var(--bg-surface)">
    <div class="max-w-7xl mx-auto px-6 relative z-10">
        <div class="text-center mb-16" data-reveal>
            <span class="text-xs font-bold tracking-[0.3em] uppercase" style="color:var(--terracota)">Depoimentos</span>
            <h2 class="section-title">Clientes <span class="ornament">satisfeitos</span></h2>
            <p class="section-subtitle">Histórias reais de quem viveu Alagoas com a gente.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5" x-data="{idx:0}">
            <?php foreach ($testimonials as $i => $t): ?>
            <div class="testimonial-card relative" data-reveal style="animation-delay: <?= $i * 100 ?>ms">
                <?php if (!empty($t['featured'])): ?>
                    <span class="absolute -top-2 left-4 inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full" style="background:var(--terracota);color:#fff;z-index:2"><i data-lucide="sparkles" class="w-3 h-3"></i>Destaque</span>
                <?php endif; ?>
                <div class="flex items-center gap-3 mb-4">
                    <?php if (!empty($t['avatar'])): ?>
                        <img src="<?= storageUrl($t['avatar']) ?>" alt="<?= e($t['name']) ?>" class="testimonial-avatar" style="border:2px solid rgba(58,107,138,0.15)">
                    <?php else: ?>
                        <div class="testimonial-avatar flex items-center justify-center font-display text-xl font-bold text-white" style="background:linear-gradient(135deg,var(--horizonte),var(--terracota))">
                            <?= e(mb_substr($t['name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="font-bold flex items-center gap-1.5" style="color:var(--sepia)">
                            <?= e($t['name']) ?>
                            <?php if (!empty($t['author_url'])): ?><a href="<?= e($t['author_url']) ?>" target="_blank" rel="noopener" title="Ver perfil" style="color:var(--horizonte)"><i data-lucide="external-link" class="w-3.5 h-3.5"></i></a><?php endif; ?>
                        </div>
                        <div class="text-xs" style="color:var(--text-muted)"><?= e(tAuto($t['location'] ?? '')) ?></div>
                    </div>
                    <div class="ml-auto flex gap-0.5">
                        <?php for ($s = 0; $s < (int)$t['rating']; $s++): ?>
                            <i data-lucide="star" class="w-4 h-4 fill-current" style="color:#F59E0B"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <p class="text-sm leading-relaxed italic" style="color:var(--text-secondary)">
                    "<?= e(tAuto(truncate($t['content'], 280))) ?>"
                </p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12" data-reveal>
            <a href="<?= url('/depoimentos') ?>" class="testimonial-see-all">
                <?= e(t('home.testimonials.see_all')) ?>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</section>

<!-- ================================================
     CTA FINAL
================================================ -->
<section class="relative py-24 overflow-hidden">
    <div class="absolute inset-0" style="background:linear-gradient(135deg,var(--horizonte) 0%,var(--horizonte-dark) 60%,var(--terracota) 100%)"></div>
    <div class="absolute inset-0" style="background-image:radial-gradient(circle at 20% 30%, rgba(255,255,255,0.1) 0%, transparent 50%), radial-gradient(circle at 80% 70%, rgba(201,107,74,0.3) 0%, transparent 50%)"></div>
    <img src="<?= asset('brand/selo-branco.png') ?>" class="seal-rotate absolute z-10 hidden md:block" style="top:40px;right:40px;width:100px;opacity:0.35" alt="">

    <div class="relative z-10 max-w-4xl mx-auto px-6 text-center text-white" data-reveal>
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-dark mb-6" style="border:1px solid rgba(255,255,255,0.2)">
            <i data-lucide="sparkles" class="w-4 h-4"></i>
            <span class="text-xs font-bold tracking-wider uppercase">Pronto para a próxima aventura?</span>
        </div>
        <h2 class="font-display text-4xl md:text-6xl font-bold mb-6 leading-tight">
            Sua viagem dos <span class="italic" style="color:var(--areia-light)">sonhos</span>
            <span class="block">começa aqui</span>
        </h2>
        <p class="text-lg md:text-xl text-white/85 max-w-2xl mx-auto mb-10">
            Fale com nossa equipe e receba um passeio personalizado
            <span class="block">em menos de 24 horas.</span>
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>" target="_blank" class="btn-primary">
                <i data-lucide="message-circle" class="w-5 h-5"></i>
                Falar no WhatsApp
            </a>
            <a href="<?= url('/contato') ?>" class="btn-secondary">
                <i data-lucide="mail" class="w-5 h-5"></i>
                Enviar mensagem
            </a>
        </div>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
