<?php
requireAdmin();
$pageTitle = 'Idiomas & Moedas';

// ===== actions =====
if (isPost() && csrfVerify()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_translation') {
        $lang = $_POST['lang'] ?? '';
        $key  = trim($_POST['tkey'] ?? '');
        $val  = trim($_POST['value'] ?? '');
        if (isset(I18N_SUPPORTED_LANGS[$lang]) && $key !== '') {
            dbExec('INSERT INTO translations (lang,tkey,value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$lang,$key,$val]);
            flash('success','Tradução salva.');
        }
    }
    if ($action === 'delete_translation') {
        dbExec('DELETE FROM translations WHERE id=?', [(int)$_POST['id']]);
        flash('success','Tradução removida.');
    }
    if ($action === 'bulk_delete_key') {
        $key = trim($_POST['tkey'] ?? '');
        if ($key) { dbExec('DELETE FROM translations WHERE tkey=?', [$key]); flash('success','Chave removida em todos os idiomas.'); }
    }
    if ($action === 'save_rates') {
        $rates = [];
        foreach (I18N_SUPPORTED_CURRENCIES as $code => $_) {
            if ($code === 'BRL') continue;
            $rates[$code] = max(0, (float) str_replace(',', '.', $_POST['rate_'.$code] ?? 0));
        }
        setSetting('currency_rates', json_encode($rates));
        flash('success','Taxas de câmbio atualizadas.');
    }
    redirect('/admin/traducoes');
}

// ===== data =====
$filterLang = $_GET['lang'] ?? '';
$filterQ    = trim($_GET['q'] ?? '');

