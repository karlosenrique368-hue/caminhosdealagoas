<?php
requireInstitution();
$i = currentInstitution();
$pageTitle = 'Visão geral';
$stats = partnerStats($i['id']);
$partner = $stats['partner'] ?? null;

$recent = dbAll("SELECT b.*, c.name AS customer_name FROM bookings b LEFT JOIN customers c ON c.id=b.customer_id WHERE b.institution_id=? OR b.referral_code=? ORDER BY b.created_at DESC LIMIT 8", [$i['id'], $partner['referral_code'] ?? '']);
$peopleTraveled = (int) (dbOne("SELECT COALESCE(SUM(adults+children),0) AS p FROM bookings WHERE (institution_id=? OR referral_code=?) AND payment_status='paid'", [$i['id'], $partner['referral_code'] ?? ''])['p'] ?? 0);
$shareUrl = referralShareUrl($partner['referral_code'] ?? '', '/');

include VIEWS_DIR . '/partials/institution_head.php';
?>

<!-- HERO DE BOAS VINDAS + LINK -->
<div class="mb-6 p-6 sm:p-8 rounded-2xl relative overflow-hidden" style="background:linear-gradient(135deg,var(--sepia),var(--terracota-dark));color:#fff">
    <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full" style="background:rgba(255,255,255,0.08)"></div>
    <div class="relative grid lg:grid-cols-[1fr_auto] gap-6 items-center">
        <div>
            <div class="text-[11px] uppercase tracking-widest font-bold mb-2 text-white/80"><i data-lucide="handshake" class="w-3.5 h-3.5 inline -mt-0.5"></i> Olá, parceiro(a)</div>
            <h1 class="font-display text-2xl sm:text-3xl font-bold mb-2">Oi, <?= e(explode(' ', $i['user_name'])[0]) ?>! 👋</h1>
            <p class="text-sm sm:text-base text-white/90 max-w-xl">Seu código de indicação está ativo. Compartilhe com quem você ama viajar junto.</p>
        </div>
        <div class="bg-white/10 backdrop-blur p-4 rounded-xl border border-white/20">
            <div class="text-[10px] uppercase tracking-widest font-bold text-white/70 mb-1">Seu código</div>
            <div class="font-mono font-bold text-3xl tracking-wider"><?= e($partner['referral_code'] ?? '—') ?></div>
        </div>
    </div>
</div>

<!-- KPIS -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-6">
    <div class="admin-card p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider" style="color:var(--text-muted)">Indicações</span>
            <i data-lucide="users" class="w-4 h-4 sm:w-5 sm:h-5" style="color:var(--horizonte)"></i>
        </div>
        <div class="font-display text-2xl sm:text-3xl font-bold" style="color:var(--sepia)"><?= (int)$stats['total_bookings'] ?></div>
        <div class="text-[11px] mt-1" style="color:var(--text-muted)">no total</div>
    </div>
    <div class="admin-card p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider" style="color:var(--text-muted)">Confirmadas</span>
            <i data-lucide="check-circle-2" class="w-4 h-4 sm:w-5 sm:h-5" style="color:var(--maresia-dark)"></i>
        </div>
        <div class="font-display text-2xl sm:text-3xl font-bold" style="color:var(--maresia-dark)"><?= (int)$stats['paid_bookings'] ?></div>
        <div class="text-[11px] mt-1" style="color:var(--text-muted)"><?= $peopleTraveled ?> pessoas já foram</div>
    </div>
    <div class="admin-card p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider" style="color:var(--text-muted)">Bônus disponível</span>
            <i data-lucide="wallet" class="w-4 h-4 sm:w-5 sm:h-5" style="color:var(--terracota)"></i>
        </div>
        <div class="font-display text-2xl sm:text-3xl font-bold" style="color:var(--terracota)"><?= formatBRL($stats['commission_pending']) ?></div>
        <div class="text-[11px] mt-1" style="color:var(--text-muted)">a receber</div>
    </div>
    <div class="admin-card p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider" style="color:var(--text-muted)">Vagas-cortesia</span>
            <i data-lucide="ticket" class="w-4 h-4 sm:w-5 sm:h-5" style="color:#F59E0B"></i>
        </div>
        <div class="font-display text-2xl sm:text-3xl font-bold" style="color:#F59E0B"><?= (int)$stats['free_available'] ?></div>
        <div class="text-[11px] mt-1" style="color:var(--text-muted)">para usar</div>
    </div>
</div>

