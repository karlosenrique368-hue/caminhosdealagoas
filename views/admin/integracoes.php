<?php
$pageTitle = 'Integrações';

// Pré-cadastra credenciais Mercado Pago do cliente uma única vez. Depois disso, o que vale é o que estiver no formulário.
function ensureMercadoPagoSeed(): void {
    if ((int)(getSetting('mp_credentials_seeded', '0')) === 1) return;
    $defaults = [
        'payment_provider'        => 'mercadopago',
        'payment_enabled'         => '1',
        'payment_sandbox'         => '1',
        'payment_public_key'      => 'APP_USR-03af1d3f-b776-43b1-b26e-c422273fef70',
        'payment_secret_key'      => 'APP_USR-5604312687171681-043018-ed0d401372793108b1c02d60484397e1-274004176',
        'payment_client_id'       => '5604312687171681',
        'payment_client_secret'   => 'b2ehBOaa6D5rPEoNtGX8CnUGLEzdrDoC',
        'payment_webhook_secret'  => 'fec988af9262dbb6e87c645aa2a0087653fff96c7e940b334003e28179c5d26b',
        'payment_test_public_key' => 'TEST-0eec7341-d548-4c6f-a7c9-242a579355df',
        'payment_test_secret_key' => 'TEST-5604312687171681-043018-b0f48b2b7685b642d48c331d32ff489d-274004176',
    ];
    foreach ($defaults as $k => $v) {
        $existing = getSetting($k, '');
        if ($existing === '' || $existing === null) setSetting($k, $v);
    }
    setSetting('mp_credentials_seeded', '1');
}
ensureMercadoPagoSeed();

$secretKeys = ['payment_secret_key','payment_test_secret_key','payment_webhook_secret','payment_client_secret','email_api_key','email_smtp_pass','ops_webhook_secret','whatsapp_token'];
$checkboxKeys = ['payment_enabled','payment_sandbox','email_enabled','ops_webhook_enabled','whatsapp_api_enabled','production_mode','security_headers_enabled','hsts_enabled','backup_enabled'];
$fields = [
    'payment_enabled','payment_sandbox','payment_public_key','payment_secret_key','payment_webhook_secret','payment_client_id','payment_client_secret','payment_test_public_key','payment_test_secret_key',
    'email_enabled','email_provider','email_from','email_from_name','email_api_key','email_smtp_host','email_smtp_port','email_smtp_user','email_smtp_pass',
    'analytics_ga4_id','analytics_gtm_id','analytics_meta_pixel_id','analytics_tiktok_pixel_id','analytics_hotjar_id','analytics_utmify_id',
    'ops_webhook_enabled','ops_webhook_url','ops_webhook_secret','whatsapp_api_enabled','whatsapp_phone_id','whatsapp_token','whatsapp_admin_phone',
    'production_mode','security_headers_enabled','hsts_enabled','backup_enabled','backup_path','backup_retention_days','logs_retention_days','legal_terms_url','legal_privacy_url',
];

if (isPost() && csrfVerify()) {
    requireAdmin();
    setSetting('payment_provider', 'mercadopago');
    foreach ($fields as $field) {
        if (in_array($field, $checkboxKeys, true)) {
            setSetting($field, isset($_POST[$field]) && $_POST[$field] === '1' ? '1' : '0');
            continue;
        }
        $value = trim($_POST[$field] ?? '');
        if (in_array($field, $secretKeys, true) && $value === '') continue;
        setSetting($field, $value);
    }
    if (isset($_POST['analytics_ga4_id'])) setSetting('ga_id', trim($_POST['analytics_ga4_id'] ?? ''));
    if (isset($_POST['analytics_meta_pixel_id'])) setSetting('fb_pixel_id', trim($_POST['analytics_meta_pixel_id'] ?? ''));
    flash('success', 'Integrações salvas.');
    redirect('/admin/integracoes');
}

require VIEWS_DIR . '/partials/admin_head.php';
$msg = flash('success');

function adminIntegrationValue(string $key, string $default = ''): string { return integrationSetting($key, $default); }
function adminSecretHint(string $key): string { return adminIntegrationValue($key) !== '' ? 'Credencial já salva. Deixe vazio para manter.' : 'Cole a credencial aqui.'; }
function adminCheck(string $key): string { return integrationEnabled($key) ? 'checked' : ''; }

