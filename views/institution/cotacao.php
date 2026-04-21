<?php
requireInstitution();
$i = currentInstitution();
$pageTitle = 'Pedir cotação em grupo';

if (isPost() && csrfVerify()) {
    if (!institutionRoleCan('request_quote')) {
        flash('error', 'Seu perfil não pode enviar pedidos.');
    } else {
        $data = [
            'institution_id'      => $i['id'],
            'institution_user_id' => $i['user_id'],
            'entity_type'         => in_array($_POST['entity_type'] ?? '', ['roteiro','pacote','custom'], true) ? $_POST['entity_type'] : 'custom',
            'entity_id'           => (int)($_POST['entity_id'] ?? 0) ?: null,
            'title'               => trim($_POST['title'] ?? '') ?: 'Cotação sem título',
            'people'              => max(1, (int)($_POST['people'] ?? 10)),
            'desired_date'        => $_POST['desired_date'] ?: null,
            'message'             => trim($_POST['message'] ?? '') ?: null,
        ];
        $fields = array_keys($data);
        dbExec("INSERT INTO group_requests (".implode(',',$fields).") VALUES (".implode(',', array_fill(0,count($fields),'?')).")", array_values($data));
        flash('success', 'Pedido enviado! Nossa equipe responde em até 24h úteis.');
    }
    redirect('/instituicao/cotacao');
}

$roteiros = dbAll("SELECT id, title FROM roteiros WHERE status='published' ORDER BY title");
$pacotes  = dbAll("SELECT id, title FROM pacotes  WHERE status='published' ORDER BY title");
$requests = dbAll("SELECT * FROM group_requests WHERE institution_id=? ORDER BY created_at DESC", [$i['id']]);
$flashOk  = flash('success');
$flashErr = flash('error');

include VIEWS_DIR . '/partials/institution_head.php';
?>
<?php if ($flashOk): ?><div class="mb-4 p-3 rounded-xl flex items-center gap-2 text-sm" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3);color:var(--maresia-dark)"><i data-lucide="check-circle" class="w-4 h-4"></i><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="mb-4 p-3 rounded-xl flex items-center gap-2 text-sm" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#B91C1C"><i data-lucide="alert-circle" class="w-4 h-4"></i><?= e($flashErr) ?></div><?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="admin-card p-6">
        <h2 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Novo pedido de cotação</h2>
        <form method="POST" class="space-y-3">
            <?= csrfField() ?>
            <div class="grid grid-cols-2 gap-3">
                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Tipo</span>
                    <select name="entity_type" class="admin-input w-full" x-data="{t:'roteiro'}" x-model="t">
                        <option value="roteiro">Passeio</option>
                        <option value="pacote">Pacote</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </label>
                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Produto (opcional)</span>
                    <select name="entity_id" class="admin-input w-full">
                        <option value="">— Customizado —</option>
                        <optgroup label="Passeios">
                            <?php foreach ($roteiros as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['title']) ?></option><?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Pacotes">
                            <?php foreach ($pacotes as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?>
                        </optgroup>
                    </select>
                </label>
            </div>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Título do pedido *</span><input type="text" name="title" required class="admin-input w-full" placeholder="Ex: Viagem de formatura 3° ano"></label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Nº de pessoas *</span><input type="number" min="1" name="people" required value="10" class="admin-input w-full"></label>
                <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Data desejada</span><input type="date" name="desired_date" class="admin-input w-full"></label>
            </div>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-muted)">Detalhes</span><textarea name="message" rows="4" class="admin-input w-full" placeholder="Roteiro personalizado, observações, preferências alimentares, etc."></textarea></label>
            <button type="submit" class="admin-btn admin-btn-primary w-full justify-center"><i data-lucide="send" class="w-4 h-4"></i>Enviar pedido</button>
        </form>
    </div>

    <div class="admin-card p-6">
        <h2 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Pedidos em andamento</h2>
        <?php if (!$requests): ?>
            <p class="text-sm" style="color:var(--text-muted)">Nenhum pedido ainda.</p>
        <?php else: ?>
        <div class="space-y-3">
            <?php
            $statusLabels = ['new'=>['Novo','info'],'in_review'=>['Em análise','warning'],'quoted'=>['Cotado','success'],'confirmed'=>['Confirmado','success'],'declined'=>['Recusado','danger'],'cancelled'=>['Cancelado','muted']];
            foreach ($requests as $gr):
                $sl = $statusLabels[$gr['status']] ?? [$gr['status'],'muted'];
            ?>
                <div class="p-4 rounded-xl" style="background:var(--bg-surface);border:1px solid var(--border-default)">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <div class="font-semibold" style="color:var(--sepia)"><?= e($gr['title']) ?></div>
                        <span class="badge badge-<?= $sl[1] ?>"><?= $sl[0] ?></span>
                    </div>
                    <div class="text-xs mb-2" style="color:var(--text-muted)"><?= (int)$gr['people'] ?> pessoas · <?= $gr['desired_date'] ? formatDate($gr['desired_date'],'d/m/Y') : 'data flexível' ?> · <?= formatDate($gr['created_at'],'d/m/Y') ?></div>
                    <?php if ($gr['quoted_total']): ?>
                        <div class="text-sm mb-1"><strong style="color:var(--terracota)">Cotação: <?= formatBRL($gr['quoted_total']) ?></strong></div>
                    <?php endif; ?>
                    <?php if ($gr['quoted_note']): ?>
                        <div class="text-xs" style="color:var(--text-secondary)"><?= nl2br(e($gr['quoted_note'])) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/institution_foot.php';
