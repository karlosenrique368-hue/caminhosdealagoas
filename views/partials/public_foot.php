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
                    <li><a href="<?= url('/passeios') ?>"><?= e(t('nav.tours')) ?></a></li>
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
    class="floating-whatsapp fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full flex items-center justify-center shadow-2xl hover:scale-110 transition-transform"
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
        <a href="<?= url('/passeios') ?>" class="btn-primary" style="padding:12px 24px">
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

<script>
function cartDateModal(){
    return {
        visible:false, type:null, id:null, title:'', loading:false,
        mode:'fixed', map:{}, basePrice:0, selectedDates:[], viewYear:0, viewMonth:0,
        today: new Date().toISOString().split('T')[0],
        init(){ const now=new Date(); this.viewYear=now.getFullYear(); this.viewMonth=now.getMonth(); },
        open(d){
            // Sinaliza ao app.js que o modal Alpine está pronto e tratou o evento
            window.dispatchEvent(new Event('cart:ask-date-handled'));
            this.type=d.type; this.id=d.id; this.title=d.title; this.selectedDates=[]; this.map={}; this.loading=true; this.visible=true;
            document.body.style.overflow='hidden';
            this.fetchAvailability();
            this.$nextTick(()=>window.lucide && window.lucide.createIcons());
        },
        close(){ this.visible=false; document.body.style.overflow=''; },
        async fetchAvailability(){
            try {
                const qs = new URLSearchParams({type:this.type, id:this.id});
                const res = await fetch('<?= url('/api/availability') ?>?' + qs.toString(), {credentials:'same-origin'});
                const j = await res.json();
                if (j.ok) {
                    this.mode = j.mode || 'fixed'; this.map = j.map || {}; this.basePrice = Number(j.basePrice || 0);
                    const first = Object.keys(this.map).find(k => this.isAvailableIso(k));
                    if (first) { const d = new Date(first+'T12:00:00'); this.viewYear=d.getFullYear(); this.viewMonth=d.getMonth(); }
                }
            } catch(e) { window.showToast && window.showToast('Não foi possível carregar as datas.', 'error'); }
            finally { this.loading=false; this.$nextTick(()=>window.lucide && window.lucide.createIcons()); }
        },
        pad(n){ return n<10?'0'+n:''+n; },
        iso(y,m,d){ return y+'-'+this.pad(m+1)+'-'+this.pad(d); },
        brl(v){ return 'R$ ' + Number(v||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); },
        isAvailableIso(iso){
            const today = new Date(this.today+'T00:00:00');
            const dt = new Date(iso+'T00:00:00');
            if (dt < today || this.mode === 'on_request') return false;
            const info = this.map[iso];
            if (info) return info.status === 'open' && Number(info.seats || 0) > 0;
            return this.mode === 'open';
        },
        get monthLabel(){ const n=['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro']; return n[this.viewMonth]+' de '+this.viewYear; },
        get cells(){
            const first = new Date(this.viewYear,this.viewMonth,1);
            const start = first.getDay();
            const days = new Date(this.viewYear,this.viewMonth+1,0).getDate();
            const cells=[];
            for(let i=0;i<start;i++) cells.push({key:'e'+i,empty:true});
            for(let d=1;d<=days;d++){
                const iso=this.iso(this.viewYear,this.viewMonth,d), info=this.map[iso], available=this.isAvailableIso(iso);
                cells.push({key:iso,iso,day:d,empty:false,available,lowSeats:available&&info&&Number(info.seats)<=3,blocked:!!info&&!available,price:(info&&info.price)||this.basePrice,seats:info?info.seats:null});
            }
            return cells;
        },
        prevYear(){ this.viewYear--; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        nextYear(){ this.viewYear++; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        prevMonth(){ if(this.viewMonth===0){this.viewMonth=11;this.viewYear--;}else this.viewMonth--; },
        nextMonth(){ if(this.viewMonth===11){this.viewMonth=0;this.viewYear++;}else this.viewMonth++; },
        toggle(cell){ if(!cell.available) return; const i=this.selectedDates.indexOf(cell.iso); if(i>=0) this.selectedDates.splice(i,1); else this.selectedDates.push(cell.iso); this.selectedDates.sort(); },
        selectedLabel(){ return this.selectedDates.length === 1 ? '1 data selecionada' : this.selectedDates.length + ' datas selecionadas'; },
        selectedDatesText(){ return this.selectedDates.map(d => new Date(d+'T12:00:00').toLocaleDateString('pt-BR',{day:'2-digit',month:'short',year:'numeric'})).join(', '); },
        async confirm(){ if(!this.selectedDates.length){ window.showToast && window.showToast('Escolha pelo menos uma data disponível.', 'error'); return; } await window.cart.add(this.type, this.id, this.selectedDates); this.close(); }
    }
}

function reviewSection(opts){
    return {
        formOpen:false, loading:false, photos:[],
        form:{ rating:0, title:'', content:'' },
        handlePhotos(e){ this.photos = Array.from(e.target.files || []).slice(0,4); },
        async submit(){
            if (this.loading) return;
            if (!this.form.rating) { window.showToast && window.showToast('Escolha uma nota.', 'error'); return; }
            if ((this.form.content||'').trim().length < 10) { window.showToast && window.showToast('Conte mais sobre sua experiência (mín. 10 caracteres).', 'error'); return; }
            this.loading = true;
            try {
                const fd = new FormData();
                fd.append('booking_id', opts.bookingId);
                fd.append('entity_type', opts.entityType);
                fd.append('entity_id', opts.entityId);
                fd.append('rating', this.form.rating);
                fd.append('title', this.form.title);
                fd.append('content', this.form.content);
                this.photos.forEach(file => fd.append('photos[]', file));
                const r = await window.avikApi ? null : null;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                fd.append('csrf_token', csrf);
                const res = await fetch('<?= url('/api/reviews?action=add_review') ?>', { method:'POST', body:fd, credentials:'same-origin' });
                const j = await res.json();
                if (j.ok) {
                    window.showToast && window.showToast('Obrigado! Sua avaliação foi enviada e será publicada após análise.', 'success');
                    this.formOpen = false; this.form = {rating:0,title:'',content:''}; this.photos = [];
                } else {
                    window.showToast && window.showToast(j.msg || 'Erro ao enviar.', 'error');
                }
            } catch(e) { window.showToast && window.showToast('Erro de rede.', 'error'); }
            finally { this.loading = false; }
        }
    }
}
</script>

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
            <div class="flex items-center justify-between gap-3 mb-3">
                <label class="block text-xs font-bold uppercase tracking-wider" style="color:var(--text-secondary)">Datas disponíveis *</label>
                <div class="flex items-center gap-1">
                    <button type="button" @click.stop="prevYear()" class="w-8 h-8 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)" aria-label="Ano anterior"><i data-lucide="chevrons-left" class="w-4 h-4"></i></button>
                    <button type="button" @click="prevMonth()" class="w-8 h-8 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                    <div class="min-w-[120px] text-center text-sm font-bold" style="color:var(--sepia)" x-text="monthLabel"></div>
                    <button type="button" @click="nextMonth()" class="w-8 h-8 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                    <button type="button" @click.stop="nextYear()" class="w-8 h-8 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)" aria-label="Próximo ano"><i data-lucide="chevrons-right" class="w-4 h-4"></i></button>
                </div>
            </div>
            <div x-show="loading" class="p-8 text-center text-sm" style="color:var(--text-muted)">Carregando datas...</div>
            <div x-show="!loading" class="calendar-grid" style="gap:5px">
                <template x-for="dow in ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb']" :key="dow">
                    <div class="text-center text-[10px] font-bold uppercase py-1" style="color:var(--text-muted)" x-text="dow"></div>
                </template>
                <template x-for="cell in cells" :key="cell.key">
                    <button type="button" :disabled="!cell.available" @click="toggle(cell)" class="calendar-cell" style="min-height:52px" :class="{'empty':cell.empty,'available':cell.available&&!cell.lowSeats,'low':cell.available&&cell.lowSeats,'blocked':cell.blocked,'selected':selectedDates.includes(cell.iso)}">
                        <span class="cal-day" x-text="cell.day"></span>
                        <span class="cal-price" x-show="cell.available" x-text="cell.seats !== null ? cell.seats + ' vagas' : 'Livre'"></span>
                    </button>
                </template>
            </div>
            <div x-show="!loading && mode==='on_request'" class="p-4 rounded-xl text-sm text-center" style="background:rgba(58,107,138,.08);color:var(--horizonte)">Essa experiência está sob consulta. Fale com a equipe para combinar a data.</div>
            <div x-show="selectedDates.length" x-cloak class="mt-3 p-3 rounded-xl text-xs" style="background:rgba(201,107,74,.08);border:1px solid rgba(201,107,74,.25);color:var(--text-secondary)">
                <b style="color:var(--sepia)" x-text="selectedLabel()"></b><br><span x-text="selectedDatesText()"></span>
            </div>
            <p class="text-[11px] mt-2" style="color:var(--text-muted)"><i data-lucide="info" class="w-3 h-3 inline -mt-0.5"></i> Você pode escolher uma ou várias datas disponíveis. No checkout os valores atualizam automaticamente.</p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="close" class="admin-btn admin-btn-secondary">Cancelar</button>
                <button type="button" @click="confirm()" class="btn-primary" :disabled="!selectedDates.length" :class="!selectedDates.length && 'opacity-60 cursor-not-allowed'"><i data-lucide="check" class="w-4 h-4"></i>Adicionar ao carrinho</button>
            </div>
        </div>
    </div>
</div>

<!-- Auto-init de mapas Leaflet em qualquer .meeting-map[data-lat][data-lng] -->
<script>
(function(){
    function initMaps(){
        if (typeof L === 'undefined') return false;
        document.querySelectorAll('.meeting-map[data-lat][data-lng]').forEach(function(el){
            if (el.dataset.initialized) return;
            el.dataset.initialized = '1';
            var lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
            if (!isFinite(lat) || !isFinite(lng)) return;
            var label = el.dataset.label || 'Ponto de encontro';
            var map = L.map(el, { zoomControl: true, scrollWheelZoom: false }).setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'&copy; OpenStreetMap', maxZoom: 19 }).addTo(map);
            var pin = L.divIcon({
                className: 'meeting-pin',
                html: '<div style="width:30px;height:30px;background:#DC2626;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid white;box-shadow:0 4px 14px rgba(0,0,0,.35);display:flex;align-items:center;justify-content:center"><div style="width:8px;height:8px;background:white;border-radius:50%;transform:rotate(45deg)"></div></div>',
                iconSize: [30, 30], iconAnchor: [15, 30], popupAnchor: [0, -28]
            });
            L.marker([lat, lng], { icon: pin }).addTo(map).bindPopup('<b>'+label.replace(/[<>&]/g,'')+'</b>');
            // Reflow no resize
            setTimeout(function(){ map.invalidateSize(); }, 200);
        });
        return true;
    }
    function tryInit(){ if (!initMaps()) setTimeout(tryInit, 100); }
    if (document.readyState !== 'loading') tryInit(); else document.addEventListener('DOMContentLoaded', tryInit);
})();
</script>

