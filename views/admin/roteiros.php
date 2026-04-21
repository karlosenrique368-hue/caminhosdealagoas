<?php
$pageTitle = 'Passeios';

// Delete
if (isPost() && ($_POST['action'] ?? '') === 'delete' && csrfVerify()) {
    requireAdmin();
    $id = (int) ($_POST['id'] ?? 0);
    dbExec("DELETE FROM roteiros WHERE id = ?", [$id]);
    flash('success', 'Passeio excluído com sucesso.');
    redirect('/admin/roteiros');
}

// Toggle featured/status
if (isPost() && ($_POST['action'] ?? '') === 'toggle' && csrfVerify()) {
    requireAdmin();
    $id = (int) ($_POST['id'] ?? 0);
    $field = $_POST['field'] ?? '';
    if ($field === 'featured') {
        dbExec("UPDATE roteiros SET featured = 1 - featured WHERE id = ?", [$id]);
    } elseif ($field === 'status') {
        dbExec("UPDATE roteiros SET status = IF(status='published','draft','published') WHERE id = ?", [$id]);
    }
    redirect('/admin/roteiros');
}

require VIEWS_DIR . '/partials/admin_head.php';

$q = trim($_GET['q'] ?? '');
$where = '1=1'; $params = [];
if ($q) { $where .= " AND r.title LIKE ?"; $params[] = "%$q%"; }

$roteiros = dbAll("SELECT r.*, c.name AS category_name FROM roteiros r LEFT JOIN categories c ON r.category_id=c.id WHERE $where ORDER BY r.created_at DESC", $params);
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
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar passeio..." class="admin-input pl-11">
    </form>
    <a href="<?= url('/admin/roteiros/novo') ?>" class="admin-btn admin-btn-primary">
        <i data-lucide="plus" class="w-4 h-4"></i>Novo Passeio
    </a>
</div>

<div class="admin-card overflow-hidden">
    <?php if (!$roteiros): ?>
        <div class="p-12 text-center">
            <i data-lucide="compass" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
            <h3 class="font-semibold mb-1" style="color:var(--sepia)">Nenhum passeio cadastrado</h3>
            <p class="text-sm mb-5" style="color:var(--text-muted)">Comece adicionando seu primeiro roteiro.</p>
            <a href="<?= url('/admin/roteiros/novo') ?>" class="admin-btn admin-btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Adicionar Passeio</a>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Passeio</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Views</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roteiros as $r): ?>
                <tr>
                    <td>
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
                                <div class="text-xs" style="color:var(--text-muted)"><?= e($r['location'] ?? '—') ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="text-sm"><?= e($r['category_name'] ?? '—') ?></span></td>
                    <td class="font-semibold"><?= formatBRL($r['price']) ?></td>
                    <td><span class="text-sm"><?= (int)$r['views'] ?></span></td>
                    <td>
                        <?php if ($r['status'] === 'published'): ?>
                            <span class="badge badge-success">Publicado</span>
                        <?php elseif ($r['status'] === 'draft'): ?>
                            <span class="badge badge-warning">Rascunho</span>
                        <?php else: ?>
                            <span class="badge badge-muted">Arquivado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex justify-end gap-1">
                            <a href="<?= url('/roteiros/' . $r['slug']) ?>" target="_blank" class="p-2 rounded-lg hover:bg-gray-100" title="Ver" style="color:var(--text-secondary)">
                                <i data-lucide="external-link" class="w-4 h-4"></i>
                            </a>
                            <a href="<?= url('/admin/roteiros/' . $r['id']) ?>" class="p-2 rounded-lg hover:bg-gray-100" title="Editar" style="color:var(--horizonte)">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </a>
                            <form method="post" class="inline" onsubmit="return confirm('Excluir este passeio?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="p-2 rounded-lg hover:bg-red-50" title="Excluir" style="color:#EF4444"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
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

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
