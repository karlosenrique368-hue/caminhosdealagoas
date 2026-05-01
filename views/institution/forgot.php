<?php
/**
 * Esqueci minha senha — institution / macaiok (escopo: institution)
 */
$loginPath = currentPath();
$isMacaiokFlow = str_starts_with($loginPath, '/macaiok');
$portalBase = $isMacaiokFlow ? '/macaiok' : '/parceiro';
$portalTitle = $isMacaiokFlow ? 'Macaiok Vivências Pedagógicas' : 'Área do Parceiro';

$step = 'request';
$message = null;
$token = $_GET['token'] ?? null;
if ($token) {
    $row = passwordResetConsume('institution', (string)$token, false);
    if ($row) { $step = 'reset'; }
    else { $step = 'invalid'; $message = 'Este link expirou ou já foi utilizado. Solicite um novo abaixo.'; }
}
$pageTitle = 'Recuperar senha · ' . $portalTitle;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
<?php if ($isMacaiokFlow): ?><link rel="stylesheet" href="<?= asset('css/macaiok.css') ?>"><?php endif; ?>
<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
<script src="<?= asset('js/app.js') ?>" defer></script>
</head>
<body class="<?= $isMacaiokFlow ? 'theme-macaiok ' : '' ?>min-h-screen flex items-center justify-center p-4" style="background:linear-gradient(135deg,var(--horizonte),var(--sepia))">
<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <h1 class="font-display text-3xl font-bold text-white">Recuperar senha</h1>
        <p class="text-white/70 text-sm mt-2"><?= e($portalTitle) ?></p>
    </div>
    <div class="rounded-2xl p-8" style="background:var(--bg-card);box-shadow:0 20px 60px rgba(0,0,0,0.3)">
        <?php if ($message): ?>
            <div class="mb-4 p-3 rounded-xl flex items-start gap-2 text-sm" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#B91C1C">
                <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0 mt-0.5"></i><span><?= e($message) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($step === 'request' || $step === 'invalid'): ?>
            <p class="text-sm mb-5" style="color:var(--text-secondary)">Digite o e-mail cadastrado para receber o link de redefinição.</p>
            <form data-ajax action="<?= url('/api/institution-forgot') ?>" method="POST" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="origin" value="<?= e($isMacaiokFlow ? 'macaiok' : 'parceiro') ?>">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-muted)">E-mail</label>
                    <input type="email" name="email" required class="admin-input w-full" placeholder="voce@email.com">
                </div>
                <button type="submit" class="admin-btn admin-btn-primary w-full justify-center">
                    <i data-lucide="send-horizontal" class="w-4 h-4"></i> Enviar link
                </button>
            </form>
        <?php else: ?>
            <p class="text-sm mb-5" style="color:var(--text-secondary)">Crie uma nova senha.</p>
            <form data-ajax action="<?= url('/api/institution-reset') ?>" method="POST" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-muted)">Nova senha</label>
                    <input type="password" name="password" required minlength="8" class="admin-input w-full" placeholder="Mínimo 8 caracteres">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-muted)">Confirmar senha</label>
                    <input type="password" name="password_confirm" required minlength="8" class="admin-input w-full" placeholder="Repita a senha">
                </div>
                <button type="submit" class="admin-btn admin-btn-primary w-full justify-center">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i> Redefinir senha
                </button>
            </form>
        <?php endif; ?>
        <div class="mt-6 pt-6 border-t text-center text-sm" style="border-color:var(--border-default);color:var(--text-secondary)">
            <a href="<?= url($portalBase . '/login') ?>" class="font-bold" style="color:var(--terracota)">
                <i data-lucide="arrow-left" class="inline w-3 h-3"></i> Voltar ao login
            </a>
        </div>
    </div>
</div>
<script>window.addEventListener('load', () => window.lucide && window.lucide.createIcons());</script>
</body>
</html>
