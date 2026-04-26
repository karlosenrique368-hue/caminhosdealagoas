<?php
$pageTitle = 'Pacotes de Viagem';

$q = trim($_GET['q'] ?? '');
$destination = trim($_GET['destination'] ?? '');
$duration = (int)($_GET['duration'] ?? 0);
$date = trim($_GET['date'] ?? '');
if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = '';
$sort = $_GET['sort'] ?? 'destaque';
$where = "p.status='published'";
$params = [];

if ($q) { $where .= " AND (p.title LIKE ? OR p.short_desc LIKE ? OR p.destination LIKE ?)"; $params = array_merge($params, ["%$q%","%$q%","%$q%"]); }
if ($destination) { $where .= " AND p.destination LIKE ?"; $params[] = "%$destination%"; }
if ($duration) {
    if ($duration === 99) $where .= " AND p.duration_days >= 8";
    else $where .= " AND p.duration_days = ?";
    if ($duration !== 99) $params[] = $duration;
}
if ($date) {
    $where .= " AND (
        (COALESCE(p.availability_mode,'fixed')='fixed' AND EXISTS (
            SELECT 1 FROM departures d
            WHERE d.entity_type='pacote' AND d.entity_id=p.id AND d.departure_date=? AND d.status='open' AND (d.seats_total - d.seats_sold) > 0
        ))
        OR (COALESCE(p.availability_mode,'fixed')='open' AND ? >= CURDATE() AND NOT EXISTS (
            SELECT 1 FROM departures d
            WHERE d.entity_type='pacote' AND d.entity_id=p.id AND d.departure_date=? AND (d.status<>'open' OR (d.seats_total - d.seats_sold) <= 0)
        ))
    )";
    $params[] = $date;
    $params[] = $date;
    $params[] = $date;
}

$orderBy = match($sort) {
    'preco_asc'  => 'COALESCE(p.price_pix,p.price) ASC',
    'preco_desc' => 'COALESCE(p.price_pix,p.price) DESC',
    'recentes'   => 'p.created_at DESC',
    'duracao'    => 'p.duration_days DESC',
    default      => 'p.featured DESC, p.created_at DESC',
};

$pag = paginate(
    "SELECT COUNT(*) AS c FROM pacotes p WHERE $where",
    "SELECT p.* FROM pacotes p WHERE $where ORDER BY $orderBy",
    $params,
    ['allowed'=>[12,24,48], 'default'=>12]
);
$pacotes = $pag['rows'];
$destinations = dbAll("SELECT DISTINCT destination FROM pacotes WHERE status='published' AND destination IS NOT NULL AND destination != '' ORDER BY destination");
include VIEWS_DIR . '/partials/public_head.php';
?>

<section class="pt-32 sm:pt-36 pb-12 sm:pb-16 relative overflow-hidden" style="background:linear-gradient(180deg,var(--terracota) 0%,var(--terracota-dark) 100%)">
    <div class="absolute inset-0" style="background-image:radial-gradient(circle at 70% 50%, rgba(58,107,138,0.3) 0%, transparent 60%)"></div>
    <div class="relative max-w-7xl mx-auto px-6 text-center text-white">
        <span class="text-xs font-bold tracking-[0.3em] uppercase" style="color:var(--areia-light)">Viagens completas</span>
        <h1 class="font-display text-4xl sm:text-5xl md:text-6xl font-bold mt-3 mb-4">Pacotes de Viagem</h1>
        <p class="text-white/85 max-w-2xl mx-auto">Experiências curadas com hospedagem, transporte e passeios incluídos.</p>
    </div>
</section>

