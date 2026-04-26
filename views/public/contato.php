<?php
$pageTitle = 'Contato';
include VIEWS_DIR . '/partials/public_head.php';
?>
<section class="pt-36 pb-10 relative" style="background:linear-gradient(180deg,var(--horizonte) 0%,var(--horizonte-dark) 100%)">
    <div class="max-w-5xl mx-auto px-6 text-center text-white">
        <span class="text-xs font-bold tracking-[0.3em] uppercase" style="color:var(--areia-light)">Fale com a gente</span>
        <h1 class="font-display text-5xl md:text-6xl font-bold mt-3 mb-4">Vamos planejar sua viagem</h1>
        <p class="text-white/85 max-w-2xl mx-auto">Entre em contato e receba um passeio personalizado em menos de 24h.</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-6xl mx-auto px-6 grid lg:grid-cols-2 gap-10">
        <!-- Form -->
        <div class="admin-card p-8" x-data="{sending:false}">
            <h2 class="font-display text-2xl font-bold mb-1" style="color:var(--sepia)">Envie uma mensagem</h2>
            <p class="text-sm mb-6" style="color:var(--text-muted)">Retornamos em até 24 horas úteis.</p>
            <form @submit.prevent="sending=true;caminhosApi('<?= url('/api/contact') ?>',{method:'POST',data:new FormData($event.target)}).then(r=>{showToast(r.msg,r.ok?'success':'error');if(r.ok)$event.target.reset();sending=false});">
                <div class="space-y-4">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Nome</label>
                            <input type="text" name="name" required class="admin-input">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">E-mail</label>
                            <input type="email" name="email" required class="admin-input">
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">WhatsApp</label>
                            <input type="tel" name="phone" class="admin-input phone-mask" placeholder="(00) 00000-0000">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Assunto</label>
                            <input type="text" name="subject" class="admin-input" placeholder="Ex: passeio personalizado">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Mensagem</label>
                        <textarea name="message" required rows="5" class="admin-input resize-none" placeholder="Conte pra gente o que você procura..."></textarea>
                    </div>
                    <button type="submit" :disabled="sending" class="btn-primary w-full" :class="sending&&'opacity-60'">
                        <i data-lucide="send" class="w-5 h-5"></i>
                        <span x-text="sending?'Enviando...':'Enviar mensagem'">Enviar mensagem</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Info -->
        <div class="space-y-5">
            <div class="admin-card p-6 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(58,107,138,0.12);color:var(--horizonte)"><i data-lucide="mail" class="w-6 h-6"></i></div>
                <div>
                    <div class="text-xs uppercase tracking-wider font-semibold mb-1" style="color:var(--text-muted)">E-mail</div>
                    <a href="mailto:<?= e(getSetting('contact_email', APP_EMAIL)) ?>" class="font-semibold text-base" style="color:var(--sepia)"><?= e(getSetting('contact_email', APP_EMAIL)) ?></a>
                </div>
            </div>
            <div class="admin-card p-6 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(37,211,102,0.12);color:#128C7E"><i data-lucide="message-circle" class="w-6 h-6"></i></div>
                <div>
                    <div class="text-xs uppercase tracking-wider font-semibold mb-1" style="color:var(--text-muted)">WhatsApp</div>
                    <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>" class="font-semibold text-base" style="color:var(--sepia)"><?= e(getSetting('contact_phone', APP_PHONE)) ?></a>
                </div>
            </div>
            <div class="admin-card p-6 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(201,107,74,0.12);color:var(--terracota)"><i data-lucide="map-pin" class="w-6 h-6"></i></div>
                <div>
                    <div class="text-xs uppercase tracking-wider font-semibold mb-1" style="color:var(--text-muted)">Endereço</div>
                    <div class="font-semibold text-base" style="color:var(--sepia)"><?= e(getSetting('address', 'Maceió, Alagoas — Brasil')) ?></div>
                </div>
            </div>
            <div class="admin-card p-6 flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(122,157,110,0.12);color:var(--maresia-dark)"><i data-lucide="clock" class="w-6 h-6"></i></div>
                <div>
                    <div class="text-xs uppercase tracking-wider font-semibold mb-1" style="color:var(--text-muted)">Atendimento</div>
                    <div class="font-semibold text-base" style="color:var(--sepia)">Seg a Sex · 08h30 às 18h</div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
