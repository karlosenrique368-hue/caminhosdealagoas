<?php
$pageTitle = 'Pacotes de Viagem';
$pacotes = dbAll("SELECT * FROM pacotes WHERE status='published' ORDER BY featured DESC, created_at DESC");
include VIEWS_DIR . '/partials/public_head.php';
?>

<section class="pt-36 pb-16 relative overflow-hidden" style="background:linear-gradient(180deg,var(--terracota) 0%,var(--terracota-dark) 100%)">
    <div class="absolute inset-0" style="background-image:radial-gradient(circle at 70% 50%, rgba(58,107,138,0.3) 0%, transparent 60%)"></div>
    <div class="relative max-w-7xl mx-auto px-6 text-center text-white">
        <span class="text-xs font-bold tracking-[0.3em] uppercase" style="color:var(--areia-light)">Viagens completas</span>
        <h1 class="font-display text-5xl md:text-6xl font-bold mt-3 mb-4">Pacotes de Viagem</h1>
        <p class="text-white/85 max-w-2xl mx-auto">Experiências curadas com hospedagem, transporte e passeios incluídos.</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-6">
        <?php if (!$pacotes): ?>
            <div class="text-center py-20">
                <i data-lucide="package-x" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
                <p class="text-lg font-semibold" style="color:var(--sepia)">Em breve novos pacotes!</p>
            </div>
        <?php else: ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($pacotes as $i => $p): 
                $slides = [];
                if ($p['cover_image']) $slides[] = storageUrl($p['cover_image']);
                if (!empty($p['gallery'])) { $dg = json_decode($p['gallery'], true); if (is_array($dg)) foreach ($dg as $g) if ($g) $slides[] = storageUrl($g); }
                $slides = array_values(array_unique($slides));
            ?>
            <a href="<?= url('/pacotes/'.$p['slug']) ?>" class="card-lift group overflow-hidden" data-reveal style="animation-delay: <?= $i * 80 ?>ms">
                <div class="relative aspect-[16/10] overflow-hidden slider-wrap" <?= count($slides)>1?'data-slider':'' ?>>
                    <?php if ($slides): foreach ($slides as $si => $src): ?>
                        <div class="slide<?= $si===0?' active':'' ?>" style="background-image:url('<?= e($src) ?>')"></div>
                    <?php endforeach; else: ?>
                        <div class="img-placeholder w-full h-full"><span><?= e(mb_substr($p['title'],0,1)) ?></span></div>
                    <?php endif; ?>
                    <?php if (count($slides) > 1): ?>
                        <div class="slider-dots"><?php foreach ($slides as $si => $_): ?><span class="dot<?= $si===0?' active':'' ?>"></span><?php endforeach; ?></div>
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent pointer-events-none"></div>
                    <div class="absolute top-4 right-4 px-3 py-1.5 rounded-full text-xs font-bold bg-white/95" style="color:var(--sepia);z-index:2"><?= e($p['duration_days']) ?>D / <?= e($p['duration_nights']) ?>N</div>
                    <div class="absolute bottom-4 left-4 right-4" style="z-index:2">
                        <div class="flex items-center gap-1.5 text-white/80 text-xs mb-2"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i><?= e($p['destination']) ?></div>
                        <h3 class="font-display text-xl font-bold text-white leading-tight line-clamp-2"><?= e($p['title']) ?></h3>
                    </div>
                </div>
                <div class="p-6">
                    <p class="text-sm line-clamp-2 mb-4" style="color:var(--text-secondary)"><?= e($p['short_desc'] ?? '') ?></p>
                    <div class="flex items-end justify-between pt-4 border-t" style="border-color:var(--border-default)">
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:var(--text-muted)">A partir de</div>
                            <div class="font-display text-2xl font-bold" style="color:var(--terracota)"><?= formatBRL($p['price_pix'] ?: $p['price']) ?></div>
                            <?php if ($p['installments']>1): ?><div class="text-xs" style="color:var(--text-muted)">ou <?= $p['installments'] ?>x sem juros</div><?php endif; ?>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white" style="background:var(--terracota)">Reservar<i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