// WHERE aplicado sobre a tabela translations
$where = []; $params = [];
if ($filterLang) { $where[] = 'lang=?'; $params[] = $filterLang; }
if ($filterQ)    { $where[] = '(tkey LIKE ? OR value LIKE ?)'; $params[] = "%$filterQ%"; $params[] = "%$filterQ%"; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// Pagina por CHAVE distinta
$pag = paginate(
    "SELECT COUNT(*) AS c FROM (SELECT tkey FROM translations$whereSql GROUP BY tkey) t",
    "SELECT tkey FROM translations$whereSql GROUP BY tkey ORDER BY tkey",
    $params,
    ['allowed'=>[20,50,100,200], 'default'=>20]
);
$pageKeys = array_column($pag['rows'], 'tkey');

// Carrega todas as linhas das chaves desta pagina (em todos os idiomas — para preview completo)
$grouped = [];
if ($pageKeys) {
    $in = implode(',', array_fill(0, count($pageKeys), '?'));
    $rows = dbAll("SELECT * FROM translations WHERE tkey IN ($in) ORDER BY tkey, lang", $pageKeys);
    foreach ($rows as $r) { $grouped[$r['tkey']][$r['lang']] = $r; }
}

// completude por idioma
$stats = [];
$totalKeys = (int)(dbOne("SELECT COUNT(DISTINCT tkey) AS c FROM translations")['c'] ?? 0);
foreach (I18N_SUPPORTED_LANGS as $code => $meta) {
    $cnt = (int)(dbOne('SELECT COUNT(*) AS c FROM translations WHERE lang=?', [$code])['c'] ?? 0);
    $stats[$code] = ['count'=>$cnt,'pct'=>$totalKeys?round($cnt*100/$totalKeys):0,'meta'=>$meta];
}

$rates = currencyRates();
$flashOk = flash('success');
$flashErr = flash('error');

require VIEWS_DIR . '/partials/admin_head.php';
?>

<?php if ($flashOk): ?>
<div class="mb-4 p-3 rounded-xl flex items-center gap-2 text-sm" style="background:rgba(122,157,110,0.1);border:1px solid rgba(122,157,110,0.3);color:var(--maresia-dark)">
    <i data-lucide="check-circle" class="w-4 h-4"></i><?= e($flashOk) ?>
</div>
<?php endif; ?>

<!-- Tabs -->
<div x-data="{tab:'<?= $filterQ || $filterLang ? 'translations' : 'translations' ?>'}" class="space-y-6">
    <div class="admin-card p-2 inline-flex flex-wrap gap-1 w-full sm:w-auto">
        <button @click="tab='translations'" :class="tab==='translations'?'admin-btn-primary':''" class="admin-btn admin-btn-secondary"><i data-lucide="languages" class="w-4 h-4"></i>Traduções</button>
        <button @click="tab='currencies'"    :class="tab==='currencies'?'admin-btn-primary':''"    class="admin-btn admin-btn-secondary"><i data-lucide="coins" class="w-4 h-4"></i>Moedas & câmbio</button>
        <button @click="tab='coverage'"      :class="tab==='coverage'?'admin-btn-primary':''"      class="admin-btn admin-btn-secondary"><i data-lucide="pie-chart" class="w-4 h-4"></i>Cobertura</button>
    </div>

    <!-- ================= TRADUÇÕES ================= -->
    <div x-show="tab==='translations'">
        <div class="grid lg:grid-cols-[380px_1fr] gap-6">
            <!-- FORM NOVA / EDITAR -->
            <div class="admin-card p-5 h-fit lg:sticky lg:top-6">
                <h2 class="font-display text-lg font-bold mb-1" style="color:var(--sepia)">Nova / atualizar</h2>
                <p class="text-xs mb-4" style="color:var(--text-muted)">Mesma chave em outro idioma sobrescreve.</p>
                <form method="POST" class="space-y-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="save_translation">
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Idioma</span>
                        <select name="lang" required class="admin-input w-full">
                            <?php foreach (I18N_SUPPORTED_LANGS as $code=>$m): ?>
                                <option value="<?= $code ?>"><?= $m['flag'] ?> <?= e($m['native']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Chave</span>
                        <input type="text" name="tkey" required placeholder="ex: nav.home" class="admin-input w-full font-mono text-sm">
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Valor</span>
                        <textarea name="value" rows="4" required class="admin-input w-full"></textarea>
                    </label>
                    <button class="admin-btn admin-btn-primary w-full justify-center"><i data-lucide="save" class="w-4 h-4"></i>Salvar</button>
                </form>
            </div>

            <!-- TABLE -->
            <div class="space-y-4">
                <!-- Filters -->
                <form method="GET" class="admin-card p-4 flex flex-wrap items-end gap-3">
                    <label class="flex-1 min-w-[200px]">
                        <span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Buscar</span>
                        <input type="text" name="q" value="<?= e($filterQ) ?>" placeholder="chave ou valor…" class="admin-input w-full">
                    </label>
                    <label class="min-w-[160px]">
                        <span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Idioma</span>
                        <select name="lang" class="admin-input w-full">
                            <option value="">Todos</option>
                            <?php foreach (I18N_SUPPORTED_LANGS as $code=>$m): ?>
                                <option value="<?= $code ?>" <?= $filterLang===$code?'selected':'' ?>><?= $m['flag'] ?> <?= e($m['native']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="admin-btn admin-btn-primary"><i data-lucide="search" class="w-4 h-4"></i>Filtrar</button>
                    <?php if ($filterQ || $filterLang): ?>
                        <a href="<?= url('/admin/traducoes') ?>" class="admin-btn admin-btn-secondary"><i data-lucide="x" class="w-4 h-4"></i>Limpar</a>
                    <?php endif; ?>
                </form>

                <!-- Grouped -->
                <div class="admin-card overflow-hidden">
                    <div class="hidden md:grid gap-3 p-4 text-xs font-bold uppercase tracking-wider" style="background:var(--bg-surface);color:var(--text-secondary);grid-template-columns:1fr 2fr 120px">
                        <span>Chave</span><span>Valores por idioma</span><span></span>
                    </div>
                    <?php if (!$grouped): ?>
                        <div class="p-10 text-center" style="color:var(--text-muted)">Nenhuma tradução encontrada.</div>
                    <?php else: foreach ($grouped as $key => $byLang): ?>
                        <div class="border-t grid gap-3 p-4 items-start" style="border-color:var(--border-default);grid-template-columns:1fr;@media (min-width:768px){grid-template-columns:1fr 2fr 120px}">
                            <div class="md:contents">
                                <code class="text-xs px-2 py-0.5 rounded font-mono inline-block" style="background:var(--bg-surface);color:var(--terracota);word-break:break-all"><?= e($key) ?></code>
                                <div class="flex flex-wrap gap-2 mt-2 md:mt-0">
                                    <?php foreach (I18N_SUPPORTED_LANGS as $code=>$m):
                                        $v = $byLang[$code] ?? null; ?>
                                        <div class="text-xs px-2 py-1 rounded-lg flex items-start gap-1.5 <?= $v?'':'opacity-40' ?>" style="background:var(--bg-surface);max-width:100%">
                                            <span title="<?= e($m['native']) ?>"><?= $m['flag'] ?></span>
                                            <span class="font-medium" style="color:<?= $v?'var(--text-primary)':'var(--text-muted)' ?>;word-break:break-word"><?= $v ? e($v['value']) : '—' ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="flex gap-1 mt-2 md:mt-0 md:justify-end">
                                    <form method="POST" onsubmit="return confirm('Remover todas traduções dessa chave?')" class="inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="bulk_delete_key">
                                        <input type="hidden" name="tkey" value="<?= e($key) ?>">
                                        <button class="admin-btn admin-btn-secondary text-xs" style="color:#DC2626"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <!-- Paginacao -->
                <?php include VIEWS_DIR . '/partials/pagination.php'; ?>

                <!-- Auto-traducao desta pagina -->
                <div class="admin-card p-4 flex flex-wrap items-center gap-3">
                    <div class="flex-1 min-w-[240px]">
                        <div class="font-bold text-sm" style="color:var(--sepia)"><i data-lucide="sparkles" class="w-4 h-4 inline"></i> Tradução automática</div>
                        <p class="text-xs mt-0.5" style="color:var(--text-muted)">Preenche as lacunas desta página usando tradução gratuita (MyMemory). O idioma origem é <b>pt-BR</b>.</p>
                    </div>
                    <button type="button" onclick="autoTranslatePage()" id="auto-tr-btn" class="admin-btn admin-btn-primary"><i data-lucide="wand-2" class="w-4 h-4"></i>Traduzir página</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    const __pageKeys = <?= json_encode($pageKeys) ?>;
    async function autoTranslatePage(){
        const btn = document.getElementById('auto-tr-btn');
        if (!__pageKeys.length) { alert('Nenhuma chave para traduzir nesta página.'); return; }
        if (!confirm('Traduzir automaticamente '+__pageKeys.length+' chaves para todos os idiomas faltantes?')) return;
        btn.disabled = true; btn.innerHTML = '<i data-lucide="loader" class="w-4 h-4 animate-spin"></i>Traduzindo...';
        try {
            const res = await fetch('<?= url('/api/autotranslate') ?>', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= csrfToken() ?>'},
                body:'csrf_token=<?= csrfToken() ?>&keys='+encodeURIComponent(JSON.stringify(__pageKeys))
            });
            const j = await res.json();
            if (j.ok) { alert('Pronto! '+j.filled+' traduções geradas.'); location.reload(); }
            else alert('Erro: '+(j.msg||'desconhecido'));
        } catch(e){ alert('Falha de rede: '+e.message); }
        finally { btn.disabled = false; }
    }
    </script>
    <div x-show="tab==='currencies'" x-cloak class="space-y-4">
        <div class="admin-card p-6">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <h2 class="font-display text-lg font-bold mb-1" style="color:var(--sepia)">Taxas de câmbio</h2>
                    <p class="text-sm" style="color:var(--text-secondary)">Base: <b>1,00 BRL</b>. Ajuste quanto vale 1 real em cada moeda.</p>
                </div>
                <i data-lucide="coins" class="w-8 h-8" style="color:var(--terracota)"></i>
            </div>
            <form method="POST" class="grid sm:grid-cols-2 gap-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save_rates">
                <?php foreach (I18N_SUPPORTED_CURRENCIES as $code => $m):
                    if ($code === 'BRL') continue;
                    $current = $rates[$code] ?? 0;
                ?>
                <label class="block p-4 rounded-xl border-2 transition hover:border-[var(--terracota)]" style="border-color:var(--border-default);background:var(--bg-surface)">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-semibold flex items-center gap-2" style="color:var(--sepia)"><span style="font-size:20px"><?= $m['flag'] ?></span><?= $code ?> <span class="text-xs font-normal" style="color:var(--text-muted)"><?= e($m['name']) ?></span></span>
                        <span class="text-xs font-mono px-2 py-0.5 rounded" style="background:white;color:var(--terracota)"><?= $m['symbol'] ?></span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-xs" style="color:var(--text-muted)">1 BRL =</span>
                        <input type="number" step="0.0001" min="0" name="rate_<?= $code ?>" value="<?= number_format($current, 4, '.', '') ?>" class="admin-input flex-1 text-base font-semibold" style="color:var(--terracota)">
                    </div>
                    <div class="text-[11px] mt-1.5" style="color:var(--text-muted)">R$ 100,00 ≈ <span class="font-mono"><?= e(formatPrice(100, $code)) ?></span></div>
                </label>
                <?php endforeach; ?>
                <div class="sm:col-span-2 flex items-center justify-end gap-2 mt-2">
                    <a href="https://wise.com/br/currency-converter/brl-to-usd-rate" target="_blank" class="text-xs" style="color:var(--horizonte)"><i data-lucide="external-link" class="w-3 h-3 inline"></i> Consultar taxa hoje</a>
                    <button class="admin-btn admin-btn-primary"><i data-lucide="save" class="w-4 h-4"></i>Atualizar taxas</button>
                </div>
            </form>
        </div>

        <div class="admin-card p-6">
            <h3 class="font-display text-base font-bold mb-3" style="color:var(--sepia)">Como funciona</h3>
            <ul class="space-y-2 text-sm" style="color:var(--text-secondary)">
                <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 mt-0.5" style="color:var(--maresia-dark)"></i>Todos os preços são salvos no banco <b>em BRL</b>.</li>
                <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 mt-0.5" style="color:var(--maresia-dark)"></i>A função <code class="text-xs px-1.5 py-0.5 rounded" style="background:var(--bg-surface)">formatPrice($brl)</code> converte automaticamente para a moeda escolhida pelo visitante.</li>
                <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 mt-0.5" style="color:var(--maresia-dark)"></i>A escolha do visitante é salva em sessão <i>e</i> cookie (1 ano), válida no site e no checkout.</li>
                <li class="flex gap-2"><i data-lucide="info" class="w-4 h-4 mt-0.5" style="color:var(--horizonte)"></i>O pagamento é sempre processado em BRL (Pix/cartão BR). A conversão é apenas visual.</li>
            </ul>
        </div>
    </div>

    <!-- ================= COBERTURA ================= -->
    <div x-show="tab==='coverage'" x-cloak>
        <div class="admin-card p-6">
            <h2 class="font-display text-lg font-bold mb-1" style="color:var(--sepia)">Cobertura das traduções</h2>
            <p class="text-sm mb-5" style="color:var(--text-secondary)"><b><?= $totalKeys ?></b> chaves distintas cadastradas. Idiomas incompletos terão fallback automático para PT-BR.</p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($stats as $code => $s): ?>
                <div class="p-4 rounded-xl border" style="border-color:var(--border-default);background:var(--bg-surface)">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-semibold flex items-center gap-2" style="color:var(--sepia)"><span style="font-size:20px"><?= $s['meta']['flag'] ?></span><?= e($s['meta']['native']) ?></span>
                        <span class="text-xs font-mono px-2 py-0.5 rounded" style="background:white;color:var(--text-muted)"><?= $code ?></span>
                    </div>
                    <div class="flex items-baseline justify-between mb-2">
                        <span class="font-display text-2xl font-bold" style="color:<?= $s['pct']>=80?'var(--maresia-dark)':($s['pct']>=40?'#D97706':'#DC2626') ?>"><?= $s['pct'] ?>%</span>
                        <span class="text-xs" style="color:var(--text-muted)"><?= $s['count'] ?> / <?= $totalKeys ?> chaves</span>
                    </div>
                    <div class="h-2 rounded-full overflow-hidden" style="background:var(--border-default)">
                        <div class="h-full rounded-full transition-all" style="width:<?= $s['pct'] ?>%;background:linear-gradient(90deg,var(--terracota),var(--horizonte))"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