$cards = [
    ['Mercado Pago', integrationEnabled('payment_enabled'), (integrationEnabled('payment_sandbox') ? integrationSetting('payment_test_secret_key') !== '' : integrationSetting('payment_secret_key') !== ''), 'credit-card'],
    ['Webhook MP', integrationSetting('payment_webhook_secret') !== '', integrationSetting('payment_webhook_secret') !== '', 'radio'],
    ['Email', integrationEnabled('email_enabled'), in_array(integrationSetting('email_provider', 'log'), ['log','mail'], true) || integrationSetting('email_api_key') !== '', 'mail-check'],
    ['Conversões', true, integrationSetting('analytics_ga4_id', integrationSetting('ga_id', '')) !== '' || integrationSetting('analytics_meta_pixel_id', integrationSetting('fb_pixel_id', '')) !== '', 'chart-no-axes-combined'],
    ['Produção', integrationEnabled('production_mode'), integrationEnabled('security_headers_enabled'), 'shield-check'],
];
?>

<?php if ($msg): ?>
<div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)"><i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i><span class="text-sm" style="color:var(--maresia-dark)"><?= e($msg) ?></span></div>
<?php endif; ?>

<div class="grid md:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
    <?php foreach ($cards as [$label, $enabled, $ready, $icon]): ?>
    <div class="admin-card p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:<?= $ready ? 'rgba(122,157,110,.12)' : 'rgba(245,158,11,.12)' ?>;color:<?= $ready ? 'var(--maresia-dark)' : '#B45309' ?>"><i data-lucide="<?= e($icon) ?>" class="w-5 h-5"></i></div>
            <span class="text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider" style="background:<?= $enabled ? 'rgba(122,157,110,.12)' : 'rgba(107,114,128,.12)' ?>;color:<?= $enabled ? 'var(--maresia-dark)' : '#6B7280' ?>"><?= $enabled ? 'Ativo' : 'Standby' ?></span>
        </div>
        <div class="font-display font-bold" style="color:var(--sepia)"><?= e($label) ?></div>
        <div class="text-xs mt-1" style="color:var(--text-muted)"><?= $ready ? 'Pronto para uso' : 'Aguardando credenciais' ?></div>
    </div>
    <?php endforeach; ?>
</div>