<div class="grid lg:grid-cols-[1fr_380px] gap-5 sm:gap-6">
    <!-- RESERVAS RECENTES -->
    <div class="admin-card p-5 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-display text-lg font-bold" style="color:var(--sepia)">Reservas via seu link</h2>
            <a href="<?= url('/parceiro/reservas') ?>" class="text-xs font-semibold" style="color:var(--horizonte)">Ver todas →</a>
        </div>
        <?php if (!$recent): ?>
            <div class="py-10 text-center">
                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3" style="color:var(--text-muted)"></i>
                <p class="text-sm font-semibold mb-1" style="color:var(--sepia)">Ainda sem indicações confirmadas</p>
                <p class="text-xs max-w-sm mx-auto" style="color:var(--text-muted)">Copie o link ao lado e compartilhe nas suas redes, grupos ou no WhatsApp.</p>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead><tr><th>Código</th><th>Produto</th><th>Cliente</th><th>Pessoas</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $b):
                    $badgeMap = ['paid'=>['success','Pago'],'pending'=>['warning','Pendente'],'cancelled'=>['danger','Cancelada'],'refunded'=>['info','Reembolsada'],'failed'=>['danger','Falhou']];
                    $bm = $badgeMap[$b['payment_status']] ?? ['muted', $b['payment_status']];
                ?>
                <tr>
                    <td data-label="Código" class="font-mono text-xs"><?= e($b['code']) ?></td>
                    <td data-label="Produto"><div class="text-xs uppercase font-semibold" style="color:var(--terracota)"><?= e($b['entity_type']) ?></div><div class="text-sm" style="color:var(--sepia)"><?= e($b['entity_title']) ?></div></td>
                    <td data-label="Cliente" class="text-sm"><?= e($b['customer_name'] ?? '—') ?></td>
                    <td data-label="Pessoas" class="text-sm"><?= (int)$b['adults'] + (int)$b['children'] ?></td>
                    <td data-label="Total" class="text-sm font-semibold" style="color:var(--sepia)"><?= formatBRL($b['total']) ?></td>
                    <td data-label="Status"><span class="badge badge-<?= $bm[0] ?>"><?= $bm[1] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- SIDEBAR: LINK + PROGRESSO -->
    <div class="space-y-5">
        <!-- Link de indicação -->
        <div class="admin-card p-5" x-data="{copied:false}">
            <h3 class="font-display text-base font-bold mb-3 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="link-2" class="w-5 h-5" style="color:var(--terracota)"></i> Seu link</h3>
            <div class="p-3 rounded-lg font-mono text-[11px] break-all mb-3" style="background:var(--bg-surface);color:var(--text-primary)"><?= e($shareUrl) ?></div>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" @click="navigator.clipboard.writeText('<?= e(addslashes($shareUrl)) ?>'); copied=true; setTimeout(()=>copied=false,2000)" class="admin-btn admin-btn-primary justify-center">
                    <i data-lucide="copy" class="w-4 h-4" x-show="!copied"></i>
                    <i data-lucide="check" class="w-4 h-4" x-show="copied" x-cloak></i>
                    <span x-text="copied?'Copiado!':'Copiar'"></span>
                </button>
                <a href="https://wa.me/?text=<?= urlencode('Vem viver Alagoas com a Caminhos de Alagoas! '.$shareUrl) ?>" target="_blank" class="admin-btn admin-btn-secondary justify-center">
                    <i data-lucide="send" class="w-4 h-4"></i>WhatsApp
                </a>
            </div>
        </div>

        <!-- Progresso gratuidade -->
        <?php if ($stats['threshold'] > 0): ?>
        <div class="admin-card p-5">
            <h3 class="font-display text-base font-bold mb-1" style="color:var(--sepia)">Próxima vaga-cortesia</h3>
            <p class="text-xs mb-4" style="color:var(--text-muted)">A cada <?= (int)$stats['threshold'] ?> indicações confirmadas, 1 vaga fica disponível pra você viajar.</p>
            <div class="flex items-baseline justify-between mb-2">
                <span class="font-display text-2xl font-bold" style="color:var(--terracota)"><?= $stats['paid_bookings'] % $stats['threshold'] ?> / <?= (int)$stats['threshold'] ?></span>
                <span class="text-xs" style="color:var(--text-muted)"><?= (int)$stats['to_next_free'] ?> restantes</span>
            </div>
            <div class="h-3 rounded-full overflow-hidden" style="background:var(--border-default)">
                <div class="h-full rounded-full transition-all" style="width:<?= (int)$stats['progress_pct'] ?>%;background:linear-gradient(90deg,var(--terracota),var(--horizonte))"></div>
            </div>
            <?php if ($stats['free_available'] > 0): ?>
                <div class="mt-4 p-3 rounded-lg flex items-start gap-2" style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3)">
                    <i data-lucide="gift" class="w-4 h-4 mt-0.5" style="color:#D97706"></i>
                    <div class="text-xs" style="color:#92400E"><b><?= (int)$stats['free_available'] ?> vaga(s)-cortesia</b> disponíveis! Fale conosco no WhatsApp pra resgatar.</div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Tipo de parceria -->
        <?php
        $typeLabels = ['individual'=>'Parceria individual','familia'=>'Família & amigos','grupo'=>'Grupo / comunidade','instituicao'=>'Instituição','revendedor'=>'Revenda / agência'];
        $typeIcons  = ['individual'=>'user','familia'=>'users','grupo'=>'users-round','instituicao'=>'building-2','revendedor'=>'store'];
        $pt = $partner['partner_type'] ?? 'individual';
        ?>
        <div class="admin-card p-5 flex items-start gap-3">
            <div class="w-10 h-10 flex-shrink-0 rounded-xl flex items-center justify-center" style="background:var(--bg-surface);color:var(--horizonte)">
                <i data-lucide="<?= $typeIcons[$pt] ?? 'user' ?>" class="w-5 h-5"></i>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider" style="color:var(--text-muted)">Tipo de parceria</div>
                <div class="font-semibold text-sm" style="color:var(--sepia)"><?= e($typeLabels[$pt] ?? 'Parceiro') ?></div>
            </div>
        </div>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/institution_foot.php';