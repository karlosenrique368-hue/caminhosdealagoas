<?php
requireInstitution();
$i = currentInstitution();
$pageTitle = 'Nossas reservas';

$pag = paginate(
    "SELECT COUNT(*) AS c FROM bookings WHERE institution_id=?",
    "SELECT b.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
     FROM bookings b LEFT JOIN customers c ON c.id=b.customer_id
     WHERE b.institution_id=?
     ORDER BY b.created_at DESC",
    [$i['id']]
);
$items = $pag['rows'];

include VIEWS_DIR . '/partials/institution_head.php';
?>
<div class="admin-card overflow-hidden">
    <?php if (!$items): ?>
        <div class="p-12 text-center">
            <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
            <h3 class="font-semibold mb-1" style="color:var(--sepia)">Nenhuma reserva ainda</h3>
            <p class="text-sm" style="color:var(--text-muted)">Compartilhe o link parceiro com seu público para começar a acumular.</p>
        </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead><tr><th>Código</th><th>Produto</th><th>Cliente</th><th>Contato</th><th>Data</th><th>Pax</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($items as $b): ?>
            <tr>
                <td class="font-mono text-xs"><?= e($b['code']) ?></td>
                <td><span class="text-xs uppercase font-semibold" style="color:var(--terracota)"><?= e($b['entity_type']) ?></span><div class="text-sm"><?= e($b['entity_title']) ?></div></td>
                <td class="text-sm"><?= e($b['customer_name'] ?? '—') ?></td>
                <td class="text-xs">
                    <?= e($b['customer_email'] ?? '') ?><br>
                    <span style="color:var(--text-muted)"><?= e($b['customer_phone'] ?? '') ?></span>
                </td>
                <td class="text-sm"><?= $b['travel_date'] ? formatDate($b['travel_date'], 'd/m/Y') : '—' ?></td>
                <td class="text-sm"><?= (int)$b['adults'] ?>A + <?= (int)$b['children'] ?>C</td>
                <td class="font-semibold" style="color:var(--sepia)"><?= formatBRL($b['total']) ?></td>
                <td><?php
                    $map = ['paid'=>['success','Pago'],'pending'=>['warning','Pendente'],'cancelled'=>['danger','Cancelada'],'refunded'=>['info','Reembolsada'],'failed'=>['danger','Falhou']];
                    $bm = $map[$b['payment_status']] ?? ['muted', $b['payment_status']];
                ?><span class="badge badge-<?= $bm[0] ?>"><?= $bm[1] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php include VIEWS_DIR . '/partials/pagination.php'; ?>
<?php include VIEWS_DIR . '/partials/institution_foot.php';
