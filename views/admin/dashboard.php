<?php
$pageTitle = 'Dashboard';
require VIEWS_DIR . '/partials/admin_head.php';

// Stats
$totalRoteiros = (int) (dbOne("SELECT COUNT(*) AS c FROM roteiros")['c'] ?? 0);
$totalPacotes = (int) (dbOne("SELECT COUNT(*) AS c FROM pacotes")['c'] ?? 0);
$totalReservas = (int) (dbOne("SELECT COUNT(*) AS c FROM bookings")['c'] ?? 0);
$totalClientes = (int) (dbOne("SELECT COUNT(*) AS c FROM customers")['c'] ?? 0);

$revenueMonth = (float) (dbOne("SELECT COALESCE(SUM(total),0) AS v FROM bookings WHERE payment_status='paid' AND MONTH(paid_at)=MONTH(NOW()) AND YEAR(paid_at)=YEAR(NOW())")['v'] ?? 0);
$revenueTotal = (float) (dbOne("SELECT COALESCE(SUM(total),0) AS v FROM bookings WHERE payment_status='paid'")['v'] ?? 0);
$pendingBookings = (int) (dbOne("SELECT COUNT(*) AS c FROM bookings WHERE payment_status='pending'")['c'] ?? 0);
$newMessages = (int) (dbOne("SELECT COUNT(*) AS c FROM contact_messages WHERE status='new'")['c'] ?? 0);

$recentBookings = dbAll("SELECT b.*, c.name AS customer_name, c.email AS customer_email FROM bookings b JOIN customers c ON b.customer_id=c.id ORDER BY b.created_at DESC LIMIT 6");

// Revenue chart (last 30 days) — usa COALESCE para sobreviver a registros sem paid_at
$chartData = dbAll("SELECT DATE(COALESCE(paid_at, updated_at, created_at)) AS d, COALESCE(SUM(total),0) AS v, COUNT(*) AS n FROM bookings WHERE payment_status='paid' AND COALESCE(paid_at, updated_at, created_at) >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY DATE(COALESCE(paid_at, updated_at, created_at)) ORDER BY d");
$chartLabels = [];
$chartValues = [];
$chartCounts = [];
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d/m', strtotime($day));
    $found = 0; $fcount = 0;
    foreach ($chartData as $cd) if ($cd['d'] === $day) { $found = (float) $cd['v']; $fcount = (int) $cd['n']; }
    $chartValues[] = $found;
    $chartCounts[] = $fcount;
}
$maxValue = max($chartValues) ?: 1;

// Receita por entity_type (donut)
$byType = dbAll("SELECT entity_type, COALESCE(SUM(total),0) AS v, COUNT(*) AS n FROM bookings WHERE payment_status='paid' GROUP BY entity_type");

$topRoteiros = dbAll("SELECT r.title, r.views, COUNT(b.id) AS bookings_count FROM roteiros r LEFT JOIN bookings b ON b.entity_id=r.id AND b.entity_type='roteiro' GROUP BY r.id ORDER BY bookings_count DESC, r.views DESC LIMIT 5");
?>

<!-- Welcome -->
<div class="mb-8">
    <h2 class="font-display text-3xl font-bold mb-1" style="color:var(--sepia)">Olá, <?= e(explode(' ', $adm['name'])[0]) ?> 👋</h2>
    <p class="text-sm" style="color:var(--text-secondary)">Aqui está o resumo da sua plataforma hoje.</p>
</div>

<!-- Stats grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php
    $cards = [
        ['label' => 'Passeios', 'value' => $totalRoteiros, 'icon' => 'compass', 'color' => 'horizonte', 'bg' => 'rgba(58,107,138,0.1)', 'fg' => '#3A6B8A'],
        ['label' => 'Pacotes', 'value' => $totalPacotes, 'icon' => 'package', 'color' => 'terracota', 'bg' => 'rgba(201,107,74,0.1)', 'fg' => '#C96B4A'],
        ['label' => 'Reservas', 'value' => $totalReservas, 'icon' => 'calendar-check', 'color' => 'maresia', 'bg' => 'rgba(122,157,110,0.1)', 'fg' => '#5E7E55'],
        ['label' => 'Clientes', 'value' => $totalClientes, 'icon' => 'users', 'color' => 'areia', 'bg' => 'rgba(245,158,11,0.1)', 'fg' => '#D97706'],
    ];
    foreach ($cards as $c): ?>
    <div class="admin-stat">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--text-muted)"><?= e($c['label']) ?></span>
            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:<?= $c['bg'] ?>">
                <i data-lucide="<?= $c['icon'] ?>" class="w-4 h-4" style="color:<?= $c['fg'] ?>"></i>
            </div>
        </div>
        <div class="font-display text-3xl font-bold mb-1" style="color:var(--sepia)"><?= number_format($c['value'], 0, ',', '.') ?></div>
        <div class="text-xs" style="color:var(--text-muted)">Total cadastrados</div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Revenue row -->
