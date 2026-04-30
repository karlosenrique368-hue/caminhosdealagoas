<?php
$pageTitle = 'Relatórios';
requireAdmin();

$today = date('Y-m-d');
$defaultStart = date('Y-m-01');
$start = $_GET['start'] ?? $defaultStart;
$end = $_GET['end'] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = $defaultStart;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) $end = $today;
if ($start > $end) [$start, $end] = [$end, $start];

$startDt = $start . ' 00:00:00';
$endDt = $end . ' 23:59:59';
$paidDateExpr = 'COALESCE(paid_at, updated_at, created_at)';
$paidDateExprBooking = 'COALESCE(b.paid_at, b.updated_at, b.created_at)';

$summary = dbOne("SELECT
    COUNT(*) AS total_bookings,
    SUM(payment_status='paid') AS paid_bookings,
    SUM(payment_status='pending') AS pending_bookings,
    SUM(payment_status IN ('failed','cancelled','refunded')) AS problem_bookings,
    COALESCE(SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END),0) AS paid_revenue,
    COALESCE(AVG(CASE WHEN payment_status='paid' THEN total END),0) AS avg_ticket
    FROM bookings
    WHERE created_at BETWEEN ? AND ?", [$startDt, $endDt]);

$revenue = (float)($summary['paid_revenue'] ?? 0);
$totalBookings = (int)($summary['total_bookings'] ?? 0);
$paidBookings = (int)($summary['paid_bookings'] ?? 0);
$pendingBookings = (int)($summary['pending_bookings'] ?? 0);
$problemBookings = (int)($summary['problem_bookings'] ?? 0);
$avgTicket = (float)($summary['avg_ticket'] ?? 0);
$conversion = $totalBookings > 0 ? round(($paidBookings / $totalBookings) * 100, 1) : 0;

$prevStartDt = date('Y-m-d 00:00:00', strtotime($start . ' -' . (max(1, (int)((strtotime($end) - strtotime($start)) / 86400) + 1)) . ' days'));
$prevEndDt = date('Y-m-d 23:59:59', strtotime($start . ' -1 day'));
$prev = dbOne("SELECT COALESCE(SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END),0) AS revenue, COUNT(*) AS bookings FROM bookings WHERE created_at BETWEEN ? AND ?", [$prevStartDt, $prevEndDt]);
$prevRevenue = (float)($prev['revenue'] ?? 0);
$revenueDelta = $prevRevenue > 0 ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) : ($revenue > 0 ? 100 : 0);

$statusRows = dbAll("SELECT payment_status, COUNT(*) AS total, COALESCE(SUM(total),0) AS value FROM bookings WHERE created_at BETWEEN ? AND ? GROUP BY payment_status", [$startDt, $endDt]);
$methodRows = dbAll("SELECT COALESCE(payment_method,'indefinido') AS method, COUNT(*) AS total, COALESCE(SUM(total),0) AS value FROM bookings WHERE created_at BETWEEN ? AND ? GROUP BY COALESCE(payment_method,'indefinido') ORDER BY total DESC", [$startDt, $endDt]);
$topProducts = dbAll("SELECT entity_type, entity_title, COUNT(*) AS bookings, SUM(payment_status='paid') AS paid, COALESCE(SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END),0) AS revenue FROM bookings WHERE created_at BETWEEN ? AND ? GROUP BY entity_type, entity_id, entity_title ORDER BY revenue DESC, bookings DESC LIMIT 10", [$startDt, $endDt]);
$recentPaid = dbAll("SELECT b.code, b.entity_title, b.total, b.payment_method, b.paid_at, c.name AS customer_name FROM bookings b JOIN customers c ON c.id=b.customer_id WHERE b.payment_status='paid' AND $paidDateExprBooking BETWEEN ? AND ? ORDER BY $paidDateExprBooking DESC LIMIT 8", [$startDt, $endDt]);
$monthlyRows = dbAll("SELECT DATE_FORMAT($paidDateExpr, '%Y-%m') AS month_key, COALESCE(SUM(total),0) AS revenue, COUNT(*) AS bookings FROM bookings WHERE payment_status='paid' AND $paidDateExpr >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY DATE_FORMAT($paidDateExpr, '%Y-%m') ORDER BY month_key", []);

$monthMap = [];
foreach ($monthlyRows as $row) $monthMap[$row['month_key']] = $row;
$chartLabels = [];
$chartRevenue = [];
$chartBookings = [];
for ($i = 11; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i months"));
    $chartLabels[] = date('m/Y', strtotime($key . '-01'));
    $chartRevenue[] = isset($monthMap[$key]) ? (float)$monthMap[$key]['revenue'] : 0;
    $chartBookings[] = isset($monthMap[$key]) ? (int)$monthMap[$key]['bookings'] : 0;
}

