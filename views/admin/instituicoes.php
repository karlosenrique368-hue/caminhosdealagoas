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

<div class="grid lg:grid-cols-[380px_1fr] gap-6">
    <div class="admin-card p-5">
        <h2 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)"><?= $edit ? 'Editar' : 'Nova' ?> instituição</h2>
        <form method="POST" class="space-y-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save">
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Nome</span><input type="text" name="name" value="<?= e($edit['name'] ?? '') ?>" required class="input-field w-full"></label>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Tipo</span>
                <select name="type" class="input-field w-full">
                    <?php foreach ($types as $k=>$v): ?><option value="<?= $k ?>" <?= ($edit['type'] ?? '')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">CNPJ</span><input type="text" name="cnpj" value="<?= e($edit['cnpj'] ?? '') ?>" class="input-field w-full"></label>
            <div class="grid grid-cols-2 gap-2">
                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Contato</span><input type="text" name="contact_name" value="<?= e($edit['contact_name'] ?? '') ?>" class="input-field w-full"></label>
                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Telefone</span><input type="tel" name="contact_phone" value="<?= e($edit['contact_phone'] ?? '') ?>" class="input-field w-full"></label>
            </div>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">E-mail</span><input type="email" name="contact_email" value="<?= e($edit['contact_email'] ?? '') ?>" class="input-field w-full"></label>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Website</span><input type="url" name="website" value="<?= e($edit['website'] ?? '') ?>" class="input-field w-full" placeholder="https://..."></label>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Notas</span><textarea name="notes" rows="3" class="input-field w-full"><?= e($edit['notes'] ?? '') ?></textarea></label>
            <label class="flex items-center gap-2"><input type="checkbox" name="active" <?= ($edit['active'] ?? 1)?'checked':'' ?>> <span class="text-sm">Ativa</span></label>
            <div class="flex gap-2">
                <button class="btn-primary flex-1 justify-center"><?= $edit ? 'Atualizar' : 'Criar' ?></button>
                <?php if ($edit): ?><a href="<?= url('/admin/instituicoes') ?>" class="btn-secondary">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="admin-card overflow-hidden">
    <table class="w-full">
        <thead style="background:var(--areia-light)"><tr class="text-left text-xs font-bold uppercase tracking-wider" style="color:var(--text-secondary)"><th class="p-4">Nome</th><th class="p-4">Tipo</th><th class="p-4">Contato</th><th class="p-4">Status</th><th class="p-4"></th></tr></thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="5" class="p-10 text-center" style="color:var(--text-muted)">Nenhuma instituição cadastrada.</td></tr>
            <?php else: foreach ($items as $it): ?>
                <tr class="border-t" style="border-color:var(--border-default)">
                    <td class="p-4 font-semibold"><?= e($it['name']) ?></td>
                    <td class="p-4 text-sm"><?= $types[$it['type']] ?? $it['type'] ?></td>
                    <td class="p-4 text-sm"><?= e($it['contact_email']) ?></td>
                    <td class="p-4"><span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full text-white" style="background:<?= $it['active']?'#059669':'#6B7280' ?>"><?= $it['active']?'Ativa':'Inativa' ?></span></td>
                    <td class="p-4">
                        <div class="flex gap-2">
                            <a href="?edit=<?= $it['id'] ?>" class="action-chip chip-edit"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i>Editar</a>
                            <form method="POST" class="inline" onsubmit="return confirm('Excluir?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $it['id'] ?>"><button class="action-chip chip-danger" title="Excluir"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/pagination.php'; ?>
<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
