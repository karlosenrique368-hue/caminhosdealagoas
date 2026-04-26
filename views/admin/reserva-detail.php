<?php
requireAdmin();
$pageTitle = 'Detalhes da reserva';

$id = (int)($_GET['id'] ?? 0);
$b = $id ? dbOne("SELECT b.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone, c.document AS customer_doc, c.rg AS customer_rg, c.birth_date AS customer_birth FROM bookings b LEFT JOIN customers c ON b.customer_id=c.id WHERE b.id=?", [$id]) : null;
if (!$b) { flash('error','Reserva não encontrada.'); redirect('/admin/reservas'); }

if (isPost() && csrfVerify()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_payment') {
        $ps = $_POST['payment_status'] ?? '';
        if (in_array($ps, ['pending','paid','failed','refunded','cancelled'])) {
            $prev = $b['payment_status'];
            $extra = $ps === 'paid' ? ", paid_at = NOW()" : "";
            dbExec("UPDATE bookings SET payment_status=? $extra WHERE id=?", [$ps, $id]);
            handleBookingPaymentStatusChanged($id, $prev, $ps, 'admin_reserva_detail');
            flash('success', 'Pagamento atualizado.');
        }
    } elseif ($action === 'update_notes') {
        dbExec("UPDATE bookings SET notes=? WHERE id=?", [trim($_POST['notes'] ?? ''), $id]);
        flash('success', 'Observações salvas.');
    }
    redirect('/admin/reservas/' . $id);
}

$answers = !empty($b['booking_answers']) ? (json_decode($b['booking_answers'], true) ?: []) : [];
$participants = !empty($b['participants']) ? (json_decode($b['participants'], true) ?: []) : [];

$paymentLabels = ['pix'=>'PIX','credit_card'=>'Cartão de crédito','boleto'=>'Boleto','bank_transfer'=>'Transferência'];
$statusLabels  = ['pending'=>'Pendente','paid'=>'Pago','failed'=>'Falhou','refunded'=>'Reembolsado','cancelled'=>'Cancelado'];
$sourceLabels  = ['instagram'=>'Instagram','whatsapp'=>'WhatsApp','indicacao'=>'Indicação','google'=>'Google','outro'=>'Outro'];

require VIEWS_DIR . '/partials/admin_head.php';
$msg = flash('success');
?>

<?php if ($msg): ?>
<div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)">
    <i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i><span class="text-sm" style="color:var(--maresia-dark)"><?= e($msg) ?></span>
