<?php
$i = currentInstitution();
$pageTitle = $pageTitle ?? 'Portal da Instituição';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= e(csrfToken()) ?>">
<title><?= e($pageTitle) ?> · <?= e($i['name'] ?? 'Portal') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: { colors: {
    horizonte:{DEFAULT:'#3A6B8A',dark:'#2D5470'},
    terracota:{DEFAULT:'#C96B4A',dark:'#A85437'},
    maresia:{DEFAULT:'#7A9D6E',dark:'#5E7E55'},
    areia:{DEFAULT:'#F4E4C1'}, sepia:{DEFAULT:'#3E2E1F'}
}, fontFamily:{display:['"Playfair Display"','serif'],sans:['Inter','system-ui']} }}}
</script>
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
<style>body{background:var(--bg-surface)}[x-cloak]{display:none!important}</style>
</head>
<body x-data="{sidebarOpen: window.innerWidth>=1024}">
<div class="flex min-h-screen">
    <aside class="admin-sidebar fixed lg:sticky lg:top-0 inset-y-0 lg:inset-y-auto left-0 z-40 flex-shrink-0 transition-transform lg:h-screen lg:self-start"
           :class="sidebarOpen?'translate-x-0':'-translate-x-full lg:translate-x-0'" style="width:260px;min-width:260px">
        <div class="p-5 border-b border-white/10 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white flex-shrink-0" style="background:linear-gradient(135deg,var(--terracota),var(--horizonte))"><i data-lucide="building-2" class="w-5 h-5"></i></div>
            <div class="min-w-0">
                <div class="font-display text-base font-bold text-white truncate"><?= e($i['name']) ?></div>
                <div class="text-[10px] font-semibold tracking-[0.2em] uppercase text-white/60">Portal parceiro</div>
            </div>
        </div>
        <nav class="p-4 space-y-1">
            <?php
            $menu = [
                ['path'=>'/instituicao/dashboard','icon'=>'layout-dashboard','label'=>'Visão geral'],
                ['path'=>'/instituicao/reservas','icon'=>'calendar-check','label'=>'Nossas reservas'],
                ['path'=>'/instituicao/cotacao','icon'=>'file-text','label'=>'Pedir cotação'],
                ['path'=>'/instituicao/catalogo','icon'=>'compass','label'=>'Catálogo'],
                ['path'=>'/instituicao/perfil','icon'=>'settings','label'=>'Conta'],
            ];
            $cur = currentPath();
            foreach ($menu as $m):
                $active = strpos($cur, $m['path']) === 0;
            ?>
                <a href="<?= url($m['path']) ?>" class="admin-sidebar-link <?= $active?'active':'' ?>">
                    <i data-lucide="<?= $m['icon'] ?>" class="w-4 h-4 flex-shrink-0"></i>
                    <span><?= e($m['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="absolute bottom-0 inset-x-0 p-4 border-t border-white/10" style="background:rgba(0,0,0,0.2)">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white" style="background:linear-gradient(135deg,var(--terracota),var(--horizonte))"><?= e(mb_substr($i['user_name'],0,1)) ?></div>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold text-white truncate"><?= e($i['user_name']) ?></div>
                    <div class="text-[11px] text-white/50 truncate"><?= e($i['user_email']) ?></div>
                </div>
                <a href="<?= url('/instituicao/logout') ?>" class="p-2 rounded-lg text-white/60 hover:text-white hover:bg-white/10 transition" title="Sair"><i data-lucide="log-out" class="w-4 h-4"></i></a>
            </div>
        </div>
    </aside>
    <div x-show="sidebarOpen && window.innerWidth<1024" @click="sidebarOpen=false" x-cloak style="display:none" class="fixed inset-0 bg-black/50 z-30 lg:hidden"></div>
    <div class="flex-1 flex flex-col min-w-0">
        <header class="sticky top-0 z-20 bg-white/90 backdrop-blur-xl border-b" style="border-color:var(--border-default)">
            <div class="h-16 px-4 md:px-8 flex items-center gap-4">
                <button @click="sidebarOpen=!sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-gray-100" style="color:var(--sepia)"><i data-lucide="menu" class="w-5 h-5"></i></button>
                <div class="flex-1">
                    <h1 class="font-display text-xl font-bold" style="color:var(--sepia)"><?= e($pageTitle) ?></h1>
                </div>
                <?php if (!empty($i['discount']) && $i['discount']>0): ?>
                <span class="hidden md:inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-full" style="background:rgba(122,157,110,0.12);color:var(--maresia-dark)"><i data-lucide="badge-percent" class="w-3.5 h-3.5"></i>Desconto parceiro <?= number_format($i['discount'],0) ?>%</span>
                <?php endif; ?>
                <a href="<?= url('/') ?>" target="_blank" class="p-2 rounded-lg hover:bg-gray-100 transition" style="color:var(--text-secondary)" title="Ver site"><i data-lucide="external-link" class="w-5 h-5"></i></a>
            </div>
        </header>
        <main class="flex-1 p-4 md:p-8">
