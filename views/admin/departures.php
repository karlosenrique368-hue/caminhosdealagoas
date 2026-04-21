<?php
/**
 * Admin · Gerenciar datas (departures)
 * Permite criar, editar, bloquear, reabrir e excluir saídas programadas
 * para roteiros e pacotes.
 */
requireAdmin();
$pageTitle = 'Datas & Disponibilidade';

$filterType = in_array($_GET['type'] ?? '', ['roteiro','pacote'], true) ? $_GET['type'] : '';
$filterEntityId = (int)($_GET['entity_id'] ?? 0);

// Dropdowns
$roteiros = dbAll("SELECT id, title FROM roteiros ORDER BY title");
$pacotes  = dbAll("SELECT id, title FROM pacotes ORDER BY title");
$entityMap = [
    'roteiro' => array_column($roteiros, 'title', 'id'),
    'pacote'  => array_column($pacotes, 'title', 'id'),
];

if (isPost() && csrfVerify()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'entity_type'    => in_array($_POST['entity_type'] ?? '', ['roteiro','pacote'], true) ? $_POST['entity_type'] : 'roteiro',
            'entity_id'      => (int)($_POST['entity_id'] ?? 0),
            'departure_date' => $_POST['departure_date'] ?? null,
            'departure_time' => $_POST['departure_time'] ?: null,
            'return_date'    => $_POST['return_date'] ?: null,
            'seats_total'    => max(1, (int)($_POST['seats_total'] ?? 20)),
            'price_override' => parseBRL($_POST['price_override'] ?? '0') ?: null,
            'status'         => in_array($_POST['status'] ?? '', ['open','closed','cancelled'], true) ? $_POST['status'] : 'open',
            'note'           => trim($_POST['note'] ?? '') ?: null,
        ];
        if (!$data['entity_id'] || !$data['departure_date']) {
            flash('error', 'Preencha produto e data.');
        } elseif ($id) {
            $sets=[]; foreach ($data as $k=>$_) $sets[]="`$k`=?";
            $vals=array_values($data); $vals[]=$id;
            dbExec("UPDATE departures SET ".implode(',',$sets)." WHERE id=?", $vals);
            flash('success','Data atualizada.');
        } else {
            $fields = array_keys($data);
            dbExec("INSERT INTO departures (".implode(',',$fields).") VALUES (".implode(',',array_fill(0,count($fields),'?')).")", array_values($data));
            flash('success','Data criada.');
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $to = $_POST['to'] ?? 'closed';
        if (in_array($to, ['open','closed','cancelled'], true)) {
            dbExec("UPDATE departures SET status=? WHERE id=?", [$to, $id]);
            flash('success', $to==='open' ? 'Data reaberta.' : ($to==='closed' ? 'Data bloqueada.' : 'Data cancelada.'));
        }
    } elseif ($action === 'delete') {
        dbExec("DELETE FROM departures WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success','Data removida.');
    } elseif ($action === 'bulk') {
        // Criar várias datas em massa (ex: todo sábado do mês)
        $type = in_array($_POST['bulk_type'] ?? '', ['roteiro','pacote'], true) ? $_POST['bulk_type'] : 'roteiro';
        $eid  = (int)($_POST['bulk_entity_id'] ?? 0);
        $from = $_POST['bulk_from'] ?? null;
        $to   = $_POST['bulk_to'] ?? null;
        $dows = array_map('intval', (array)($_POST['bulk_dows'] ?? []));
        $seats= max(1,(int)($_POST['bulk_seats'] ?? 20));
        $time = $_POST['bulk_time'] ?: null;
        if ($eid && $from && $to && $dows) {
            $cursor = strtotime($from);
            $end = strtotime($to);
            $created = 0;
            while ($cursor <= $end) {
                $dow = (int)date('w', $cursor);
                if (in_array($dow, $dows, true)) {
                    $date = date('Y-m-d', $cursor);
                    $dup = dbOne("SELECT id FROM departures WHERE entity_type=? AND entity_id=? AND departure_date=?", [$type, $eid, $date]);
                    if (!$dup) {
                        dbExec("INSERT INTO departures (entity_type, entity_id, departure_date, departure_time, seats_total, status) VALUES (?,?,?,?,?,'open')", [$type, $eid, $date, $time, $seats]);
                        $created++;
                    }
                }
                $cursor = strtotime('+1 day', $cursor);
            }
            flash('success', "$created datas criadas em lote.");
        } else {
            flash('error','Preencha produto, período e dias da semana.');
        }
    }
    redirect('/admin/departures' . ($filterType ? "?type=$filterType&entity_id=$filterEntityId" : ''));
}

