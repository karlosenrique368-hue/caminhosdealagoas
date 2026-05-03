<?php
$bookingCode = strtoupper(preg_replace('/[^A-Z0-9-]/i', '', $_GET['code'] ?? ''));
if ($bookingCode === '') { redirect('/'); }
$booking = dbOne('SELECT * FROM bookings WHERE code = ? LIMIT 1', [$bookingCode]);
if (!$booking) { http_response_code(404); echo 'Reserva nao encontrada'; return; }
$customer = dbOne('SELECT * FROM customers WHERE id = ?', [$booking['customer_id']]);
$mpPublicKey = mercadoPagoActivePublicKey();
$sandbox = integrationEnabled('payment_sandbox', true);
$macaiokMode = false;
if (!empty($booking['institution_id'])) {
    $macaiokMode = (bool) dbOne("SELECT id FROM institutions WHERE id=? AND program='macaiok' LIMIT 1", [(int)$booking['institution_id']]);
}
if (!$macaiokMode && !empty($booking['referral_code'])) {
    $macaiokMode = (bool) dbOne("SELECT id FROM institutions WHERE referral_code=? AND program='macaiok' LIMIT 1", [$booking['referral_code']]);
}
if ($macaiokMode) $GLOBALS['macaiokMode'] = true;
$pageTitle = ($macaiokMode ? 'Macaiok - Pagamento' : 'Pagamento') . ' - ' . $bookingCode;
$solidNav = true;

if ((string)$booking['payment_status'] === 'paid') {
    include VIEWS_DIR . '/partials/public_head.php';
    ?>
    <section class="pt-32 pb-20" style="background:var(--bg-surface);min-height:80vh">
        <div class="max-w-xl mx-auto px-6 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full flex items-center justify-center" style="background:rgba(34,197,94,.12);color:#15803D"><i data-lucide="check-circle-2" class="w-10 h-10"></i></div>
            <h1 class="font-display text-3xl font-bold mb-3" style="color:var(--sepia)">Pagamento confirmado</h1>
            <p class="text-sm mb-6" style="color:var(--text-secondary)">Sua reserva <strong><?= e($bookingCode) ?></strong> esta confirmada. Enviamos os detalhes por email.</p>
            <a href="<?= url($macaiokMode ? '/macaiok/conta/reservas' : '/conta/reservas') ?>" class="btn-primary inline-flex"><i data-lucide="ticket" class="w-4 h-4"></i>Ver minhas reservas</a>
        </div>
    </section>
    <?php
    include VIEWS_DIR . '/partials/public_foot.php';
    return;
}

include VIEWS_DIR . '/partials/public_head.php';
?>
<script src="https://sdk.mercadopago.com/js/v2"></script>

<?php if ($macaiokMode): ?>
<div class="pt-24 pb-2" style="background:linear-gradient(180deg,#324500 0%, #2F1607 100%)">
    <div class="max-w-5xl mx-auto px-6 flex items-center gap-3 flex-wrap">
        <img src="<?= asset('img/macaiok/VerdeEscuro_Horizontal.png') ?>" alt="Macaiok" class="h-8" style="filter:brightness(0) invert(1)">
        <span class="text-[11px] font-bold uppercase tracking-[0.24em] text-white/90">Vivencias pedagogicas - Pagamento da reserva</span>
        <span class="ml-auto text-[11px] text-white/70">Processado por <strong>Caminhos de Alagoas</strong></span>
    </div>
</div>
<?php endif; ?>

