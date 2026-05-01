<?php
$pageTitle = 'Macaiok Vivências';

if (isPost() && csrfVerify()) {
    requireAdmin();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'save_school') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            flash('error', 'Informe o nome da escola.');
            redirect('/admin/macaiok');
        }

        $data = [
            'name' => $name,
            'program' => 'macaiok',
            'type' => 'escola',
            'partner_type' => 'instituicao',
            'cnpj' => trim($_POST['cnpj'] ?? ''),
            'school_code' => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $_POST['school_code'] ?? '')) ?: strtoupper(substr(md5($name . microtime()), 0, 8)),
            'contact_name' => trim($_POST['contact_name'] ?? ''),
            'coordinator_name' => trim($_POST['coordinator_name'] ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? ''),
            'contact_phone' => trim($_POST['contact_phone'] ?? ''),
            'whatsapp' => trim($_POST['whatsapp'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'discount_percent' => (float)($_POST['discount_percent'] ?? 0),
            'commission_percent' => 0,
            'bookings_threshold' => 0,
            'allow_group_checkout' => 1,
            'parent_share_note' => trim($_POST['parent_share_note'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'active' => isset($_POST['active']) ? 1 : 0,
        ];

        if ($id) {
            $sets = [];
            foreach ($data as $k => $_) $sets[] = "`$k`=?";
            $values = array_values($data);
            $values[] = $id;
            dbExec("UPDATE institutions SET " . implode(',', $sets) . " WHERE id=? AND program='macaiok'", $values);
            $schoolId = $id;
        } else {
            $data['referral_code'] = generateReferralCode();
            $data['slug'] = slugify($name) . '-' . substr(md5(uniqid('', true)), 0, 6);
            $fields = array_keys($data);
            $schoolId = dbInsert('INSERT INTO institutions (' . implode(',', array_map(fn($f) => "`$f`", $fields)) . ') VALUES (' . implode(',', array_fill(0, count($fields), '?')) . ')', array_values($data));
        }

        $userName = trim($_POST['user_name'] ?? '') ?: ($data['coordinator_name'] ?: ($data['contact_name'] ?: $name));
        $userEmail = strtolower(trim($_POST['user_email'] ?? $data['contact_email']));
        $userPassword = (string)($_POST['user_password'] ?? '');
        if ($userEmail !== '') {
            $existingUser = dbOne('SELECT id FROM institution_users WHERE institution_id=? AND email=? LIMIT 1', [$schoolId, $userEmail]);
            if ($existingUser) {
                if ($userPassword !== '' && strlen($userPassword) >= PASSWORD_MIN_LENGTH) {
                    dbExec('UPDATE institution_users SET name=?, password_hash=?, active=1 WHERE id=?', [$userName, password_hash($userPassword, PASSWORD_DEFAULT), $existingUser['id']]);
                } else {
                    dbExec('UPDATE institution_users SET name=?, active=1 WHERE id=?', [$userName, $existingUser['id']]);
                }
            } elseif ($userPassword !== '' && strlen($userPassword) >= PASSWORD_MIN_LENGTH) {
                dbInsert("INSERT INTO institution_users (institution_id, name, email, password_hash, role, active) VALUES (?, ?, ?, ?, 'owner', 1)", [$schoolId, $userName, $userEmail, password_hash($userPassword, PASSWORD_DEFAULT)]);
            }
        }

        flash('success', 'Escola Macaiok salva.');
    }

    if ($action === 'delete_school' && $id) {
        dbExec("DELETE FROM institutions WHERE id=? AND program='macaiok'", [$id]);
        flash('success', 'Escola removida do Macaiok.');
    }

    redirect('/admin/macaiok');
}

require VIEWS_DIR . '/partials/admin_head.php';
$flashOk = flash('success');
$flashErr = flash('error');

$totals = dbOne("SELECT COUNT(*) AS schools, SUM(active=1) AS active_schools FROM institutions WHERE program='macaiok'");
$bookingStats = dbOne("SELECT COUNT(b.id) AS bookings, SUM(b.payment_status='paid') AS paid, SUM(b.payment_status='pending') AS pending, COALESCE(SUM(CASE WHEN b.payment_status='paid' THEN b.total ELSE 0 END),0) AS revenue, COALESCE(SUM(CASE WHEN b.payment_status='paid' THEN b.adults+b.children+b.infants ELSE 0 END),0) AS people FROM bookings b JOIN institutions i ON i.id=b.institution_id WHERE i.program='macaiok'");
$schools = dbAll("SELECT i.*, COUNT(DISTINCT u.id) AS users_count, COUNT(b.id) AS bookings_count, SUM(b.payment_status='paid') AS paid_count, SUM(b.payment_status='pending') AS pending_count, COALESCE(SUM(CASE WHEN b.payment_status='paid' THEN b.total ELSE 0 END),0) AS revenue FROM institutions i LEFT JOIN institution_users u ON u.institution_id=i.id LEFT JOIN bookings b ON b.institution_id=i.id WHERE i.program='macaiok' GROUP BY i.id ORDER BY i.created_at DESC");
$recent = dbAll("SELECT b.*, c.name AS customer_name, c.email AS customer_email, i.name AS school_name FROM bookings b JOIN institutions i ON i.id=b.institution_id LEFT JOIN customers c ON c.id=b.customer_id WHERE i.program='macaiok' ORDER BY b.created_at DESC LIMIT 8");
?>

<?php if ($flashOk): ?><div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)"><i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i><span class="text-sm" style="color:var(--maresia-dark)"><?= e($flashOk) ?></span></div><?php endif; ?>
<?php if ($flashErr): ?><div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2)"><i data-lucide="alert-circle" class="w-5 h-5 text-red-500"></i><span class="text-sm text-red-700"><?= e($flashErr) ?></span></div><?php endif; ?>