<section class="py-10 sm:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <!-- Premium Filter -->
        <form method="GET" class="filter-premium filter-with-date">
            <div class="filter-search">
                <label class="filter-label">Buscar</label>
                <i data-lucide="search" class="filter-search-icon"></i>
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="Nome ou descrição do pacote..." class="filter-input">
            </div>
            <div>
                <label class="filter-label">Destino</label>
                <select name="destination" class="filter-input">
                    <option value="">Todos</option>
                    <?php foreach ($destinations as $d): ?>
                        <option value="<?= e($d['destination']) ?>" <?= $destination === $d['destination'] ? 'selected' : '' ?>><?= e($d['destination']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="filter-label">Duração</label>
                <select name="duration" class="filter-input">
                    <option value="0">Qualquer</option>
                    <option value="3" <?= $duration===3?'selected':'' ?>>3 dias</option>
                    <option value="4" <?= $duration===4?'selected':'' ?>>4 dias</option>
                    <option value="5" <?= $duration===5?'selected':'' ?>>5 dias</option>
                    <option value="7" <?= $duration===7?'selected':'' ?>>7 dias</option>
                    <option value="99" <?= $duration===99?'selected':'' ?>>8+ dias</option>
                </select>
            </div>
            <div>
                <label class="filter-label">Data</label>
                <input type="date" name="date" value="<?= e($date) ?>" class="filter-input" placeholder="Escolher data">
            </div>
            <div>
                <label class="filter-label">Ordenar</label>
                <select name="sort" class="filter-input">
                    <option value="destaque"  <?= $sort==='destaque'?'selected':'' ?>>Em destaque</option>
                    <option value="recentes"  <?= $sort==='recentes'?'selected':'' ?>>Mais recentes</option>
                    <option value="preco_asc" <?= $sort==='preco_asc'?'selected':'' ?>>Menor preço</option>
                    <option value="preco_desc"<?= $sort==='preco_desc'?'selected':'' ?>>Maior preço</option>
                    <option value="duracao"   <?= $sort==='duracao'?'selected':'' ?>>Mais longos</option>
                </select>
            </div>
            <div class="flex gap-2 items-end">
                <button type="submit" class="filter-submit"><i data-lucide="sliders-horizontal" class="w-4 h-4"></i>Filtrar</button>
                <?php if ($q || $destination || $duration || $date || ($sort && $sort !== 'destaque')): ?>
                    <a href="<?= url('/pacotes') ?>" class="filter-reset" title="Limpar"><i data-lucide="x" class="w-4 h-4"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!$pacotes): ?>
            <div class="text-center py-20">
                <i data-lucide="package-x" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
                <p class="text-lg font-semibold" style="color:var(--sepia)">Nenhum pacote encontrado.</p>
                <p class="text-sm mt-2" style="color:var(--text-muted)">Ajuste os filtros ou <a href="<?= url('/contato') ?>" class="underline" style="color:var(--terracota)">fale com a gente</a>.</p>
            </div>
        <?php else: ?>
        <p class="text-sm mb-6" style="color:var(--text-muted)"><strong style="color:var(--sepia)"><?= (int)$pag['total'] ?></strong> pacote<?= (int)$pag['total']===1?'':'s' ?> encontrado<?= (int)$pag['total']===1?'':'s' ?></p>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($pacotes as $i => $p): 
                $slides = [];
                if ($p['cover_image']) $slides[] = storageUrl($p['cover_image']);
                if (!empty($p['gallery'])) { $dg = json_decode($p['gallery'], true); if (is_array($dg)) foreach ($dg as $g) if ($g) $slides[] = storageUrl($g); }
                $slides = array_values(array_unique($slides));
            ?>
            <a href="<?= url('/pacotes/'.$p['slug']) ?>" class="roteiro-card group" data-reveal style="animation-delay: <?= $i * 80 ?>ms">
                <div class="img-wrap slider-wrap" <?= count($slides)>1?'data-slider':'' ?> style="aspect-ratio:4/3;position:relative">
                    <?php if ($slides): foreach ($slides as $si => $src): ?>
                        <div class="slide<?= $si===0?' active':'' ?>" style="background-image:url('<?= e($src) ?>')"></div>
                    <?php endforeach; else: ?>
                        <div class="img-placeholder w-full h-full"><span><?= e(mb_substr($p['title'],0,1)) ?></span></div>
                    <?php endif; ?>
                    <?php if (count($slides) > 1): ?>
                        <div class="slider-dots"><?php foreach ($slides as $si => $_): ?><span class="dot<?= $si===0?' active':'' ?>"></span><?php endforeach; ?></div>
                        <button type="button" class="slider-arrow prev" tabindex="-1" aria-label="Anterior"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                        <button type="button" class="slider-arrow next" tabindex="-1" aria-label="Próximo"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                    <?php endif; ?>
                    <?php if (!empty($p['featured'])): ?><div class="badge-featured">Destaque</div><?php endif; ?>
                    <button type="button" class="heart-btn" data-fav-type="pacote" data-fav-id="<?= (int)$p['id'] ?>" aria-label="Favoritar"><i data-lucide="heart" class="w-4 h-4"></i></button>
                </div>
                <div class="p-5">
                    <?php if (!empty($p['destination'])): ?>
                    <div class="flex items-center gap-1.5 text-xs font-semibold mb-2" style="color:var(--horizonte)">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i><?= e(tAuto($p['destination'])) ?>
                    </div>
                    <?php endif; ?>
                    <h3 class="font-display text-lg font-bold leading-snug mb-2 line-clamp-2" style="color:var(--sepia)"><?= e(tAuto($p['title'])) ?></h3>
                    <p class="text-sm line-clamp-2 mb-3" style="color:var(--text-secondary)"><?= e(tAuto($p['short_desc'] ?? '')) ?></p>
                    <div class="flex items-center gap-3 text-[11px] font-semibold mb-3" style="color:var(--text-muted)">
                        <span class="inline-flex items-center gap-1"><i data-lucide="calendar-days" class="w-3.5 h-3.5"></i><?= (int)$p['duration_days'] ?>D / <?= (int)$p['duration_nights'] ?>N</span>
                    </div>
                    <div class="flex items-end justify-between pt-3 border-t" style="border-color:var(--border-default)">
                        <div>
                            <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:var(--text-muted)">A partir de</div>
                            <div class="font-display text-xl font-bold" style="color:var(--terracota)"><?= formatPrice($p['price_pix'] ?: $p['price']) ?></div>
                            <?php if ($p['installments']>1): ?><div class="text-[11px]" style="color:var(--text-muted)">ou <?= $p['installments'] ?>x sem juros</div><?php endif; ?>
                        </div>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center transition group-hover:bg-terracota group-hover:text-white" style="background:var(--bg-surface);color:var(--terracota)">
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php include VIEWS_DIR . '/partials/pagination.php'; ?>
        <?php endif; ?>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
