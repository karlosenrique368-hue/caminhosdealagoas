<?php
$pageTitle = 'Cadastro de parceiro';
$err = null; $ok = null;

if (isPost() && csrfVerify()) {
    try {
        $r = createPartner([
            'name'         => $_POST['name'] ?? '',
            'email'        => $_POST['email'] ?? '',
            'partner_type' => $_POST['partner_type'] ?? 'individual',
            'cpf'          => $_POST['cpf'] ?? '',
            'phone'        => $_POST['whatsapp'] ?? $_POST['phone'] ?? '',
            'city'         => $_POST['city'] ?? '',
        ], $_POST['password'] ?? '');
        // Auto-login
        institutionLogin($_POST['email'], $_POST['password']);
        flash('success', 'Parceria criada! Seu código: <b>' . $r['code'] . '</b>');
        redirect('/parceiro/dashboard');
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

include VIEWS_DIR . '/partials/public_head.php';
?>

<section class="py-12 sm:py-16" style="min-height:calc(100vh - 80px);background:linear-gradient(180deg,var(--bg-surface) 0%,var(--bg-page) 50%)">
    <div class="max-w-2xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-8">
            <a href="<?= url('/parceiro') ?>" class="inline-flex items-center gap-1 text-sm mb-4" style="color:var(--horizonte)"><i data-lucide="arrow-left" class="w-4 h-4"></i> Voltar</a>
            <div class="inline-block text-[11px] uppercase tracking-widest font-bold px-4 py-1.5 rounded-full mb-3" style="background:rgba(201,107,74,0.12);color:var(--terracota)"><i data-lucide="user-plus" class="w-3.5 h-3.5 inline -mt-0.5"></i> Cadastro gratuito</div>
            <h1 class="font-display text-3xl sm:text-4xl font-bold mb-2" style="color:var(--sepia)">Vamos criar sua parceria</h1>
            <p class="text-sm sm:text-base" style="color:var(--text-secondary)">Preencha abaixo. Em 30 segundos você recebe seu link pessoal de indicação.</p>
        </div>

        <?php if ($err): ?>
            <div class="mb-5 p-4 rounded-xl flex items-center gap-3" style="background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.25);color:#B91C1C">
                <i data-lucide="alert-circle" class="w-5 h-5"></i><span class="text-sm"><?= e($err) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="admin-card p-6 sm:p-8 space-y-5">
            <?= csrfField() ?>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider mb-2" style="color:var(--text-secondary)">Você se cadastra como <span style="color:var(--terracota)">*</span></label>
                <div class="grid sm:grid-cols-2 gap-2" x-data="{sel: 'individual'}">
                    <?php foreach ([
                        ['individual','user','Individual','Só eu'],
                        ['familia','users','Família & amigos','Um grupo fechado'],
                        ['grupo','users-round','Grupo / comunidade','Igreja, escola, clube'],
                        ['revendedor','store','Revenda / agência','Parceria profissional'],
                    ] as $t): ?>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="partner_type" value="<?= $t[0] ?>" x-model="sel" class="peer sr-only" <?= $t[0]==='individual'?'checked':'' ?>>
                            <div class="p-4 rounded-xl border-2 transition flex items-start gap-3" :class="sel==='<?= $t[0] ?>' ? 'border-[var(--terracota)] bg-[rgba(201,107,74,0.08)]' : 'border-[var(--border-default)] hover:border-[var(--text-muted)]'">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" :class="sel==='<?= $t[0] ?>' ? 'bg-[var(--terracota)] text-white' : 'bg-[var(--bg-surface)]'" style="color:<?= 'var(--terracota)' ?>">
                                    <i data-lucide="<?= $t[1] ?>" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-sm" style="color:var(--sepia)"><?= e($t[2]) ?></div>
                                    <div class="text-xs" style="color:var(--text-muted)"><?= e($t[3]) ?></div>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">Nome completo <span style="color:var(--terracota)">*</span></span>
                    <input type="text" name="name" required class="admin-input w-full" placeholder="Seu nome (ou nome do grupo)">
                </label>
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">CPF</span>
                    <input type="text" name="cpf" class="admin-input w-full" placeholder="000.000.000-00">
                </label>
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">Email <span style="color:var(--terracota)">*</span></span>
                    <input type="email" name="email" required class="admin-input w-full" placeholder="seu@email.com">
                </label>
                <label class="block">
                    <span class="text-xs font-bold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">WhatsApp <span style="color:var(--terracota)">*</span></span>
                    <input type="tel" name="whatsapp" required class="admin-input w-full" placeholder="(82) 9 9999-0000">
                </label>
                <label class="block sm:col-span-2">
                    <span class="text-xs font-bold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">Cidade</span>
                    <input type="text" name="city" class="admin-input w-full" placeholder="Maceió / AL">
                </label>
                <label class="block sm:col-span-2">
                    <span class="text-xs font-bold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">Senha de acesso <span style="color:var(--terracota)">*</span></span>
                    <input type="password" name="password" required minlength="6" class="admin-input w-full" placeholder="Mínimo 6 caracteres">
                </label>
            </div>

            <div class="p-4 rounded-xl text-xs" style="background:var(--bg-surface);color:var(--text-secondary)">
                <i data-lucide="shield" class="w-4 h-4 inline -mt-0.5" style="color:var(--maresia-dark)"></i>
                Seus dados são armazenados com segurança. Não compartilhamos com terceiros. Você pode pedir exclusão a qualquer momento.
            </div>

            <button class="btn-primary w-full justify-center">
                <i data-lucide="sparkles" class="w-5 h-5"></i> Criar minha parceria
            </button>

            <p class="text-center text-xs" style="color:var(--text-muted)">Já tem conta? <a href="<?= url('/parceiro/login') ?>" class="font-semibold underline" style="color:var(--horizonte)">Entrar</a></p>
        </form>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
