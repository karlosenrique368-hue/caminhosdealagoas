<?php
$accountTitle = 'Perfil';
$accountTab = 'perfil';
include VIEWS_DIR . '/partials/account_layout.php';

$cid = currentCustomerId();
$msg = '';
if (isPost() && csrfVerify()) {
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 6) $msg = 'Senha precisa ter 6+ caracteres.';
        else {
            dbExec('UPDATE customers SET password_hash=? WHERE id=?', [password_hash($_POST['new_password'], PASSWORD_DEFAULT), $cid]);
            $msg = 'Senha atualizada!';
        }
    } else {
        dbExec('UPDATE customers SET name=?, phone=?, document=?, city=?, state=?, country=? WHERE id=?', [
            trim($_POST['name'] ?? ''),
            trim($_POST['phone'] ?? ''),
            trim($_POST['document'] ?? ''),
            trim($_POST['city'] ?? ''),
            trim($_POST['state'] ?? ''),
            trim($_POST['country'] ?? ''),
            $cid
        ]);
        $_SESSION['customer_name'] = trim($_POST['name'] ?? '');
        $msg = 'Perfil atualizado!';
    }
}
$cust = currentCustomer();
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="rounded-2xl border p-6" style="background:#fff;border-color:var(--border-default)">
        <h2 class="font-display text-xl font-bold mb-5" style="color:var(--sepia)">Dados pessoais</h2>
        <?php if ($msg): ?><div class="p-3 rounded-xl text-sm mb-4" style="background:var(--areia-light);color:var(--text-primary)"><?= e($msg) ?></div><?php endif; ?>

        <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">Nome</span>
                <input type="text" name="name" value="<?= e($cust['name']) ?>" class="input-field w-full" required>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">Telefone</span>
                    <input type="tel" name="phone" value="<?= e($cust['phone']) ?>" class="input-field w-full">
                </label>
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">CPF</span>
                    <input type="text" name="document" value="<?= e($cust['document']) ?>" class="input-field w-full">
                </label>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">Cidade</span>
                    <input type="text" name="city" value="<?= e($cust['city']) ?>" class="input-field w-full">
                </label>
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">UF</span>
                    <input type="text" name="state" value="<?= e($cust['state']) ?>" maxlength="2" class="input-field w-full">
                </label>
                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">País</span>
                    <input type="text" name="country" value="<?= e($cust['country']) ?>" class="input-field w-full">
                </label>
            </div>
            <button class="btn-primary w-full justify-center">Salvar alterações</button>
        </form>
    </div>

    <div class="rounded-2xl border p-6" style="background:#fff;border-color:var(--border-default)">
        <h2 class="font-display text-xl font-bold mb-5" style="color:var(--sepia)">Alterar senha</h2>
        <form method="POST" class="space-y-4">
            <?= csrfField() ?>
            <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--text-secondary)">Nova senha</span>
                <input type="password" name="new_password" minlength="6" required class="input-field w-full" placeholder="Mínimo 6 caracteres">
            </label>
            <button class="btn-primary w-full justify-center">Atualizar senha</button>
        </form>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/account_layout_end.php'; ?>