$labelsStatus = ['paid'=>'Pago','pending'=>'Pendente','failed'=>'Falhou','cancelled'=>'Cancelado','refunded'=>'Reembolsado'];
$labelsMethod = ['pix'=>'PIX Mercado Pago','credit_card'=>'Cartão Mercado Pago','boleto'=>'Boleto Mercado Pago','bank_transfer'=>'Transferência','indefinido'=>'Indefinido'];

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="relatorio-caminhos-' . $start . '-' . $end . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Periodo', $start . ' ate ' . $end], ';');
    fputcsv($out, ['Receita paga', number_format($revenue, 2, ',', '.')], ';');
    fputcsv($out, ['Reservas', $totalBookings], ';');
    fputcsv($out, []);
    fputcsv($out, ['Tipo', 'Experiencia', 'Reservas', 'Pagas', 'Receita paga'], ';');
    foreach ($topProducts as $row) {
        fputcsv($out, [$row['entity_type'], $row['entity_title'], $row['bookings'], $row['paid'], number_format((float)$row['revenue'], 2, ',', '.')], ';');
    }
    fclose($out);
    exit;
}

require VIEWS_DIR . '/partials/admin_head.php';
?>

<div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
    <div>
        <span class="text-xs font-bold tracking-[0.25em] uppercase" style="color:var(--terracota)">Inteligência comercial</span>
        <h2 class="font-display text-3xl font-bold mt-1" style="color:var(--sepia)">Relatórios do painel</h2>
        <p class="text-sm mt-1" style="color:var(--text-muted)">Receita, reservas, conversão e desempenho das experiências em um só lugar.</p>
    </div>
    <form method="GET" class="admin-card p-3 flex flex-col sm:flex-row gap-2 sm:items-end">
        <label><span class="admin-label">Início</span><input type="date" name="start" value="<?= e($start) ?>" class="admin-input" data-premium-date="off"></label>
        <label><span class="admin-label">Fim</span><input type="date" name="end" value="<?= e($end) ?>" class="admin-input" data-premium-date="off"></label>
        <button class="admin-btn admin-btn-primary" type="submit"><i data-lucide="filter" class="w-4 h-4"></i>Filtrar</button>
        <a class="admin-btn admin-btn-secondary" href="<?= e(url('/admin/relatorios?start=' . urlencode($start) . '&end=' . urlencode($end) . '&export=csv')) ?>"><i data-lucide="download" class="w-4 h-4"></i>CSV</a>
        <button class="admin-btn admin-btn-secondary" type="button" onclick="window.print()"><i data-lucide="printer" class="w-4 h-4"></i>Imprimir</button>
    </form>
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <?php foreach ([
        ['Receita paga', formatBRL($revenue), 'trending-up', $revenueDelta . '% vs. período anterior', $revenueDelta >= 0 ? 'var(--maresia-dark)' : '#DC2626'],
        ['Reservas', number_format($totalBookings, 0, ',', '.'), 'calendar-check', $paidBookings . ' pagas no período', 'var(--horizonte)'],
        ['Conversão', number_format($conversion, 1, ',', '.') . '%', 'gauge', $pendingBookings . ' pendentes', 'var(--terracota)'],
        ['Ticket médio', formatBRL($avgTicket), 'receipt', $problemBookings . ' com falha/cancelamento', '#B45309'],
    ] as $card): ?>
    <div class="admin-stat">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--text-muted)"><?= e($card[0]) ?></span>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(201,107,74,.08);color:<?= e($card[4]) ?>"><i data-lucide="<?= e($card[2]) ?>" class="w-5 h-5"></i></div>
        </div>
        <div class="font-display text-3xl font-bold mb-1" style="color:var(--sepia)"><?= e($card[1]) ?></div>
        <div class="text-xs" style="color:<?= e($card[4]) ?>"><?= e($card[3]) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid xl:grid-cols-3 gap-6 mb-6">
    <div class="admin-card p-6 xl:col-span-2">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Receita paga por mês</h3>
                <p class="text-xs" style="color:var(--text-muted)">Últimos 12 meses confirmados pelo pagamento.</p>
            </div>
            <span class="badge badge-success">Mercado Pago ready</span>
        </div>
        <div style="height:280px"><canvas id="reportsRevenueChart"></canvas></div>
    </div>
    <div class="admin-card p-6">
        <h3 class="font-display text-lg font-bold mb-1" style="color:var(--sepia)">Status das reservas</h3>
        <p class="text-xs mb-5" style="color:var(--text-muted)">Distribuição do período filtrado.</p>
        <?php if ($statusRows): ?>
            <div class="space-y-3">
                <?php foreach ($statusRows as $row):
                    $count = (int)$row['total'];
                    $pct = $totalBookings > 0 ? max(3, round(($count / $totalBookings) * 100)) : 0;
                    $status = $row['payment_status'];
                ?>
                <div>
                    <div class="flex items-center justify-between text-xs font-semibold mb-1"><span style="color:var(--sepia)"><?= e($labelsStatus[$status] ?? $status) ?></span><span style="color:var(--text-muted)"><?= $count ?></span></div>
                    <div class="h-2 rounded-full overflow-hidden" style="background:var(--bg-surface)"><div class="h-full rounded-full" style="width:<?= $pct ?>%;background:<?= $status === 'paid' ? 'var(--maresia)' : ($status === 'pending' ? '#F59E0B' : 'var(--terracota)') ?>"></div></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="p-8 text-center text-sm" style="color:var(--text-muted)">Sem reservas neste período.</div>
        <?php endif; ?>
    </div>
