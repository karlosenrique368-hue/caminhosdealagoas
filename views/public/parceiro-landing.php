<?php
$pageTitle = 'Parceria que rende';
$pageDesc  = 'Indique amigos, familiares e grupos para viver as melhores experiências em Alagoas. Você ganha benefícios exclusivos a cada indicação confirmada.';
include VIEWS_DIR . '/partials/public_head.php';
?>

<!-- HERO -->
<section class="relative overflow-hidden" style="margin-top:-80px">
    <div class="absolute inset-0" style="background:linear-gradient(135deg, var(--sepia) 0%, var(--terracota-dark) 60%, var(--horizonte) 100%)"></div>
    <img src="<?= asset('brand/selo-branco.png') ?>" class="seal-rotate absolute hidden md:block" style="top:110px;right:60px;width:120px;opacity:0.18;z-index:5" alt="">
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 pt-40 sm:pt-48 pb-20 sm:pb-28 text-white">
        <div class="inline-block text-[11px] uppercase tracking-widest font-bold px-4 py-1.5 rounded-full mb-5" style="background:rgba(255,255,255,0.15);backdrop-filter:blur(4px)">
            <i data-lucide="handshake" class="w-3.5 h-3.5 inline -mt-0.5"></i> Programa de indicação
        </div>
        <h1 class="font-display text-4xl sm:text-6xl font-bold leading-tight mb-5 max-w-4xl">Indique. Compartilhe.<br><span class="inline-block" style="background:linear-gradient(90deg,#fff,#F5C99B);-webkit-background-clip:text;-webkit-text-fill-color:transparent">Viva com a gente.</span></h1>
        <p class="text-lg sm:text-xl leading-relaxed max-w-2xl text-white/90 mb-8">Qualquer pessoa pode ser nosso parceiro(a). Você indica amigos, família ou seu grupo para uma vivência, e a gente te recompensa de formas exclusivas. É simples, discreto e feito pra quem ama Alagoas.</p>
        <div class="flex flex-wrap gap-3">
            <a href="<?= url('/parceiro/cadastro') ?>" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-base transition hover:scale-[1.02]" style="background:#fff;color:var(--terracota-dark)">
                <i data-lucide="sparkles" class="w-5 h-5"></i> Quero ser parceiro(a)
            </a>
            <a href="<?= url('/parceiro/login') ?>" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl border-2 font-semibold text-base text-white transition hover:bg-white/10" style="border-color:rgba(255,255,255,0.5)">
                <i data-lucide="log-in" class="w-5 h-5"></i> Já sou parceiro(a)
            </a>
        </div>
    </div>
    <svg class="absolute bottom-0 left-0 right-0 w-full" viewBox="0 0 1440 80" preserveAspectRatio="none" style="height:60px"><path fill="var(--bg-page)" d="M0,0 C480,80 960,80 1440,0 L1440,80 L0,80 Z"/></svg>
</section>

<!-- COMO FUNCIONA -->
<section class="py-16 sm:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
            <div class="text-xs font-bold uppercase tracking-widest mb-3" style="color:var(--terracota)">É simples assim</div>
            <h2 class="font-display text-3xl sm:text-4xl font-bold" style="color:var(--sepia)">Como funciona</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-5 sm:gap-6">
            <?php
            $steps = [
                ['1','user-plus','Cadastro rápido','Preencha seus dados. Em poucos segundos você recebe seu <b>código exclusivo</b> de indicação.'],
                ['2','share-2','Compartilhe o link','Envie o link para amigos, familiares, colegas, grupos. Funciona em qualquer rede, em qualquer lugar.'],
                ['3','trophy','Receba benefícios','A cada indicação confirmada, seus benefícios se acumulam na sua área exclusiva. Discreto. Automatizado.'],
            ];
            foreach ($steps as $s): ?>
            <div class="admin-card p-7 text-center group hover:-translate-y-1 transition">
                <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center mb-4" style="background:linear-gradient(135deg,var(--terracota),var(--terracota-dark));color:#fff">
                    <i data-lucide="<?= $s[1] ?>" class="w-6 h-6"></i>
                </div>
                <div class="text-xs font-bold uppercase tracking-wider mb-2" style="color:var(--text-muted)">Passo <?= $s[0] ?></div>
                <h3 class="font-display text-xl font-bold mb-3" style="color:var(--sepia)"><?= $s[2] ?></h3>
                <p class="text-sm leading-relaxed" style="color:var(--text-secondary)"><?= $s[3] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TIPOS DE PARCERIA -->
