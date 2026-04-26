<!-- ============== FOOTER ============== -->
<footer class="footer-dark pt-20 pb-8 relative overflow-hidden">
    <!-- Decorative wave top -->
    <div class="absolute top-0 inset-x-0 h-px" style="background:linear-gradient(90deg,transparent,rgba(201,107,74,0.5),transparent)"></div>
    <!-- Strategic rotating seal (single, footer corner) -->
    <img src="<?= asset('brand/selo-branco.png') ?>" class="seal-rotate absolute hidden md:block" style="bottom:30px;right:30px;width:90px;opacity:0.12" alt="">

    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
            <!-- Brand -->
            <div>
                <div class="mb-5">
                    <img src="<?= asset('brand/logo-areia.png') ?>" alt="Caminhos de Alagoas" style="height:46px;width:auto">
                </div>
                <p class="text-sm text-white/60 leading-relaxed mb-4">
                    <?= e(t('foot.tagline')) ?>
                </p>
                <p class="text-xs text-white/40">CNPJ <?= e(getSetting('cnpj', '50.770.482/0001-37')) ?></p>
            </div>

            <!-- SAC -->
            <div>
                <h4 class="font-display text-lg font-semibold text-white mb-5"><?= e(t('foot.support')) ?></h4>
                <ul class="space-y-3 text-sm">
                    <li class="flex items-start gap-2">
                        <i data-lucide="mail" class="w-4 h-4 mt-0.5 text-terracota-light"></i>
                        <div>
                            <div class="text-xs uppercase tracking-wider text-white/50 mb-0.5"><?= e(t('foot.email')) ?></div>
                            <a href="mailto:<?= e(getSetting('contact_email', APP_EMAIL)) ?>"><?= e(getSetting('contact_email', APP_EMAIL)) ?></a>
                        </div>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="phone" class="w-4 h-4 mt-0.5 text-terracota-light"></i>
                        <div>
                            <div class="text-xs uppercase tracking-wider text-white/50 mb-0.5">WhatsApp</div>
                            <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>"><?= e(getSetting('contact_phone', APP_PHONE)) ?></a>
                        </div>
                    </li>
                    <li class="flex items-start gap-2">
                        <i data-lucide="clock" class="w-4 h-4 mt-0.5 text-terracota-light"></i>
                        <div>
                            <div class="text-xs uppercase tracking-wider text-white/50 mb-0.5"><?= e(t('foot.hours')) ?></div>
                            <div><?= e(t('foot.hours_value')) ?></div>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Navegue -->
            <div>
                <h4 class="font-display text-lg font-semibold text-white mb-5"><?= e(t('foot.browse')) ?></h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?= url('/') ?>"><?= e(t('nav.home')) ?></a></li>
                    <li><a href="<?= url('/roteiros') ?>"><?= e(t('nav.tours')) ?></a></li>
                    <li><a href="<?= url('/pacotes') ?>"><?= e(t('nav.packages')) ?></a></li>
                    <li><a href="<?= url('/sobre') ?>"><?= e(t('nav.about')) ?></a></li>
                    <li><a href="<?= url('/contato') ?>"><?= e(t('nav.contact')) ?></a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div>
                <h4 class="font-display text-lg font-semibold text-white mb-3"><?= e(t('foot.newsletter_title')) ?></h4>
                <p class="text-sm text-white/60 mb-4"><?= e(t('foot.newsletter_sub')) ?></p>
                <form class="flex gap-2" onsubmit="event.preventDefault();caminhosApi('<?= url('/api/newsletter') ?>',{method:'POST',data:new FormData(this)}).then(r=>showToast(r.msg||'Inscrito!',r.ok?'success':'error'));this.reset();">
                    <input type="email" name="email" required placeholder="seu@email.com"
                           class="flex-1 px-4 py-2.5 rounded-xl text-sm text-white placeholder-white/50 border outline-none"
                           style="background:rgba(255,255,255,0.08);border-color:rgba(255,255,255,0.15)">
                    <button type="submit" class="px-4 py-2.5 rounded-xl text-white" style="background:var(--terracota)">
                        <i data-lucide="send" class="w-4 h-4"></i>
                    </button>
                </form>
                <div class="flex gap-3 mt-5">
                    <a href="<?= e(getSetting('instagram_url', '#')) ?>" target="_blank" class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,0.08)">
                        <i data-lucide="instagram" class="w-4 h-4"></i>
                    </a>
                    <a href="<?= e(getSetting('facebook_url', '#')) ?>" target="_blank" class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,0.08)">
                        <i data-lucide="facebook" class="w-4 h-4"></i>
                    </a>
                    <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>" class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,0.08)">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="pt-8 border-t border-white/10 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-white/50">
            <p>© <?= date('Y') ?> <?= e(t('foot.copyright')) ?></p>
            <div class="flex gap-6">
                <a href="#" class="hover:text-white"><?= e(t('foot.privacy')) ?></a>
                <a href="#" class="hover:text-white"><?= e(t('foot.terms')) ?></a>
            </div>
        </div>
    </div>
