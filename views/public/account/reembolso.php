<?php
$accountTitle = 'Reembolsos';
$accountTab = 'reembolso';
include VIEWS_DIR . '/partials/account_layout.php';

$cid = currentCustomerId();
$msg = '';
if (isPost() && csrfVerify()) {
    $bid = (int)($_POST['booking_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $b = dbOne('SELECT * FROM bookings WHERE id=? AND customer_user_id=?', [$bid,$cid]);
    if ($b && $reason) {
        dbExec('INSERT INTO refund_requests (booking_id,customer_id,reason,amount) VALUES (?,?,?,?)',
            [$bid,$cid,$reason,(float)$b['total']]);
        $msg = 'Solicitação enviada! Nossa equipe analisará em breve.';
    } else {
        $msg = 'Reserva inválida ou motivo não informado.';
    }
}

$refunds = dbAll('SELECT rr.*, b.total AS total, b.entity_title FROM refund_requests rr LEFT JOIN bookings b ON rr.booking_id=b.id WHERE rr.customer_id=? ORDER BY rr.created_at DESC', [$cid]);
$eligibleBookings = dbAll("SELECT b.id, b.total, b.entity_title AS title FROM bookings b WHERE b.customer_user_id=? AND b.payment_status='paid' AND b.id NOT IN (SELECT booking_id FROM refund_requests WHERE customer_id=?) ORDER BY b.created_at DESC", [$cid,$cid]);
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="rounded-2xl border p-6" style="background:#fff;border-color:var(--border-default)">
        <h2 class="font-display text-xl font-bold mb-5" style="color:var(--sepia)">Solicitar reembolso</h2>
        <?php if ($msg): ?><div class="p-3 rounded-xl text-sm mb-4" style="background:var(--areia-light);color:var(--text-primary)"><?= e($msg) ?></div><?php endif; ?>

        <?php if (empty($eligibleBookings)): ?>
            <p class="text-sm" style="color:var(--text-muted)">Nenhuma reserva elegível para reembolso.</p>
        <?php else: ?>
            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">Reserva</span>
                    <select name="booking_id" required class="input-field w-full">
                        <option value="">Selecione...</option>
                        <?php foreach ($eligibleBookings as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= e($b['title']) ?> — <?= formatPrice((float)$b['total']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">Motivo</span>
                    <textarea name="reason" rows="4" required class="input-field w-full" placeholder="Descreva o motivo do reembolso..."></textarea>
                </label>
                <button class="btn-primary w-full justify-center">Enviar solicitação</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="rounded-2xl border p-6" style="background:#fff;border-color:var(--border-default)">
        <h2 class="font-display text-xl font-bold mb-5" style="color:var(--sepia)">Histórico</h2>
        <?php if (empty($refunds)): ?>
            <p class="text-sm" style="color:var(--text-muted)">Nenhuma solicitação anterior.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($refunds as $r): ?>
                    <div class="p-4 rounded-xl" style="background:var(--areia-light)">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-sm" style="color:var(--text-primary)"><?= e($r['entity_title']) ?></span>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full" style="background:var(--terracota);color:#fff"><?= e($r['status']) ?></span>
                        </div>
                        <p class="text-xs" style="color:var(--text-muted)"><?= e($r['reason']) ?></p>
                        <p class="text-xs mt-2" style="color:var(--text-secondary)">Valor: <strong><?= formatPrice((float)$r['amount']) ?></strong> · <?= date('d/m/Y', strtotime($r['created_at'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/account_layout_end.php'; ?>
