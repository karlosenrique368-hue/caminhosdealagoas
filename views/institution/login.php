<?php
$loginPath = currentPath();
$isMacaiokLogin = str_starts_with($loginPath, '/macaiok');
$portalBase = $isMacaiokLogin ? '/macaiok' : '/parceiro';
$portalTitle = $isMacaiokLogin ? 'Macaiok Vivências Pedagógicas' : 'Área do Parceiro';
$portalSubtitle = $isMacaiokLogin ? 'Acesso para escolas acompanharem responsáveis, reservas e pagamentos' : 'Acesso para parceiros cadastrados';
$portalIcon = $isMacaiokLogin ? 'graduation-cap' : 'handshake';
if (isInstitutionUser()) redirect(institutionPortalBasePath(currentInstitution()) . '/dashboard');
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
            $current = currentInstitution();
            if ($isMacaiokLogin && (($current['program'] ?? 'parceiros') !== 'macaiok')) {
                institutionLogout();
                loginThrottleFail($throttleKey);
                $error = 'Este login pertence à área de parceiros. Solicite o acesso Macaiok da escola.';
            } elseif (!$isMacaiokLogin && (($current['program'] ?? 'parceiros') === 'macaiok')) {
                institutionLogout();
                loginThrottleFail($throttleKey);
                $error = 'Este acesso é da Macaiok Vivências Pedagógicas. Use a área Macaiok.';
            } else {
            loginThrottleClear($throttleKey);
                redirect($portalBase . '/dashboard');
            }
        } else {
            loginThrottleFail($throttleKey);
            $error = 'E-mail ou senha inválidos.';
        }
    }
}
$pageTitle = 'Entrar · ' . $portalTitle;
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
<?php if ($isMacaiokLogin): ?><link rel="stylesheet" href="<?= asset('css/macaiok.css') ?>"><?php endif; ?>
<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
</head>
<body class="<?= $isMacaiokLogin ? 'theme-macaiok ' : '' ?>min-h-screen flex items-center justify-center p-4" style="background:linear-gradient(135deg,var(--horizonte),var(--sepia))">
<div class="w-full max-w-md">
    <div class="text-center mb-8">
        <?php if ($isMacaiokLogin): ?>
            <img src="<?= asset('img/macaiok/VerdeEscuro_Horizontal.png') ?>" alt="Macaiok" class="h-14 mx-auto mb-4" style="filter:brightness(0) invert(1)">
        <?php else: ?>
        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background:rgba(255,255,255,0.15)">
            <i data-lucide="<?= e($portalIcon) ?>" class="w-8 h-8 text-white"></i>
        </div>
        <?php endif; ?>
        <h1 class="font-display text-3xl font-bold text-white"><?= e($portalTitle) ?></h1>
        <p class="text-white/70 text-sm mt-2"><?= e($portalSubtitle) ?></p>
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
        <div class="text-center mt-4">
            <a href="<?= url($portalBase . '/esqueci-senha') ?>" class="text-xs font-semibold" style="color:var(--horizonte)">Esqueci minha senha</a>
        </div>
        <p class="text-center text-xs mt-6" style="color:var(--text-muted)">
            <?php if ($isMacaiokLogin): ?>Acesso liberado pela coordenação Macaiok.<?php else: ?>Ainda não é parceiro? <a href="<?= url('/parceiro/cadastro') ?>" class="font-semibold" style="color:var(--terracota)">Criar parceria grátis</a><?php endif; ?>
        </p>
    </div>
    <p class="text-center text-xs mt-6 text-white/50">
        <a href="<?= url('/') ?>" class="hover:text-white transition"><i data-lucide="arrow-left" class="inline w-3 h-3"></i> Voltar ao site</a>
    </p>
</div>
<script>window.addEventListener('load', ()=>window.lucide && window.lucide.createIcons());</script>
</body>
</html>
