<?php
$pageTitle = 'Passeios e Roteiros';
$pageDesc = 'Confira todos os passeios e roteiros disponíveis em Alagoas.';

$q = trim($_GET['q'] ?? '');
$categoryId = (int) ($_GET['cat'] ?? 0);
$where = "status='published'";
$params = [];

if ($q) { $where .= " AND (title LIKE ? OR short_desc LIKE ? OR location LIKE ?)"; $params = array_merge($params, ["%$q%","%$q%","%$q%"]); }
if ($categoryId) { $where .= " AND category_id = ?"; $params[] = $categoryId; }

$roteiros = dbAll("SELECT * FROM roteiros WHERE $where ORDER BY featured DESC, created_at DESC", $params);
$categories = dbAll("SELECT * FROM categories WHERE type='roteiro' AND active=1 ORDER BY sort_order");

include VIEWS_DIR . '/partials/public_head.php';
?>

<!-- Page header -->
<section class="pt-36 pb-16 relative overflow-hidden" style="background:linear-gradient(180deg,var(--horizonte) 0%,var(--horizonte-dark) 100%)">
    <div class="absolute inset-0" style="background-image:radial-gradient(circle at 30% 50%, rgba(201,107,74,0.3) 0%, transparent 60%)"></div>
    <div class="relative max-w-7xl mx-auto px-6 text-center text-white">
        <span class="text-xs font-bold tracking-[0.3em] uppercase" style="color:var(--areia-light)">Nossas experiências</span>
        <h1 class="font-display text-5xl md:text-6xl font-bold mt-3 mb-4">Passeios & Roteiros</h1>
        <p class="text-white/80 max-w-2xl mx-auto">Explore Alagoas com quem conhece cada praia, trilha e história.</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-6">
        <!-- Filters -->
        <form method="GET" class="admin-card p-4 mb-10 flex flex-wrap gap-3 items-center">
            <div class="flex-1 min-w-[240px] relative">
                <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2" style="color:var(--text-muted)"></i>
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar por nome, local..." class="admin-input pl-11">
            </div>
            <select name="cat" class="admin-input md:max-w-[220px]">
                <option value="">Todas as categorias</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $categoryId == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="admin-btn admin-btn-primary"><i data-lucide="filter" class="w-4 h-4"></i>Filtrar</button>
        </form>

        <?php if (!$roteiros): ?>
            <div class="text-center py-20">
                <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
                <p class="text-lg font-semibold" style="color:var(--sepia)">Nenhum passeio encontrado.</p>
                <p class="text-sm mt-2" style="color:var(--text-muted)">Tente ajustar os filtros ou <a href="<?= url('/contato') ?>" class="underline" style="color:var(--terracota)">falar com a gente</a>.</p>
            </div>
        <?php else: ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($roteiros as $i => $r): 
                $slides = [];
                if ($r['cover_image']) $slides[] = storageUrl($r['cover_image']);
                if (!empty($r['gallery'])) { $dg = json_decode($r['gallery'], true); if (is_array($dg)) foreach ($dg as $g) if ($g) $slides[] = storageUrl($g); }
                $slides = array_values(array_unique($slides));
            ?>
            <a href="<?= url('/roteiros/' . $r['slug']) ?>" class="roteiro-card group" data-reveal style="animation-delay: <?= $i * 50 ?>ms">
                <div class="img-wrap slider-wrap" <?= count($slides)>1?'data-slider':'' ?> style="aspect-ratio:4/3;position:relative">
                    <?php if ($slides): ?>
                        <?php foreach ($slides as $si => $src): ?>
                            <div class="slide<?= $si===0?' active':'' ?>" style="background-image:url('<?= e($src) ?>')"></div>
                        <?php endforeach; ?>
                        <?php if (count($slides) > 1): ?>
                            <div class="slider-dots"><?php foreach ($slides as $si => $_): ?><span class="dot<?= $si===0?' active':'' ?>"></span><?php endforeach; ?></div>
                            <button type="button" class="slider-arrow prev" tabindex="-1" aria-label="Anterior"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                            <button type="button" class="slider-arrow next" tabindex="-1" aria-label="Próximo"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="img-placeholder w-full h-full"><span><?= e(mb_substr($r['title'],0,1)) ?></span></div>
                    <?php endif; ?>
                    <?php if ($r['featured']): ?><div class="badge-featured">Destaque</div><?php endif; ?>
                    <button type="button" class="heart-btn" data-fav-type="roteiro" data-fav-id="<?= (int)$r['id'] ?>" aria-label="Favoritar"><i data-lucide="heart" class="w-4 h-4"></i></button>
                </div>
                <div class="p-5">
                    <?php if ($r['location']): ?>
                    <div class="flex items-center gap-1.5 text-xs font-semibold mb-2" style="color:var(--horizonte)">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i><?= e($r['location']) ?>
                    </div>
                    <?php endif; ?>
                    <h3 class="font-display text-lg font-bold leading-snug mb-2 line-clamp-2" style="color:var(--sepia)"><?= e($r['title']) ?></h3>
                    <p class="text-sm line-clamp-2 mb-4" style="color:var(--text-secondary)"><?= e($r['short_desc'] ?? '') ?></p>
                    <div class="flex items-end justify-between pt-3 border-t" style="border-color:var(--border-default)">
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:var(--text-muted)">A partir de</div>
                            <div class="font-display text-xl font-bold" style="color:var(--terracota)"><?= formatBRL($r['price_pix'] ?: $r['price']) ?></div>
                        </div>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center transition group-hover:bg-terracota group-hover:text-white" style="background:var(--bg-surface);color:var(--terracota)">
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
