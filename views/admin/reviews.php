<?php
$pageTitle = 'Avaliações';

if (isPost() && csrfVerify()) {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $r = dbOne('SELECT * FROM reviews WHERE id=?', [$id]);
    if ($r) {
        if ($action === 'approve') dbExec("UPDATE reviews SET status='approved' WHERE id=?", [$id]);
        if ($action === 'reject') dbExec("UPDATE reviews SET status='rejected' WHERE id=?", [$id]);
        if ($action === 'delete') dbExec('DELETE FROM reviews WHERE id=?', [$id]);
        // Recompute avg
        $table = $r['entity_type'] === 'roteiro' ? 'roteiros' : 'pacotes';
        $stats = dbOne("SELECT AVG(rating) a, COUNT(*) c FROM reviews WHERE entity_type=? AND entity_id=? AND status='approved'", [$r['entity_type'], $r['entity_id']]);
        dbExec("UPDATE $table SET rating_avg=?, rating_count=? WHERE id=?", [(float)($stats['a'] ?? 0), (int)($stats['c'] ?? 0), $r['entity_id']]);
    }
    redirect('/admin/reviews');
}

require VIEWS_DIR . '/partials/admin_head.php';
$pag = paginate(
    "SELECT COUNT(*) AS c FROM reviews",
    'SELECT rv.*, c.name AS customer_name, COALESCE(r.title,p.title) AS entity_title FROM reviews rv LEFT JOIN customers c ON rv.customer_id=c.id LEFT JOIN roteiros r ON rv.entity_type="roteiro" AND rv.entity_id=r.id LEFT JOIN pacotes p ON rv.entity_type="pacote" AND rv.entity_id=p.id ORDER BY rv.created_at DESC'
);
$reviews = $pag['rows'];
$colors = ['pending'=>'#D97706','approved'=>'#059669','rejected'=>'#DC2626'];
?>

<div class="flex justify-between items-center mb-6">
    <p class="text-sm" style="color:var(--text-secondary)"><?= $pag['total'] ?? count($reviews) ?> avaliações</p>
</div>

<div class="admin-card overflow-hidden">
    <?php if (empty($reviews)): ?>
        <div class="p-12 text-center">
            <i data-lucide="star" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
            <h3 class="font-semibold mb-1" style="color:var(--sepia)">Nenhuma avaliação</h3>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead><tr><th>Experiência</th><th>Cliente</th><th>Nota</th><th>Avaliação</th><th>Data</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($reviews as $rv):
                $statusLabel = ['pending'=>'Pendente','approved'=>'Aprovado','rejected'=>'Rejeitado'][$rv['status']] ?? $rv['status'];
                $statusBadge = ['pending'=>'warning','approved'=>'success','rejected'=>'muted'][$rv['status']] ?? 'muted';
            ?>
            <tr>
                <td>
                    <div class="font-semibold"><?= e($rv['entity_title']) ?></div>
                    <div class="text-xs uppercase tracking-wider" style="color:var(--text-muted)"><?= e($rv['entity_type']) ?></div>
                </td>
                <td>
                    <div class="text-sm"><?= e($rv['customer_name']) ?></div>
                    <?php if ($rv['verified']): ?><span class="badge badge-info text-[10px]">Verificado</span><?php endif; ?>
                </td>
                <td>
                    <div class="flex gap-0.5">
                        <?php for ($i=1;$i<=5;$i++): ?>
                            <i data-lucide="star" class="w-3.5 h-3.5" style="<?= $i<=$rv['rating']?'fill:#F59E0B;color:#F59E0B':'color:#D1D5DB' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </td>
                <td style="max-width:320px">
                    <?php if ($rv['title']): ?><div class="font-semibold text-sm"><?= e($rv['title']) ?></div><?php endif; ?>
                    <p class="text-sm line-clamp-2" style="color:var(--text-secondary)"><?= e($rv['content']) ?></p>
                </td>
                <td><span class="text-xs" style="color:var(--text-muted)"><?= date('d/m/Y', strtotime($rv['created_at'])) ?></span></td>
                <td><span class="badge badge-<?= $statusBadge ?>"><?= $statusLabel ?></span></td>
                <td class="actions-cell">
                    <div class="flex justify-end gap-1">
                        <?php if ($rv['status']!=='approved'): ?>
                        <form method="POST" class="inline"><?= csrfField() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $rv['id'] ?>"><button class="action-chip chip-success" title="Aprovar"><i data-lucide="check" class="w-3.5 h-3.5"></i>Aprovar</button></form>
                        <?php endif; ?>
                        <?php if ($rv['status']!=='rejected'): ?>
                        <form method="POST" class="inline"><?= csrfField() ?><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?= $rv['id'] ?>"><button class="action-chip chip-warning" title="Rejeitar"><i data-lucide="x-circle" class="w-3.5 h-3.5"></i>Rejeitar</button></form>
                        <?php endif; ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Excluir?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $rv['id'] ?>"><button class="action-chip chip-danger" title="Excluir"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include VIEWS_DIR . '/partials/pagination.php'; ?>
<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
