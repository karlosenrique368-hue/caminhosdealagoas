<?php
$pageTitle = 'Pacotes';

if (isPost() && ($_POST['action'] ?? '') === 'delete' && csrfVerify()) {
    requireAdmin();
    dbExec("DELETE FROM pacotes WHERE id = ?", [(int)$_POST['id']]);
    flash('success', 'Pacote excluído.');
    redirect('/admin/pacotes');
}

require VIEWS_DIR . '/partials/admin_head.php';
$pag = paginate(
    "SELECT COUNT(*) AS c FROM pacotes",
    "SELECT p.*, c.name AS category_name FROM pacotes p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.created_at DESC"
);
$pacotes = $pag['rows'];
$msg = flash('success');
?>

<?php if ($msg): ?>
<div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)">
    <i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i>
    <span class="text-sm" style="color:var(--maresia-dark)"><?= e($msg) ?></span>
</div>
<?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <p class="text-sm" style="color:var(--text-secondary)"><?= $pag['total'] ?> pacotes cadastrados</p>
    <a href="<?= url('/admin/pacotes/novo') ?>" class="admin-btn admin-btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Novo Pacote</a>
</div>

<div class="admin-card overflow-hidden">
    <?php if (!$pacotes): ?>
        <div class="p-12 text-center">
            <i data-lucide="package" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
            <h3 class="font-semibold mb-1" style="color:var(--sepia)">Nenhum pacote cadastrado</h3>
            <a href="<?= url('/admin/pacotes/novo') ?>" class="admin-btn admin-btn-primary mt-4"><i data-lucide="plus" class="w-4 h-4"></i>Adicionar</a>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead><tr><th>Pacote</th><th>Destino</th><th>Duração</th><th>Preço</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
            <tbody>
                <?php foreach ($pacotes as $p): ?>
                <tr>
                    <td data-label="Pacote">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg flex-shrink-0 overflow-hidden" style="background:var(--bg-surface)">
                                <?php if ($p['cover_image']): ?><img src="<?= storageUrl($p['cover_image']) ?>" class="w-full h-full object-cover"><?php else: ?><div class="img-placeholder w-full h-full text-sm"><span><?= e(mb_substr($p['title'],0,1)) ?></span></div><?php endif; ?>
                            </div>
                            <div>
                                <div class="font-semibold flex items-center gap-2"><?= e($p['title']) ?><?php if ($p['featured']): ?><i data-lucide="star" class="w-3.5 h-3.5" style="color:#F59E0B;fill:#F59E0B"></i><?php endif; ?></div>
                                <div class="text-xs" style="color:var(--text-muted)"><?= e($p['category_name'] ?? '—') ?></div>
                            </div>
                        </div>
                    </td>
                    <td data-label="Destino"><span class="text-sm"><?= e($p['destination'] ?? '—') ?></span></td>
                    <td data-label="Duração"><span class="text-sm"><?= $p['duration_days'] ?>D / <?= $p['duration_nights'] ?>N</span></td>
                    <td data-label="Preço" class="font-semibold"><?= formatBRL($p['price']) ?></td>
                    <td data-label="Status"><span class="badge badge-<?= $p['status']==='published'?'success':($p['status']==='draft'?'warning':'muted') ?>"><?= ['published'=>'Publicado','draft'=>'Rascunho','archived'=>'Arquivado'][$p['status']] ?></span></td>
                    <td class="actions-cell">
                        <div class="flex justify-end gap-1">
                            <a href="<?= url('/pacotes/'.$p['slug']) ?>" target="_blank" class="action-chip chip-view" title="Ver no site"><i data-lucide="external-link" class="w-3.5 h-3.5"></i>Ver</a>
                            <a href="<?= url('/admin/pacotes/'.$p['id']) ?>" class="action-chip chip-edit" title="Editar"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i>Editar</a>
                            <form method="post" class="inline" onsubmit="return confirm('Excluir este pacote?')">
                                <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button class="action-chip chip-danger" title="Excluir"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include VIEWS_DIR . '/partials/pagination.php'; ?>
    <?php endif; ?>
</div>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
