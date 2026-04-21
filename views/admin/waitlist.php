<?php
$pageTitle = 'Lista de espera';

if (isPost() && csrfVerify()) {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'status') {
        $st = $_POST['status'] ?? 'waiting';
        dbExec('UPDATE waitlist SET status=?, notified_at=IF(?="notified",NOW(),notified_at) WHERE id=?', [$st,$st,$id]);
    }
    if ($action === 'delete') dbExec('DELETE FROM waitlist WHERE id=?', [$id]);
    redirect('/admin/waitlist');
}

require VIEWS_DIR . '/partials/admin_head.php';
$list = dbAll('SELECT w.*, COALESCE(r.title,p.title) AS entity_title FROM waitlist w LEFT JOIN roteiros r ON w.entity_type="roteiro" AND w.entity_id=r.id LEFT JOIN pacotes p ON w.entity_type="pacote" AND w.entity_id=p.id ORDER BY w.created_at DESC');
$statuses = ['waiting'=>'Aguardando','notified'=>'Notificado','converted'=>'Convertido','cancelled'=>'Cancelado'];
$colors = ['waiting'=>'#D97706','notified'=>'#2563EB','converted'=>'#059669','cancelled'=>'#6B7280'];
?>

<div class="admin-card overflow-hidden">
<table class="w-full">
    <thead style="background:var(--areia-light)">
        <tr class="text-left text-xs font-bold uppercase tracking-wider" style="color:var(--text-secondary)">
            <th class="p-4">Pessoa</th><th class="p-4">Interesse</th><th class="p-4">Data desejada</th><th class="p-4">Status</th><th class="p-4"></th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($list)): ?>
        <tr><td colspan="5" class="p-10 text-center" style="color:var(--text-muted)">Ninguém na lista de espera.</td></tr>
    <?php else: foreach ($list as $w): ?>
        <tr class="border-t" style="border-color:var(--border-default)">
            <td class="p-4"><div class="font-semibold"><?= e($w['name']) ?></div><div class="text-xs" style="color:var(--text-muted)"><?= e($w['email']) ?> · <?= e($w['phone']) ?></div></td>
            <td class="p-4"><?= e($w['entity_title']) ?></td>
            <td class="p-4 text-sm"><?= $w['desired_date'] ? date('d/m/Y', strtotime($w['desired_date'])) : '—' ?></td>
            <td class="p-4"><span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full text-white" style="background:<?= $colors[$w['status']] ?>"><?= $statuses[$w['status']] ?></span></td>
            <td class="p-4">
                <form method="POST" class="flex gap-1"><?= csrfField() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $w['id'] ?>">
                    <select name="status" class="input-field text-xs" onchange="this.form.submit()">
                        <?php foreach ($statuses as $k=>$v): ?><option value="<?= $k ?>" <?= $w['status']===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
                    </select>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