<section class="py-16 sm:py-20" style="background:var(--bg-surface)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
            <h2 class="font-display text-3xl sm:text-4xl font-bold mb-3" style="color:var(--sepia)">Para cada jeito de indicar</h2>
            <p class="text-base sm:text-lg max-w-2xl mx-auto" style="color:var(--text-secondary)">Não importa se você é uma pessoa, uma família reunida ou lidera um grupo: a gente tem um modelo pra você.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            <?php
            $kinds = [
                ['user','Individual','Você indica, a gente confirma. Simples e direto.'],
                ['users','Família & amigos','Reuniu a galera? Tudo fica no mesmo cadastro.'],
                ['users-round','Grupos','Igrejas, faculdades, clubes, academias — vamos juntos.'],
                ['store','Revenda / agência','Você vende, a gente opera. Parceria profissional.'],
            ];
            foreach ($kinds as $k): ?>
            <div class="admin-card p-5 hover:border-[var(--terracota)] transition" style="border:2px solid var(--border-default)">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-3" style="background:rgba(201,107,74,0.12);color:var(--terracota)">
                    <i data-lucide="<?= $k[0] ?>" class="w-5 h-5"></i>
                </div>
                <h3 class="font-display font-bold text-lg mb-1" style="color:var(--sepia)"><?= $k[1] ?></h3>
                <p class="text-sm" style="color:var(--text-secondary)"><?= $k[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- BENEFICIOS GENERICOS -->
<section class="py-16 sm:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
            <div>
                <div class="text-xs font-bold uppercase tracking-widest mb-3" style="color:var(--horizonte)">Benefícios exclusivos</div>
                <h2 class="font-display text-3xl sm:text-4xl font-bold mb-5" style="color:var(--sepia)">Vantagens que crescem com você</h2>
                <div class="space-y-4">
                    <?php
                    $perks = [
                        ['gift','Vantagens acumulativas','Quanto mais você indica, mais vantagens se abrem. A progressão é visível apenas na sua área exclusiva.'],
                        ['link-2','Link pessoal permanente','Você recebe um código único que funciona pra sempre. Nenhum limite de uso ou validade.'],
                        ['bell','Notificações automáticas','Toda vez que alguém confirma via seu link, você é avisado na hora.'],
                        ['shield-check','Controle total e discreto','Sua área é privada, visível só pra você. Os valores e regras não aparecem para clientes finais.'],
                    ];
                    foreach ($perks as $p): ?>
                    <div class="flex gap-4 p-4 rounded-xl" style="background:var(--bg-surface)">
                        <div class="w-10 h-10 flex-shrink-0 rounded-xl flex items-center justify-center" style="background:var(--maresia);color:#fff">
                            <i data-lucide="<?= $p[0] ?>" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h4 class="font-display font-bold mb-1" style="color:var(--sepia)"><?= $p[1] ?></h4>
                            <p class="text-sm" style="color:var(--text-secondary)"><?= $p[2] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="relative">
                <div class="rounded-3xl p-8 sm:p-10 text-white relative overflow-hidden" style="background:linear-gradient(135deg,var(--terracota),var(--terracota-dark))">
                    <div class="absolute -top-12 -right-12 w-48 h-48 rounded-full" style="background:rgba(255,255,255,0.08)"></div>
                    <div class="absolute -bottom-16 -left-8 w-56 h-56 rounded-full" style="background:rgba(255,255,255,0.05)"></div>
                    <i data-lucide="handshake" class="w-10 h-10 mb-5"></i>
                    <h3 class="font-display text-2xl sm:text-3xl font-bold mb-3 leading-tight">Cadastro gratuito.<br>Sem mensalidade. Sem pegadinha.</h3>
                    <p class="text-white/90 text-sm sm:text-base leading-relaxed mb-6">Você só precisa indicar pessoas que tenham o mesmo amor que você pelas experiências que entregamos. O resto a gente cuida.</p>
                    <a href="<?= url('/parceiro/cadastro') ?>" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold text-sm transition hover:scale-[1.02]" style="background:#fff;color:var(--terracota-dark)">
                        Cadastrar agora <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA FINAL -->
<section class="py-16 sm:py-20" style="background:var(--sepia);color:#fff">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <h2 class="font-display text-3xl sm:text-4xl font-bold mb-4">Pronto pra começar a indicar?</h2>
        <p class="text-white/85 text-base sm:text-lg mb-8 max-w-xl mx-auto">Crie seu cadastro em menos de 1 minuto e receba seu link personalizado de indicação.</p>
        <a href="<?= url('/parceiro/cadastro') ?>" class="inline-flex items-center gap-2 px-7 py-4 rounded-xl font-semibold text-base transition hover:scale-[1.02]" style="background:var(--terracota);color:#fff">
            <i data-lucide="sparkles" class="w-5 h-5"></i> Quero meu código
        </a>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
