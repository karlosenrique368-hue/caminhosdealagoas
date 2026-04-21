<?php
$pageTitle = 'Instituições';

if (isPost() && csrfVerify()) {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $data = [
            'name'=>trim($_POST['name'] ?? ''),
            'type'=>$_POST['type'] ?? 'empresa',
            'cnpj'=>trim($_POST['cnpj'] ?? ''),
            'contact_name'=>trim($_POST['contact_name'] ?? ''),
            'contact_email'=>trim($_POST['contact_email'] ?? ''),
            'contact_phone'=>trim($_POST['contact_phone'] ?? ''),
            'website'=>trim($_POST['website'] ?? ''),
            'notes'=>trim($_POST['notes'] ?? ''),
            'active'=>isset($_POST['active'])?1:0,
        ];
        if ($id) {
            $sets=[]; foreach ($data as $k=>$_) $sets[]="`$k`=?";
            $vals=array_values($data); $vals[]=$id;
            dbExec("UPDATE institutions SET ".implode(',',$sets)." WHERE id=?", $vals);
        } else {
            $fields=array_keys($data);
            dbExec("INSERT INTO institutions (".implode(',',$fields).") VALUES (".implode(',',array_fill(0,count($fields),'?')).")", array_values($data));
        }
        flash('success','Salvo.');
    }
    if ($action === 'delete') dbExec('DELETE FROM institutions WHERE id=?', [$id]);
    redirect('/admin/instituicoes');
}

require VIEWS_DIR . '/partials/admin_head.php';
$pag = paginate("SELECT COUNT(*) AS c FROM institutions", "SELECT * FROM institutions ORDER BY name");
$items = $pag['rows'];
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? dbOne('SELECT * FROM institutions WHERE id=?', [$editId]) : null;
$types = ['escola'=>'Escola','empresa'=>'Empresa','ong'=>'ONG','governo'=>'Governo','outro'=>'Outro'];
?>

<div x-data="{open:false, editing:null}" x-init="<?= $edit ? 'editing=' . htmlspecialchars(json_encode($edit), ENT_QUOTES) . '; open=true' : '' ?>">
    <div class="flex justify-between items-center mb-6">
        <p class="text-sm" style="color:var(--text-secondary)"><?= $pag['total'] ?? count($items) ?> instituições</p>
        <button type="button" @click="editing=null; open=true" class="admin-btn admin-btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Nova instituição</button>
    </div>

    <div class="admin-card overflow-hidden">
        <?php if (empty($items)): ?>
            <div class="p-12 text-center">
                <i data-lucide="building-2" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
                <h3 class="font-semibold mb-1" style="color:var(--sepia)">Nenhuma instituição cadastrada</h3>
                <button type="button" @click="editing=null; open=true" class="admin-btn admin-btn-primary mt-4"><i data-lucide="plus" class="w-4 h-4"></i>Adicionar</button>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead><tr><th>Nome</th><th>Tipo</th><th>Contato</th><th>Telefone</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                <tr>
                    <td>
                        <div class="font-semibold"><?= e($it['name']) ?></div>
                        <?php if ($it['website']): ?><a href="<?= e($it['website']) ?>" target="_blank" class="text-xs" style="color:var(--horizonte)"><?= e($it['website']) ?></a><?php endif; ?>
                    </td>
                    <td><span class="badge badge-info"><?= $types[$it['type']] ?? $it['type'] ?></span></td>
                    <td>
                        <div class="text-sm"><?= e($it['contact_name'] ?: '—') ?></div>
                        <div class="text-xs" style="color:var(--text-muted)"><?= e($it['contact_email']) ?></div>
                    </td>
                    <td><span class="text-sm"><?= e($it['contact_phone'] ?: '—') ?></span></td>
                    <td><span class="badge badge-<?= $it['active']?'success':'muted' ?>"><?= $it['active']?'Ativa':'Inativa' ?></span></td>
                    <td class="actions-cell">
                        <div class="flex justify-end gap-1">
                            <button type="button" @click="editing=<?= htmlspecialchars(json_encode($it), ENT_QUOTES) ?>; open=true" class="action-chip chip-edit" title="Editar"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i>Editar</button>
                            <form method="POST" class="inline" onsubmit="return confirm('Excluir?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $it['id'] ?>"><button class="action-chip chip-danger" title="Excluir"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></form>
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
    <div x-show="open" x-cloak @keydown.escape.window="open=false" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <div @click.outside="open=false" class="w-full max-w-xl rounded-2xl p-6" style="background:var(--bg-card);max-height:90vh;overflow-y:auto">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-display text-xl font-bold" style="color:var(--sepia)" x-text="editing?'Editar instituição':'Nova instituição'"></h3>
                <button type="button" @click="open=false" style="color:var(--text-muted)"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="POST" class="space-y-3">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" :value="editing?editing.id:''">
                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Nome *</span><input type="text" name="name" :value="editing?editing.name:''" required class="admin-input w-full"></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Tipo</span>
                        <select name="type" class="admin-input w-full" :value="editing?editing.type:'empresa'">
                            <?php foreach ($types as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">CNPJ</span><input type="text" name="cnpj" :value="editing?editing.cnpj:''" class="admin-input w-full"></label>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Contato</span><input type="text" name="contact_name" :value="editing?editing.contact_name:''" class="admin-input w-full"></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Telefone</span><input type="tel" name="contact_phone" :value="editing?editing.contact_phone:''" class="admin-input w-full"></label>
                </div>
                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">E-mail</span><input type="email" name="contact_email" :value="editing?editing.contact_email:''" class="admin-input w-full"></label>
                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Website</span><input type="url" name="website" :value="editing?editing.website:''" class="admin-input w-full" placeholder="https://..."></label>
                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Notas</span><textarea name="notes" rows="3" class="admin-input w-full" x-text="editing?editing.notes:''"></textarea></label>
                <label class="flex items-center gap-2"><input type="checkbox" name="active" value="1" :checked="editing?editing.active==1:true"> <span class="text-sm">Ativa</span></label>
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
