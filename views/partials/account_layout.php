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

// Initials
$parts = preg_split('/\s+/', trim($cust['name'] ?? 'U'));
$initials = mb_strtoupper(mb_substr($parts[0] ?? 'U', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
$avatarUrl = function_exists('avatarUrl') ? avatarUrl($cust['avatar'] ?? null) : null;
?>
<section class="pt-24 pb-16 min-h-screen" style="background:var(--bg-surface)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <!-- Premium hero banner -->
        <div class="account-hero mb-8" data-reveal>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center gap-5">
                <div class="account-hero-avatar"><?php if ($avatarUrl): ?><img src="<?= e($avatarUrl) ?>" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover"><?php else: ?><?= e($initials) ?><?php endif; ?></div>
                <div class="flex-1 min-w-0">
                    <div class="account-hero-eyebrow">Olá, viajante</div>
                    <h1 class="account-hero-name"><?= e($cust['name']) ?></h1>
                    <div class="account-hero-email"><i data-lucide="mail" class="w-3.5 h-3.5 inline mr-1"></i><?= e($cust['email']) ?></div>
                </div>
                <div class="flex items-center gap-2 md:self-start flex-wrap">
                    <a href="<?= url('/passeios') ?>" class="account-hero-logout" style="background:rgba(201,107,74,0.85);border-color:rgba(255,255,255,0.3)">
                        <i data-lucide="compass" class="w-4 h-4"></i> Explorar
                    </a>
                    <a href="<?= url('/conta/sair') ?>" class="account-hero-logout">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Sair
                    </a>
                </div>
            </div>
        </div>

        <div class="account-grid grid grid-cols-1 md:grid-cols-[280px_1fr] gap-6">
            <!-- Premium sidebar -->
            <aside class="account-sidebar h-fit">
                <nav class="flex md:flex-col gap-1 overflow-x-auto">
                    <?php foreach ($links as [$key,$href,$icon,$label]): $active = ($tab===$key); ?>
                        <a href="<?= url($href) ?>" class="account-sidebar-link whitespace-nowrap<?= $active ? ' active' : '' ?>">
                            <i data-lucide="<?= $icon ?>" class="w-4 h-4"></i> <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <div class="mt-4 pt-4 border-t" style="border-color:var(--border-default)">
                    <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>" target="_blank" class="account-sidebar-link" style="color:#10B981">
                        <i data-lucide="message-circle" class="w-4 h-4"></i> Suporte
                    </a>
                    <a href="<?= url('/conta/sair') ?>" class="account-sidebar-link" style="color:#DA4A34">
                        <i data-lucide="log-out" class="w-4 h-4"></i> Sair da conta
                    </a>
                </div>
            </aside>

            <div class="account-content min-w-0">