<form method="post" class="space-y-6" x-data="{showSecrets:false, sandbox:<?= integrationEnabled('payment_sandbox') ? 'true' : 'false' ?>}">
    <?= csrfField() ?>

    <!-- Mercado Pago -->
    <div class="admin-card p-6 space-y-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h3 class="font-display text-xl font-bold flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="credit-card" class="w-5 h-5" style="color:var(--terracota)"></i>Mercado Pago</h3>
                <p class="text-sm mt-1" style="color:var(--text-muted)">Gateway oficial. Aceita PIX, cartão e boleto.</p>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
                <button type="button" @click="showSecrets=!showSecrets" class="admin-btn admin-btn-secondary text-xs"><i data-lucide="eye" class="w-4 h-4" x-show="!showSecrets"></i><i data-lucide="eye-off" class="w-4 h-4" x-show="showSecrets" x-cloak></i><span x-text="showSecrets ? 'Ocultar credenciais' : 'Mostrar credenciais'"></span></button>
                <button type="button" onclick="testIntegration('payment_webhook_info')" class="admin-btn admin-btn-secondary"><i data-lucide="radio" class="w-4 h-4"></i>Verificar webhook</button>
                <button type="button" onclick="testIntegration('test_payment_webhook')" class="admin-btn admin-btn-secondary"><i data-lucide="send-horizontal" class="w-4 h-4"></i>Simular pagamento</button>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <label class="flex items-start gap-3 p-4 rounded-xl cursor-pointer" style="background:rgba(122,157,110,0.06);border:1px solid rgba(122,157,110,0.2)">
                <input type="checkbox" name="payment_enabled" value="1" <?= adminCheck('payment_enabled') ?> class="w-5 h-5 mt-0.5" style="accent-color:var(--terracota)">
                <span>
                    <span class="block text-sm font-bold" style="color:var(--sepia)">Gateway de pagamento ativo</span>
                    <span class="block text-xs mt-1" style="color:var(--text-muted)">Quando ligado, novas reservas geram cobrança real no Mercado Pago. Desligado, ficam pendentes para confirmação manual.</span>
                </span>
            </label>
            <label class="flex items-start gap-3 p-4 rounded-xl cursor-pointer" style="background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.25)">
                <input type="checkbox" name="payment_sandbox" value="1" <?= adminCheck('payment_sandbox') ?> class="w-5 h-5 mt-0.5" style="accent-color:#B45309" @change="sandbox=$event.target.checked">
                <span>
                    <span class="block text-sm font-bold" style="color:#B45309">Modo TESTE (sandbox)</span>
                    <span class="block text-xs mt-1" style="color:var(--text-muted)">Ligado: usa as credenciais TEST-*. Desligado: usa APP_USR-* (produção real).</span>
                </span>
            </label>
        </div>

        <div class="rounded-xl p-4 flex items-center gap-3" :style="sandbox ? 'background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.3)' : 'background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.3)'">
            <i data-lucide="info" class="w-5 h-5 flex-shrink-0" :style="sandbox ? 'color:#B45309' : 'color:#15803D'"></i>
            <div class="text-sm">
                <strong x-show="sandbox" style="color:#B45309">Ambiente: TESTE</strong>
                <strong x-show="!sandbox" x-cloak style="color:#15803D">Ambiente: PRODUÇÃO</strong>
                <span class="block text-xs mt-0.5" style="color:var(--text-muted)" x-show="sandbox">Pode usar cartões de teste do Mercado Pago. Nenhum dinheiro real é movimentado.</span>
                <span class="block text-xs mt-0.5" style="color:var(--text-muted)" x-show="!sandbox" x-cloak>Cobranças reais. Cartões de teste serão recusados pelo gateway.</span>
            </div>
        </div>

        <!-- Credenciais PRODUÇÃO -->
        <div class="rounded-2xl p-5 space-y-4" style="background:rgba(34,197,94,0.04);border:1px solid rgba(34,197,94,0.18)">
            <div class="flex items-center gap-2"><i data-lucide="rocket" class="w-4 h-4" style="color:#15803D"></i><h4 class="font-display font-bold" style="color:#15803D">Credenciais de PRODUÇÃO (APP_USR-*)</h4></div>
            <div class="grid md:grid-cols-2 gap-4">
                <label><span class="admin-label">Public Key</span><input :type="showSecrets ? 'text' : 'password'" name="payment_public_key" value="<?= e(adminIntegrationValue('payment_public_key')) ?>" class="admin-input font-mono text-xs" autocomplete="off" placeholder="APP_USR-..."></label>
                <label><span class="admin-label">Access Token</span><input :type="showSecrets ? 'text' : 'password'" name="payment_secret_key" value="<?= e(adminIntegrationValue('payment_secret_key')) ?>" class="admin-input font-mono text-xs" autocomplete="off" placeholder="APP_USR-..."></label>
                <label><span class="admin-label">Client ID</span><input type="text" name="payment_client_id" value="<?= e(adminIntegrationValue('payment_client_id')) ?>" class="admin-input font-mono text-xs" autocomplete="off"></label>
                <label><span class="admin-label">Client Secret</span><input :type="showSecrets ? 'text' : 'password'" name="payment_client_secret" value="<?= e(adminIntegrationValue('payment_client_secret')) ?>" class="admin-input font-mono text-xs" autocomplete="off"></label>
            </div>
        </div>

        <!-- Credenciais TESTE -->
        <div class="rounded-2xl p-5 space-y-4" style="background:rgba(245,158,11,0.04);border:1px solid rgba(245,158,11,0.22)">
            <div class="flex items-center gap-2"><i data-lucide="flask-conical" class="w-4 h-4" style="color:#B45309"></i><h4 class="font-display font-bold" style="color:#B45309">Credenciais de TESTE (TEST-*)</h4></div>
            <div class="grid md:grid-cols-2 gap-4">
                <label><span class="admin-label">Public Key (teste)</span><input :type="showSecrets ? 'text' : 'password'" name="payment_test_public_key" value="<?= e(adminIntegrationValue('payment_test_public_key')) ?>" class="admin-input font-mono text-xs" autocomplete="off" placeholder="TEST-..."></label>
                <label><span class="admin-label">Access Token (teste)</span><input :type="showSecrets ? 'text' : 'password'" name="payment_test_secret_key" value="<?= e(adminIntegrationValue('payment_test_secret_key')) ?>" class="admin-input font-mono text-xs" autocomplete="off" placeholder="TEST-..."></label>
            </div>
            <div class="text-xs flex items-start gap-2 mt-2" style="color:var(--text-muted)"><i data-lucide="lightbulb" class="w-3.5 h-3.5 flex-shrink-0 mt-0.5"></i><span>Cartões de teste oficiais: <code class="font-mono">5031 4332 1540 6351</code> CVV <code>123</code> · titular <code>APRO</code> aprova · <code>OTHE</code> recusa · <code>CONT</code> pendente.</span></div>
        </div>

        <!-- Webhook -->
        <div class="rounded-2xl p-5 space-y-3" style="background:rgba(58,107,138,0.04);border:1px solid rgba(58,107,138,0.18)">
            <h4 class="font-display font-bold flex items-center gap-2" style="color:var(--horizonte)"><i data-lucide="radio" class="w-4 h-4"></i>Webhook (notificações de pagamento)</h4>
            <label><span class="admin-label">Chave do webhook</span><input :type="showSecrets ? 'text' : 'password'" name="payment_webhook_secret" value="<?= e(adminIntegrationValue('payment_webhook_secret')) ?>" class="admin-input font-mono text-xs" autocomplete="off"><span class="admin-hint">O Mercado Pago assina cada notificação com este segredo. Configure este mesmo valor no painel do MP em <em>Suas integrações → Webhooks</em>.</span></label>
            <div class="text-xs p-3 rounded-lg" style="background:rgba(255,255,255,0.5);color:var(--text-secondary)"><strong>URL para colar no Mercado Pago:</strong><br><span class="font-mono text-[11px] break-all select-all"><?= e(paymentWebhookUrl()) ?></span></div>
        </div>
    </div>


    <div class="admin-card p-6 space-y-5">
        <div class="flex items-start justify-between gap-4 flex-wrap"><h3 class="font-display text-xl font-bold flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="mail-check" class="w-5 h-5" style="color:var(--horizonte)"></i>Email transacional</h3><button type="button" onclick="testIntegration('test_email')" class="admin-btn admin-btn-secondary"><i data-lucide="send" class="w-4 h-4"></i>Enviar teste</button></div>
        <label class="flex items-center gap-3"><input type="checkbox" name="email_enabled" value="1" <?= adminCheck('email_enabled') ?> class="w-4 h-4" style="accent-color:var(--terracota)"><span class="text-sm font-semibold" style="color:var(--sepia)">Ativar emails de reserva, pagamento e reembolso</span></label>
        <div class="grid md:grid-cols-3 gap-4">
            <label><span class="admin-label">Provedor</span><select name="email_provider" class="admin-input"><option value="log" <?= adminIntegrationValue('email_provider','log')==='log'?'selected':'' ?>>Log interno</option><option value="mail" <?= adminIntegrationValue('email_provider')==='mail'?'selected':'' ?>>PHP mail()</option><option value="resend" <?= adminIntegrationValue('email_provider')==='resend'?'selected':'' ?>>Resend</option><option value="sendgrid" <?= adminIntegrationValue('email_provider')==='sendgrid'?'selected':'' ?>>SendGrid</option><option value="smtp" <?= adminIntegrationValue('email_provider')==='smtp'?'selected':'' ?>>SMTP pré-configurado</option></select><span class="admin-hint">Log interno é seguro para testes. Resend/SendGrid enviam emails reais por API.</span></label>
            <label><span class="admin-label">Email remetente</span><input type="email" name="email_from" value="<?= e(adminIntegrationValue('email_from', APP_EMAIL)) ?>" class="admin-input"></label>
            <label><span class="admin-label">Nome remetente</span><input name="email_from_name" value="<?= e(adminIntegrationValue('email_from_name', APP_NAME)) ?>" class="admin-input"></label>
            <label class="md:col-span-3"><span class="admin-label">API key</span><input type="password" name="email_api_key" class="admin-input" placeholder="<?= e(adminSecretHint('email_api_key')) ?>" autocomplete="new-password"><span class="admin-hint">Cole aqui a chave do Resend ou SendGrid. Deixe vazio para não trocar a chave atual.</span></label>
            <label><span class="admin-label">SMTP host</span><input name="email_smtp_host" value="<?= e(adminIntegrationValue('email_smtp_host')) ?>" class="admin-input"></label>
            <label><span class="admin-label">SMTP porta</span><input name="email_smtp_port" value="<?= e(adminIntegrationValue('email_smtp_port','587')) ?>" class="admin-input"></label>
            <label><span class="admin-label">SMTP usuário</span><input name="email_smtp_user" value="<?= e(adminIntegrationValue('email_smtp_user')) ?>" class="admin-input"></label>
            <label class="md:col-span-3"><span class="admin-label">SMTP senha</span><input type="password" name="email_smtp_pass" class="admin-input" placeholder="<?= e(adminSecretHint('email_smtp_pass')) ?>" autocomplete="new-password"></label>
        </div>
    </div>

    <div class="admin-card p-6 space-y-5">
        <h3 class="font-display text-xl font-bold flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="chart-no-axes-combined" class="w-5 h-5" style="color:var(--maresia-dark)"></i>Analytics, pixels e conversão</h3>
        <div class="grid md:grid-cols-3 gap-4">
            <label><span class="admin-label">GA4 Measurement ID</span><input name="analytics_ga4_id" value="<?= e(adminIntegrationValue('analytics_ga4_id', adminIntegrationValue('ga_id'))) ?>" class="admin-input" placeholder="G-XXXXXXXXXX"><span class="admin-hint">Mede visitas, cliques e conversões no Google Analytics.</span></label>
            <label><span class="admin-label">Google Tag Manager</span><input name="analytics_gtm_id" value="<?= e(adminIntegrationValue('analytics_gtm_id')) ?>" class="admin-input" placeholder="GTM-XXXXXXX"></label>
            <label><span class="admin-label">Meta Pixel ID</span><input name="analytics_meta_pixel_id" value="<?= e(adminIntegrationValue('analytics_meta_pixel_id', adminIntegrationValue('fb_pixel_id'))) ?>" class="admin-input"></label>
            <label><span class="admin-label">TikTok Pixel ID</span><input name="analytics_tiktok_pixel_id" value="<?= e(adminIntegrationValue('analytics_tiktok_pixel_id')) ?>" class="admin-input"></label>
            <label><span class="admin-label">Hotjar ID</span><input name="analytics_hotjar_id" value="<?= e(adminIntegrationValue('analytics_hotjar_id')) ?>" class="admin-input"></label>
            <label><span class="admin-label">UTMify Pixel ID</span><input name="analytics_utmify_id" value="<?= e(adminIntegrationValue('analytics_utmify_id')) ?>" class="admin-input"></label>
        </div>
    </div>

    <div class="admin-card p-6 space-y-5">
        <div class="flex items-start justify-between gap-4 flex-wrap"><h3 class="font-display text-xl font-bold flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="bell-ring" class="w-5 h-5" style="color:#B45309"></i>Notificações operacionais</h3><button type="button" onclick="testIntegration('test_webhook')" class="admin-btn admin-btn-secondary"><i data-lucide="send-horizontal" class="w-4 h-4"></i>Enviar teste</button></div>
        <div class="grid md:grid-cols-2 gap-4">
            <label class="flex items-center gap-3"><input type="checkbox" name="ops_webhook_enabled" value="1" <?= adminCheck('ops_webhook_enabled') ?> class="w-4 h-4" style="accent-color:var(--terracota)"><span class="text-sm font-semibold" style="color:var(--sepia)">Webhook operacional</span></label>
            <label class="flex items-center gap-3"><input type="checkbox" name="whatsapp_api_enabled" value="1" <?= adminCheck('whatsapp_api_enabled') ?> class="w-4 h-4" style="accent-color:var(--terracota)"><span class="text-sm font-semibold" style="color:var(--sepia)">WhatsApp Cloud API</span></label>
        </div>
        <div class="grid md:grid-cols-3 gap-4">
            <label class="md:col-span-2"><span class="admin-label">URL do webhook interno</span><input name="ops_webhook_url" value="<?= e(adminIntegrationValue('ops_webhook_url')) ?>" class="admin-input" placeholder="Slack, Discord, Make, n8n ou endpoint próprio"><span class="admin-hint">Recebe alertas de nova reserva, pagamento confirmado, falha, cancelamento e reembolso.</span></label>
            <label><span class="admin-label">Segredo do webhook</span><input type="password" name="ops_webhook_secret" class="admin-input" placeholder="<?= e(adminSecretHint('ops_webhook_secret')) ?>" autocomplete="new-password"></label>
            <label><span class="admin-label">WhatsApp phone ID</span><input name="whatsapp_phone_id" value="<?= e(adminIntegrationValue('whatsapp_phone_id')) ?>" class="admin-input"><span class="admin-hint">ID do número dentro do Meta Developers.</span></label>
            <label><span class="admin-label">Telefone admin</span><input name="whatsapp_admin_phone" value="<?= e(adminIntegrationValue('whatsapp_admin_phone')) ?>" class="admin-input" placeholder="5582999999999"></label>
            <label><span class="admin-label">WhatsApp token</span><input type="password" name="whatsapp_token" class="admin-input" placeholder="<?= e(adminSecretHint('whatsapp_token')) ?>" autocomplete="new-password"></label>
        </div>
    </div>

    <div class="admin-card p-6 space-y-5">
        <div class="flex items-start justify-between gap-4 flex-wrap"><h3 class="font-display text-xl font-bold flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="shield-check" class="w-5 h-5" style="color:#2563EB"></i>Produção, backups e legal</h3><button type="button" onclick="testIntegration('test_backup')" class="admin-btn admin-btn-secondary"><i data-lucide="hard-drive-download" class="w-4 h-4"></i>Testar backup</button></div>
        <div class="grid md:grid-cols-4 gap-4">
            <label class="flex items-center gap-3"><input type="checkbox" name="production_mode" value="1" <?= adminCheck('production_mode') ?> class="w-4 h-4" style="accent-color:var(--terracota)"><span class="text-sm font-semibold" style="color:var(--sepia)">Modo produção</span></label>
            <label class="flex items-center gap-3"><input type="checkbox" name="security_headers_enabled" value="1" <?= adminCheck('security_headers_enabled') ?> class="w-4 h-4" style="accent-color:var(--terracota)"><span class="text-sm font-semibold" style="color:var(--sepia)">Headers seguros</span></label>
            <label class="flex items-center gap-3"><input type="checkbox" name="hsts_enabled" value="1" <?= adminCheck('hsts_enabled') ?> class="w-4 h-4" style="accent-color:var(--terracota)"><span class="text-sm font-semibold" style="color:var(--sepia)">HSTS HTTPS</span></label>
            <label class="flex items-center gap-3"><input type="checkbox" name="backup_enabled" value="1" <?= adminCheck('backup_enabled') ?> class="w-4 h-4" style="accent-color:var(--terracota)"><span class="text-sm font-semibold" style="color:var(--sepia)">Backups</span></label>
        </div>
        <div class="grid md:grid-cols-3 gap-4">
            <label class="md:col-span-3"><span class="admin-label">Diretório de backup</span><input name="backup_path" value="<?= e(adminIntegrationValue('backup_path', ROOT_DIR . '/storage/backups')) ?>" class="admin-input"><span class="admin-hint">O teste confirma se o servidor consegue gravar arquivos nessa pasta.</span></label>
            <label><span class="admin-label">Retenção de backup em dias</span><input name="backup_retention_days" value="<?= e(adminIntegrationValue('backup_retention_days','30')) ?>" class="admin-input"></label>
            <label><span class="admin-label">Retenção de logs em dias</span><input name="logs_retention_days" value="<?= e(adminIntegrationValue('logs_retention_days','90')) ?>" class="admin-input"></label>
            <label><span class="admin-label">URL termos</span><input name="legal_terms_url" value="<?= e(adminIntegrationValue('legal_terms_url')) ?>" class="admin-input"></label>
            <label class="md:col-span-3"><span class="admin-label">URL privacidade</span><input name="legal_privacy_url" value="<?= e(adminIntegrationValue('legal_privacy_url')) ?>" class="admin-input"></label>
        </div>
    </div>

    <div class="flex justify-end sticky bottom-4">
        <button type="submit" class="admin-btn admin-btn-primary shadow-lg"><i data-lucide="save" class="w-4 h-4"></i>Salvar integrações</button>
    </div>
</form>

<script>
async function testIntegration(action){
    const res = await caminhosApi('<?= url('/api/integrations') ?>?action=' + encodeURIComponent(action), {method:'POST'});
    showToast(res.msg || (res.ok ? 'Teste executado.' : 'Falha no teste.'), res.ok ? 'success' : 'error');
}
</script>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
