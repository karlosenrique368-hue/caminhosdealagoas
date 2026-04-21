<?php
$pageTitle = 'Reembolsos';

if (isPost() && csrfVerify()) {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? 'em_analise';
        $note = trim($_POST['admin_note'] ?? '');
        if (in_array($status, ['em_analise','aprovado','negado','pago'], true)) {
            dbExec('UPDATE refund_requests SET status=?, admin_note=?, resolved_at=IF(?="em_analise",NULL,NOW()) WHERE id=?', [$status,$note,$status,$id]);
        }
        flash('success', 'Status atualizado.');
    }
    redirect('/admin/reembolsos');
}

require VIEWS_DIR . '/partials/admin_head.php';
$pag = paginate(
    "SELECT COUNT(*) AS c FROM refund_requests",
    "SELECT rr.*, c.name AS customer_name, c.email AS customer_email, b.entity_title FROM refund_requests rr LEFT JOIN customers c ON rr.customer_id=c.id LEFT JOIN bookings b ON rr.booking_id=b.id ORDER BY rr.created_at DESC"
);
$refunds = $pag['rows'];
$statuses = ['em_analise'=>'Em análise','aprovado'=>'Aprovado','negado'=>'Negado','pago'=>'Pago'];
$colors = ['em_analise'=>'#D97706','aprovado'=>'#059669','negado'=>'#DC2626','pago'=>'#2563EB'];
?>

<div class="space-y-4">
    <?php if (empty($refunds)): ?>
        <div class="admin-card p-12 text-center" style="color:var(--text-muted)">Nenhuma solicitação de reembolso.</div>
    <?php else: foreach ($refunds as $rr): ?>
        <div class="admin-card p-5" x-data="{open:false}">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full text-white" style="background:<?= $colors[$rr['status']] ?>"><?= $statuses[$rr['status']] ?></span>
                        <span class="text-xs" style="color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($rr['created_at'])) ?></span>
                    </div>
                    <h3 class="font-display font-bold text-lg" style="color:var(--sepia)"><?= e($rr['entity_title']) ?></h3>
                    <p class="text-sm" style="color:var(--text-secondary)"><?= e($rr['customer_name']) ?> · <?= e($rr['customer_email']) ?></p>
                </div>
                <div class="text-right">
                    <p class="font-display font-bold text-xl" style="color:var(--sepia)">R$ <?= number_format((float)$rr['amount'],2,',','.') ?></p>
                </div>
                <button @click="open=!open" class="btn-secondary text-sm"><span x-text="open?'Fechar':'Gerenciar'"></span></button>
            </div>
            <div x-show="open" x-cloak class="mt-4 pt-4 border-t" style="border-color:var(--border-default)">
                <p class="text-sm mb-3"><strong>Motivo:</strong> <?= e($rr['reason']) ?></p>
                <form method="POST" class="grid md:grid-cols-[1fr_2fr_auto] gap-3 items-end">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" value="<?= $rr['id'] ?>">
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Status</span>
                        <select name="status" class="input-field w-full">
                            <?php foreach ($statuses as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= $rr['status']===$k?'selected':'' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Nota interna</span>
                        <input type="text" name="admin_note" value="<?= e($rr['admin_note']) ?>" class="input-field w-full">
                    </label>
                    <button class="btn-primary">Salvar</button>
                </form>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php include VIEWS_DIR . '/partials/pagination.php'; ?>
<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
