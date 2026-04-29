<?php
requireInstitution();
$i = currentInstitution();
$pageTitle = 'Conta da instituição';

if (isPost() && csrfVerify()) {
    if (!institutionRoleCan('manage_users')) {
        flash('error','Somente o dono da conta pode editar.');
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'profile') {
            dbExec("UPDATE institution_users SET name=? WHERE id=?", [trim($_POST['name'] ?? ''), $i['user_id']]);
            $_SESSION['inst_user_name'] = trim($_POST['name'] ?? '');
            flash('success','Perfil atualizado.');
        } elseif ($action === 'password') {
            $cur = dbOne("SELECT password_hash FROM institution_users WHERE id=?", [$i['user_id']]);
            $old = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            if (!password_verify($old, $cur['password_hash'])) { flash('error','Senha atual incorreta.'); }
            elseif (strlen($new) < PASSWORD_MIN_LENGTH) { flash('error','A nova senha precisa ter ao menos ' . PASSWORD_MIN_LENGTH . ' caracteres.'); }
            else {
                dbExec("UPDATE institution_users SET password_hash=? WHERE id=?", [password_hash($new, PASSWORD_DEFAULT), $i['user_id']]);
                flash('success','Senha atualizada.');
            }
        }
    }
    redirect('/instituicao/perfil');
}

$user = dbOne("SELECT * FROM institution_users WHERE id=?", [$i['user_id']]);
$inst = dbOne("SELECT * FROM institutions WHERE id=?", [$i['id']]);
$team = dbAll("SELECT * FROM institution_users WHERE institution_id=? ORDER BY role, name", [$i['id']]);
$flashOk  = flash('success');
$flashErr = flash('error');

include VIEWS_DIR . '/partials/institution_head.php';
?>
<?php if ($flashOk): ?><div class="mb-4 p-3 rounded-xl flex items-center gap-2 text-sm" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3);color:var(--maresia-dark)"><i data-lucide="check-circle" class="w-4 h-4"></i><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="mb-4 p-3 rounded-xl flex items-center gap-2 text-sm" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#B91C1C"><i data-lucide="alert-circle" class="w-4 h-4"></i><?= e($flashErr) ?></div><?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="admin-card p-6">
        <h2 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Meus dados</h2>
        <form method="POST" class="space-y-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="profile">
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Nome</span><input type="text" name="name" value="<?= e($user['name']) ?>" class="admin-input w-full"></label>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">E-mail</span><input type="email" value="<?= e($user['email']) ?>" disabled class="admin-input w-full" style="opacity:0.6"></label>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Permissão</span><input type="text" value="<?= ['owner'=>'Dono(a)','manager'=>'Gestor(a)','viewer'=>'Consulta'][$user['role']] ?? $user['role'] ?>" disabled class="admin-input w-full" style="opacity:0.6"></label>
            <button class="admin-btn admin-btn-primary w-full justify-center">Salvar</button>
        </form>
    </div>

    <div class="admin-card p-6">
        <h2 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Trocar senha</h2>
        <form method="POST" class="space-y-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="password">
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Senha atual</span><input type="password" name="current_password" required class="admin-input w-full"></label>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Nova senha (mín <?= PASSWORD_MIN_LENGTH ?>)</span><input type="password" name="new_password" required minlength="<?= PASSWORD_MIN_LENGTH ?>" class="admin-input w-full"></label>
            <button class="admin-btn admin-btn-secondary w-full justify-center"><i data-lucide="key" class="w-4 h-4"></i>Atualizar</button>
        </form>
    </div>

    <div class="admin-card p-6 lg:col-span-2">
        <h2 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Instituição</h2>
        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div><span class="text-xs uppercase font-semibold" style="color:var(--text-muted)">Nome</span><div class="font-semibold"><?= e($inst['name']) ?></div></div>
            <div><span class="text-xs uppercase font-semibold" style="color:var(--text-muted)">Tipo</span><div><?= e($inst['type']) ?></div></div>
            <div><span class="text-xs uppercase font-semibold" style="color:var(--text-muted)">CNPJ</span><div class="font-mono"><?= e($inst['cnpj'] ?: '—') ?></div></div>
            <div><span class="text-xs uppercase font-semibold" style="color:var(--text-muted)">Site</span><div><?= $inst['website'] ? '<a href="'.e($inst['website']).'" target="_blank" style="color:var(--horizonte)">'.e($inst['website']).'</a>' : '—' ?></div></div>
            <div><span class="text-xs uppercase font-semibold" style="color:var(--text-muted)">Desconto parceiro</span><div class="font-semibold" style="color:var(--maresia-dark)"><?= number_format($inst['discount_percent'],0) ?>%</div></div>
            <div><span class="text-xs uppercase font-semibold" style="color:var(--text-muted)">Comissão</span><div class="font-semibold" style="color:var(--terracota)"><?= number_format($inst['commission_percent'],0) ?>%</div></div>
        </div>
        <p class="text-xs mt-4" style="color:var(--text-muted)">Para alterar dados da instituição, fale com a equipe Caminhos.</p>
    </div>

    <div class="admin-card p-6 lg:col-span-2">
        <h2 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Equipe (<?= count($team) ?>)</h2>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead><tr><th>Nome</th><th>E-mail</th><th>Permissão</th><th>Último acesso</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($team as $t): ?>
                <tr>
                    <td class="font-semibold"><?= e($t['name']) ?></td>
                    <td class="text-sm"><?= e($t['email']) ?></td>
                    <td><span class="badge badge-info"><?= ['owner'=>'Dono(a)','manager'=>'Gestor(a)','viewer'=>'Consulta'][$t['role']] ?? $t['role'] ?></span></td>
                    <td class="text-xs"><?= $t['last_login_at'] ? formatDate($t['last_login_at'],'d/m/Y H:i') : 'nunca' ?></td>
                    <td><span class="badge badge-<?= $t['active']?'success':'muted' ?>"><?= $t['active']?'Ativo':'Inativo' ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-xs mt-3" style="color:var(--text-muted)">Para adicionar ou remover membros, solicite à equipe Caminhos.</p>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/institution_foot.php';
