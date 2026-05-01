<?php
/**
 * Esqueci minha senha — admin
 */
$step = 'request';
$message = null;
$token = $_GET['token'] ?? null;
if ($token) {
    $row = passwordResetConsume('admin', (string)$token, false);
    if ($row) { $step = 'reset'; }
    else { $step = 'invalid'; $message = 'Link expirado ou já utilizado.'; }
}
$pageTitle = 'Recuperar senha · Admin';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title><?= e($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
<script src="<?= asset('js/app.js') ?>" defer></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4" style="background:linear-gradient(135deg,#1E3A52,#C96B4A)">
<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <h1 class="font-display text-3xl font-bold text-white">Recuperar senha (admin)</h1>
    </div>
    <div class="rounded-2xl p-8" style="background:var(--bg-card);box-shadow:0 20px 60px rgba(0,0,0,0.3)">
        <?php if ($message): ?>
            <div class="mb-4 p-3 rounded-xl text-sm" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#B91C1C"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($step === 'request' || $step === 'invalid'): ?>
            <form data-ajax action="<?= url('/api/admin-forgot') ?>" method="POST" class="space-y-4">
                <?= csrfField() ?>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-muted)">E-mail do administrador</label>
                    <input type="email" name="email" required class="admin-input w-full">
                </div>
                <button type="submit" class="btn-primary w-full justify-center">
                    <i data-lucide="send-horizontal" class="w-4 h-4"></i> Enviar link
                </button>
            </form>
        <?php else: ?>
            <form data-ajax action="<?= url('/api/admin-reset') ?>" method="POST" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-muted)">Nova senha</label>
                    <input type="password" name="password" required minlength="8" class="admin-input w-full">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-muted)">Confirmar</label>
                    <input type="password" name="password_confirm" required minlength="8" class="admin-input w-full">
                </div>
                <button type="submit" class="btn-primary w-full justify-center">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i> Redefinir
                </button>
            </form>
        <?php endif; ?>
        <div class="mt-6 pt-6 border-t text-center text-sm" style="border-color:var(--border-default);color:var(--text-secondary)">
            <a href="<?= url('/admin/login') ?>" class="font-bold" style="color:var(--terracota)">
                <i data-lucide="arrow-left" class="inline w-3 h-3"></i> Voltar
            </a>
        </div>
    </div>
</div>
<script>window.addEventListener('load', () => window.lucide && window.lucide.createIcons());</script>
</body>
</html>
