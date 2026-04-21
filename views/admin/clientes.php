<?php
$pageTitle = 'Clientes';
require VIEWS_DIR . '/partials/admin_head.php';

$q = trim($_GET['q'] ?? '');
$where = '1=1'; $params = [];
if ($q) { $where .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)"; array_push($params, "%$q%", "%$q%", "%$q%"); }

$clientes = dbAll("SELECT c.*, COUNT(b.id) AS total_bookings, COALESCE(SUM(CASE WHEN b.payment_status='paid' THEN b.total ELSE 0 END),0) AS total_spent FROM customers c LEFT JOIN bookings b ON b.customer_id=c.id WHERE $where GROUP BY c.id ORDER BY c.created_at DESC", $params);
?>

<form method="GET" class="mb-6 max-w-md relative">
    <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2" style="color:var(--text-muted)"></i>
    <input name="q" value="<?= e($q) ?>" placeholder="Buscar cliente..." class="admin-input pl-11">
</form>

<div class="admin-card overflow-hidden">
    <?php if (!$clientes): ?>
        <div class="p-12 text-center">
            <i data-lucide="users" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
            <h3 class="font-semibold" style="color:var(--sepia)">Nenhum cliente encontrado</h3>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead><tr><th>Cliente</th><th>Contato</th><th>Reservas</th><th>Total gasto</th><th>Cadastro</th></tr></thead>
            <tbody>
                <?php foreach ($clientes as $c): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm" style="background:linear-gradient(135deg,var(--terracota),var(--terracota-dark));color:white"><?= e(mb_strtoupper(mb_substr($c['name'],0,2))) ?></div>
                            <div>
                                <div class="font-semibold"><?= e($c['name']) ?></div>
                                <?php if ($c['document']): ?><div class="text-xs" style="color:var(--text-muted)">Doc: <?= e($c['document']) ?></div><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><div class="text-sm"><?= e($c['email']) ?></div><div class="text-xs" style="color:var(--text-muted)"><?= e($c['phone'] ?? '') ?></div></td>
                    <td><span class="badge badge-info"><?= (int)$c['total_bookings'] ?> reserva<?= $c['total_bookings']!=1?'s':'' ?></span></td>
                    <td class="font-semibold"><?= formatBRL($c['total_spent']) ?></td>
                    <td><span class="text-sm"><?= date('d/m/Y', strtotime($c['created_at'])) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
