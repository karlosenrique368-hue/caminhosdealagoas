<?php
$pageTitle = 'Mensagens';

if (isPost() && csrfVerify()) {
    requireAdmin();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'mark_read') dbExec("UPDATE contact_messages SET status='read' WHERE id=?", [$id]);
    if ($action === 'mark_replied') dbExec("UPDATE contact_messages SET status='replied' WHERE id=?", [$id]);
    if ($action === 'delete') dbExec("DELETE FROM contact_messages WHERE id=?", [$id]);
    redirect('/admin/mensagens');
}

require VIEWS_DIR . '/partials/admin_head.php';
$messages = dbAll("SELECT * FROM contact_messages ORDER BY created_at DESC");
?>

<div class="grid md:grid-cols-3 gap-4 mb-6">
    <?php
    $newC = count(array_filter($messages, fn($m)=>$m['status']==='new'));
    $readC = count(array_filter($messages, fn($m)=>$m['status']==='read'));
    $repliedC = count(array_filter($messages, fn($m)=>$m['status']==='replied'));
    ?>
    <div class="admin-stat"><div class="flex items-center justify-between mb-3"><span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Novas</span><div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(245,158,11,0.12)"><i data-lucide="mail" class="w-4 h-4" style="color:#D97706"></i></div></div><div class="font-display text-3xl font-bold" style="color:var(--sepia)"><?= $newC ?></div></div>
    <div class="admin-stat"><div class="flex items-center justify-between mb-3"><span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Lidas</span><div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(58,107,138,0.12)"><i data-lucide="mail-open" class="w-4 h-4" style="color:var(--horizonte)"></i></div></div><div class="font-display text-3xl font-bold" style="color:var(--sepia)"><?= $readC ?></div></div>
    <div class="admin-stat"><div class="flex items-center justify-between mb-3"><span class="text-xs font-semibold uppercase tracking-wider" style="color:var(--text-muted)">Respondidas</span><div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(122,157,110,0.12)"><i data-lucide="check-check" class="w-4 h-4" style="color:var(--maresia-dark)"></i></div></div><div class="font-display text-3xl font-bold" style="color:var(--sepia)"><?= $repliedC ?></div></div>
</div>

<div class="space-y-3">
    <?php if (!$messages): ?>
    <div class="admin-card p-12 text-center">
        <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
        <h3 class="font-semibold" style="color:var(--sepia)">Nenhuma mensagem ainda</h3>
    </div>
    <?php endif; ?>

    <?php foreach ($messages as $m): ?>
    <div class="admin-card p-5" x-data="{expanded: <?= $m['status']==='new'?'true':'false' ?>}">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0" style="background:linear-gradient(135deg,var(--horizonte),var(--horizonte-dark));color:white"><?= e(mb_strtoupper(mb_substr($m['name'],0,2))) ?></div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1 flex-wrap gap-2">
                    <div>
                        <span class="font-semibold"><?= e($m['name']) ?></span>
                        <span class="text-xs ml-2" style="color:var(--text-muted)"><?= e($m['email']) ?><?php if ($m['phone']): ?> · <?= e($m['phone']) ?><?php endif; ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge badge-<?= $m['status']==='new'?'warning':($m['status']==='replied'?'success':'info') ?>"><?= ['new'=>'Nova','read'=>'Lida','replied'=>'Respondida','archived'=>'Arquivada'][$m['status']] ?></span>
                        <span class="text-xs" style="color:var(--text-muted)"><?= date('d/m H:i', strtotime($m['created_at'])) ?></span>
                    </div>
                </div>
                <?php if ($m['subject']): ?><div class="text-sm font-semibold mb-2" style="color:var(--sepia)"><?= e($m['subject']) ?></div><?php endif; ?>
                <div class="text-sm whitespace-pre-wrap" style="color:var(--text-secondary)" :class="!expanded && 'line-clamp-2'"><?= e($m['message']) ?></div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button @click="expanded=!expanded" class="text-xs font-semibold hover:underline" style="color:var(--horizonte)" x-text="expanded ? 'Recolher' : 'Ver mensagem'"></button>
                    <a href="mailto:<?= e($m['email']) ?>?subject=Re: <?= e($m['subject']) ?>" class="text-xs font-semibold hover:underline" style="color:var(--terracota)">Responder por email</a>
                    <?php if ($m['phone']): ?>
                        <a href="https://wa.me/55<?= e(preg_replace('/\D/', '', $m['phone'])) ?>" target="_blank" class="text-xs font-semibold hover:underline" style="color:var(--maresia-dark)">WhatsApp</a>
                    <?php endif; ?>
                    <?php if ($m['status']==='new'): ?>
                    <form method="post" class="inline"><?= csrfField() ?><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="text-xs font-semibold hover:underline" style="color:var(--text-secondary)">Marcar como lida</button></form>
                    <?php endif; ?>
                    <?php if ($m['status']!=='replied'): ?>
                    <form method="post" class="inline"><?= csrfField() ?><input type="hidden" name="action" value="mark_replied"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="text-xs font-semibold hover:underline" style="color:var(--maresia-dark)">Marcar respondida</button></form>
                    <?php endif; ?>
                    <form method="post" class="inline" onsubmit="return confirm('Excluir mensagem?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="text-xs font-semibold hover:underline text-red-500">Excluir</button></form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
