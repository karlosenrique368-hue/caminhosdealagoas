<?php
$code = $_GET['code'] ?? '';
$v = $code ? dbOne('SELECT v.*, b.*, c.name AS customer_name, c.email AS customer_email, b.entity_title FROM vouchers v JOIN bookings b ON v.booking_id=b.id LEFT JOIN customers c ON COALESCE(b.customer_user_id, b.customer_id)=c.id WHERE v.code=?', [$code]) : null;
if (!$v) {
    http_response_code(404);
    echo '<h1 style="text-align:center;padding:100px">Voucher não encontrado</h1>'; exit;
}
$pageTitle = 'Voucher ' . $v['code'];
$solidNav = true;
include VIEWS_DIR . '/partials/public_head.php';
?>

<section class="min-h-screen py-16 flex items-center justify-center" style="background:var(--bg-surface)">
    <div class="max-w-xl w-full mx-auto px-6">
        <div class="rounded-3xl overflow-hidden shadow-2xl" style="background:#fff">
            <div class="p-8 relative overflow-hidden text-center" style="background:linear-gradient(135deg,var(--terracota),var(--sepia));color:#fff">
                <img src="<?= asset('brand/selo-branco.png') ?>" class="seal-watermark xl dark" style="top:-80px;right:-80px" alt="">
                <div class="relative z-10">
                    <p class="text-xs uppercase tracking-[0.3em] opacity-80 mb-2">Voucher</p>
                    <h1 class="font-display text-4xl font-bold"><?= e($v['entity_title']) ?></h1>
                    <p class="mt-4 text-2xl font-mono font-bold tracking-wider"><?= e($v['code']) ?></p>
                </div>
            </div>
            <div class="p-8">
                <div class="flex justify-center mb-6">
                    <div id="qrcode" class="p-4 rounded-2xl border-2" style="border-color:var(--border-default)"></div>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between py-2 border-b" style="border-color:var(--border-default)"><dt style="color:var(--text-muted)">Viajante</dt><dd class="font-semibold"><?= e($v['customer_name']) ?></dd></div>
                    <div class="flex justify-between py-2 border-b" style="border-color:var(--border-default)"><dt style="color:var(--text-muted)">E-mail</dt><dd class="font-semibold"><?= e($v['customer_email']) ?></dd></div>
                    <?php if ($v['travel_date']): ?><div class="flex justify-between py-2 border-b" style="border-color:var(--border-default)"><dt style="color:var(--text-muted)">Data da viagem</dt><dd class="font-semibold"><?= date('d/m/Y', strtotime($v['travel_date'])) ?></dd></div><?php endif; ?>
                    <div class="flex justify-between py-2 border-b" style="border-color:var(--border-default)"><dt style="color:var(--text-muted)">Valor</dt><dd class="font-semibold"><?= formatPrice((float)$v['total']) ?></dd></div>
                    <div class="flex justify-between py-2"><dt style="color:var(--text-muted)">Status</dt><dd class="font-semibold"><?= $v['used'] ? '✓ Utilizado' : 'Válido' ?></dd></div>
                </dl>
                <button onclick="window.print()" class="btn-primary w-full justify-center mt-6"><i data-lucide="printer" class="w-4 h-4"></i> Imprimir voucher</button>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const qr = new QRious({ value: <?= json_encode($v['qr_data']) ?>, size: 220, padding: 8, level: 'M' });
    const img = document.createElement('img');
    img.src = qr.toDataURL();
    img.style.display = 'block';
    document.getElementById('qrcode').appendChild(img);
});
</script>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