</div>

<div class="grid xl:grid-cols-3 gap-6">
    <div class="admin-card overflow-hidden xl:col-span-2">
        <div class="p-5 border-b flex items-center justify-between" style="border-color:var(--border-default)">
            <div>
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Experiências com melhor desempenho</h3>
                <p class="text-xs" style="color:var(--text-muted)">Ordenado por receita paga.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead><tr><th>Experiência</th><th>Tipo</th><th>Reservas</th><th>Pagas</th><th>Receita</th></tr></thead>
                <tbody>
                    <?php if ($topProducts): foreach ($topProducts as $row): ?>
                    <tr>
                        <td class="font-semibold"><?= e($row['entity_title']) ?></td>
                        <td><span class="badge badge-info"><?= e($row['entity_type'] === 'roteiro' ? 'Passeio' : ($row['entity_type'] === 'pacote' ? 'Pacote' : 'Transfer')) ?></span></td>
                        <td><?= (int)$row['bookings'] ?></td>
                        <td><?= (int)$row['paid'] ?></td>
                        <td class="font-bold" style="color:var(--terracota)"><?= formatBRL((float)$row['revenue']) ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center py-8" style="color:var(--text-muted)">Sem dados para o período.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="admin-card p-6">
            <h3 class="font-display text-lg font-bold mb-1" style="color:var(--sepia)">Métodos de pagamento</h3>
            <p class="text-xs mb-4" style="color:var(--text-muted)">Todos passam pelo Mercado Pago.</p>
            <div class="space-y-3">
                <?php foreach ($methodRows as $row): ?>
                <div class="flex items-center justify-between gap-3 p-3 rounded-xl" style="background:var(--bg-surface)">
                    <div class="min-w-0">
                        <div class="text-sm font-bold truncate" style="color:var(--sepia)"><?= e($labelsMethod[$row['method']] ?? $row['method']) ?></div>
                        <div class="text-[11px]" style="color:var(--text-muted)"><?= (int)$row['total'] ?> reserva(s)</div>
                    </div>
                    <div class="font-bold text-sm" style="color:var(--terracota)"><?= formatBRL((float)$row['value']) ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (!$methodRows): ?><div class="text-sm text-center py-5" style="color:var(--text-muted)">Sem pagamentos.</div><?php endif; ?>
            </div>
        </div>

        <div class="admin-card p-6">
            <h3 class="font-display text-lg font-bold mb-1" style="color:var(--sepia)">Últimos pagamentos</h3>
            <p class="text-xs mb-4" style="color:var(--text-muted)">Pagamentos confirmados no período.</p>
            <div class="space-y-3">
                <?php foreach ($recentPaid as $row): ?>
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(122,157,110,.12);color:var(--maresia-dark)"><i data-lucide="check" class="w-4 h-4"></i></div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-bold truncate" style="color:var(--sepia)"><?= e($row['customer_name']) ?></div>
                        <div class="text-[11px] truncate" style="color:var(--text-muted)"><?= e($row['code']) ?> · <?= e($row['entity_title']) ?></div>
                    </div>
                    <div class="text-sm font-bold" style="color:var(--terracota)"><?= formatBRL((float)$row['total']) ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (!$recentPaid): ?><div class="text-sm text-center py-5" style="color:var(--text-muted)">Nenhum pagamento confirmado.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
    const labels = <?= json_encode($chartLabels) ?>;
    const revenue = <?= json_encode($chartRevenue) ?>;
    const bookings = <?= json_encode($chartBookings) ?>;
    const canvas = document.getElementById('reportsRevenueChart');
    if (!canvas || !window.Chart) return;
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(201,107,74,0.36)');
    gradient.addColorStop(1, 'rgba(201,107,74,0.02)');
    new Chart(ctx, {
        type: 'line',
        data: { labels, datasets: [{ label: 'Receita paga', data: revenue, borderColor: '#C96B4A', backgroundColor: gradient, fill: true, tension: 0.38, pointRadius: 3, pointHoverRadius: 6, borderWidth: 2.5 }] },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display:false }, tooltip: { backgroundColor:'#3E2E1F', displayColors:false, callbacks: { label: (ctx) => 'Receita: ' + Number(ctx.parsed.y || 0).toLocaleString('pt-BR', {style:'currency', currency:'BRL'}), afterLabel: (ctx) => (bookings[ctx.dataIndex] || 0) + ' reserva(s) pagas' } } },
            scales: { x: { grid:{display:false}, ticks:{color:'#9CA3AF', font:{size:11}} }, y: { beginAtZero:true, grid:{color:'rgba(0,0,0,.05)'}, ticks:{color:'#9CA3AF', callback:(v)=> Number(v).toLocaleString('pt-BR', {style:'currency', currency:'BRL', maximumFractionDigits:0})} } }
        }
    });
})();
</script>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
