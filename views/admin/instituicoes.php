<?php
$pageTitle = 'Parceiros';

if (isPost() && csrfVerify()) {
    requireAdmin();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $data = [
            'name'=>trim($_POST['name'] ?? ''),
            'partner_type'=>$_POST['partner_type'] ?? 'individual',
            'type'=>$_POST['type'] ?? 'outro',
            'cnpj'=>trim($_POST['cnpj'] ?? ''),
            'cpf'=>trim($_POST['cpf'] ?? ''),
            'contact_name'=>trim($_POST['contact_name'] ?? ''),
            'contact_email'=>trim($_POST['contact_email'] ?? ''),
            'contact_phone'=>trim($_POST['contact_phone'] ?? ''),
            'whatsapp'=>trim($_POST['whatsapp'] ?? ''),
            'discount_percent'=>(float)($_POST['discount_percent'] ?? 0),
            'commission_percent'=>(float)($_POST['commission_percent'] ?? 10),
            'bookings_threshold'=>max(0,(int)($_POST['bookings_threshold'] ?? 10)),
            'notes'=>trim($_POST['notes'] ?? ''),
            'active'=>isset($_POST['active'])?1:0,
        ];
        if ($id) {
            $sets=[]; foreach ($data as $k=>$_) $sets[]="`$k`=?";
            $vals=array_values($data); $vals[]=$id;
            dbExec("UPDATE institutions SET ".implode(',',$sets)." WHERE id=?", $vals);
        } else {
            // gerar codigo e slug se criado aqui
            if (empty($_POST['referral_code'])) {
                $data['referral_code'] = generateReferralCode();
            } else {
                $data['referral_code'] = strtoupper(preg_replace('/[^A-Z0-9]/i','',$_POST['referral_code']));
            }
            $data['slug'] = preg_replace('/[^a-z0-9]+/','-', strtolower($data['name'])).'-'.substr(md5(uniqid()),0,6);
            $fields=array_keys($data);
            dbExec("INSERT INTO institutions (".implode(',',$fields).") VALUES (".implode(',',array_fill(0,count($fields),'?')).")", array_values($data));
        }
        flash('success','Parceiro salvo.');
    }
    if ($action === 'delete') { dbExec('DELETE FROM institutions WHERE id=?', [$id]); flash('success','Parceiro removido.'); }
    if ($action === 'credit_payout') {
        $p = dbOne('SELECT commission_pending FROM institutions WHERE id=?', [$id]);
        if ($p && $p['commission_pending'] > 0) {
            dbExec('UPDATE institutions SET commission_paid = commission_paid + commission_pending, commission_pending = 0 WHERE id=?', [$id]);
            flash('success','Pagamento creditado — bônus zerado e contabilizado como pago.');
        }
    }
    redirect('/admin/instituicoes');
}

require VIEWS_DIR . '/partials/admin_head.php';

$filterType = $_GET['tipo'] ?? '';
$where = '1=1';
$params = [];
if ($filterType && in_array($filterType, ['individual','familia','grupo','instituicao','revendedor'])) {
    $where .= ' AND partner_type=?';
    $params[] = $filterType;
}

$pag = paginate("SELECT COUNT(*) AS c FROM institutions WHERE $where", "SELECT * FROM institutions WHERE $where ORDER BY created_at DESC", $params);
$items = $pag['rows'];
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? dbOne('SELECT * FROM institutions WHERE id=?', [$editId]) : null;

$partnerTypes = ['individual'=>'Individual','familia'=>'Família & amigos','grupo'=>'Grupo / comunidade','instituicao'=>'Instituição','revendedor'=>'Revenda / agência'];
$typeIcons = ['individual'=>'user','familia'=>'users','grupo'=>'users-round','instituicao'=>'building-2','revendedor'=>'store'];

$totals = dbOne("SELECT COUNT(*) AS n, COALESCE(SUM(commission_pending),0) AS pend, COALESCE(SUM(commission_paid),0) AS pago, COALESCE(SUM(free_spots_earned),0) AS vagas FROM institutions WHERE active=1");
?>

