<?php
$pageTitle = 'Entrar';
$solidNav = true;
$err = '';
if (isPost()) {
    if (!csrfVerify()) $err = 'Token CSRF inválido.';
    else {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        if (customerLogin($email, $pass)) {
            $redirect = $_GET['redirect'] ?? '/conta';
            redirect($redirect);
        }
        $err = 'E-mail ou senha incorretos.';
    }
}
include VIEWS_DIR . '/partials/public_head.php';
?>

<section class="min-h-screen flex items-center justify-center py-24 relative overflow-hidden" style="background:linear-gradient(135deg,var(--bg-surface),var(--areia-light))">
    <img src="<?= asset('brand/selo-azul.png') ?>" class="seal-watermark xl" style="top:-100px;left:-100px" alt="">
    <img src="<?= asset('brand/selo-terracota.png') ?>" class="seal-watermark lg reverse" style="bottom:-80px;right:-80px" alt="">

    <div class="max-w-md w-full mx-auto px-6 relative z-10">
        <div class="glass-card p-8 md:p-10 rounded-3xl border shadow-2xl" style="background:rgba(255,255,255,0.95);backdrop-filter:blur(16px);border-color:var(--border-default)">
            <div class="text-center mb-8">
                <img src="<?= asset('brand/selo-azul.png') ?>" class="seal-rotate mx-auto mb-4" style="width:72px;height:72px" alt="">
                <h1 class="font-display text-3xl font-bold mb-2" style="color:var(--sepia)">Bem-vindo de volta</h1>
                <p class="text-sm" style="color:var(--text-muted)">Entre para gerenciar suas viagens</p>
            </div>

            <?php if ($err): ?>
                <div class="mb-5 p-3 rounded-xl text-sm" style="background:rgba(239,68,68,0.1);color:#DC2626;border:1px solid rgba(239,68,68,0.2)">
                    <?= e($err) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">E-mail</span>
                    <input type="email" name="email" required autofocus class="input-field w-full" placeholder="seu@email.com">
                </label>
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">Senha</span>
                    <input type="password" name="password" required class="input-field w-full" placeholder="••••••••">
                </label>
                <button type="submit" class="btn-primary w-full justify-center">
                    <i data-lucide="log-in" class="w-4 h-4"></i> Entrar
                </button>
            </form>

            <div class="mt-6 pt-6 border-t text-center text-sm" style="border-color:var(--border-default);color:var(--text-secondary)">
                Não tem uma conta?
                <a href="<?= url('/conta/registrar') ?>" class="font-bold" style="color:var(--terracota)">Criar agora</a>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
