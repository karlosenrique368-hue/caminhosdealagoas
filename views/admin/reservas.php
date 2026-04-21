<?php
$pageTitle = 'Reservas';

if (isPost() && csrfVerify()) {
    requireAdmin();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'update_payment' && $id) {
        $ps = $_POST['payment_status'] ?? '';
        if (in_array($ps, ['pending','paid','failed','refunded','cancelled'])) {
            $extra = $ps==='paid' ? ", paid_at = NOW()" : "";
            dbExec("UPDATE bookings SET payment_status=? $extra WHERE id=?", [$ps, $id]);
            flash('success', 'Pagamento atualizado.');
        }
    }
    redirect('/admin/reservas');
}

require VIEWS_DIR . '/partials/admin_head.php';

$statusFilter = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');
$where = '1=1'; $params = [];
if ($statusFilter) { $where .= " AND b.payment_status = ?"; $params[] = $statusFilter; }
if ($q) { $where .= " AND (b.code LIKE ? OR c.name LIKE ? OR c.email LIKE ?)"; array_push($params, "%$q%", "%$q%", "%$q%"); }

$pag = paginate(
    "SELECT COUNT(*) AS c FROM bookings b JOIN customers c ON b.customer_id=c.id WHERE $where",
    "SELECT b.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone FROM bookings b JOIN customers c ON b.customer_id=c.id WHERE $where ORDER BY b.created_at DESC",
    $params
);
$bookings = $pag['rows'];
$msg = flash('success');
?>

<?php if ($msg): ?><div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)"><i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i><span class="text-sm" style="color:var(--maresia-dark)"><?= e($msg) ?></span></div><?php endif; ?>

<form method="GET" class="flex flex-col md:flex-row gap-3 mb-6">
    <div class="flex-1 relative">
        <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2" style="color:var(--text-muted)"></i>
        <input name="q" value="<?= e($q) ?>" placeholder="Buscar por código, cliente ou email..." class="admin-input pl-11">
    </div>
    <select name="status" class="admin-input md:w-52" onchange="this.form.submit()">
        <option value="">Todos os pagamentos</option>
        <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Pendente</option>
        <option value="paid" <?= $statusFilter==='paid'?'selected':'' ?>>Pago</option>
        <option value="failed" <?= $statusFilter==='failed'?'selected':'' ?>>Falhou</option>
        <option value="cancelled" <?= $statusFilter==='cancelled'?'selected':'' ?>>Cancelada</option>
        <option value="refunded" <?= $statusFilter==='refunded'?'selected':'' ?>>Reembolsada</option>
    </select>
</form>

<div class="admin-card overflow-hidden">
    <?php if (!$bookings): ?>
        <div class="p-12 text-center">
            <i data-lucide="calendar-x" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
            <h3 class="font-semibold" style="color:var(--sepia)">Nenhuma reserva encontrada</h3>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead><tr><th>Código</th><th>Cliente</th><th>Passeio/Pacote</th><th>Data</th><th>Pessoas</th><th>Total</th><th>Pagamento</th></tr></thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><span class="font-mono text-xs font-semibold"><?= e($b['code']) ?></span><div class="text-[10px]" style="color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></div></td>
                    <td><div class="font-semibold text-sm"><?= e($b['customer_name']) ?></div><div class="text-xs" style="color:var(--text-muted)"><?= e($b['customer_email']) ?></div></td>
                    <td><div class="text-sm"><?= e($b['entity_title']) ?></div><div class="text-[10px] uppercase tracking-wider" style="color:var(--text-muted)"><?= e($b['entity_type']) ?></div></td>
                    <td><span class="text-sm"><?= $b['travel_date'] ? date('d/m/Y', strtotime($b['travel_date'])) : '—' ?></span></td>
                    <td><span class="text-sm"><?= (int)($b['adults'] + $b['children']) ?></span></td>
                    <td class="font-semibold"><?= formatBRL($b['total']) ?></td>
                    <td>
                        <form method="post" class="inline">
                            <?= csrfField() ?><input type="hidden" name="action" value="update_payment"><input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <select name="payment_status" onchange="this.form.submit()" class="text-xs px-2 py-1 rounded-md border" style="border-color:var(--border-default)">
                                <?php foreach (['pending'=>'Pendente','paid'=>'Pago','failed'=>'Falhou','refunded'=>'Reembolsado','cancelled'=>'Cancelado'] as $k=>$v): ?>
                                    <option value="<?= $k ?>" <?= $b['payment_status']===$k?'selected':'' ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
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