<!-- Tradução automática site-wide: instantânea para UI comum + cache/API para conteúdo dinâmico -->
<script>
(function(){
    var INITIAL_LANG = <?= json_encode(currentLang()) ?>;
    var ENDPOINT = '<?= url('/api/translate-batch') ?>';
    var BATCH = 36;
    var current = readCookie('lang') || localStorage.getItem('caminhos_lang') || INITIAL_LANG || 'pt-BR';
    var SKIP_TAGS = {SCRIPT:1,STYLE:1,CODE:1,PRE:1,TEXTAREA:1,INPUT:1,SELECT:1,OPTION:1,NOSCRIPT:1,SVG:1,IFRAME:1};
    var ORIGINAL_TEXT = new WeakMap();
    var ATTR_PREFIX = 'i18nOriginal';
    var DICT = {
        en:{'Home':'Home','Passeios':'Tours','Pacotes':'Packages','Transfers':'Transfers','Sobre nós':'About us','Contato':'Contact','Entrar':'Sign in','Criar conta':'Create account','Minha conta':'My account','Minhas reservas':'My bookings','Favoritos':'Favorites','Sair':'Sign out','Navegação':'Navigation','Conta':'Account','Preferências':'Preferences','Idioma':'Language','Reservar via WhatsApp':'Book via WhatsApp','Reservar':'Book','Reservar agora':'Book now','Reservar datas':'Book dates','Reservar por data':'Book by date','Falar no WhatsApp':'Chat on WhatsApp','Buscar':'Search','Origem':'Origin','Destino':'Destination','Passageiros':'Passengers','Data':'Date','Ordenar':'Sort','Filtrar':'Filter','Limpar':'Clear','Todos':'All','Todas':'All','Qualquer':'Any','Em destaque':'Featured','Mais recentes':'Newest','Menor preço':'Lowest price','Maior preço':'Highest price','Maior capacidade':'Highest capacity','Transfers privativos':'Private transfers','Chegue tranquilo, viaje no conforto':'Arrive calmly, travel comfortably','A partir de':'From','Apenas ida':'One way','Ida e volta':'Round trip','Nenhum transfer disponível':'No transfer available','Tente outra busca ou fale com a gente no WhatsApp.':'Try another search or talk to us on WhatsApp.','Seu Carrinho':'Your cart','Carrinho vazio':'Your cart is empty','Explorar passeios':'Explore tours','Finalizar Reserva':'Complete booking','Limpar carrinho':'Clear cart','Total':'Total','Escolha a data':'Choose a date','Datas disponíveis *':'Available dates *','Carregando datas...':'Loading dates...','Adicionar ao carrinho':'Add to cart','Cancelar':'Cancel','Você pode escolher uma ou várias datas disponíveis. No checkout os valores atualizam automaticamente.':'You can choose one or more available dates. Prices update automatically at checkout.','Outros transfers':'Other transfers','Sobre o transfer':'About the transfer','Sobre o passeio':'About the tour','Destaques':'Highlights','Itinerário':'Itinerary','O que está incluso':'What is included','Não incluso':'Not included','Ponto de encontro':'Meeting point','Como chegar (Google Maps)':'Get directions (Google Maps)','Avaliações':'Reviews','Ver mais':'See more','Pagamento seguro':'Secure payment','Pix ou cartão até 12x':'Pix or card up to 12x','Curadoria local':'Local curation','Feito por alagoanos':'Made by locals','Suporte 24/7':'24/7 support','Durante sua viagem':'During your trip','Melhor preço':'Best price','Garantido ou devolvemos':'Guaranteed or your money back'},
        es:{'Home':'Inicio','Passeios':'Paseos','Pacotes':'Paquetes','Transfers':'Traslados','Sobre nós':'Sobre nosotros','Contato':'Contacto','Entrar':'Ingresar','Criar conta':'Crear cuenta','Minha conta':'Mi cuenta','Minhas reservas':'Mis reservas','Favoritos':'Favoritos','Sair':'Salir','Navegação':'Navegación','Conta':'Cuenta','Preferências':'Preferencias','Idioma':'Idioma','Reservar via WhatsApp':'Reservar por WhatsApp','Reservar':'Reservar','Reservar agora':'Reservar ahora','Reservar datas':'Reservar fechas','Reservar por data':'Reservar por fecha','Falar no WhatsApp':'Hablar por WhatsApp','Buscar':'Buscar','Origem':'Origen','Destino':'Destino','Passageiros':'Pasajeros','Data':'Fecha','Ordenar':'Ordenar','Filtrar':'Filtrar','Limpar':'Limpiar','Todos':'Todos','Todas':'Todas','Qualquer':'Cualquiera','Em destaque':'Destacados','Mais recentes':'Más recientes','Menor preço':'Menor precio','Maior preço':'Mayor precio','Maior capacidade':'Mayor capacidad','Transfers privativos':'Traslados privados','Chegue tranquilo, viaje no conforto':'Llegue tranquilo, viaje cómodo','A partir de':'Desde','Apenas ida':'Solo ida','Ida e volta':'Ida y vuelta','Nenhum transfer disponível':'No hay traslados disponibles','Tente outra busca ou fale com a gente no WhatsApp.':'Pruebe otra búsqueda o hable con nosotros por WhatsApp.','Seu Carrinho':'Tu carrito','Carrinho vazio':'Carrito vacío','Explorar passeios':'Explorar paseos','Finalizar Reserva':'Finalizar reserva','Limpar carrinho':'Vaciar carrito','Total':'Total','Escolha a data':'Elige la fecha','Datas disponíveis *':'Fechas disponibles *','Carregando datas...':'Cargando fechas...','Adicionar ao carrinho':'Añadir al carrito','Cancelar':'Cancelar','Outros transfers':'Otros traslados','Sobre o transfer':'Sobre el traslado','Sobre o passeio':'Sobre el paseo','Destaques':'Destacados','Itinerário':'Itinerario','O que está incluso':'Qué está incluido','Não incluso':'No incluido','Ponto de encontro':'Punto de encuentro','Como chegar (Google Maps)':'Cómo llegar (Google Maps)','Avaliações':'Reseñas','Pagamento seguro':'Pago seguro','Curadoria local':'Curaduría local','Suporte 24/7':'Soporte 24/7','Melhor preço':'Mejor precio'},
        fr:{'Home':'Accueil','Passeios':'Excursions','Pacotes':'Forfaits','Transfers':'Transferts','Sobre nós':'À propos','Contato':'Contact','Entrar':'Connexion','Criar conta':'Créer un compte','Minha conta':'Mon compte','Navegação':'Navigation','Preferências':'Préférences','Idioma':'Langue','Reservar':'Réserver','Buscar':'Rechercher','Origem':'Origine','Destino':'Destination','Passageiros':'Passagers','Data':'Date','Ordenar':'Trier','Filtrar':'Filtrer','Limpar':'Effacer','Todos':'Tous','Todas':'Toutes','Qualquer':'Tous','A partir de':'À partir de','Apenas ida':'Aller simple','Ida e volta':'Aller-retour','Seu Carrinho':'Votre panier','Carrinho vazio':'Panier vide','Finalizar Reserva':'Finaliser la réservation','Adicionar ao carrinho':'Ajouter au panier','Cancelar':'Annuler','Itinerário':'Itinéraire','Destaques':'Points forts','Avaliações':'Avis'},
        de:{'Home':'Startseite','Passeios':'Touren','Pacotes':'Pakete','Transfers':'Transfers','Sobre nós':'Über uns','Contato':'Kontakt','Entrar':'Anmelden','Criar conta':'Konto erstellen','Minha conta':'Mein Konto','Navegação':'Navigation','Preferências':'Einstellungen','Idioma':'Sprache','Reservar':'Buchen','Buscar':'Suchen','Origem':'Abfahrt','Destino':'Ziel','Passageiros':'Passagiere','Data':'Datum','Ordenar':'Sortieren','Filtrar':'Filtern','Limpar':'Löschen','Todos':'Alle','Todas':'Alle','Qualquer':'Beliebig','A partir de':'Ab','Apenas ida':'Nur Hinfahrt','Ida e volta':'Hin und zurück','Seu Carrinho':'Ihr Warenkorb','Carrinho vazio':'Warenkorb leer','Finalizar Reserva':'Buchung abschließen','Adicionar ao carrinho':'In den Warenkorb','Cancelar':'Abbrechen','Itinerário':'Reiseplan','Destaques':'Highlights','Avaliações':'Bewertungen'},
        it:{'Home':'Home','Passeios':'Tour','Pacotes':'Pacchetti','Transfers':'Transfer','Sobre nós':'Chi siamo','Contato':'Contatto','Entrar':'Accedi','Criar conta':'Crea account','Minha conta':'Il mio account','Navegação':'Navigazione','Preferências':'Preferenze','Idioma':'Lingua','Reservar':'Prenota','Buscar':'Cerca','Origem':'Origine','Destino':'Destinazione','Passageiros':'Passeggeri','Data':'Data','Ordenar':'Ordina','Filtrar':'Filtra','Limpar':'Pulisci','Todos':'Tutti','Todas':'Tutte','Qualquer':'Qualsiasi','A partir de':'Da','Apenas ida':'Solo andata','Ida e volta':'Andata e ritorno','Seu Carrinho':'Il tuo carrello','Carrinho vazio':'Carrello vuoto','Finalizar Reserva':'Completa prenotazione','Adicionar ao carrinho':'Aggiungi al carrello','Cancelar':'Annulla','Itinerário':'Itinerario','Destaques':'Highlights','Avaliações':'Recensioni'},
        zh:{'Home':'首页','Passeios':'游览','Pacotes':'套餐','Transfers':'接送','Sobre nós':'关于我们','Contato':'联系','Entrar':'登录','Criar conta':'创建账户','Minha conta':'我的账户','Navegação':'导航','Preferências':'偏好','Idioma':'语言','Reservar':'预订','Buscar':'搜索','Origem':'出发地','Destino':'目的地','Passageiros':'乘客','Data':'日期','Ordenar':'排序','Filtrar':'筛选','Limpar':'清除','Todos':'全部','Todas':'全部','Qualquer':'任意','A partir de':'起价','Apenas ida':'单程','Ida e volta':'往返','Seu Carrinho':'购物车','Carrinho vazio':'购物车为空','Finalizar Reserva':'完成预订','Adicionar ao carrinho':'加入购物车','Cancelar':'取消','Itinerário':'行程','Destaques':'亮点','Avaliações':'评价'}
    };

    function readCookie(name){ var m = document.cookie.match(new RegExp('(?:^|; )'+name.replace(/[.$?*|{}()\[\]\\\/\+^]/g,'\\$&')+'=([^;]*)')); return m ? decodeURIComponent(m[1]) : ''; }
    function setCookie(name, value){ document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=31536000; samesite=Lax'; }
    function normalize(s){ return (s || '').replace(/\s+/g,' ').trim(); }
    function dict(text, lang){ return (DICT[lang] && DICT[lang][normalize(text)]) || null; }
    function attrMark(attr){ return '__t_' + attr.replace(/[^a-z0-9]/gi, ''); }
    function looksPortuguese(text){ return /[áàâãéêíóôõúçÁÀÂÃÉÊÍÓÔÕÚÇ]/.test(text) || /\b(de|da|do|das|dos|para|com|em|por|uma|você|seu|sua|passeio|pacote|reserva|reservar|carrinho|preço|maior|menor|origem|destino|disponível|avaliações|itinerário)\b/i.test(text); }

    function isTranslatable(node){
        if (!node || !node.parentElement) return false;
        var p = node.parentElement;
        if (p.closest('[data-no-translate],[translate="no"],.notranslate')) return false;
        if (SKIP_TAGS[p.tagName]) return false;
        var v = node.nodeValue;
        if (!v) return false;
        var s = v.trim();
        if (s.length < 2) return false;
        if (!/[\p{L}]/u.test(s)) return false; // só símbolos/números
        if (node.__i18nLang === current) return false;
        if (current !== 'pt-BR' && !dict(s, current) && !looksPortuguese(s)) return false;
        return true;
    }

    function collectNodes(root){
        var out = [];
        var w = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: function(n){ return isTranslatable(n) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT; }
        });
        var n; while ((n = w.nextNode())) out.push(n);
        return out;
    }

    function collectAttrs(root){
        // Atributos visíveis: placeholder, title, alt, aria-label, value de submit
        var out = [];
        root.querySelectorAll('[placeholder],[title],[alt],[aria-label]').forEach(function(el){
            if (el.closest('[data-no-translate],[translate="no"],.notranslate')) return;
            ['placeholder','title','alt','aria-label'].forEach(function(attr){
                var v = el.getAttribute(attr);
                if (!v || !v.trim() || !/[\p{L}]/u.test(v)) return;
                if (current !== 'pt-BR' && !dict(v, current) && !looksPortuguese(v)) return;
                if (el.dataset[attrMark(attr)] === current) return;
                out.push({el:el, attr:attr, original:v});
            });
        });
        return out;
    }

    async function translateChunk(items, applyFn){
        var texts = items.map(function(it){ return it.original; });
        try {
            var res = await fetch(ENDPOINT, {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({lang:LANG, texts:texts}),
                credentials:'same-origin'
            });
            if (!res.ok) return;
            var j = await res.json();
            if (!j || !j.ok || !j.translations) return;
            j.translations.forEach(function(tr, i){ if (tr) applyFn(items[i], tr); });
        } catch(e) {}
    }

    function originalText(node){
        if (!ORIGINAL_TEXT.has(node)) ORIGINAL_TEXT.set(node, node.nodeValue);
        return ORIGINAL_TEXT.get(node);
    }
    function originalAttr(item){
        var key = ATTR_PREFIX + item.attr.replace(/[^a-z0-9]/gi, '');
        if (!item.el.dataset[key]) item.el.dataset[key] = item.el.getAttribute(item.attr) || '';
        return item.el.dataset[key];
    }
    function applyText(item, tr){
        var lead = (item.raw.match(/^\s*/) || [''])[0];
        var tail = (item.raw.match(/\s*$/) || [''])[0];
        item.node.nodeValue = lead + tr + tail;
        item.node.__i18nLang = current;
    }

    async function run(root, lang){
        current = lang || current || 'pt-BR';
        document.documentElement.lang = current;
        var nodes = collectNodes(root);
        var nodeItems = [];
        nodes.forEach(function(n){
            var rawOriginal = originalText(n);
            var clean = normalize(rawOriginal);
            if (current === 'pt-BR') { n.nodeValue = rawOriginal; n.__i18nLang = current; return; }
            var immediate = dict(clean, current);
            if (immediate) { applyText({node:n, raw:rawOriginal}, immediate); return; }
            nodeItems.push({node:n, original:clean, raw:rawOriginal});
        });
        for (var i=0; i<nodeItems.length; i+=BATCH) {
            await translateChunk(nodeItems.slice(i, i+BATCH), function(it, tr){
                applyText(it, tr);
            });
        }
        var attrItems = collectAttrs(root);
        var attrMiss = [];
        attrItems.forEach(function(it){
            var src = originalAttr(it), immediate = current === 'pt-BR' ? src : dict(src, current);
            if (current === 'pt-BR' || immediate) {
                it.el.setAttribute(it.attr, immediate || src);
                it.el.dataset[attrMark(it.attr)] = current;
            } else {
                it.original = src;
                attrMiss.push(it);
            }
        });
        for (var i=0; i<attrMiss.length; i+=BATCH) {
            await translateChunk(attrMiss.slice(i, i+BATCH), function(it, tr){
                it.el.dataset[attrMark(it.attr)] = current;
                it.el.setAttribute(it.attr, tr);
            });
        }
    }

    function switchLang(lang, href){
        current = lang || 'pt-BR';
        setCookie('lang', current);
        localStorage.setItem('caminhos_lang', current);
        run(document.body, current);
        if (href) fetch(href, {credentials:'same-origin', cache:'no-store'}).catch(function(){});
    }

    function start(){
        run(document.body, current);
        document.addEventListener('click', function(e){
            var a = e.target.closest('[data-lang-switch]');
            if (!a || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button) return;
            e.preventDefault();
            switchLang(a.dataset.langSwitch || 'pt-BR', a.href);
        }, true);
        // Observa mudanças (Alpine, fetch dinâmico, modais)
        var debounce = null;
        var pending = new Set();
        var mo = new MutationObserver(function(muts){
            muts.forEach(function(m){
                m.addedNodes.forEach(function(n){
                    if (n.nodeType === 1) pending.add(n);
                });
            });
            if (pending.size === 0) return;
            clearTimeout(debounce);
            debounce = setTimeout(function(){
                pending.forEach(function(n){ if (document.contains(n)) run(n, current); });
                pending.clear();
            }, 80);
        });
        mo.observe(document.body, {childList:true, subtree:true});
    }

    window.CaminhosI18n = { run: run, switchLang: switchLang };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
</script>

</body>
</html>
