<?php
if (isInstitutionUser()) redirect('/parceiro/dashboard');
$error = null;

if (isPost()) {
    if (!csrfVerify()) { $error = 'Token inválido.'; }
    else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $throttleKey = loginThrottleKey('partner', $email);
        if (loginThrottleBlocked($throttleKey)) {
            $error = 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';
        } elseif (institutionLogin($email, $pass)) {
            loginThrottleClear($throttleKey);
            redirect('/parceiro/dashboard');
        } else {
            loginThrottleFail($throttleKey);
            $error = 'E-mail ou senha inválidos.';
        }
    }
}
$pageTitle = 'Entrar · Área do Parceiro';
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
<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4" style="background:linear-gradient(135deg,var(--horizonte),var(--sepia))">
<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background:rgba(255,255,255,0.15)">
            <i data-lucide="handshake" class="w-8 h-8 text-white"></i>
        </div>
        <h1 class="font-display text-3xl font-bold text-white">Área do Parceiro</h1>
        <p class="text-white/70 text-sm mt-2">Acesso para parceiros cadastrados</p>
    </div>

    <div class="rounded-2xl p-8" style="background:var(--bg-card);box-shadow:0 20px 60px rgba(0,0,0,0.3)">
        <?php if ($error): ?>
        <div class="mb-4 p-3 rounded-xl flex items-center gap-2 text-sm" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#B91C1C">
            <i data-lucide="alert-circle" class="w-4 h-4"></i><?= e($error) ?>
        </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color:var(--text-muted)">E-mail</label>
                <input type="email" name="email" required class="admin-input w-full" placeholder="voce@email.com">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider mb-1.5" style="color:var(--text-muted)">Senha</label>
                <input type="password" name="password" required class="admin-input w-full" placeholder="••••••••">
            </div>
            <button type="submit" class="admin-btn admin-btn-primary w-full justify-center"><i data-lucide="log-in" class="w-4 h-4"></i>Entrar</button>
        </form>
        <p class="text-center text-xs mt-6" style="color:var(--text-muted)">
            Ainda não é parceiro? <a href="<?= url('/parceiro/cadastro') ?>" class="font-semibold" style="color:var(--terracota)">Criar parceria grátis</a>
        </p>
    </div>
    <p class="text-center text-xs mt-6 text-white/50">
        <a href="<?= url('/') ?>" class="hover:text-white transition"><i data-lucide="arrow-left" class="inline w-3 h-3"></i> Voltar ao site</a>
    </p>
</div>
<script>window.addEventListener('load', ()=>window.lucide && window.lucide.createIcons());</script>
</body>
</html>
