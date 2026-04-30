<?php
$pageTitle = 'Cupons';

if (isPost() && csrfVerify()) {
    requireAdmin();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $type = in_array($_POST['type'] ?? 'percent', ['percent','fixed'], true) ? $_POST['type'] : 'percent';
        $value = max(0.0, (float) str_replace(',', '.', $_POST['value'] ?? '0'));
        if ($type === 'percent' && $value > 100) $value = 100.0;
        $data = [
            'code'          => strtoupper(trim($_POST['code'] ?? '')),
            'description'   => trim($_POST['description'] ?? ''),
            'type'          => $type,
            'value'         => $value,
            'min_purchase'  => parseBRL($_POST['min_amount'] ?? '0') ?: null,
            'max_uses'      => (int)($_POST['max_uses'] ?? 0) ?: null,
            'valid_from'    => $_POST['valid_from'] ?: null,
            'valid_until'   => $_POST['valid_until'] ?: null,
            'active'        => isset($_POST['active']) ? 1 : 0,
        ];
        if ($data['code'] === '') { flash('error', 'Código do cupom é obrigatório.'); redirect('/admin/cupons'); }
        if ($id) {
            $sets = []; foreach ($data as $k=>$_) $sets[] = "`$k`=?";
            $values = array_values($data); $values[] = $id;
            dbExec("UPDATE coupons SET ".implode(',',$sets)." WHERE id=?", $values);
        } else {
            $fields = array_keys($data);
            dbInsert("INSERT INTO coupons (".implode(',', array_map(fn($f)=>"`$f`", $fields)).") VALUES (".implode(',', array_fill(0,count($fields),'?')).")", array_values($data));
        }
        flash('success', 'Cupom salvo.');
    }
    if ($action === 'delete') {
        dbExec("DELETE FROM coupons WHERE id=?", [(int)$_POST['id']]);
        flash('success', 'Cupom excluído.');
    }
    redirect('/admin/cupons');
}

require VIEWS_DIR . '/partials/admin_head.php';
$pag = paginate("SELECT COUNT(*) AS c FROM coupons", "SELECT * FROM coupons ORDER BY created_at DESC");
$coupons = $pag['rows'];
$msg = flash('success');
?>

<?php if ($msg): ?><div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)"><i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i><span class="text-sm" style="color:var(--maresia-dark)"><?= e($msg) ?></span></div><?php endif; ?>

<div x-data="{open:false, editing:null}" class="space-y-6">
    <div class="flex justify-between items-center">
        <p class="text-sm" style="color:var(--text-secondary)"><?= count($coupons) ?> cupons</p>
        <button @click="editing=null; open=true" class="admin-btn admin-btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Novo Cupom</button>
    </div>

    <div class="admin-card overflow-hidden">
        <?php if (!$coupons): ?>
            <div class="p-12 text-center">
                <i data-lucide="ticket" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
                <h3 class="font-semibold" style="color:var(--sepia)">Nenhum cupom criado</h3>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead><tr><th>Código</th><th>Desconto</th><th>Usos</th><th>Validade</th><th>Ativo</th><th class="text-right">Ações</th></tr></thead>
                <tbody>
                    <?php foreach ($coupons as $c): ?>
                    <tr>
                        <td><span class="font-mono font-bold px-2 py-1 rounded" style="background:rgba(201,107,74,0.1);color:var(--terracota-dark)"><?= e($c['code']) ?></span><?php if ($c['description']): ?><div class="text-xs mt-1" style="color:var(--text-muted)"><?= e($c['description']) ?></div><?php endif; ?></td>
                        <td class="font-semibold"><?= $c['type']==='percent' ? $c['value'].'%' : formatBRL($c['value']) ?></td>
                        <td><span class="text-sm"><?= (int)$c['used_count'] ?><?= $c['max_uses']?' / '.$c['max_uses']:'' ?></span></td>
                        <td><span class="text-xs"><?= $c['valid_until'] ? 'Até '.date('d/m/Y', strtotime($c['valid_until'])) : 'Sem prazo' ?></span></td>
                        <td><span class="badge badge-<?= $c['active']?'success':'muted' ?>"><?= $c['active']?'Ativo':'Inativo' ?></span></td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <button @click="editing=<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>; open=true" class="p-2 rounded-lg hover:bg-gray-100" style="color:var(--horizonte)"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                                <form method="post" class="inline" onsubmit="return confirm('Excluir cupom?')">
                                    <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button class="p-2 rounded-lg hover:bg-red-50" style="color:#EF4444"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div x-show="open" x-cloak @keydown.escape.window="open=false" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)" x-transition.opacity>
        <div @click.outside="open=false" class="w-full max-w-lg rounded-2xl p-6" style="background:var(--bg-card);max-height:90vh;overflow-y:auto" x-transition>
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-display text-xl font-bold" style="color:var(--sepia)" x-text="editing ? 'Editar cupom' : 'Novo cupom'"></h3>
                <button @click="open=false" style="color:var(--text-muted)"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="post" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" :value="editing?editing.id:''">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Código *</label><input name="code" required :value="editing?editing.code:''" class="admin-input uppercase" placeholder="VERAO10"></div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Tipo</label>
                        <select name="type" class="admin-input" :value="editing?editing.type:'percent'">
                            <option value="percent">% Percentual</option>
                            <option value="fixed">R$ Fixo</option>
                        </select>
                    </div>
                </div>
                <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Descrição</label><input name="description" :value="editing?editing.description:''" class="admin-input" placeholder="Ex: Desconto de verão"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Valor *</label><input name="value" required :value="editing?editing.value:''" class="admin-input" placeholder="10"></div>
                    <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Valor mínimo (R$)</label><input name="min_amount" :value="editing && editing.min_purchase?editing.min_purchase:''" class="admin-input brl-mask"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Máximo de usos</label><input type="number" name="max_uses" :value="editing && editing.max_uses?editing.max_uses:''" class="admin-input"></div>
                    <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Validade até</label><input type="date" name="valid_until" :value="editing && editing.valid_until?editing.valid_until.split(' ')[0]:''" class="admin-input"></div>
                </div>
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="active" value="1" :checked="editing?editing.active==1:true" class="w-4 h-4" style="accent-color:var(--terracota)"><span class="text-sm font-semibold" style="color:var(--sepia)">Ativo</span></label>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="open=false" class="admin-btn admin-btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="admin-btn admin-btn-primary flex-1">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/pagination.php'; ?>
<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