</div>
<?php endif; ?>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <a href="<?= url('/admin/reservas') ?>" class="text-sm font-semibold inline-flex items-center gap-1 mb-2" style="color:var(--horizonte)"><i data-lucide="arrow-left" class="w-4 h-4"></i>Todas as reservas</a>
        <h2 class="font-display text-2xl font-bold" style="color:var(--sepia)">Reserva <span class="font-mono text-base"><?= e($b['code']) ?></span></h2>
        <div class="text-xs mt-1" style="color:var(--text-muted)">Criada em <?= date('d/m/Y \à\s H:i', strtotime($b['created_at'])) ?></div>
    </div>
    <form method="post" class="flex items-center gap-2">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_payment">
        <select name="payment_status" onchange="this.form.submit()" class="admin-input md:w-44">
            <?php foreach ($statusLabels as $k => $v): ?>
                <option value="<?= $k ?>" <?= $b['payment_status']===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    <!-- COL ESQUERDA -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Cliente -->
        <div class="admin-card p-6">
            <h3 class="font-display font-bold text-lg mb-4 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="user" class="w-5 h-5" style="color:var(--terracota)"></i> Cliente</h3>
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Nome</div><div class="font-semibold"><?= e($b['customer_name']) ?></div></div>
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">E-mail</div><div><?= e($b['customer_email']) ?></div></div>
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Telefone</div><div><?= e($b['customer_phone']) ?></div></div>
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Nascimento</div><div><?= $b['customer_birth'] ? date('d/m/Y', strtotime($b['customer_birth'])) : '—' ?></div></div>
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">CPF</div><div class="font-mono"><?= e($b['customer_doc'] ?: '—') ?></div></div>
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">RG</div><div class="font-mono"><?= e($b['customer_rg'] ?: '—') ?></div></div>
            </div>
        </div>

        <!-- Viagem -->
        <div class="admin-card p-6">
            <h3 class="font-display font-bold text-lg mb-4 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="map-pin" class="w-5 h-5" style="color:var(--terracota)"></i> Detalhes da viagem</h3>
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div class="sm:col-span-2"><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Produto</div><div class="font-semibold"><?= e($b['entity_title']) ?> <span class="text-[10px] uppercase font-bold ml-1 px-2 py-0.5 rounded-full" style="background:rgba(201,107,74,.1);color:var(--terracota)"><?= e($b['entity_type']) ?></span></div></div>
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Data</div><div class="font-semibold"><?= $b['travel_date'] ? dateBR($b['travel_date'], 'dayMonthY') : '—' ?></div></div>
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Pessoas</div><div class="font-semibold"><?= (int)$b['adults'] ?> adulto(s)<?= (int)$b['children']>0 ? ' · '.(int)$b['children'].' criança(s)' : '' ?><?= (int)$b['infants']>0 ? ' · '.(int)$b['infants'].' bebê(s)' : '' ?></div></div>
                <?php if (!empty($b['comorbidity'])): ?>
                <div class="sm:col-span-2 p-3 rounded-lg" style="background:rgba(220,38,38,0.06);border:1px solid rgba(220,38,38,0.18)">
                    <div class="text-[10px] uppercase tracking-wider font-bold mb-1" style="color:#DC2626"><i data-lucide="heart-pulse" class="w-3 h-3 inline -mt-0.5"></i> Comorbidade declarada</div>
                    <div class="text-sm" style="color:var(--text-secondary)"><?= e($b['comorbidity']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Origem (como conheceu) -->
        <div class="admin-card p-6">
            <h3 class="font-display font-bold text-lg mb-4 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="megaphone" class="w-5 h-5" style="color:var(--terracota)"></i> Como nos conheceu</h3>
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Canal</div>
                    <div class="font-semibold"><?= e($sourceLabels[$b['source']] ?? '—') ?></div>
                </div>
                <div>
                    <div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Detalhe informado pelo cliente</div>
                    <div class="font-semibold" style="color:var(--horizonte)"><?= $b['source_detail'] ? e($b['source_detail']) : '<span style="color:var(--text-muted);font-weight:400">—</span>' ?></div>
                </div>
                <?php if ($b['referral_code']): ?>
                <div class="sm:col-span-2 p-3 rounded-lg" style="background:rgba(58,107,138,.06)">
                    <div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--horizonte)"><i data-lucide="handshake" class="w-3 h-3 inline -mt-0.5"></i> Indicação registrada</div>
                    <div class="text-sm font-mono mt-1"><?= e($b['referral_code']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagamento -->
        <div class="admin-card p-6">
            <h3 class="font-display font-bold text-lg mb-4 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="wallet" class="w-5 h-5" style="color:var(--terracota)"></i> Pagamento</h3>
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Método</div><div class="font-semibold"><?= e($paymentLabels[$b['payment_method']] ?? $b['payment_method']) ?></div></div>
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Status</div><div class="font-semibold"><?= e($statusLabels[$b['payment_status']] ?? '—') ?></div></div>
                <?php if ((int)$b['installments'] > 0): ?>
                <div class="sm:col-span-2 p-3 rounded-lg" style="background:rgba(58,107,138,.06);border:1px solid rgba(58,107,138,.18)">
                    <div class="text-[10px] uppercase tracking-wider font-bold mb-1" style="color:var(--horizonte)"><i data-lucide="calendar-clock" class="w-3 h-3 inline -mt-0.5"></i> PIX parcelado</div>
                    <div class="font-semibold"><?= (int)$b['installments'] ?>× de <?= formatBRL($b['installment_amount'] ?? ($b['total'] / (int)$b['installments'])) ?></div>
                </div>
                <?php endif; ?>
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Subtotal</div><div><?= formatBRL($b['subtotal']) ?></div></div>
                <div><div class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Desconto</div><div><?= formatBRL($b['discount']) ?></div></div>
                <div class="sm:col-span-2 pt-3 border-t" style="border-color:var(--border-default)"><div class="flex items-end justify-between"><span class="text-[10px] uppercase tracking-wider font-bold" style="color:var(--text-muted)">Total <?= e($b['currency'] ?? 'BRL') ?></span><span class="font-display text-2xl font-bold" style="color:var(--terracota)"><?= formatBRL($b['total']) ?></span></div></div>
            </div>
        </div>

        <?php if ($participants): ?>
        <div class="admin-card p-6">
            <h3 class="font-display font-bold text-lg mb-4 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="users" class="w-5 h-5" style="color:var(--terracota)"></i> Participantes (<?= count($participants) ?>)</h3>
            <div class="space-y-2">
                <?php foreach ($participants as $i => $p): ?>
                <div class="p-3 rounded-lg flex justify-between items-center" style="background:var(--bg-surface)">
                    <div><div class="font-semibold text-sm"><?= e($p['nome'] ?? $p['name'] ?? 'Participante '.($i+1)) ?></div><div class="text-xs" style="color:var(--text-muted)"><?= e($p['cpf'] ?? '') ?> <?= !empty($p['rg']) ? '· RG '.e($p['rg']) : '' ?></div></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Observações -->
        <form method="post" class="admin-card p-6">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_notes">
            <h3 class="font-display font-bold text-lg mb-3 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="sticky-note" class="w-5 h-5" style="color:var(--terracota)"></i> Observações internas</h3>
            <textarea name="notes" rows="3" class="admin-input" placeholder="Anotações internas sobre esta reserva..."><?= e($b['notes'] ?? '') ?></textarea>
            <div class="mt-3 flex justify-end"><button class="admin-btn admin-btn-primary"><i data-lucide="save" class="w-4 h-4"></i>Salvar</button></div>
        </form>
    </div>

    <!-- COL DIREITA -->
    <aside class="space-y-6">
        <div class="admin-card p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Status</span>
                <?php
                    $statusColor = ['pending'=>'#F59E0B','paid'=>'#10B981','failed'=>'#DC2626','refunded'=>'#6366F1','cancelled'=>'#9CA3AF'][$b['payment_status']] ?? '#6B7280';
                ?>
                <span class="text-xs font-bold px-3 py-1 rounded-full" style="background:<?= $statusColor ?>15;color:<?= $statusColor ?>"><?= e($statusLabels[$b['payment_status']]) ?></span>
            </div>
            <div class="text-3xl font-display font-bold" style="color:var(--terracota)"><?= formatBRL($b['total']) ?></div>
            <div class="text-xs" style="color:var(--text-muted)">Total da reserva</div>
        </div>

        <div class="admin-card p-5">
            <h4 class="font-bold text-sm mb-3" style="color:var(--sepia)">Ações rápidas</h4>
            <a href="https://wa.me/55<?= preg_replace('/\D/','',$b['customer_phone']) ?>?text=<?= urlencode('Olá! Sobre sua reserva ' . $b['code'] . ': ') ?>" target="_blank" class="admin-btn w-full mb-2" style="background:#25D366;color:#fff"><i data-lucide="message-circle" class="w-4 h-4"></i>Falar no WhatsApp</a>
            <a href="mailto:<?= e($b['customer_email']) ?>?subject=<?= urlencode('Reserva ' . $b['code']) ?>" class="admin-btn admin-btn-secondary w-full"><i data-lucide="mail" class="w-4 h-4"></i>Enviar e-mail</a>
        </div>

        <?php if ($answers): ?>
        <details class="admin-card p-5">
            <summary class="cursor-pointer font-bold text-sm flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="file-json" class="w-4 h-4"></i>Respostas brutas (JSON)</summary>
            <pre class="mt-3 text-[11px] p-3 rounded-lg overflow-x-auto" style="background:var(--bg-surface);color:var(--text-secondary)"><?= e(json_encode($answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
        </details>
        <?php endif; ?>
    </aside>
</div>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