</footer>

<!-- Floating WhatsApp -->
<a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>" target="_blank"
   class="fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full flex items-center justify-center shadow-2xl hover:scale-110 transition-transform"
   style="background:#25D366;box-shadow:0 10px 30px rgba(37,211,102,0.45)">
    <i data-lucide="message-circle" class="w-6 h-6 text-white"></i>
</a>

<!-- ============== CART DRAWER ============== -->
<div id="cart-backdrop" class="cart-backdrop" onclick="window.cart && window.cart.close()"></div>
<aside id="cart-drawer" class="cart-drawer" aria-hidden="true">
    <header class="px-6 py-5 flex items-center justify-between border-b" style="border-color:var(--border-default)">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(201,107,74,0.1)">
                <i data-lucide="shopping-bag" class="w-5 h-5" style="color:var(--terracota)"></i>
            </div>
            <div>
                <div class="font-display text-lg font-bold" style="color:var(--sepia)">Seu Carrinho</div>
                <div class="text-xs" style="color:var(--text-muted)" id="cart-subtitle">0 itens</div>
            </div>
        </div>
        <button onclick="window.cart && window.cart.close()" class="w-9 h-9 rounded-lg flex items-center justify-center hover:bg-gray-100 transition" style="color:var(--text-secondary)">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </header>

    <div id="cart-body" class="flex-1 overflow-y-auto px-6 py-4 space-y-3">
        <!-- populated by JS -->
    </div>

    <div id="cart-empty" class="flex-1 flex flex-col items-center justify-center px-6 py-10 text-center">
        <div class="w-20 h-20 rounded-2xl flex items-center justify-center mb-4" style="background:rgba(58,107,138,0.08)">
            <i data-lucide="shopping-bag" class="w-10 h-10" style="color:var(--horizonte-light)"></i>
        </div>
        <h3 class="font-display text-xl font-bold mb-1" style="color:var(--sepia)">Carrinho vazio</h3>
        <p class="text-sm mb-6 max-w-xs" style="color:var(--text-muted)">Escolha um passeio ou pacote para começar sua próxima aventura.</p>
        <a href="<?= url('/roteiros') ?>" class="btn-primary" style="padding:12px 24px">
            <i data-lucide="compass" class="w-4 h-4"></i> Explorar passeios
        </a>
    </div>

    <footer id="cart-footer" class="border-t px-6 py-5 space-y-4" style="border-color:var(--border-default);display:none">
        <div class="flex justify-between items-end">
            <div class="text-xs uppercase tracking-wider font-semibold" style="color:var(--text-muted)">Total</div>
            <div class="font-display text-2xl font-bold" id="cart-total" style="color:var(--terracota)">R$ 0,00</div>
        </div>
        <a href="<?= url('/checkout') ?>" id="cart-checkout-link" class="btn-primary w-full">
            <i data-lucide="lock" class="w-4 h-4"></i> Finalizar Reserva
        </a>
        <button onclick="window.cart && window.cart.clear()" class="w-full text-xs font-semibold py-2 hover:underline" style="color:var(--text-muted)">
            Limpar carrinho
        </button>
    </footer>