<section class="<?= $macaiokMode ? 'pt-6' : 'pt-28' ?> pb-20" style="background:linear-gradient(180deg,var(--bg-surface) 0%, var(--bg-page) 100%);min-height:80vh">
<div class="max-w-5xl mx-auto px-4 sm:px-6" x-data="paymentPage()" x-init="init()">

    <div class="mb-8">
        <span class="text-[11px] font-bold uppercase tracking-[0.24em]" style="color:var(--terracota)">Reserva <?= e($bookingCode) ?></span>
        <h1 class="font-display text-3xl sm:text-4xl font-bold mt-2" style="color:var(--sepia)">Finalize seu pagamento</h1>
        <p class="text-sm mt-2" style="color:var(--text-secondary)"><?= e($booking['entity_title']) ?> - Total <strong style="color:var(--terracota)"><?= formatBRL($booking['total']) ?></strong></p>
        <?php if ($sandbox): ?>
            <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold" style="background:rgba(245,158,11,.12);color:#B45309"><i data-lucide="flask-conical" class="w-3.5 h-3.5"></i>Modo TESTE - cartao oficial: 5031 4332 1540 6351 / CVV 123 / titular APRO</div>
        <?php endif; ?>
    </div>

    <div class="grid lg:grid-cols-[1fr_360px] gap-6">
        <div class="space-y-4">
            <!-- Tabs -->
            <div class="flex gap-2">
                <button @click="tab='card'" type="button" class="flex-1 py-3 px-4 rounded-xl font-semibold text-sm transition border-2" :style="tab==='card' ? 'border-color:var(--terracota);background:rgba(218,74,52,.06);color:var(--terracota)' : 'border-color:var(--border-default);background:var(--bg-card);color:var(--text-secondary)'"><i data-lucide="credit-card" class="w-4 h-4 inline"></i> Cartao</button>
                <button @click="tab='pix'; loadPix()" type="button" class="flex-1 py-3 px-4 rounded-xl font-semibold text-sm transition border-2" :style="tab==='pix' ? 'border-color:var(--maresia-dark);background:rgba(122,157,110,.08);color:var(--maresia-dark)' : 'border-color:var(--border-default);background:var(--bg-card);color:var(--text-secondary)'"><i data-lucide="qr-code" class="w-4 h-4 inline"></i> PIX</button>
            </div>

            <!-- Card Brick -->
            <div x-show="tab==='card'" class="admin-card p-6">
                <h3 class="font-display font-bold mb-4 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="credit-card" class="w-5 h-5" style="color:var(--terracota)"></i>Pagar com cartao</h3>
                <div id="cardPaymentBrick_container" wire:ignore></div>
                <div x-show="cardError" x-cloak class="mt-4 p-3 rounded-lg text-sm" style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.3);color:#B91C1C" x-text="cardError"></div>
                <div x-show="cardLoading" x-cloak class="mt-4 flex items-center gap-2 text-sm" style="color:var(--text-secondary)"><div class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>Processando pagamento...</div>
            </div>

            <!-- PIX -->
            <div x-show="tab==='pix'" x-cloak class="admin-card p-6 text-center">
                <h3 class="font-display font-bold mb-4 flex items-center justify-center gap-2" style="color:var(--sepia)"><i data-lucide="qr-code" class="w-5 h-5" style="color:var(--maresia-dark)"></i>Pague com PIX</h3>
                <div x-show="pixLoading" class="py-8 flex items-center justify-center gap-2" style="color:var(--text-secondary)"><div class="w-5 h-5 border-2 border-current border-t-transparent rounded-full animate-spin"></div>Gerando QR Code...</div>
                <div x-show="pixError" x-cloak class="p-3 rounded-lg text-sm" style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.3);color:#B91C1C" x-text="pixError"></div>
                <template x-if="pixData">
                    <div>
                        <img :src="'data:image/png;base64,'+pixData.qr_code_base64" alt="QR Code PIX" class="mx-auto w-64 h-64 rounded-xl" style="border:1px solid var(--border-default)">
                        <p class="text-sm mt-4" style="color:var(--text-secondary)">Escaneie com seu app do banco ou copie o codigo abaixo:</p>
                        <div class="mt-3 flex gap-2">
                            <input type="text" :value="pixData.qr_code" readonly class="admin-input font-mono text-xs" id="pixCode">
                            <button type="button" @click="copyPix()" class="btn-primary text-xs"><i data-lucide="copy" class="w-4 h-4"></i><span x-text="copied ? 'Copiado!' : 'Copiar'"></span></button>
                        </div>
                        <p class="text-xs mt-3" style="color:var(--text-muted)">Valido por 24h. Confirmaremos automaticamente quando o pagamento entrar.</p>
                    </div>
                </template>
            </div>
        </div>

        <!-- Resumo -->
        <aside>
            <div class="admin-card p-5 lg:sticky lg:top-24">
                <div class="text-[10px] uppercase font-bold tracking-widest mb-1" style="color:var(--terracota)"><?= e($booking['entity_type']) ?></div>
                <div class="font-display font-semibold leading-snug mb-3" style="color:var(--sepia)"><?= e($booking['entity_title']) ?></div>
                <div class="py-3 border-t border-b space-y-2 text-sm" style="border-color:var(--border-default)">
                    <div class="flex justify-between"><span style="color:var(--text-secondary)">Reserva</span><span class="font-mono" style="color:var(--sepia)"><?= e($bookingCode) ?></span></div>
                    <div class="flex justify-between"><span style="color:var(--text-secondary)">Subtotal</span><span style="color:var(--sepia)"><?= formatBRL($booking['subtotal']) ?></span></div>
                    <?php if ((float)$booking['discount'] > 0): ?>
                    <div class="flex justify-between"><span style="color:var(--text-secondary)">Desconto</span><span style="color:#15803D">-<?= formatBRL($booking['discount']) ?></span></div>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between items-end pt-4">
                    <span class="text-sm font-semibold" style="color:var(--text-secondary)">Total</span>
                    <span class="font-display text-3xl font-bold" style="color:var(--terracota)"><?= formatBRL($booking['total']) ?></span>
                </div>
                <div class="mt-4 text-[11px] flex items-center gap-1.5" style="color:var(--text-muted)"><i data-lucide="shield-check" class="w-3.5 h-3.5"></i>Pagamento processado pelo Mercado Pago</div>
            </div>
        </aside>
    </div>
</div>
</section>

