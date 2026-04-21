<?php
/**
 * Shared layout for customer account area.
 * Include BEFORE content. Expects $accountTitle, $accountTab.
 */
requireCustomer();
$cust = currentCustomer();
$solidNav = true;
$pageTitle = ($accountTitle ?? 'Minha Conta') . ' · Caminhos de Alagoas';
include VIEWS_DIR . '/partials/public_head.php';
$tab = $accountTab ?? 'dashboard';

$links = [
    ['dashboard', '/conta', 'layout-dashboard', 'Visão geral'],
    ['reservas',  '/conta/reservas', 'calendar-check', 'Minhas reservas'],
    ['favoritos', '/conta/favoritos', 'heart', 'Favoritos'],
    ['reembolso', '/conta/reembolso', 'refresh-ccw', 'Reembolsos'],
    ['perfil',    '/conta/perfil', 'user-cog', 'Perfil'],
];
?>
<section class="pt-24 pb-16 min-h-screen" style="background:var(--bg-surface)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <!-- greeting banner -->
        <div class="mb-8 p-6 md:p-8 rounded-3xl relative overflow-hidden" style="background:linear-gradient(135deg,var(--azul-profundo),#1a3a5c);color:#fff">
            <img src="<?= asset('brand/selo-branco.png') ?>" class="seal-watermark xl dark reverse" style="top:-80px;right:-80px" alt="">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] opacity-70 mb-2">Olá, viajante</p>
                    <h1 class="font-display text-3xl md:text-4xl font-bold"><?= e($cust['name']) ?></h1>
                    <p class="text-sm opacity-80 mt-2"><?= e($cust['email']) ?></p>
                </div>
                <a href="<?= url('/conta/sair') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition" style="background:rgba(255,255,255,0.15);backdrop-filter:blur(8px)">
                    <i data-lucide="log-out" class="w-4 h-4"></i> Sair
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-6">
            <!-- sidebar -->
            <aside class="glass-card rounded-2xl p-4 h-fit border" style="background:#fff;border-color:var(--border-default)">
                <nav class="flex md:flex-col gap-1 overflow-x-auto">
                    <?php foreach ($links as [$key,$href,$icon,$label]): $active = ($tab===$key); ?>
                        <a href="<?= url($href) ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition whitespace-nowrap"
                           style="<?= $active ? 'background:var(--terracota);color:#fff' : 'color:var(--text-secondary)' ?>"
                           onmouseover="if(!<?= $active?'true':'false' ?>)this.style.background='var(--areia-light)'"
                           onmouseout="if(!<?= $active?'true':'false' ?>)this.style.background=''">
                            <i data-lucide="<?= $icon ?>" class="w-4 h-4"></i> <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>

            <div class="min-w-0">