// Listagem
$where = ['1=1'];
$params = [];
if ($filterType) { $where[] = 'd.entity_type=?'; $params[] = $filterType; }
if ($filterEntityId) { $where[] = 'd.entity_id=?'; $params[] = $filterEntityId; }
$whereSql = implode(' AND ', $where);

$pag = paginate(
    "SELECT COUNT(*) AS c FROM departures d WHERE $whereSql",
    "SELECT d.*,
        CASE d.entity_type WHEN 'roteiro' THEN r.title WHEN 'pacote' THEN p.title END AS entity_title,
        CASE d.entity_type WHEN 'roteiro' THEN r.slug ELSE p.slug END AS entity_slug
     FROM departures d
     LEFT JOIN roteiros r ON d.entity_type='roteiro' AND d.entity_id=r.id
     LEFT JOIN pacotes  p ON d.entity_type='pacote'  AND d.entity_id=p.id
     WHERE $whereSql
     ORDER BY d.departure_date DESC, d.id DESC",
    $params
);
$items = $pag['rows'];

require VIEWS_DIR . '/partials/admin_head.php';
$flashOk  = flash('success');
$flashErr = flash('error');
$statusLabels = ['open'=>'Aberta','closed'=>'Bloqueada','cancelled'=>'Cancelada'];
$statusBadge  = ['open'=>'success','closed'=>'muted','cancelled'=>'danger'];
?>

<?php if ($flashOk): ?><div class="mb-4 p-3 rounded-xl flex items-center gap-2 text-sm" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3);color:var(--maresia-dark)"><i data-lucide="check-circle" class="w-4 h-4"></i><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="mb-4 p-3 rounded-xl flex items-center gap-2 text-sm" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#B91C1C"><i data-lucide="alert-circle" class="w-4 h-4"></i><?= e($flashErr) ?></div><?php endif; ?>

