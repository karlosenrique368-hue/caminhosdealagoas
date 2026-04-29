<?php
$accountTitle = 'Reembolsos';
$accountTab = 'reembolso';
include VIEWS_DIR . '/partials/account_layout.php';

$cid = currentCustomerId();
$refunds = dbAll('
    SELECT rr.*, b.total AS total, b.entity_title, b.code
    FROM refund_requests rr
    LEFT JOIN bookings b ON rr.booking_id=b.id
    WHERE rr.customer_id=?
    ORDER BY rr.created_at DESC', [$cid]);
$eligibleBookings = dbAll("
    SELECT b.id, b.total, b.entity_title AS title, b.code
    FROM bookings b
        WHERE (b.customer_id=? OR b.customer_user_id=?)
      AND b.payment_status='paid'
      AND b.id NOT IN (SELECT booking_id FROM refund_requests WHERE customer_id=?)
        ORDER BY b.created_at DESC", [$cid, $cid, $cid]);
$preselect = (int)($_GET['booking'] ?? 0);
?>

<div class="grid grid-cols-1 lg:grid-cols-[1.1fr_1fr] gap-6">
    <!-- Request form -->
    <div class="glass-card p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(201,107,74,0.1);color:var(--terracota)">
                <i data-lucide="refresh-ccw" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Solicitar reembolso</h2>
                <p class="text-xs" style="color:var(--text-muted)">Sua solicitação será analisada em até 48h</p>
            </div>
        </div>

        <?php if (empty($eligibleBookings)): ?>
            <div class="empty-state" style="padding:32px 20px">
                <div class="empty-state-icon"><i data-lucide="check-circle" class="w-7 h-7"></i></div>
                <div class="empty-state-title">Nada para reembolsar</div>
                <div class="empty-state-desc">Todas as suas reservas pagas já têm solicitações ou ainda não há reservas elegíveis.</div>
            </div>
        <?php else: ?>
            <form id="refund-form" data-ajax action="<?= url('/api/refund') ?>" method="POST" class="refund-form-premium space-y-5">
                <?= csrfField() ?>
                <div class="form-field">
                    <label class="form-field-label">Reserva a reembolsar</label>
                    <div class="form-input-group">
                        <i data-lucide="calendar-check" class="form-input-icon w-4 h-4"></i>
                        <select name="booking_id" required class="form-input">
                            <option value="">Selecione uma reserva...</option>
                            <?php foreach ($eligibleBookings as $b): ?>
                                <option value="<?= (int)$b['id'] ?>" <?= $preselect===(int)$b['id']?'selected':'' ?>>
                                    <?= e($b['title']) ?> — <?= formatPrice((float)$b['total']) ?> (<?= e($b['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="text-[11px] mt-1.5" style="color:var(--text-muted)"><i data-lucide="info" class="inline-block w-3 h-3 align-[-2px]"></i> O valor será estornado na forma de pagamento original.</p>
                </div>
                <div class="form-field">
                    <label class="form-field-label">Motivo do reembolso</label>
                    <textarea name="reason" rows="6" required class="form-input auto-grow" minlength="10" placeholder="Descreva com detalhes o motivo do reembolso (alteração de data, imprevisto, problema com a experiência, etc.)..."></textarea>
                    <p class="text-xs mt-1.5" style="color:var(--text-muted)">Mínimo 10 caracteres. Quanto mais detalhes, mais rápida a análise.</p>
                </div>
                <button type="submit" class="refund-submit">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Enviar solicitação</span>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- History -->
    <div class="glass-card p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(58,107,138,0.1);color:var(--horizonte)">
                <i data-lucide="history" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Histórico</h2>
                <p class="text-xs" style="color:var(--text-muted)"><?= count($refunds) ?> solicitação<?= count($refunds)===1?'':'s' ?></p>
            </div>
        </div>

        <?php if (empty($refunds)): ?>
            <p class="text-sm text-center py-8" style="color:var(--text-muted)">Nenhuma solicitação anterior.</p>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($refunds as $r):
                    $pill = ['em_analise'=>'pill-warning','aprovado'=>'pill-success','negado'=>'pill-danger','pago'=>'pill-info'][$r['status']] ?? 'pill-info';
                    $statusLabel = ['em_analise'=>'em análise','aprovado'=>'aprovado','negado'=>'negado','pago'=>'pago'][$r['status']] ?? $r['status'];
                ?>
                    <div class="p-4 rounded-xl border" style="background:var(--areia-light);border-color:var(--border-default)">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="font-bold text-sm truncate" style="color:var(--sepia)"><?= e($r['entity_title']) ?></span>
                            <span class="pill <?= $pill ?>"><?= e($statusLabel) ?></span>
                        </div>
                        <p class="text-xs mb-2 line-clamp-2" style="color:var(--text-secondary)"><?= e($r['reason']) ?></p>
                        <div class="flex items-center justify-between text-xs" style="color:var(--text-muted)">
                            <span><?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                            <span class="font-bold" style="color:var(--terracota)"><?= formatPrice((float)$r['amount']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/account_layout_end.php'; ?>
