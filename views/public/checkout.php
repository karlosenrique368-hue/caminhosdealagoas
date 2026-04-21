<?php
$pageTitle = 'Checkout';
$solidNav = true;

$roteiroId = (int)($_GET['roteiro'] ?? 0);
$pacoteId  = (int)($_GET['pacote'] ?? 0);
$item = null;
$type = null;

// Fallback: use first cart item if no query params
if (!$roteiroId && !$pacoteId && !empty($_SESSION['cart'])) {
    $first = reset($_SESSION['cart']);
    if ($first['type'] === 'roteiro') $roteiroId = (int)$first['id'];
    elseif ($first['type'] === 'pacote') $pacoteId = (int)$first['id'];
}

if ($roteiroId) {
    $item = dbOne("SELECT * FROM roteiros WHERE id=? AND status='published'", [$roteiroId]);
    $type = 'roteiro';
} elseif ($pacoteId) {
    $item = dbOne("SELECT * FROM pacotes WHERE id=? AND status='published'", [$pacoteId]);
    $type = 'pacote';
}

if (!$item) { redirect('/roteiros'); }
include VIEWS_DIR . '/partials/public_head.php';
?>
<section class="pt-32 pb-16" style="background:var(--bg-surface)">
    <div class="max-w-6xl mx-auto px-6">
        <h1 class="font-display text-3xl md:text-4xl font-bold mb-8 text-center" style="color:var(--sepia)">Finalizar reserva</h1>

        <div class="grid lg:grid-cols-3 gap-8" x-data="checkoutApp()" @submit.prevent="submit">
            <!-- Form -->
            <form class="lg:col-span-2 space-y-6">
                <div class="admin-card p-6">
                    <h3 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Seus dados</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Nome completo</label><input x-model="form.name" required class="admin-input"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">CPF</label><input x-model="form.document" required class="admin-input" placeholder="000.000.000-00"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">E-mail</label><input type="email" x-model="form.email" required class="admin-input"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">WhatsApp</label><input type="tel" x-model="form.phone" required class="admin-input phone-mask"></div>
                    </div>
                </div>

                <div class="admin-card p-6">
                    <h3 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Detalhes da viagem</h3>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Data</label><input type="date" x-model="form.travel_date" class="admin-input"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Adultos</label><input type="number" min="1" x-model.number="form.adults" class="admin-input"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Crianças</label><input type="number" min="0" x-model.number="form.children" class="admin-input"></div>
                    </div>
                </div>

                <div class="admin-card p-6">
                    <h3 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Forma de pagamento</h3>
                    <div class="grid grid-cols-3 gap-3">
                        <template x-for="pm in paymentMethods">
                            <button type="button" @click="form.payment_method=pm.id" class="p-4 rounded-xl border-2 text-center transition" :style="form.payment_method===pm.id ? 'border-color:var(--terracota);background:rgba(201,107,74,0.05)' : 'border-color:var(--border-default)'">
                                <i :data-lucide="pm.icon" class="w-6 h-6 mx-auto mb-2" style="color:var(--terracota)"></i>
                                <div class="text-xs font-semibold" style="color:var(--sepia)" x-text="pm.label"></div>
                            </button>
                        </template>
                    </div>
                </div>

                <button type="submit" :disabled="loading" class="btn-primary w-full" :class="loading&&'opacity-60'">
                    <i data-lucide="lock" class="w-5 h-5"></i>
                    <span x-text="loading?'Processando...':'Finalizar reserva'">Finalizar reserva</span>
                </button>
            </form>

            <!-- Summary -->
            <aside>
                <div class="admin-card p-6 lg:sticky lg:top-28">
                    <h3 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Resumo</h3>
                    <div class="flex gap-3 mb-4">
                        <?php if ($item['cover_image']): ?>
                            <img src="<?= storageUrl($item['cover_image']) ?>" class="w-20 h-20 rounded-lg object-cover">
                        <?php else: ?>
                            <div class="w-20 h-20 rounded-lg img-placeholder"><span class="text-xl"><?= e(mb_substr($item['title'],0,1)) ?></span></div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:var(--terracota)"><?= e($type) ?></div>
                            <div class="text-sm font-semibold leading-snug" style="color:var(--sepia)"><?= e($item['title']) ?></div>
                        </div>
                    </div>
                    <div class="py-4 border-t border-b space-y-2" style="border-color:var(--border-default)">
                        <div class="flex justify-between text-sm"><span style="color:var(--text-secondary)">Subtotal</span><span class="font-semibold" style="color:var(--sepia)" x-text="'R$ ' + subtotal().toFixed(2).replace('.',',')"></span></div>
                        <div class="flex justify-between text-sm"><span style="color:var(--text-secondary)">Desconto PIX</span><span class="font-semibold" style="color:var(--maresia-dark)" x-text="form.payment_method==='pix' ? '- R$ ' + discount().toFixed(2).replace('.',','): 'R$ 0,00'"></span></div>
                    </div>
                    <div class="flex justify-between items-end pt-4">
                        <span class="text-sm" style="color:var(--text-secondary)">Total</span>
                        <span class="font-display text-3xl font-bold" style="color:var(--terracota)" x-text="'R$ ' + total().toFixed(2).replace('.',',')"></span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
function checkoutApp() {
    return {
        loading: false,
        form: {
            name:'', document:'', email:'', phone:'',
            travel_date:'', adults:1, children:0,
            payment_method:'pix',
            entity_type: '<?= $type ?>',
            entity_id: <?= (int)$item['id'] ?>
        },
        paymentMethods: [
            {id:'pix', label:'PIX', icon:'qr-code'},
            {id:'credit_card', label:'Cartão', icon:'credit-card'},
            {id:'boleto', label:'Boleto', icon:'file-text'}
        ],
        price: <?= (float)$item['price'] ?>,
        pricePix: <?= (float)($item['price_pix'] ?: $item['price']) ?>,
        subtotal() { return this.price * Math.max(1, this.form.adults) + this.price * 0.5 * Math.max(0, this.form.children); },
        discount() { return this.form.payment_method === 'pix' ? this.subtotal() - (this.pricePix * this.form.adults + this.pricePix * 0.5 * this.form.children) : 0; },
        total() { return this.subtotal() - this.discount(); },
        async submit() {
            this.loading = true;
            const res = await caminhosApi('<?= url('/api/booking') ?>', { method:'POST', data: this.form });
            showToast(res.msg, res.ok ? 'success' : 'error');
            if (res.ok && res.redirect) window.location = res.redirect;
            this.loading = false;
        }
    }
}
</script>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
