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
$reviews = dbAll('SELECT rv.*, c.name AS customer_name, COALESCE(r.title,p.title) AS entity_title FROM reviews rv LEFT JOIN customers c ON rv.customer_id=c.id LEFT JOIN roteiros r ON rv.entity_type="roteiro" AND rv.entity_id=r.id LEFT JOIN pacotes p ON rv.entity_type="pacote" AND rv.entity_id=p.id ORDER BY rv.created_at DESC');
$colors = ['pending'=>'#D97706','approved'=>'#059669','rejected'=>'#DC2626'];
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php if (empty($reviews)): ?>
        <div class="admin-card p-12 text-center col-span-full" style="color:var(--text-muted)">Nenhuma avaliação.</div>
    <?php else: foreach ($reviews as $rv): ?>
        <div class="admin-card p-5">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full text-white" style="background:<?= $colors[$rv['status']] ?>"><?= e($rv['status']) ?></span>
                        <?php if ($rv['verified']): ?><span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full" style="background:var(--maresia);color:#fff">Verificado</span><?php endif; ?>
                    </div>
                    <h3 class="font-display font-bold truncate" style="color:var(--sepia)"><?= e($rv['entity_title']) ?></h3>
                    <p class="text-xs" style="color:var(--text-muted)"><?= e($rv['customer_name']) ?> · <?= date('d/m/Y', strtotime($rv['created_at'])) ?></p>
                </div>
                <div class="flex gap-0.5">
                    <?php for ($i=1;$i<=5;$i++): ?>
                        <i data-lucide="star" class="w-4 h-4" style="<?= $i<=$rv['rating']?'fill:#F59E0B;color:#F59E0B':'color:#D1D5DB' ?>"></i>
                    <?php endfor; ?>
                </div>
            </div>
            <?php if ($rv['title']): ?><p class="font-semibold mb-1" style="color:var(--text-primary)"><?= e($rv['title']) ?></p><?php endif; ?>
            <p class="text-sm mb-4" style="color:var(--text-secondary)"><?= e($rv['content']) ?></p>
            <div class="flex gap-2">
                <?php if ($rv['status']!=='approved'): ?>
                <form method="POST" class="inline"><?= csrfField() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $rv['id'] ?>"><button class="btn-primary text-xs">Aprovar</button></form>
                <?php endif; ?>
                <?php if ($rv['status']!=='rejected'): ?>
                <form method="POST" class="inline"><?= csrfField() ?><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?= $rv['id'] ?>"><button class="btn-secondary text-xs">Rejeitar</button></form>
                <?php endif; ?>
                <form method="POST" class="inline" onsubmit="return confirm('Excluir?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $rv['id'] ?>"><button class="btn-secondary text-xs" style="color:#DC2626">Excluir</button></form>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