<script>
function paymentPage() {
    let mpInstance = null;
    let bricksBuilder = null;
    return {
        tab: 'card',
        cardError: '',
        cardLoading: false,
        pixLoading: false,
        pixError: '',
        pixData: null,
        copied: false,
        async waitForMP() {
            for (let i=0;i<60;i++){
                if (window.MercadoPago) return true;
                await new Promise(r=>setTimeout(r,200));
            }
            return !!window.MercadoPago;
        },
        async init() {
            if (window.lucide) window.lucide.createIcons();
            const pk = <?= json_encode($mpPublicKey) ?>;
            if (!pk) { this.cardError = 'Mercado Pago nao configurado. Acesse Admin > Integracoes e cadastre as credenciais.'; return; }
            const ok = await this.waitForMP();
            if (!ok) { this.cardError = 'Falha ao carregar SDK do Mercado Pago. Verifique sua conexao e recarregue.'; return; }
            try {
                mpInstance = new MercadoPago(pk, { locale: 'pt-BR' });
                bricksBuilder = mpInstance.bricks();
                await this.renderCardBrick();
                this.pollStatus();
            } catch (err) {
                console.error('[mp.init]', err);
                this.cardError = 'Erro ao iniciar checkout: ' + (err && err.message || err);
            }
        },
        async renderCardBrick() {
            const total = <?= json_encode((float)$booking['total']) ?>;
            const self = this;
            const container = document.getElementById('cardPaymentBrick_container');
            if (container && container.dataset.mounted === '1') return;
            if (container) { container.dataset.mounted = '1'; container.innerHTML = ''; }
            await bricksBuilder.create('cardPayment', 'cardPaymentBrick_container', {
                initialization: {
                    amount: total,
                    payer: { email: <?= json_encode($customer['email'] ?? '') ?> }
                },
                customization: {
                    visual: { style: { theme: 'default' } },
                    paymentMethods: { maxInstallments: 12, minInstallments: 1 }
                },
                callbacks: {
                    onReady: () => {},
                    onError: (e) => { console.error('[mp.cardBrick]', e); self.cardError = 'Erro ao carregar formulario do cartao. Confirme se a public key TESTE pertence ao mesmo vendedor do access token.'; },
                    onSubmit: async (cardData) => {
                        self.cardError = '';
                        self.cardLoading = true;
                        try {
                            const formData = cardData && (cardData.formData || cardData);
                            if (!formData || !formData.token) {
                                console.error('[mp.cardSubmit.invalidPayload]', cardData);
                                self.cardError = 'O Mercado Pago nao retornou o token do cartao. Recarregue a pagina e tente novamente.';
                                return;
                            }
                            const payer = formData.payer || {};
                            const identification = payer.identification || {};
                            const res = await caminhosApi('<?= url('/api/payment-process') ?>?action=card', {
                                method: 'POST',
                                data: {
                                    booking_code: <?= json_encode($bookingCode) ?>,
                                    token: formData.token,
                                    payment_method_id: formData.payment_method_id,
                                    issuer_id: formData.issuer_id || '',
                                    installments: formData.installments || 1,
                                    payer_email: payer.email || formData.payer_email || '',
                                    payer_doc_type: identification.type || 'CPF',
                                    payer_doc_number: identification.number || ''
                                }
                            });
                            if (res.ok && res.status === 'approved') {
                                showToast('Pagamento aprovado!', 'success');
                                setTimeout(() => window.location.reload(), 800);
                            } else if (res.ok && res.status === 'in_process') {
                                showToast('Pagamento em analise. Avisaremos por email.', 'info');
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                self.cardError = res.msg || 'Pagamento recusado. Tente outro cartao.';
                            }
                        } catch (e) {
                            console.error('[mp.cardSubmit]', e);
                            self.cardError = 'Erro ao enviar o pagamento. Recarregue a pagina e tente novamente.';
                        } finally {
                            self.cardLoading = false;
                        }
                    }
                }
            });
        },
        async loadPix() {
            if (this.pixData || this.pixLoading) return;
            this.pixLoading = true;
            this.pixError = '';
            try {
                const res = await caminhosApi('<?= url('/api/payment-process') ?>?action=pix', {
                    method: 'POST',
                    data: { booking_code: <?= json_encode($bookingCode) ?> }
                });
                if (res.ok) {
                    this.pixData = res;
                    this.$nextTick(() => window.lucide && window.lucide.createIcons());
                } else {
                    this.pixError = res.msg || 'Falha ao gerar PIX.';
                }
            } catch (e) {
                this.pixError = 'Erro de conexao.';
            } finally {
                this.pixLoading = false;
            }
        },
        copyPix() {
            const el = document.getElementById('pixCode');
            el.select();
            document.execCommand('copy');
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
        async pollStatus() {
            // verifica a cada 5s se webhook ja confirmou
            setInterval(async () => {
                try {
                    const res = await caminhosApi('<?= url('/api/payment-process') ?>?action=status&booking_code=' + encodeURIComponent(<?= json_encode($bookingCode) ?>), { method: 'GET' });
                    if (res.ok && res.payment_status === 'paid') window.location.reload();
                } catch (e) {}
            }, 5000);
        }
    };
}
</script>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>