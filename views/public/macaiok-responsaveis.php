<?php
$pageTitle = 'Macaiok · Para responsáveis';
$solidNav  = true;
$macaiokMode = true;

$escolaSlug   = trim($_GET['escola'] ?? '');
$vivenciaId   = (int)($_GET['vivencia'] ?? 0);
$vivenciaType = $_GET['tipo'] ?? 'roteiro';

$escola = null;
if ($escolaSlug) {
    try {
        $escola = dbOne("SELECT * FROM institutions WHERE slug=? AND program='macaiok' AND active=1 LIMIT 1", [$escolaSlug]);
    } catch (Throwable $e) {
        try { $escola = dbOne('SELECT * FROM institutions WHERE slug=? AND active=1 LIMIT 1', [$escolaSlug]); }
        catch (Throwable $e2) { error_log('[macaiok-resp.escola] ' . $e2->getMessage()); }
    }
}
$vivencia = null;
if ($vivenciaId) {
    try {
        if ($vivenciaType === 'pacote') {
            $vivencia = dbOne("SELECT id, title, slug, cover_image, price, price_pix, destination AS location, summary FROM pacotes WHERE id=? AND status='published'", [$vivenciaId]);
        } elseif ($vivenciaType === 'transfer') {
            $vivencia = dbOne("SELECT id, title, slug, cover_image, price, price_pix, location_to AS location, summary FROM transfers WHERE id=? AND status='published'", [$vivenciaId]);
        } else {
            $vivencia = dbOne("SELECT id, title, slug, cover_image, price, price_pix, location, summary FROM roteiros WHERE id=? AND status='published'", [$vivenciaId]);
            $vivenciaType = 'roteiro';
        }
    } catch (Throwable $e) { error_log('[macaiok-responsaveis] '.$e->getMessage()); $vivencia = null; }
}

$checkoutQuery = [];
if ($vivencia) $checkoutQuery[$vivenciaType] = (int)$vivencia['id'];
if ($escola && !empty($escola['referral_code'])) $checkoutQuery['parceiro'] = $escola['referral_code'];
$checkoutUrl = $vivencia ? url('/macaiok/checkout?' . http_build_query($checkoutQuery)) : url('/macaiok');

include VIEWS_DIR . '/partials/public_head.php';
?>

<section class="pt-32 pb-16">
    <div class="max-w-4xl mx-auto px-6">
        <div class="text-center mb-10">
            <img src="<?= asset('img/macaiok/VerdeEscuro_Horizontal.png') ?>" alt="Macaiok" class="h-14 sm:h-16 mx-auto mb-6">
            <span class="text-xs font-bold uppercase tracking-[0.24em]" style="color:var(--mk-terracota,#DA4A34)">Macaiok · Vivências Pedagógicas</span>
            <h1 class="font-display text-3xl sm:text-5xl font-bold mt-2" style="color:var(--mk-sepia,#2F1607)">
                <?php if ($escola): ?>Caro responsável da <?= e($escola['name']) ?><?php else: ?>Olá, responsável!<?php endif; ?>
            </h1>
            <p class="mt-3 text-base max-w-2xl mx-auto" style="color:var(--text-secondary)">
                <?= $escola && !empty($escola['parent_share_note']) ? e($escola['parent_share_note']) : 'Conclua o pagamento e os dados do(a) estudante para garantir a participação na vivência selecionada.' ?>
            </p>
        </div>

        <?php if ($vivencia): ?>
            <div class="admin-card p-6 sm:p-8 mb-8">
                <div class="flex flex-col sm:flex-row gap-5">
                    <?php if (!empty($vivencia['cover_image'])): ?>
                        <img src="<?= e(storageUrl($vivencia['cover_image'])) ?>" alt="<?= e($vivencia['title']) ?>" class="w-full sm:w-64 aspect-[4/3] object-cover rounded-2xl">
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--mk-terracota,#DA4A34)"><?= ucfirst($vivenciaType) ?></div>
                        <h2 class="font-display text-2xl font-bold mb-2" style="color:var(--mk-sepia,#2F1607)"><?= e($vivencia['title']) ?></h2>
                        <?php if (!empty($vivencia['location'])): ?><div class="text-sm flex items-center gap-1.5 mb-3" style="color:var(--text-secondary)"><i data-lucide="map-pin" class="w-4 h-4"></i> <?= e($vivencia['location']) ?></div><?php endif; ?>
                        <?php if (!empty($vivencia['summary'])): ?><p class="text-sm mb-4" style="color:var(--text-secondary)"><?= e($vivencia['summary']) ?></p><?php endif; ?>
                        <div class="flex items-end justify-between gap-3 flex-wrap">
                            <div>
                                <div class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted)">Investimento</div>
                                <strong class="font-display text-3xl" style="color:var(--mk-mangue,#324500)"><?= formatBRL($vivencia['price_pix'] ?: $vivencia['price']) ?></strong>
                            </div>
                            <a href="<?= e($checkoutUrl) ?>" class="btn-primary inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold"><i data-lucide="credit-card" class="w-4 h-4"></i> Ir para o pagamento</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="admin-card p-6 sm:p-8 text-center">
                <i data-lucide="info" class="w-10 h-10 mx-auto mb-3" style="color:var(--mk-terracota,#DA4A34)"></i>
                <h2 class="font-display text-xl font-bold mb-2" style="color:var(--mk-sepia,#2F1607)">Link incompleto</h2>
                <p class="text-sm" style="color:var(--text-secondary)">Solicite à coordenação da escola um novo link com a vivência específica do(a) estudante.</p>
            </div>
        <?php endif; ?>

        <div class="grid sm:grid-cols-3 gap-4 mt-8 text-center text-sm">
            <div class="admin-card p-5"><i data-lucide="shield-check" class="w-6 h-6 mx-auto mb-2" style="color:var(--mk-mangue,#324500)"></i><div class="font-bold" style="color:var(--mk-sepia,#2F1607)">Pagamento seguro</div><div style="color:var(--text-secondary)">Ambiente criptografado</div></div>
            <div class="admin-card p-5"><i data-lucide="users" class="w-6 h-6 mx-auto mb-2" style="color:var(--mk-mangue,#324500)"></i><div class="font-bold" style="color:var(--mk-sepia,#2F1607)">Equipe Caminhos</div><div style="color:var(--text-secondary)">Operação completa no destino</div></div>
            <div class="admin-card p-5"><i data-lucide="leaf" class="w-6 h-6 mx-auto mb-2" style="color:var(--mk-mangue,#324500)"></i><div class="font-bold" style="color:var(--mk-sepia,#2F1607)">Educação ao ar livre</div><div style="color:var(--text-secondary)">Vivências pedagógicas reais</div></div>
        </div>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
