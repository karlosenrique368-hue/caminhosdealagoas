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
            $refund = dbOne('SELECT rr.*, b.payment_status, b.total AS booking_total FROM refund_requests rr JOIN bookings b ON b.id=rr.booking_id WHERE rr.id=? LIMIT 1', [$id]);
            if (!$refund) { flash('error', 'Reembolso não encontrado.'); redirect('/admin/reembolsos'); }
            // Maquina de estados: pago exige aprovado primeiro; negado/aprovado vem de em_analise
            $current = $refund['status'];
            $allowed = [
                'em_analise' => ['aprovado','negado'],
                'aprovado'   => ['pago','negado'],
                'negado'     => [],
                'pago'       => [],
            ];
            if ($status !== $current && !in_array($status, $allowed[$current] ?? [], true)) {
                flash('error', "Transição inválida: {$current} → {$status}.");
                redirect('/admin/reembolsos');
            }
            // Validacao financeira: nao pode reembolsar mais que o pago
            if ($status === 'pago' && (float)$refund['amount'] > (float)$refund['booking_total']) {
                flash('error', 'Valor do reembolso excede o total pago.');
                redirect('/admin/reembolsos');
            }
            dbExec('UPDATE refund_requests SET status=?, admin_note=?, resolved_at=IF(?="em_analise",NULL,NOW()) WHERE id=?', [$status,$note,$status,$id]);
            if ($status === 'pago' && $refund['payment_status'] !== 'refunded') {
                dbExec('UPDATE bookings SET payment_status="refunded" WHERE id=?', [$refund['booking_id']]);
                handleBookingPaymentStatusChanged((int)$refund['booking_id'], $refund['payment_status'], 'refunded', 'admin_refund_paid');
            }
            logActivity(null, 'refund_status', 'refund', $id, "Reembolso #{$id} {$current} -> {$status}");
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

<div class="flex justify-between items-center mb-6">
    <p class="text-sm" style="color:var(--text-secondary)"><?= $pag['total'] ?? count($refunds) ?> solicitações</p>
</div>

<div class="admin-card overflow-hidden">
    <?php if (empty($refunds)): ?>
        <div class="p-12 text-center">
            <i data-lucide="refresh-ccw" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
            <h3 class="font-semibold mb-1" style="color:var(--sepia)">Nenhuma solicitação de reembolso</h3>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto" x-data="{openId:null}">
        <table class="admin-table">
            <thead><tr><th>Experiência</th><th>Cliente</th><th>Valor</th><th>Data</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($refunds as $rr):
                $badgeMap = ['em_analise'=>'warning','aprovado'=>'success','negado'=>'muted','pago'=>'info'];
            ?>
            <tr>
                <td>
                    <div class="font-semibold"><?= e($rr['entity_title']) ?></div>
                    <div class="text-xs" style="color:var(--text-muted)">Booking #<?= (int)$rr['booking_id'] ?></div>
                </td>
                <td>
                    <div class="text-sm"><?= e($rr['customer_name']) ?></div>
                    <div class="text-xs" style="color:var(--text-muted)"><?= e($rr['customer_email']) ?></div>
                </td>
                <td class="font-semibold">R$ <?= number_format((float)$rr['amount'],2,',','.') ?></td>
                <td><span class="text-xs" style="color:var(--text-muted)"><?= date('d/m/Y', strtotime($rr['created_at'])) ?></span></td>
                <td><span class="badge badge-<?= $badgeMap[$rr['status']] ?? 'muted' ?>"><?= $statuses[$rr['status']] ?></span></td>
                <td class="actions-cell">
                    <div class="flex justify-end gap-1">
                        <button type="button" @click="openId = openId===<?= $rr['id'] ?> ? null : <?= $rr['id'] ?>" class="action-chip chip-edit" title="Gerenciar"><i data-lucide="settings-2" class="w-3.5 h-3.5"></i>Gerenciar</button>
                    </div>
                </td>
            </tr>
            <tr x-show="openId===<?= $rr['id'] ?>" x-cloak>
                <td colspan="6" style="background:var(--bg-surface)">
                    <div class="p-4">
                        <div class="mb-3">
                            <span class="text-xs font-bold uppercase tracking-wider" style="color:var(--text-muted)">Motivo informado:</span>
                            <p class="text-sm mt-1" style="color:var(--text-secondary)"><?= e($rr['reason']) ?></p>
                        </div>
                        <form method="POST" class="grid md:grid-cols-[180px_1fr_auto] gap-3 items-end">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $rr['id'] ?>">
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Status</span>
                                <select name="status" class="admin-input w-full">
                                    <?php foreach ($statuses as $k=>$v): ?>
                                        <option value="<?= $k ?>" <?= $rr['status']===$k?'selected':'' ?>><?= $v ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Nota interna</span>
                                <input type="text" name="admin_note" value="<?= e($rr['admin_note']) ?>" class="admin-input w-full" placeholder="Observações do admin...">
                            </label>
                            <button class="admin-btn admin-btn-primary"><i data-lucide="save" class="w-4 h-4"></i>Salvar</button>
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

<?php include VIEWS_DIR . '/partials/pagination.php'; ?>
<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
