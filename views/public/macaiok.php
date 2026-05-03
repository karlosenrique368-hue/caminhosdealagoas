<?php
$pageTitle = 'Macaiok Vivências Pedagógicas';
$solidNav = true;
$macaiokMode = true;
$hero = dbOne("SELECT cover_image FROM roteiros WHERE status='published' AND cover_image IS NOT NULL ORDER BY featured DESC, views DESC LIMIT 1");
$heroImage = !empty($hero['cover_image']) ? storageUrl($hero['cover_image']) : asset('img/macaiok/VerdeEscuro_Horizontal.png');
$stats = dbOne("SELECT COUNT(*) AS escolas FROM institutions WHERE program='macaiok' AND active=1");
include VIEWS_DIR . '/partials/public_head.php';
?>

<section class="pt-28 pb-10" style="background:var(--bg-page)">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="relative overflow-hidden rounded-[28px] min-h-[520px] flex items-end" style="background:linear-gradient(180deg,rgba(0,0,0,.12),rgba(0,0,0,.72)),url('<?= e($heroImage) ?>') center/cover no-repeat">
            <div class="p-6 sm:p-10 lg:p-12 max-w-3xl text-white">
                <img src="<?= asset('img/macaiok/VerdeEscuro_Horizontal.png') ?>" alt="Macaiok" class="h-12 sm:h-14 mb-5" style="filter:brightness(0) invert(1)">
                <span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.22em] px-3 py-1.5 rounded-full" style="background:rgba(255,255,255,.16);backdrop-filter:blur(10px)"><i data-lucide="graduation-cap" class="w-4 h-4"></i>Macaiok Vivências Pedagógicas</span>
                <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-bold mt-5 leading-tight">Educação ao ar livre com controle de pagamentos por escola</h1>
                <p class="text-base sm:text-lg mt-4 text-white/88 max-w-2xl">Vivências, estudos do meio e saídas pedagógicas para conectar alunos ao território, com operação Caminhos de Alagoas, acesso exclusivo para colégios e links de pagamento para responsáveis.</p>
                <div class="flex flex-wrap gap-3 mt-7">
                    <a href="<?= url('/macaiok/login') ?>" class="btn-primary"><i data-lucide="log-in" class="w-4 h-4"></i>Acessar escola</a>
                    <a href="<?= url('/contato') ?>" class="admin-btn" style="background:rgba(255,255,255,.14);color:#fff;border-color:rgba(255,255,255,.28)"><i data-lucide="message-circle" class="w-4 h-4"></i>Falar com a equipe</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-10" style="background:var(--bg-page)">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 grid md:grid-cols-3 gap-5">
        <?php foreach ([
            ['map','Estudos do meio','Roteiros conectados ao currículo, ao território e às comunidades visitadas.'],
            ['shield-check','Operação segura','Controle de participantes, responsáveis, pagamentos e status em um painel único.'],
            ['school','Portal da escola','Cada colégio acessa sua área, envia links aos pais e acompanha quem pagou.'],
        ] as $card): ?>
        <div class="admin-card p-6">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background:rgba(201,107,74,.1);color:var(--terracota)"><i data-lucide="<?= $card[0] ?>" class="w-5 h-5"></i></div>
            <h2 class="font-display text-xl font-bold" style="color:var(--sepia)"><?= e($card[1]) ?></h2>
            <p class="text-sm mt-2" style="color:var(--text-secondary)"><?= e($card[2]) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="py-12" style="background:var(--bg-surface)">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 grid lg:grid-cols-[.9fr_1.1fr] gap-8 items-start">
        <div>
            <span class="text-[11px] uppercase tracking-[0.22em] font-bold" style="color:var(--terracota)">Referência premium</span>
            <h2 class="font-display text-3xl sm:text-4xl font-bold mt-2" style="color:var(--sepia)">O que trouxemos das melhores operações pedagógicas</h2>
            <p class="text-sm sm:text-base mt-4" style="color:var(--text-secondary)">A análise das referências mostrou três pilares fortes: educação experiencial, confiança institucional e acesso por escola. A Macaiok traduz isso para Alagoas com uma camada operacional própria para escolas e responsáveis.</p>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <div class="admin-card p-4"><div class="font-display text-2xl font-bold" style="color:var(--terracota)"><?= (int)($stats['escolas'] ?? 0) ?></div><div class="text-xs" style="color:var(--text-muted)">escolas no painel</div></div>
                <div class="admin-card p-4"><div class="font-display text-2xl font-bold" style="color:var(--horizonte)">100%</div><div class="text-xs" style="color:var(--text-muted)">controle por responsável</div></div>
            </div>
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <?php foreach ([
                ['clipboard-list','Roteiro pedagógico','Briefing, objetivos, logística e cuidados antes da saída.'],
                ['users','Turma organizada','A escola acompanha responsáveis confirmados e pendentes.'],
                ['credit-card','Pagamento rastreável','Cada checkout fica ligado ao colégio e à vivência escolhida.'],
                ['bar-chart-3','Métricas Macaiok','Admin acompanha receita, escolas, pagamentos e participantes.'],
            ] as $item): ?>
            <div class="rounded-2xl p-5" style="background:var(--bg-card);border:1px solid var(--border-default)">
                <i data-lucide="<?= $item[0] ?>" class="w-5 h-5 mb-3" style="color:var(--horizonte)"></i>
                <h3 class="font-display font-bold" style="color:var(--sepia)"><?= e($item[1]) ?></h3>
                <p class="text-sm mt-1" style="color:var(--text-secondary)"><?= e($item[2]) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>