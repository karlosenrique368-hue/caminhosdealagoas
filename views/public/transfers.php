<?php
$pageTitle = 'Transfers · Caminhos de Alagoas';
$pageDesc  = 'Traslados privativos do aeroporto e entre cidades de Alagoas, com motoristas profissionais.';
$solidNav  = true;

$q = trim($_GET['q'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');
$capacity = (int)($_GET['capacity'] ?? 0);
$sort = $_GET['sort'] ?? 'destaque';
$where = "status='published'"; $params = [];
if ($q) { $where .= " AND (title LIKE ? OR location_from LIKE ? OR location_to LIKE ?)"; $params = ["%$q%","%$q%","%$q%"]; }
if ($from) { $where .= " AND location_from LIKE ?"; $params[] = "%$from%"; }
if ($to) { $where .= " AND location_to LIKE ?"; $params[] = "%$to%"; }
if ($capacity) { $where .= " AND capacity >= ?"; $params[] = $capacity; }

$orderBy = match($sort) {
    'preco_asc'  => 'COALESCE(price_pix,price) ASC',
    'preco_desc' => 'COALESCE(price_pix,price) DESC',
    'recentes'   => 'created_at DESC',
    'capacidade' => 'capacity DESC',
    default      => 'featured DESC, created_at DESC',
};

$pag = paginate(
    "SELECT COUNT(*) AS c FROM transfers WHERE $where",
    "SELECT * FROM transfers WHERE $where ORDER BY $orderBy",
    $params,
    ['allowed'=>[12,24,48], 'default'=>12]
);
$rows = $pag['rows'];
$origins = dbAll("SELECT DISTINCT location_from FROM transfers WHERE status='published' AND location_from IS NOT NULL AND location_from != '' ORDER BY location_from");
$destinations = dbAll("SELECT DISTINCT location_to FROM transfers WHERE status='published' AND location_to IS NOT NULL AND location_to != '' ORDER BY location_to");

include VIEWS_DIR . '/partials/public_head.php';
?>

<section class="py-20 pt-32" style="background:linear-gradient(135deg,var(--horizonte) 0%,var(--horizonte-dark) 100%);color:#fff;position:relative;overflow:hidden">
    <img src="<?= asset('brand/selo-branco.png') ?>" class="seal-rotate absolute hidden md:block" style="top:80px;right:40px;width:110px;opacity:0.25" alt="">
    <div class="relative z-10 max-w-7xl mx-auto px-6 text-center">
        <span class="inline-block text-[11px] font-bold uppercase tracking-[0.3em] px-3 py-1 rounded-full mb-4" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2)">Transfers privativos</span>
        <h1 class="font-brand text-5xl md:text-7xl leading-[0.95] mb-4 mx-auto max-w-4xl">Chegue tranquilo, viaje no conforto</h1>
        <p class="text-lg md:text-xl text-white/85 max-w-2xl mx-auto">Traslados privativos do aeroporto e entre destinos de Alagoas. Motoristas profissionais, veículos confortáveis, atendimento 24h.</p>
    </div>
</section>

<section class="py-16" style="background:var(--bg-surface)">
    <div class="max-w-7xl mx-auto px-6">
        <form method="GET" class="filter-premium">
            <div class="filter-search">
                <label class="filter-label">Buscar</label>
                <i data-lucide="search" class="filter-search-icon"></i>
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="Origem, destino ou nome..." class="filter-input">
            </div>
            <div>
                <label class="filter-label">Origem</label>
                <select name="from" class="filter-input">
                    <option value="">Todas</option>
                    <?php foreach ($origins as $o): ?><option value="<?= e($o['location_from']) ?>" <?= $from===$o['location_from']?'selected':'' ?>><?= e($o['location_from']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="filter-label">Destino</label>
                <select name="to" class="filter-input">
                    <option value="">Todos</option>
                    <?php foreach ($destinations as $d): ?><option value="<?= e($d['location_to']) ?>" <?= $to===$d['location_to']?'selected':'' ?>><?= e($d['location_to']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="filter-label">Passageiros</label>
                <select name="capacity" class="filter-input">
                    <option value="0">Qualquer</option>
                    <?php foreach ([3,4,6,8,12] as $cap): ?><option value="<?= $cap ?>" <?= $capacity===$cap?'selected':'' ?>><?= $cap ?>+ pax</option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="filter-label">Ordenar</label>
                <select name="sort" class="filter-input">
                    <option value="destaque" <?= $sort==='destaque'?'selected':'' ?>>Em destaque</option>
                    <option value="recentes" <?= $sort==='recentes'?'selected':'' ?>>Mais recentes</option>
                    <option value="preco_asc" <?= $sort==='preco_asc'?'selected':'' ?>>Menor preço</option>
                    <option value="preco_desc" <?= $sort==='preco_desc'?'selected':'' ?>>Maior preço</option>
                    <option value="capacidade" <?= $sort==='capacidade'?'selected':'' ?>>Maior capacidade</option>
                </select>
            </div>
            <div class="flex gap-2 items-end">
                <button type="submit" class="filter-submit"><i data-lucide="sliders-horizontal" class="w-4 h-4"></i>Filtrar</button>
                <?php if ($q || $from || $to || $capacity || ($sort && $sort !== 'destaque')): ?><a href="<?= url('/transfers') ?>" class="filter-reset" title="Limpar"><i data-lucide="x" class="w-4 h-4"></i></a><?php endif; ?>
            </div>
        </form>

        <?php if (!$rows): ?>
            <div class="text-center py-20">
                <i data-lucide="car" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
                <h3 class="font-display text-xl font-bold mb-1" style="color:var(--sepia)">Nenhum transfer disponível</h3>
                <p class="text-sm" style="color:var(--text-muted)">Tente outra busca ou fale com a gente no WhatsApp.</p>
            </div>
        <?php else: ?>
            <p class="text-sm mb-6" style="color:var(--text-muted)"><strong style="color:var(--sepia)"><?= (int)$pag['total'] ?></strong> transfer<?= (int)$pag['total']===1?'':'s' ?> encontrado<?= (int)$pag['total']===1?'':'s' ?></p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($rows as $i => $r): ?>
                <a href="<?= url('/transfers/' . $r['slug']) ?>" class="roteiro-card group" data-reveal style="animation-delay: <?= $i * 50 ?>ms">
                    <div class="img-wrap" style="aspect-ratio:4/3;position:relative">
                        <?php if (!empty($r['cover_image'])): ?>
                            <div class="slide active" style="background-image:url('<?= e(storageUrl($r['cover_image'])) ?>')"></div>
                        <?php else: ?>
                            <div class="img-placeholder w-full h-full"><span><?= e(mb_substr($r['title'],0,1)) ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($r['featured'])): ?><div class="badge-featured">Destaque</div><?php endif; ?>
                        <div class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest" style="background:rgba(255,255,255,.95);color:var(--horizonte)"><i data-lucide="car" class="w-3 h-3 inline"></i> Transfer</div>
                    </div>
                    <div class="p-5">
                        <?php if ($r['location_from'] && $r['location_to']): ?>
                        <div class="flex items-center gap-1.5 text-xs font-semibold mb-2" style="color:var(--horizonte)">
                            <i data-lucide="route" class="w-3.5 h-3.5"></i><?= e(tAuto($r['location_from'])) ?> → <?= e(tAuto($r['location_to'])) ?>
                        </div>
                        <?php endif; ?>
                        <h3 class="font-display text-lg font-bold leading-snug mb-2 line-clamp-2" style="color:var(--sepia)"><?= e(tAuto($r['title'])) ?></h3>
                        <div class="flex items-center gap-3 text-[11px] font-semibold mb-3" style="color:var(--text-muted)">
                            <span class="inline-flex items-center gap-1"><i data-lucide="users" class="w-3.5 h-3.5"></i><?= (int)$r['capacity'] ?> pax</span>
                            <?php if ($r['duration_minutes']): ?>
                            <span class="inline-flex items-center gap-1"><i data-lucide="clock" class="w-3.5 h-3.5"></i><?= (int)$r['duration_minutes'] ?> min</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-end justify-between pt-3 border-t" style="border-color:var(--border-default)">
                            <div>
                                <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:var(--text-muted)">A partir de</div>
                                <div class="font-display text-xl font-bold" style="color:var(--terracota)"><?= formatPrice($r['price_pix'] ?: $r['price']) ?></div>
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