<!-- KPIs -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6">
    <div class="admin-card p-5"><div class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Parceiros ativos</div><div class="font-display text-2xl font-bold" style="color:var(--sepia)"><?= (int)$totals['n'] ?></div></div>
    <div class="admin-card p-5"><div class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Bônus a pagar</div><div class="font-display text-2xl font-bold" style="color:var(--terracota)"><?= formatBRL($totals['pend']) ?></div></div>
    <div class="admin-card p-5"><div class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Bônus pago</div><div class="font-display text-2xl font-bold" style="color:var(--maresia-dark)"><?= formatBRL($totals['pago']) ?></div></div>
    <div class="admin-card p-5"><div class="text-[10px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-muted)">Vagas-cortesia geradas</div><div class="font-display text-2xl font-bold" style="color:#F59E0B"><?= (int)$totals['vagas'] ?></div></div>
</div>

<div x-data="{open:false, editing:null}" x-init="<?= $edit ? 'editing=' . htmlspecialchars(json_encode($edit), ENT_QUOTES) . '; open=true' : '' ?>">
    <!-- Filtros + Ação -->
    <div class="flex flex-wrap gap-3 items-center justify-between mb-5">
        <div class="flex flex-wrap gap-1.5">
            <a href="<?= url('/admin/instituicoes') ?>" class="admin-btn <?= !$filterType ? 'admin-btn-primary' : 'admin-btn-secondary' ?>">Todos (<?= (int)$pag['total'] ?>)</a>
            <?php foreach ($partnerTypes as $k=>$v): ?>
                <a href="<?= url('/admin/instituicoes?tipo='.$k) ?>" class="admin-btn <?= $filterType===$k ? 'admin-btn-primary' : 'admin-btn-secondary' ?>"><i data-lucide="<?= $typeIcons[$k] ?>" class="w-3.5 h-3.5"></i><?= e($v) ?></a>
            <?php endforeach; ?>
        </div>
        <button type="button" @click="editing=null; open=true" class="admin-btn admin-btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Novo parceiro</button>
    </div>

    <div class="admin-card overflow-hidden">
        <?php if (empty($items)): ?>
            <div class="p-12 text-center">
                <i data-lucide="handshake" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
                <h3 class="font-semibold mb-1" style="color:var(--sepia)">Nenhum parceiro por aqui ainda</h3>
                <p class="text-sm mb-4" style="color:var(--text-muted)">Parceiros podem se cadastrar sozinhos em /parceiro/cadastro ou adicione um manualmente.</p>
                <button type="button" @click="editing=null; open=true" class="admin-btn admin-btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Adicionar</button>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead><tr><th>Parceiro</th><th>Tipo</th><th>Código</th><th>Reservas pagas</th><th>Bônus pendente</th><th>Bônus pago</th><th>Vagas</th><th>Status</th><th class="text-right">Ações</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it):
                    $pt = $it['partner_type'] ?? 'individual';
                ?>
                <tr>
                    <td>
                        <div class="font-semibold" style="color:var(--sepia)"><?= e($it['name']) ?></div>
                        <div class="text-xs" style="color:var(--text-muted)"><?= e($it['contact_email'] ?: '—') ?></div>
                    </td>
                    <td><span class="badge badge-info"><i data-lucide="<?= $typeIcons[$pt] ?? 'user' ?>" class="w-3 h-3"></i><?= e($partnerTypes[$pt] ?? $pt) ?></span></td>
                    <td><code class="font-mono text-xs font-bold" style="color:var(--terracota)"><?= e($it['referral_code'] ?: '—') ?></code></td>
                    <td class="text-sm font-semibold"><?= (int)($it['bookings_count_paid'] ?? 0) ?></td>
                    <td class="text-sm font-semibold" style="color:var(--terracota)"><?= formatBRL($it['commission_pending'] ?? 0) ?></td>
                    <td class="text-sm" style="color:var(--maresia-dark)"><?= formatBRL($it['commission_paid'] ?? 0) ?></td>
                    <td class="text-sm"><span class="badge badge-warning"><?= (int)($it['free_spots_earned'] ?? 0) - (int)($it['free_spots_used'] ?? 0) ?></span></td>
                    <td><span class="badge badge-<?= $it['active']?'success':'muted' ?>"><?= $it['active']?'Ativo':'Inativo' ?></span></td>
                    <td class="actions-cell">
                        <div class="flex justify-end gap-1">
                            <?php if (($it['commission_pending'] ?? 0) > 0): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Creditar pagamento de <?= formatBRL($it['commission_pending']) ?> para <?= e($it['name']) ?>?')"><?= csrfField() ?><input type="hidden" name="action" value="credit_payout"><input type="hidden" name="id" value="<?= $it['id'] ?>"><button class="action-chip chip-success" title="Creditar pagamento"><i data-lucide="banknote" class="w-3.5 h-3.5"></i>Pagar</button></form>
                            <?php endif; ?>
                            <button type="button" @click="editing=<?= htmlspecialchars(json_encode($it), ENT_QUOTES) ?>; open=true" class="action-chip chip-edit" title="Editar"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i>Editar</button>
                            <form method="POST" class="inline" onsubmit="return confirm('Excluir este parceiro?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $it['id'] ?>"><button class="action-chip chip-danger" title="Excluir"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <div x-show="open" x-cloak @keydown.escape.window="open=false" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <div @click.outside="open=false" class="w-full max-w-2xl rounded-2xl p-6" style="background:var(--bg-card);max-height:90vh;overflow-y:auto">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-display text-xl font-bold" style="color:var(--sepia)" x-text="editing?'Editar parceiro':'Novo parceiro'"></h3>
                <button type="button" @click="open=false" style="color:var(--text-muted)"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="POST" class="space-y-3">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" :value="editing?editing.id:''">

                <div class="grid grid-cols-2 gap-3">
                    <label class="block col-span-2 md:col-span-1"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Nome *</span><input type="text" name="name" :value="editing?editing.name:''" required class="admin-input w-full"></label>
                    <label class="block col-span-2 md:col-span-1"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Tipo de parceria</span>
                        <select name="partner_type" class="admin-input w-full" :value="editing?editing.partner_type:'individual'">
                            <?php foreach ($partnerTypes as $k=>$v): ?><option value="<?= $k ?>" <?= $k==='individual'?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">CPF</span><input type="text" name="cpf" :value="editing?editing.cpf:''" class="admin-input w-full"></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">CNPJ</span><input type="text" name="cnpj" :value="editing?editing.cnpj:''" class="admin-input w-full"></label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">E-mail *</span><input type="email" name="contact_email" :value="editing?editing.contact_email:''" required class="admin-input w-full"></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">WhatsApp</span><input type="tel" name="whatsapp" :value="editing?editing.whatsapp:''" class="admin-input w-full"></label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Contato adicional</span><input type="text" name="contact_name" :value="editing?editing.contact_name:''" class="admin-input w-full"></label>
                    <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Telefone fixo</span><input type="tel" name="contact_phone" :value="editing?editing.contact_phone:''" class="admin-input w-full"></label>
                </div>

                <div class="p-3 rounded-xl" style="background:var(--bg-surface);border:1px dashed var(--border-default)">
                    <div class="text-xs font-bold uppercase tracking-wider mb-2" style="color:var(--text-muted)">Benefícios & comissionamento (INTERNO)</div>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="block"><span class="text-xs font-semibold mb-1 block" style="color:var(--text-secondary)">Desconto %</span><input type="number" step="0.1" min="0" max="100" name="discount_percent" :value="editing?editing.discount_percent:0" class="admin-input w-full"></label>
                        <label class="block"><span class="text-xs font-semibold mb-1 block" style="color:var(--text-secondary)">Comissão %</span><input type="number" step="0.1" min="0" max="100" name="commission_percent" :value="editing?editing.commission_percent:10" class="admin-input w-full"></label>
                        <label class="block"><span class="text-xs font-semibold mb-1 block" style="color:var(--text-secondary)">Gratuidade a cada</span><input type="number" min="0" name="bookings_threshold" :value="editing?editing.bookings_threshold:10" class="admin-input w-full"></label>
                    </div>
                </div>

                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Notas internas</span><textarea name="notes" rows="2" class="admin-input w-full" x-text="editing?editing.notes:''"></textarea></label>
                <label class="flex items-center gap-2"><input type="checkbox" name="active" value="1" :checked="editing?editing.active==1:true"> <span class="text-sm">Parceiro ativo</span></label>
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="open=false" class="admin-btn admin-btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="admin-btn admin-btn-primary flex-1"><i data-lucide="save" class="w-4 h-4"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/pagination.php'; ?>
<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