<div class="grid lg:grid-cols-3 gap-4 mb-8">
    <div class="admin-stat lg:col-span-1">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Receita do mês</span>
            <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(122,157,110,0.12)">
                <i data-lucide="trending-up" class="w-4 h-4" style="color:var(--maresia-dark)"></i>
            </div>
        </div>
        <div class="font-display text-3xl font-bold mb-1" style="color:var(--sepia)"><?= formatBRL($revenueMonth) ?></div>
        <div class="text-xs" style="color:var(--text-muted)"><?= formatBRL($revenueTotal) ?> no total</div>
    </div>

    <div class="admin-card p-6 lg:col-span-2">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Receita diária</h3>
                <p class="text-xs" style="color:var(--text-muted)">Últimos 30 dias · passe o mouse nas barras</p>
            </div>
            <div class="badge badge-success">Ao vivo</div>
        </div>
        <div style="position:relative;height:220px">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<!-- Distribuição por tipo + Alerts -->
<div class="grid md:grid-cols-3 gap-4 mb-8">
    <div class="admin-card p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Composição de receita</h3>
                <p class="text-xs" style="color:var(--text-muted)">Passeios × Pacotes</p>
            </div>
        </div>
        <div style="position:relative;height:180px"><canvas id="typeChart"></canvas></div>
    </div>
    <div class="md:col-span-2 grid md:grid-cols-2 gap-4">
        <?php if ($pendingBookings): ?>
        <a href="<?= url('/admin/reservas?status=pending') ?>" class="admin-card p-5 flex items-center gap-4 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(245,158,11,0.12);color:#D97706">
                <i data-lucide="clock" class="w-6 h-6"></i>
            </div>
            <div class="flex-1">
                <div class="text-sm font-semibold" style="color:var(--sepia)"><?= $pendingBookings ?> pendente<?= $pendingBookings>1?'s':'' ?></div>
                <div class="text-xs" style="color:var(--text-muted)">Aguardando pagamento</div>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4" style="color:var(--text-muted)"></i>
        </a>
        <?php endif; ?>
        <?php if ($newMessages): ?>
        <a href="<?= url('/admin/mensagens') ?>" class="admin-card p-5 flex items-center gap-4 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(58,107,138,0.12);color:var(--horizonte)">
                <i data-lucide="mail" class="w-6 h-6"></i>
            </div>
            <div class="flex-1">
                <div class="text-sm font-semibold" style="color:var(--sepia)"><?= $newMessages ?> nova<?= $newMessages>1?'s':'' ?></div>
                <div class="text-xs" style="color:var(--text-muted)">Mensagens de contato</div>
            </div>
            <i data-lucide="arrow-right" class="w-4 h-4" style="color:var(--text-muted)"></i>
        </a>
        <?php endif; ?>
        <?php if (!$pendingBookings && !$newMessages): ?>
        <div class="admin-card p-5 md:col-span-2 flex items-center gap-4" style="background:rgba(122,157,110,0.05);border-color:rgba(122,157,110,0.2)">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:rgba(122,157,110,0.15);color:var(--maresia-dark)">
                <i data-lucide="check-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-sm font-semibold" style="color:var(--sepia)">Tudo em ordem!</div>
                <div class="text-xs" style="color:var(--text-muted)">Sem pendências no momento</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- (Alerts já exibidos acima dentro da coluna) -->
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 admin-card overflow-hidden">
        <div class="p-5 border-b flex items-center justify-between" style="border-color:var(--border-default)">
            <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Reservas recentes</h3>
            <a href="<?= url('/admin/reservas') ?>" class="text-xs font-semibold hover:underline" style="color:var(--terracota)">Ver todas →</a>
        </div>
        <?php if ($recentBookings): ?>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentBookings as $b): ?>
                    <tr>
                        <td><span class="font-mono text-xs"><?= e($b['code']) ?></span></td>
                        <td>
                            <div class="font-semibold"><?= e($b['customer_name']) ?></div>
                            <div class="text-xs" style="color:var(--text-muted)"><?= e(truncate($b['entity_title'], 40)) ?></div>
                        </td>
                        <td class="font-semibold"><?= formatBRL($b['total']) ?></td>
                        <td>
                            <?php
                            $s = $b['payment_status'];
                            $badge = ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'cancelled' => 'muted', 'refunded' => 'info'][$s] ?? 'muted';
                            $label = ['paid' => 'Pago', 'pending' => 'Pendente', 'failed' => 'Falhou', 'cancelled' => 'Cancelada', 'refunded' => 'Reembolsada'][$s] ?? $s;
                            ?>
                            <span class="badge badge-<?= $badge ?>"><?= e($label) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="p-10 text-center">
                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3" style="color:var(--text-muted)"></i>
                <p class="text-sm font-semibold" style="color:var(--sepia)">Nenhuma reserva ainda</p>
                <p class="text-xs mt-1" style="color:var(--text-muted)">As reservas aparecerão aqui quando os clientes comprarem.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="p-5 border-b" style="border-color:var(--border-default)">
            <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Mais populares</h3>
            <p class="text-xs" style="color:var(--text-muted)">Passeios com mais reservas</p>
        </div>
        <div class="p-3">
            <?php if ($topRoteiros): ?>
                <?php foreach ($topRoteiros as $i => $tr): ?>
                <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold" style="background:<?= $i === 0 ? 'linear-gradient(135deg,#F59E0B,#D97706)' : 'var(--bg-surface)' ?>;color:<?= $i === 0 ? 'white' : 'var(--text-secondary)' ?>"><?= $i + 1 ?></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold truncate" style="color:var(--sepia)"><?= e($tr['title']) ?></div>
                        <div class="text-[11px]" style="color:var(--text-muted)"><?= $tr['bookings_count'] ?> reservas · <?= $tr['views'] ?> views</div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-6 text-center text-sm" style="color:var(--text-muted)">Sem dados ainda</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    const labels = <?= json_encode($chartLabels) ?>;
    const values = <?= json_encode($chartValues) ?>;
    const counts = <?= json_encode($chartCounts) ?>;
    const byType = <?= json_encode($byType) ?>;

    const ctx = document.getElementById('revenueChart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 220);
    grad.addColorStop(0, 'rgba(201,107,74,0.45)');
    grad.addColorStop(1, 'rgba(201,107,74,0.02)');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Receita',
                data: values,
                borderColor: '#C96B4A',
                backgroundColor: grad,
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#C96B4A',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#3E2E1F',
                    titleFont: { family: 'Inter', weight: 'bold', size: 12 },
                    bodyFont:  { family: 'Inter', size: 12 },
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        title: (items) => items[0].label,
                        label: (item) => {
                            const v = item.parsed.y;
                            const c = counts[item.dataIndex] || 0;
                            return ['  R$ ' + v.toLocaleString('pt-BR', {minimumFractionDigits:2}), '  ' + c + ' reserva' + (c===1?'':'s')];
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { size: 10, family:'Inter' }, color: '#9CA3AF', maxRotation: 0, autoSkipPadding: 12 },
                },
                y: {
                    grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                    ticks: {
                        font: { size: 10, family:'Inter' }, color: '#9CA3AF',
                        callback: (v) => 'R$ ' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v),
                    },
                    beginAtZero: true,
                }
            }
        }
    });

    // Donut: tipo de reserva
    if (byType && byType.length) {
        const donutCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: byType.map(t => t.entity_type === 'roteiro' ? 'Passeios' : 'Pacotes'),
                datasets: [{
                    data: byType.map(t => parseFloat(t.v)),
                    backgroundColor: ['#3A6B8A', '#C96B4A'],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 10,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { font:{family:'Inter', size:12, weight:'600'}, color:'#3E2E1F', usePointStyle:true, pointStyle:'circle', padding:16 } },
                    tooltip: {
                        backgroundColor: '#3E2E1F',
                        callbacks: {
                            label: (ctx) => ' ' + ctx.label + ': R$ ' + ctx.parsed.toLocaleString('pt-BR', {minimumFractionDigits:2})
                        }
                    }
                }
            }
        });
    }
})();
</script>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
