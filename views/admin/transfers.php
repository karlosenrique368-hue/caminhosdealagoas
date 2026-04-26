<?php
$pageTitle = 'Transfers';

if (isPost() && ($_POST['action'] ?? '') === 'delete' && csrfVerify()) {
    requireAdmin();
    $id = (int) ($_POST['id'] ?? 0);
    dbExec("DELETE FROM transfers WHERE id = ?", [$id]);
    flash('success', 'Transfer excluído com sucesso.');
    redirect('/admin/transfers');
}

if (isPost() && ($_POST['action'] ?? '') === 'toggle' && csrfVerify()) {
    requireAdmin();
    $id = (int) ($_POST['id'] ?? 0);
    $field = $_POST['field'] ?? '';
    if ($field === 'featured') {
        dbExec("UPDATE transfers SET featured = 1 - featured WHERE id = ?", [$id]);
    } elseif ($field === 'status') {
        dbExec("UPDATE transfers SET status = IF(status='published','draft','published') WHERE id = ?", [$id]);
    }
    redirect('/admin/transfers');
}

require VIEWS_DIR . '/partials/admin_head.php';

$q = trim($_GET['q'] ?? '');
$where = '1=1'; $params = [];
if ($q) { $where .= " AND title LIKE ?"; $params[] = "%$q%"; }

$pag = paginate(
    "SELECT COUNT(*) AS c FROM transfers WHERE $where",
    "SELECT * FROM transfers WHERE $where ORDER BY created_at DESC",
    $params
);
$rows = $pag['rows'];
$successMsg = flash('success');
?>

<?php if ($successMsg): ?>
<div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)">
    <i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i>
    <span class="text-sm" style="color:var(--maresia-dark)"><?= e($successMsg) ?></span>
</div>
<?php endif; ?>

<div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between mb-6">
    <form method="GET" class="flex-1 max-w-md relative">
        <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2" style="color:var(--text-muted)"></i>
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar transfer..." class="admin-input pl-11">
    </form>
    <a href="<?= url('/admin/transfers/novo') ?>" class="admin-btn admin-btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>Novo Transfer
    </a>
</div>

<div class="admin-card overflow-hidden">
    <?php if (!$rows): ?>
        <div class="p-12 text-center">
            <i data-lucide="car" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
            <h3 class="font-semibold mb-1" style="color:var(--sepia)">Nenhum transfer cadastrado</h3>
            <p class="text-sm mb-5" style="color:var(--text-muted)">Comece adicionando seu primeiro traslado.</p>
            <a href="<?= url('/admin/transfers/novo') ?>" class="admin-btn admin-btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Adicionar Transfer</a>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Transfer</th>
                    <th>Trecho</th>
                    <th>Veículo</th>
                    <th>Preço</th>
                    <th>Views</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td data-label="Transfer">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg flex-shrink-0 overflow-hidden" style="background:var(--bg-surface)">
                                <?php if ($r['cover_image']): ?>
                                    <img src="<?= storageUrl($r['cover_image']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="img-placeholder w-full h-full text-sm"><span><?= e(mb_substr($r['title'],0,1)) ?></span></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="font-semibold flex items-center gap-2">
                                    <?= e($r['title']) ?>
                                    <?php if ($r['featured']): ?><i data-lucide="star" class="w-3.5 h-3.5" style="color:#F59E0B;fill:#F59E0B"></i><?php endif; ?>
                                </div>
                                <div class="text-xs" style="color:var(--text-muted)">max <?= (int)$r['capacity'] ?> · <?= (int)$r['duration_minutes'] ?> min</div>
                            </div>
                        </div>
                    </td>
                    <td data-label="Trecho"><div class="text-xs"><?= e($r['location_from']) ?> →<br><?= e($r['location_to']) ?></div></td>
                    <td data-label="Veículo"><span class="text-sm"><?= e($r['vehicle_type'] ?? '—') ?></span></td>
                    <td data-label="Preço" class="font-semibold"><?= formatBRL($r['price']) ?></td>
                    <td data-label="Views"><span class="text-sm"><?= (int)$r['views'] ?></span></td>
                    <td data-label="Status">
                        <?php if ($r['status'] === 'published'): ?>
                            <span class="badge badge-success">Publicado</span>
                        <?php elseif ($r['status'] === 'draft'): ?>
                            <span class="badge badge-warning">Rascunho</span>
                        <?php else: ?>
                            <span class="badge badge-muted">Arquivado</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions-cell">
                        <div class="flex justify-end gap-1">
                            <a href="<?= url('/transfers/' . $r['slug']) ?>" target="_blank" class="action-chip chip-view"><i data-lucide="external-link" class="w-3.5 h-3.5"></i>Ver</a>
                            <a href="<?= url('/admin/transfers/' . $r['id']) ?>" class="action-chip chip-edit"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i>Editar</a>
                            <form method="post" class="inline" onsubmit="return confirm('Excluir este transfer?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="action-chip chip-danger"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
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