</aside>

<script src="<?= asset('js/app.js') ?>"></script>

<?php $__pending = autotrPending(); if ($__pending): ?>
<script>
/* Auto-translate flush: envia ao /api/autotranslate-flush os textos que não couberam no budget deste request.
   Na próxima navegação, a pagina abre ja traduzida. */
(function(){
    var items = <?= json_encode(array_map(fn($h,$d)=>['hash'=>$h,'text'=>$d['text'],'lang'=>$d['lang']], array_keys($__pending), $__pending), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    if (!items.length) return;
    // Roda depois que a pagina carrega para não concorrer com o resto
    function flushBatch(batch) {
        return fetch('<?= url('/api/autotranslate-flush') ?>', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({items: batch}), keepalive: true
        }).catch(()=>{});
    }
    window.addEventListener('load', function(){
        setTimeout(async function(){
            for (var i=0; i<items.length; i+=20) await flushBatch(items.slice(i,i+20));
        }, 800);
    });
})();
</script>
<?php endif; ?>

<!-- Modal: escolher data antes de adicionar ao carrinho -->
<div x-data="cartDateModal()" x-init="init()" @cart:ask-date.window="open($event.detail)" x-cloak>
    <div x-show="visible" x-transition.opacity class="fixed inset-0 z-[100]" style="background:rgba(15,23,42,0.55);backdrop-filter:blur(4px)" @click="close"></div>
    <div x-show="visible" x-transition class="fixed inset-0 z-[101] flex items-center justify-center p-4 pointer-events-none">
        <div class="admin-card max-w-md w-full p-6 pointer-events-auto" @click.stop style="box-shadow:0 24px 60px rgba(0,0,0,0.25)">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:rgba(201,107,74,0.1);color:var(--terracota)"><i data-lucide="calendar-days" class="w-6 h-6"></i></div>
                <div class="flex-1">
                    <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Escolha a data</h3>
                    <p class="text-xs" style="color:var(--text-muted)" x-text="title || 'Para qual dia você quer reservar?'"></p>
                </div>
                <button @click="close" class="text-2xl leading-none" style="color:var(--text-muted)">&times;</button>
            </div>
            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">Data preferida *</label>
            <input type="date" x-model="travelDate" :min="today" class="admin-input w-full">
            <p class="text-[11px] mt-2" style="color:var(--text-muted)"><i data-lucide="info" class="w-3 h-3 inline -mt-0.5"></i> Você pode alterar no checkout. Itens com datas diferentes ficam separados no carrinho.</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="close" class="admin-btn admin-btn-secondary">Cancelar</button>
                <button type="button" @click="confirm()" class="btn-primary" :disabled="!travelDate" :class="!travelDate && 'opacity-60 cursor-not-allowed'"><i data-lucide="check" class="w-4 h-4"></i>Adicionar ao carrinho</button>
            </div>
        </div>
    </div>
</div>
<script>
function cartDateModal(){
    return {
        visible:false, type:null, id:null, title:'', travelDate:'',
        today: new Date().toISOString().split('T')[0],
        init(){},
        open(d){ this.type=d.type; this.id=d.id; this.title=d.title; this.travelDate=''; this.visible=true; document.body.style.overflow='hidden'; this.$nextTick(()=>window.lucide && window.lucide.createIcons()); },
        close(){ this.visible=false; document.body.style.overflow=''; },
        async confirm(){ if(!this.travelDate) return; await window.cart.add(this.type, this.id, this.travelDate); this.close(); }
    }
}
</script>
</body>
</html>