<div x-data="{open:false, openBulk:false, editing:null}">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-5">
        <p class="text-sm" style="color:var(--text-secondary)"><?= $pag['total'] ?> data<?= $pag['total']===1?'':'s' ?> cadastrada<?= $pag['total']===1?'':'s' ?></p>
        <div class="flex gap-2">
            <button type="button" @click="openBulk=true" class="admin-btn admin-btn-secondary"><i data-lucide="calendar-range" class="w-4 h-4"></i>Criar em lote</button>
            <button type="button" @click="editing=null; open=true" class="admin-btn admin-btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Nova data</button>
        </div>
    </div>

    <!-- Filtros -->
    <form method="GET" class="admin-card p-4 mb-5 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[160px]">
            <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Tipo</label>
            <select name="type" class="admin-input" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="roteiro" <?= $filterType==='roteiro'?'selected':'' ?>>Passeios</option>
                <option value="pacote"  <?= $filterType==='pacote'?'selected':'' ?>>Pacotes</option>
            </select>
        </div>
        <div class="flex-1 min-w-[220px]">
            <label class="block text-xs font-semibold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Produto</label>
            <select name="entity_id" class="admin-input" onchange="this.form.submit()">
                <option value="0">— Todos —</option>
                <?php if ($filterType==='roteiro' || !$filterType): ?>
                    <optgroup label="Passeios">
                        <?php foreach ($roteiros as $r): ?><option value="<?= $r['id'] ?>" <?= $filterEntityId===(int)$r['id']?'selected':'' ?>><?= e($r['title']) ?></option><?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
                <?php if ($filterType==='pacote' || !$filterType): ?>
                    <optgroup label="Pacotes">
                        <?php foreach ($pacotes as $p): ?><option value="<?= $p['id'] ?>" <?= $filterEntityId===(int)$p['id']?'selected':'' ?>><?= e($p['title']) ?></option><?php endforeach; ?>
                    </optgroup>
                <?php endif; ?>
            </select>
        </div>
        <?php if ($filterType || $filterEntityId): ?>
        <a href="<?= url('/admin/departures') ?>" class="admin-btn admin-btn-secondary"><i data-lucide="x" class="w-4 h-4"></i>Limpar</a>
        <?php endif; ?>
    </form>

    <div class="admin-card overflow-hidden">
        <?php if (!$items): ?>
            <div class="p-12 text-center">
                <i data-lucide="calendar-x" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
                <h3 class="font-semibold mb-1" style="color:var(--sepia)">Nenhuma data cadastrada</h3>
                <p class="text-sm" style="color:var(--text-muted)">Use <b>Criar em lote</b> para gerar várias datas de uma vez.</p>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead><tr><th>Data</th><th>Produto</th><th>Vagas</th><th>Preço override</th><th>Status</th><th>Observação</th><th class="text-right">Ações</th></tr></thead>
                <tbody>
                <?php foreach ($items as $d): ?>
                <tr>
                    <td>
                        <div class="font-semibold" style="color:var(--sepia)"><?= formatDate($d['departure_date'], 'd/m/Y') ?></div>
                        <?php if ($d['departure_time']): ?><div class="text-xs" style="color:var(--text-muted)">às <?= date('H:i', strtotime($d['departure_time'])) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <div class="text-xs uppercase tracking-wider font-semibold" style="color:var(--terracota)"><?= $d['entity_type']==='roteiro'?'Passeio':'Pacote' ?></div>
                        <div class="text-sm font-semibold"><?= e($d['entity_title'] ?? '—') ?></div>
                    </td>
                    <td>
                        <div class="text-sm"><?= (int)$d['seats_sold'] ?> / <?= (int)$d['seats_total'] ?></div>
                        <div class="text-xs" style="color:var(--text-muted)"><?= max(0, $d['seats_total']-$d['seats_sold']) ?> livres</div>
                    </td>
                    <td><span class="text-sm font-semibold" style="color:var(--terracota)"><?= $d['price_override'] !== null ? formatBRL($d['price_override']) : '<span style=\'color:var(--text-muted)\'>—</span>' ?></span></td>
                    <td><span class="badge badge-<?= $statusBadge[$d['status']] ?? 'muted' ?>"><?= $statusLabels[$d['status']] ?? $d['status'] ?></span></td>
                    <td class="text-xs" style="color:var(--text-muted);max-width:200px"><?= e($d['note'] ?: '—') ?></td>
                    <td class="actions-cell">
                        <div class="flex justify-end gap-1">
                            <?php if ($d['status']==='open'): ?>
                                <form method="POST" class="inline"><?= csrfField() ?><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="<?= $d['id'] ?>"><input type="hidden" name="to" value="closed"><button class="action-chip" title="Bloquear"><i data-lucide="lock" class="w-3.5 h-3.5"></i></button></form>
                            <?php else: ?>
                                <form method="POST" class="inline"><?= csrfField() ?><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="id" value="<?= $d['id'] ?>"><input type="hidden" name="to" value="open"><button class="action-chip chip-success" title="Reabrir"><i data-lucide="unlock" class="w-3.5 h-3.5"></i></button></form>
                            <?php endif; ?>
                            <button type="button" @click="editing=<?= htmlspecialchars(json_encode($d), ENT_QUOTES) ?>; open=true" class="action-chip chip-edit" title="Editar"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i></button>
                            <form method="POST" class="inline" onsubmit="return confirm('Excluir esta data? As reservas ficarão sem saída associada.')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $d['id'] ?>"><button class="action-chip chip-danger" title="Excluir"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal single -->
    <div x-show="open" x-cloak @keydown.escape.window="open=false" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <div @click.outside="open=false" class="w-full max-w-xl rounded-2xl p-6" style="background:var(--bg-card);max-height:90vh;overflow-y:auto">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-display text-xl font-bold" style="color:var(--sepia)" x-text="editing?'Editar data':'Nova data'"></h3>
                <button type="button" @click="open=false" style="color:var(--text-muted)"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="POST" class="space-y-3">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" :value="editing?editing.id:''">
                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Tipo</span>
                        <select name="entity_type" class="admin-input w-full" x-model="(editing||{entity_type:'<?= $filterType ?: 'roteiro' ?>'}).entity_type">
                            <option value="roteiro">Passeio</option>
                            <option value="pacote">Pacote</option>
                        </select>
                    </label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Produto *</span>
                        <select name="entity_id" required class="admin-input w-full" :value="editing?editing.entity_id:'<?= $filterEntityId ?>'">
                            <option value="">— Selecione —</option>
                            <optgroup label="Passeios">
                                <?php foreach ($roteiros as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['title']) ?></option><?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Pacotes">
                                <?php foreach ($pacotes as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?>
                            </optgroup>
                        </select>
                    </label>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Data *</span><input type="date" name="departure_date" required :value="editing?editing.departure_date:''" class="admin-input w-full"></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Horário</span><input type="time" name="departure_time" :value="editing?editing.departure_time:''" class="admin-input w-full"></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Retorno</span><input type="date" name="return_date" :value="editing?editing.return_date:''" class="admin-input w-full"></label>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Vagas totais *</span><input type="number" name="seats_total" min="1" required :value="editing?editing.seats_total:20" class="admin-input w-full"></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Preço especial</span><input type="text" name="price_override" class="admin-input w-full brl-mask" :value="editing && editing.price_override ? 'R$ ' + Number(editing.price_override).toFixed(2).replace('.',',') : ''" placeholder="Deixe vazio p/ usar padrão"></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Status</span>
                        <select name="status" class="admin-input w-full" :value="editing?editing.status:'open'">
                            <option value="open">Aberta</option>
                            <option value="closed">Bloqueada</option>
                            <option value="cancelled">Cancelada</option>
                        </select>
                    </label>
                </div>
                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Observação interna</span><textarea name="note" rows="2" class="admin-input w-full" x-text="editing?editing.note:''" placeholder="Ex: Saída confirmada, condutor X"></textarea></label>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="open=false" class="admin-btn admin-btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="admin-btn admin-btn-primary flex-1">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal bulk -->
    <div x-show="openBulk" x-cloak @keydown.escape.window="openBulk=false" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <div @click.outside="openBulk=false" class="w-full max-w-xl rounded-2xl p-6" style="background:var(--bg-card);max-height:90vh;overflow-y:auto">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-display text-xl font-bold" style="color:var(--sepia)">Criar várias datas em lote</h3>
                <button type="button" @click="openBulk=false" style="color:var(--text-muted)"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <p class="text-sm mb-4" style="color:var(--text-secondary)">Ex: criar toda <b>sexta, sábado e domingo</b> entre duas datas automaticamente.</p>
            <form method="POST" class="space-y-3">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="bulk">
                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Tipo</span>
                        <select name="bulk_type" class="admin-input w-full">
                            <option value="roteiro">Passeio</option>
                            <option value="pacote">Pacote</option>
                        </select>
                    </label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Produto *</span>
                        <select name="bulk_entity_id" required class="admin-input w-full">
                            <option value="">— Selecione —</option>
                            <optgroup label="Passeios">
                                <?php foreach ($roteiros as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['title']) ?></option><?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Pacotes">
                                <?php foreach ($pacotes as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?>
                            </optgroup>
                        </select>
                    </label>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">De *</span><input type="date" name="bulk_from" required class="admin-input w-full" value="<?= date('Y-m-d') ?>"></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Até *</span><input type="date" name="bulk_to" required class="admin-input w-full" value="<?= date('Y-m-d', strtotime('+3 months')) ?>"></label>
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider mb-2 block" style="color:var(--text-secondary)">Dias da semana *</span>
                    <div class="grid grid-cols-7 gap-1">
                        <?php foreach (['Dom'=>0,'Seg'=>1,'Ter'=>2,'Qua'=>3,'Qui'=>4,'Sex'=>5,'Sáb'=>6] as $lbl=>$val): ?>
                        <label class="flex flex-col items-center gap-1 p-2 rounded-lg cursor-pointer border hover:bg-gray-50" style="border-color:var(--border-default)">
                            <input type="checkbox" name="bulk_dows[]" value="<?= $val ?>" class="w-4 h-4" style="accent-color:var(--terracota)">
                            <span class="text-xs font-semibold"><?= $lbl ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Vagas por data</span><input type="number" name="bulk_seats" min="1" value="20" class="admin-input w-full"></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Horário (opcional)</span><input type="time" name="bulk_time" class="admin-input w-full"></label>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="openBulk=false" class="admin-btn admin-btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="admin-btn admin-btn-primary flex-1"><i data-lucide="calendar-range" class="w-4 h-4"></i>Gerar datas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/pagination.php'; ?>
<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
