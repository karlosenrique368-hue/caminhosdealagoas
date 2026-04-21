<?php
if (isAdmin()) redirect('/admin/dashboard');

$error = null;
if (isPost()) {
    if (!csrfVerify()) {
        $error = 'Token CSRF inválido. Recarregue a página.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (adminLogin($email, $password)) {
            redirect('/admin/dashboard');
        } else {
            $error = 'E-mail ou senha incorretos.';
        }
    }
}
$flashError = flash('error');
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Login · Admin — <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,600&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
<style>
body { font-family: Inter, system-ui, sans-serif; }
.bg-scene { background: linear-gradient(135deg, #1E3A52 0%, #2D5470 50%, #C96B4A 100%); }
.bg-scene::before { content:''; position:absolute; inset:0; background-image:radial-gradient(circle at 20% 30%, rgba(255,255,255,0.1) 0%, transparent 50%), radial-gradient(circle at 80% 70%, rgba(244,228,193,0.15) 0%, transparent 50%); }
</style>
</head>
<body class="min-h-screen flex">

<!-- Left: visual -->
<div class="hidden lg:flex lg:w-1/2 bg-scene relative items-center justify-center p-12 overflow-hidden">
    <div class="relative z-10 text-white max-w-md">
        <div class="mb-8">
            <img src="<?= asset('brand/logo-areia.png') ?>" alt="Caminhos de Alagoas" style="height:56px;width:auto">
        </div>
        <h1 class="font-display text-5xl font-bold leading-tight mb-5">
            Painel <span class="italic" style="color:var(--areia-light)">administrativo</span>
        </h1>
        <p class="text-lg text-white/75 leading-relaxed mb-10">
            Gerencie passeios, pacotes, reservas e conteúdo da plataforma <?= e(APP_NAME) ?>.
        </p>
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 rounded-xl" style="background:rgba(255,255,255,0.08);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.1)">
                <i data-lucide="shield-check" class="w-5 h-5 mb-2" style="color:var(--areia-light)"></i>
                <div class="text-xs font-bold tracking-wider uppercase text-white/60 mb-1">Seguro</div>
                <div class="text-sm font-semibold">Acesso protegido</div>
            </div>
            <div class="p-4 rounded-xl" style="background:rgba(255,255,255,0.08);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.1)">
                <i data-lucide="zap" class="w-5 h-5 mb-2" style="color:var(--areia-light)"></i>
                <div class="text-xs font-bold tracking-wider uppercase text-white/60 mb-1">Rápido</div>
                <div class="text-sm font-semibold">Interface otimizada</div>
            </div>
        </div>
    </div>
    <div class="absolute bottom-8 left-12 text-white/40 text-xs tracking-widest uppercase">
        © <?= date('Y') ?> <?= e(APP_NAME) ?>
    </div>
</div>

<!-- Right: form -->
<div class="flex-1 flex items-center justify-center p-6 lg:p-12" style="background:var(--bg-page)">
    <div class="w-full max-w-md">
        <a href="<?= url('/') ?>" class="inline-flex items-center gap-2 text-sm mb-8 hover:opacity-75" style="color:var(--text-secondary)">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Voltar ao site
        </a>

        <h2 class="font-display text-3xl font-bold mb-2" style="color:var(--sepia)">Bem-vindo de volta</h2>
        <p class="text-sm mb-8" style="color:var(--text-secondary)">Faça login para acessar o painel.</p>

        <?php if ($error || $flashError): ?>
            <div class="mb-4 p-4 rounded-xl flex items-start gap-3" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2)">
                <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5 text-red-500"></i>
                <span class="text-sm text-red-700"><?= e($error ?: $flashError) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-5">
            <?= csrfField() ?>
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">E-mail</label>
                <div class="relative">
                    <i data-lucide="mail" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2" style="color:var(--text-muted)"></i>
                    <input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>" class="admin-input pl-11" placeholder="admin@caminhosdealagoas.com">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Senha</label>
                <div class="relative" x-data="{show:false}">
                    <i data-lucide="lock" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2" style="color:var(--text-muted)"></i>
                    <input :type="show?'text':'password'" name="password" required class="admin-input pl-11 pr-11" placeholder="••••••••">
                    <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 p-1" style="color:var(--text-muted)">
                        <i :data-lucide="show?'eye-off':'eye'" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-primary w-full">
                <i data-lucide="log-in" class="w-5 h-5"></i> Entrar
            </button>
        </form>

        <div class="mt-8 p-4 rounded-xl text-xs" style="background:var(--bg-surface);border:1px dashed var(--border-default);color:var(--text-secondary)">
            <strong style="color:var(--sepia)">Acesso padrão:</strong> admin@caminhosdealagoas.com / admin123
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>
