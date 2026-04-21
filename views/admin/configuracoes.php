<?php
$pageTitle = 'Configurações';

$keys = [
    'site_name'         => ['label' => 'Nome do site', 'type' => 'text'],
    'site_tagline'      => ['label' => 'Slogan/Tagline', 'type' => 'text'],
    'site_description'  => ['label' => 'Descrição (SEO)', 'type' => 'textarea'],
    'contact_email'     => ['label' => 'Email de contato', 'type' => 'email'],
    'contact_phone'     => ['label' => 'Telefone', 'type' => 'text'],
    'contact_whatsapp'  => ['label' => 'WhatsApp (só números, com DDD)', 'type' => 'text'],
    'contact_address'   => ['label' => 'Endereço', 'type' => 'text'],
    'social_instagram'  => ['label' => 'Instagram (URL)', 'type' => 'text'],
    'social_facebook'   => ['label' => 'Facebook (URL)', 'type' => 'text'],
    'social_youtube'    => ['label' => 'YouTube (URL)', 'type' => 'text'],
    'hero_title'        => ['label' => 'Título do hero (home)', 'type' => 'text'],
    'hero_subtitle'     => ['label' => 'Subtítulo do hero', 'type' => 'textarea'],
    'hero_image'        => ['label' => 'Imagem do hero (URL)', 'type' => 'text'],
    'about_text'        => ['label' => 'Texto "Sobre nós"', 'type' => 'textarea'],
    'pix_enabled'       => ['label' => 'PIX habilitado', 'type' => 'checkbox'],
    'card_enabled'      => ['label' => 'Cartão habilitado', 'type' => 'checkbox'],
    'boleto_enabled'    => ['label' => 'Boleto habilitado', 'type' => 'checkbox'],
];

if (isPost() && csrfVerify()) {
    requireAdmin();
    foreach ($keys as $k => $cfg) {
        if ($cfg['type'] === 'checkbox') {
            setSetting($k, isset($_POST[$k]) && $_POST[$k] === '1' ? '1' : '0');
        } else {
            setSetting($k, trim($_POST[$k] ?? ''));
        }
    }
    flash('success', 'Configurações salvas.');
    redirect('/admin/configuracoes');
}

require VIEWS_DIR . '/partials/admin_head.php';
$msg = flash('success');
?>

<?php if ($msg): ?><div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)"><i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i><span class="text-sm" style="color:var(--maresia-dark)"><?= e($msg) ?></span></div><?php endif; ?>

<form method="post" class="space-y-6 max-w-3xl">
    <?= csrfField() ?>

    <div class="admin-card p-6 space-y-5">
        <h3 class="font-display text-lg font-bold mb-2 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="globe" class="w-5 h-5" style="color:var(--terracota)"></i>Identidade</h3>
        <?php foreach (['site_name','site_tagline','site_description'] as $k):
            $cfg = $keys[$k]; $val = getSetting($k); ?>
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)"><?= e($cfg['label']) ?></label>
                <?php if ($cfg['type']==='textarea'): ?>
                    <textarea name="<?= $k ?>" rows="3" class="admin-input"><?= e($val) ?></textarea>
                <?php else: ?>
                    <input type="<?= $cfg['type'] ?>" name="<?= $k ?>" value="<?= e($val) ?>" class="admin-input">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="admin-card p-6 space-y-5">
        <h3 class="font-display text-lg font-bold mb-2 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="phone" class="w-5 h-5" style="color:var(--horizonte)"></i>Contato</h3>
        <?php foreach (['contact_email','contact_phone','contact_whatsapp','contact_address'] as $k):
            $cfg = $keys[$k]; $val = getSetting($k); ?>
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)"><?= e($cfg['label']) ?></label>
                <input type="<?= $cfg['type'] ?>" name="<?= $k ?>" value="<?= e($val) ?>" class="admin-input">
            </div>
        <?php endforeach; ?>
    </div>

    <div class="admin-card p-6 space-y-5">
        <h3 class="font-display text-lg font-bold mb-2 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="share-2" class="w-5 h-5" style="color:var(--maresia-dark)"></i>Redes sociais</h3>
        <?php foreach (['social_instagram','social_facebook','social_youtube'] as $k):
            $cfg = $keys[$k]; $val = getSetting($k); ?>
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)"><?= e($cfg['label']) ?></label>
                <input type="text" name="<?= $k ?>" value="<?= e($val) ?>" class="admin-input">
            </div>
        <?php endforeach; ?>
    </div>

    <div class="admin-card p-6 space-y-5">
        <h3 class="font-display text-lg font-bold mb-2 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="image" class="w-5 h-5" style="color:var(--terracota)"></i>Home & Sobre</h3>
        <?php foreach (['hero_title','hero_subtitle','hero_image','about_text'] as $k):
            $cfg = $keys[$k]; $val = getSetting($k); ?>
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)"><?= e($cfg['label']) ?></label>
                <?php if ($cfg['type']==='textarea'): ?>
                    <textarea name="<?= $k ?>" rows="4" class="admin-input"><?= e($val) ?></textarea>
                <?php else: ?>
                    <input type="text" name="<?= $k ?>" value="<?= e($val) ?>" class="admin-input">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="admin-card p-6 space-y-3">
        <h3 class="font-display text-lg font-bold mb-2 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="credit-card" class="w-5 h-5" style="color:#F59E0B"></i>Pagamento</h3>
        <?php foreach (['pix_enabled','card_enabled','boleto_enabled'] as $k):
            $cfg = $keys[$k]; $val = getSetting($k); ?>
            <label class="flex items-center gap-3 cursor-pointer py-2">
                <input type="checkbox" name="<?= $k ?>" value="1" <?= $val==='1'?'checked':'' ?> class="w-4 h-4 rounded" style="accent-color:var(--terracota)">
                <span class="text-sm font-semibold" style="color:var(--sepia)"><?= e($cfg['label']) ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <div class="flex justify-end sticky bottom-4">
        <button type="submit" class="admin-btn admin-btn-primary shadow-lg"><i data-lucide="save" class="w-4 h-4"></i>Salvar configurações</button>
    </div>
</form>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