<div class="mb-6 rounded-2xl p-6 sm:p-8 relative overflow-hidden" style="background:linear-gradient(135deg,var(--horizonte),var(--sepia));color:#fff">
    <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
        <div>
            <span class="text-[11px] uppercase tracking-[0.24em] font-bold text-white/70">Macaiok Vivências Pedagógicas</span>
            <h2 class="font-display text-2xl sm:text-3xl font-bold mt-2">Controle escolar, pagamento dos pais e operação Caminhos no mesmo painel</h2>
            <p class="text-sm text-white/80 mt-2 max-w-3xl">Inspirado em estudos do meio e educação ao ar livre: cada escola recebe acesso próprio, compartilha links com responsáveis e a equipe acompanha pagamentos confirmados, pendentes e volume por vivência.</p>
        </div>
        <a href="<?= url('/macaiok') ?>" target="_blank" class="admin-btn bg-white text-sm justify-center" style="color:var(--sepia)"><i data-lucide="external-link" class="w-4 h-4"></i>Ver portal</a>
    </div>
</div>

<div class="grid grid-cols-2 xl:grid-cols-5 gap-3 sm:gap-5 mb-6">
    <div class="admin-card p-5"><div class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Escolas</div><div class="font-display text-2xl font-bold" style="color:var(--sepia)"><?= (int)($totals['schools'] ?? 0) ?></div><div class="text-[11px]" style="color:var(--text-muted)"><?= (int)($totals['active_schools'] ?? 0) ?> ativas</div></div>
    <div class="admin-card p-5"><div class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Reservas</div><div class="font-display text-2xl font-bold" style="color:var(--horizonte)"><?= (int)($bookingStats['bookings'] ?? 0) ?></div><div class="text-[11px]" style="color:var(--text-muted)">responsáveis</div></div>
    <div class="admin-card p-5"><div class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Pagas</div><div class="font-display text-2xl font-bold" style="color:var(--maresia-dark)"><?= (int)($bookingStats['paid'] ?? 0) ?></div><div class="text-[11px]" style="color:var(--text-muted)"><?= (int)($bookingStats['people'] ?? 0) ?> participantes</div></div>
    <div class="admin-card p-5"><div class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Pendentes</div><div class="font-display text-2xl font-bold" style="color:#D97706"><?= (int)($bookingStats['pending'] ?? 0) ?></div><div class="text-[11px]" style="color:var(--text-muted)">aguardando pagamento</div></div>
    <div class="admin-card p-5"><div class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Receita confirmada</div><div class="font-display text-2xl font-bold" style="color:var(--terracota)"><?= formatBRL($bookingStats['revenue'] ?? 0) ?></div><div class="text-[11px]" style="color:var(--text-muted)">Macaiok</div></div>
</div>

<div class="grid xl:grid-cols-[1fr_420px] gap-6">
    <div class="space-y-6">
        <div class="admin-card overflow-hidden">
            <div class="p-5 border-b flex items-center justify-between gap-3" style="border-color:var(--border-default)">
                <div><h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Escolas Macaiok</h3><p class="text-xs" style="color:var(--text-muted)">Cada escola usa /macaiok/login e acompanha seus próprios responsáveis.</p></div>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead><tr><th>Escola</th><th>Código</th><th>Acessos</th><th>Reservas</th><th>Pagas</th><th>Pendentes</th><th>Receita</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($schools as $s): ?>
                    <tr>
                        <td><div class="font-semibold" style="color:var(--sepia)"><?= e($s['name']) ?></div><div class="text-xs" style="color:var(--text-muted)"><?= e($s['contact_email'] ?: $s['whatsapp'] ?: '—') ?></div></td>
                        <td><code class="font-mono text-xs font-bold" style="color:var(--terracota)"><?= e($s['school_code'] ?: $s['referral_code']) ?></code></td>
                        <td class="text-sm"><?= (int)$s['users_count'] ?></td>
                        <td class="text-sm font-semibold"><?= (int)$s['bookings_count'] ?></td>
                        <td><span class="badge badge-success"><?= (int)$s['paid_count'] ?></span></td>
                        <td><span class="badge badge-warning"><?= (int)$s['pending_count'] ?></span></td>
                        <td class="font-semibold" style="color:var(--sepia)"><?= formatBRL($s['revenue']) ?></td>
                        <td><span class="badge badge-<?= $s['active'] ? 'success' : 'muted' ?>"><?= $s['active'] ? 'Ativa' : 'Inativa' ?></span></td>
                        <td class="actions-cell">
                            <div class="flex justify-end gap-1">
                                <a href="<?= url('/admin/macaiok?edit=' . (int)$s['id']) ?>" class="action-chip chip-edit"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i>Editar</a>
                                <form method="POST" class="inline" onsubmit="return confirm('Remover esta escola Macaiok?')"><?= csrfField() ?><input type="hidden" name="action" value="delete_school"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="action-chip chip-danger"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$schools): ?><tr><td colspan="9" class="text-center py-10" style="color:var(--text-muted)">Nenhuma escola Macaiok cadastrada ainda.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="p-5 border-b" style="border-color:var(--border-default)"><h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Pagamentos recentes</h3></div>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead><tr><th>Código</th><th>Escola</th><th>Responsável</th><th>Vivência</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $b): $map=['paid'=>['success','Pago'],'pending'=>['warning','Pendente'],'failed'=>['danger','Falhou'],'cancelled'=>['danger','Cancelado'],'refunded'=>['info','Reembolsado']]; $bm=$map[$b['payment_status']] ?? ['muted',$b['payment_status']]; ?>
                    <tr><td class="font-mono text-xs"><?= e($b['code']) ?></td><td><?= e($b['school_name']) ?></td><td><div class="text-sm"><?= e($b['customer_name'] ?: '—') ?></div><div class="text-xs" style="color:var(--text-muted)"><?= e($b['customer_email'] ?: '') ?></div></td><td><?= e($b['entity_title']) ?></td><td class="font-semibold"><?= formatBRL($b['total']) ?></td><td><span class="badge badge-<?= $bm[0] ?>"><?= e($bm[1]) ?></span></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$recent): ?><tr><td colspan="6" class="text-center py-8" style="color:var(--text-muted)">Ainda sem pagamentos Macaiok.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php $edit = !empty($_GET['edit']) ? dbOne("SELECT * FROM institutions WHERE id=? AND program='macaiok'", [(int)$_GET['edit']]) : null; ?>
    <form method="POST" class="admin-card p-5 sm:p-6 space-y-4 lg:sticky lg:top-24 self-start">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="save_school">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <div><h3 class="font-display text-lg font-bold" style="color:var(--sepia)"><?= $edit ? 'Editar escola' : 'Nova escola Macaiok' ?></h3><p class="text-xs" style="color:var(--text-muted)">O acesso criado entra pelo endereço /macaiok/login.</p></div>
        <label class="block"><span class="admin-label">Nome da escola *</span><input name="name" required value="<?= e($edit['name'] ?? '') ?>" class="admin-input"></label>
        <div class="grid grid-cols-2 gap-3"><label><span class="admin-label">Código interno</span><input name="school_code" value="<?= e($edit['school_code'] ?? '') ?>" class="admin-input" placeholder="MACAIOK01"></label><label><span class="admin-label">CNPJ</span><input name="cnpj" value="<?= e($edit['cnpj'] ?? '') ?>" class="admin-input"></label></div>
        <div class="grid grid-cols-2 gap-3"><label><span class="admin-label">Coordenação</span><input name="coordinator_name" value="<?= e($edit['coordinator_name'] ?? '') ?>" class="admin-input"></label><label><span class="admin-label">Contato</span><input name="contact_name" value="<?= e($edit['contact_name'] ?? '') ?>" class="admin-input"></label></div>
        <div class="grid grid-cols-2 gap-3"><label><span class="admin-label">E-mail</span><input type="email" name="contact_email" value="<?= e($edit['contact_email'] ?? '') ?>" class="admin-input"></label><label><span class="admin-label">WhatsApp</span><input name="whatsapp" value="<?= e($edit['whatsapp'] ?? '') ?>" class="admin-input"></label></div>
        <label class="block"><span class="admin-label">Site</span><input name="website" value="<?= e($edit['website'] ?? '') ?>" class="admin-input"></label>
        <div class="rounded-xl p-4" style="background:var(--bg-surface);border:1px dashed var(--border-default)">
            <div class="text-xs font-bold uppercase tracking-wider mb-3" style="color:var(--text-muted)">Acesso da escola</div>
            <div class="grid gap-3"><label><span class="admin-label">Nome do usuário</span><input name="user_name" class="admin-input" placeholder="Coordenação pedagógica"></label><label><span class="admin-label">E-mail de login</span><input type="email" name="user_email" value="<?= e($edit['contact_email'] ?? '') ?>" class="admin-input"></label><label><span class="admin-label">Senha nova/inicial</span><input type="password" name="user_password" class="admin-input" placeholder="Mínimo <?= PASSWORD_MIN_LENGTH ?> caracteres"></label></div>
        </div>
        <label class="block"><span class="admin-label">Mensagem para responsáveis</span><textarea name="parent_share_note" rows="3" class="admin-input"><?= e($edit['parent_share_note'] ?? '') ?></textarea></label>
        <label class="block"><span class="admin-label">Notas internas</span><textarea name="notes" rows="2" class="admin-input"><?= e($edit['notes'] ?? '') ?></textarea></label>
        <label class="flex items-center gap-2"><input type="checkbox" name="active" value="1" <?= !isset($edit['active']) || $edit['active'] ? 'checked' : '' ?>> <span class="text-sm">Escola ativa</span></label>
        <button class="admin-btn admin-btn-primary w-full justify-center"><i data-lucide="save" class="w-4 h-4"></i><?= $edit ? 'Salvar escola' : 'Criar escola' ?></button>
    </form>
</div>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>