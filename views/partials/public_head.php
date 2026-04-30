<?php
$pageTitle = $pageTitle ?? APP_NAME;
$pageDesc = $pageDesc ?? 'Descubra as belezas de Alagoas com passeios e pacotes premium.';
$pageImage = $pageImage ?? asset('brand/logo-azul.png');
$pageSchema = $pageSchema ?? null; // optional JSON-LD injected by child page
$currentUrl = 'http' . (!empty($_SERVER['HTTPS'])?'s':'') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
$currentLang = currentLang();
$currentCurrency = currentCurrency();
?><!DOCTYPE html>
<html lang="<?= e($currentLang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= e($pageDesc) ?>">
<meta name="csrf-token" content="<?= e(csrfToken()) ?>">
<meta name="robots" content="index,follow">
<meta name="author" content="Caminhos de Alagoas">
<link rel="canonical" href="<?= e($currentUrl) ?>">
<link rel="icon" type="image/svg+xml" href="<?= asset('brand/Adesivo_1.svg') ?>">
    <link rel="alternate icon" type="image/png" href="<?= asset('brand/logo-terracota.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('brand/Adesivo_1.svg') ?>">

<script>window.BASE_PATH = '<?= e(BASE_PATH) ?>';</script>

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($pageTitle) ?> — Caminhos de Alagoas">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:image" content="<?= e($pageImage) ?>">
<meta property="og:url" content="<?= e($currentUrl) ?>">
<meta property="og:locale" content="pt_BR">
<meta property="og:site_name" content="Caminhos de Alagoas">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($pageDesc) ?>">
<meta name="twitter:image" content="<?= e($pageImage) ?>">

<title><?= e($pageTitle) ?> — Caminhos de Alagoas</title>

<!-- Schema.org JSON-LD (Organization + optional page-level) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "TravelAgency",
  "name": "Caminhos de Alagoas",
    "description": "Passeios e pacotes autênticos em Alagoas.",
  "url": "<?= e(rtrim($currentUrl, '/')) ?>",
  "logo": "<?= e(asset('brand/logo-azul.png')) ?>",
  "telephone": "<?= e(getSetting('contact_phone', '+5582988220546')) ?>",
  "email": "<?= e(getSetting('contact_email', APP_EMAIL)) ?>",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Maceió",
    "addressRegion": "AL",
    "addressCountry": "BR"
  },
  "sameAs": [
    "<?= e(getSetting('social_instagram', 'https://instagram.com/caminhosdealagoas')) ?>",
    "<?= e(getSetting('social_facebook', 'https://facebook.com/caminhosdealagoas')) ?>"
  ]
}
</script>
<?php if ($pageSchema): ?>
<script type="application/ld+json"><?= $pageSchema ?></script>
<?php endif; ?>

<?= renderAnalyticsHead() ?>

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;0,9..144,800;0,9..144,900;1,9..144,400;1,9..144,700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                horizonte: { DEFAULT: '#3A6B8A', dark: '#2D5470', light: '#5A8FB2' },
                terracota: { DEFAULT: '#C96B4A', dark: '#A85437', light: '#E28D6E' },
                maresia:   { DEFAULT: '#7A9D6E', dark: '#5E7E55', light: '#A3C297' },
                areia:     { DEFAULT: '#F4E4C1', light: '#FAF1DE' },
                sepia:     { DEFAULT: '#3E2E1F' }
            },
            fontFamily: {
                display: ['Fraunces', '"Playfair Display"', 'serif'],
                sans: ['Inter', 'system-ui', 'sans-serif']
            }
        }
    }
}
</script>

<!-- Theme CSS -->
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
<style>
[x-cloak]{display:none!important}
.meeting-map,.meeting-map .leaflet-container{z-index:0!important}
.meeting-map .leaflet-pane{z-index:1!important}
.meeting-map .leaflet-control-container,.meeting-map .leaflet-top,.meeting-map .leaflet-bottom{z-index:2!important}
</style>

<!-- Lucide -->
<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>

<!-- Leaflet (mapas) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<link rel="icon" type="image/png" href="<?= asset('brand/selo-azul.png') ?>">
</head>
<body<?= !empty($solidNav) ? ' class="has-solid-nav"' : '' ?>>

<!-- ============== NAVBAR ============== -->
<nav data-navbar class="fixed top-0 inset-x-0 z-50 transition-all duration-300<?= !empty($solidNav) ? ' nav-scrolled' : '' ?>">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-4 flex items-center justify-between">
        <!-- Logo -->
        <a href="<?= url('/') ?>" class="flex items-center gap-3 group">
            <div class="nav-logo-wrap">
                <img src="<?= asset('brand/logo-branco.png') ?>" alt="Caminhos de Alagoas" class="logo-light">
                <img src="<?= asset('brand/logo-azul.png') ?>" alt="Caminhos de Alagoas" class="logo-dark">
            </div>
        </a>

        <!-- Desktop menu -->
        <div class="hidden lg:flex items-center gap-8">
            <a href="<?= url('/') ?>" class="nav-link nav-link-tr text-sm"><?= e(t('nav.home')) ?></a>
            <a href="<?= url('/passeios') ?>" class="nav-link nav-link-tr text-sm"><?= e(t('nav.tours')) ?></a>
            <a href="<?= url('/pacotes') ?>" class="nav-link nav-link-tr text-sm"><?= e(t('nav.packages')) ?></a>
            <a href="<?= url('/transfers') ?>" class="nav-link nav-link-tr text-sm">Transfers</a>
            <a href="<?= url('/sobre') ?>" class="nav-link nav-link-tr text-sm"><?= e(t('nav.about')) ?></a>
            <a href="<?= url('/contato') ?>" class="nav-link nav-link-tr text-sm"><?= e(t('nav.contact')) ?></a>
            <?php if (isCustomerLoggedIn()): ?>
                <a href="<?= url('/conta') ?>" class="nav-link nav-link-tr text-sm font-bold" style="color:var(--terracota)"><i data-lucide="user-circle" class="w-4 h-4 inline"></i> <?= e(t('nav.account')) ?></a>
            <?php else: ?>
                <a href="<?= url('/conta/login') ?>" class="nav-link nav-link-tr text-sm"><?= e(t('nav.login')) ?></a>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-3">
            <!-- Language switcher -->
            <div class="relative hidden md:block" x-data="{open:false}" @click.away="open=false">
                <?php
                    $flagFiles = ['pt-BR'=>'pt_BR.png','en'=>'en_US.png','es'=>'es_ES.png','fr'=>'fr_FR.png','de'=>'de_DE.png','it'=>'it_IT.png','zh'=>'zh_CN.png'];
                    $siglas = ['pt-BR'=>'PT','en'=>'EN','es'=>'ES','fr'=>'FR','de'=>'DE','it'=>'IT','zh'=>'ZH'];
                    $langNames = ['pt-BR'=>'Português','en'=>'English','es'=>'Español','fr'=>'Français','de'=>'Deutsch','it'=>'Italiano','zh'=>'中文'];
                    $flagUrl = fn($code) => storageUrl('uploads/img/bandeiras/' . ($flagFiles[$code] ?? 'pt_BR.png'));
                ?>
                <button @click="open=!open" data-lang-trigger class="inline-flex items-center gap-2 px-2.5 py-2 rounded-xl nav-link-tr text-sm font-semibold" style="line-height:1" aria-label="Idioma">
                    <img data-lang-current-flag src="<?= $flagUrl($currentLang) ?>" alt="" style="width:22px;height:16px;object-fit:cover;border-radius:2px;display:block;flex-shrink:0">
                    <span data-lang-current-code class="text-xs font-bold tracking-wide" style="line-height:1"><?= e($siglas[$currentLang] ?? 'PT') ?></span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5" style="display:block"></i>
                </button>
                <div x-show="open" x-transition class="absolute right-0 mt-2 w-56 rounded-xl shadow-xl border py-1.5 z-50" style="background:white;border-color:var(--border-default);display:none">
                    <?php foreach ($langNames as $code=>$name): $activeLang = $currentLang === $code; ?>
                    <a href="<?= e(urlWithParam('lang', $code)) ?>" data-lang-switch="<?= e($code) ?>" data-lang-option="<?= e($code) ?>" data-lang-flag="<?= e($flagUrl($code)) ?>" data-lang-sigla="<?= e($siglas[$code]) ?>" data-lang-name="<?= e($name) ?>" aria-current="<?= $activeLang ? 'true' : 'false' ?>" class="lang-option flex items-center gap-2.5 px-3 py-2 text-sm hover:bg-gray-50 <?= $activeLang ? 'is-active' : '' ?>" style="color:var(--text-primary)">
                        <img src="<?= $flagUrl($code) ?>" alt="" style="width:24px;height:18px;object-fit:cover;border-radius:2px;display:block;flex-shrink:0">
                        <span class="font-bold text-xs w-6"><?= e($siglas[$code]) ?></span>
                        <span class="flex-1" translate="no"><?= e($name) ?></span>
                        <i data-lang-check data-lucide="check" class="w-4 h-4" style="color:var(--terracota);<?= $activeLang ? '' : 'display:none' ?>"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Currency switcher -->
            <div class="relative hidden md:block" x-data="{open:false}" @click.away="open=false">
                <?php
                    $currencySymbols = ['BRL'=>'R$','USD'=>'US$','EUR'=>'€','GBP'=>'£','ARS'=>'AR$'];
                    $currencyNames   = ['BRL'=>'Real','USD'=>'Dólar','EUR'=>'Euro','GBP'=>'Libra','ARS'=>'Peso Arg.'];
                ?>
                <button @click="open=!open" class="flex items-center gap-1.5 px-2.5 py-2 rounded-xl nav-link-tr text-sm font-bold" aria-label="Moeda">
                    <span style="color:var(--terracota)"><?= e($currencySymbols[$currentCurrency] ?? 'R$') ?></span>
                    <span class="text-xs tracking-wide"><?= e($currentCurrency) ?></span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </button>
                <div x-show="open" x-transition class="absolute right-0 mt-2 w-48 rounded-xl shadow-xl border py-1.5 z-50" style="background:white;border-color:var(--border-default);display:none">
                    <?php foreach ($currencyNames as $code=>$name): ?>
                    <a href="<?= e(urlWithParam('currency', $code)) ?>" class="flex items-center gap-2.5 px-3 py-2 text-sm hover:bg-gray-50" style="color:var(--text-primary)">
                        <span class="font-bold w-9" style="color:var(--terracota)"><?= e($currencySymbols[$code]) ?></span>
                        <span class="flex-1"><?= e($name) ?></span>
                        <span class="text-[11px] font-bold" style="color:var(--text-muted)"><?= e($code) ?></span>
                        <?php if ($currentCurrency===$code): ?><i data-lucide="check" class="w-4 h-4 ml-1" style="color:var(--terracota)"></i><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart button -->
            <button type="button" data-cart-open onclick="window.cart && window.cart.open()"
                    class="cart-open-btn relative p-2.5 rounded-xl transition hover:scale-105 nav-link-tr"
                    aria-label="Carrinho">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                <span class="cart-badge" id="cart-count" style="display:none">0</span>
            </button>

            <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>" target="_blank"
               class="hidden md:inline-flex items-center gap-2 px-4 py-2 rounded-xl font-semibold text-sm text-white shadow-md hover:shadow-lg transition"
               style="background:linear-gradient(135deg,var(--terracota),var(--terracota-dark))">
                <i data-lucide="message-circle" class="w-4 h-4"></i> <?= e(t('nav.book_now')) ?>
            </a>
            <!-- Menu drawer button (mobile only) -->
            <button type="button" class="lg:hidden p-2.5 rounded-xl nav-link-tr transition hover:scale-105"
                    x-data @click="window.openMenuDrawer && window.openMenuDrawer()"
                    aria-label="Abrir menu">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
        </div>
    </div>
</nav>

<!-- ============== PREMIUM OFF-CANVAS DRAWER ============== -->
<div id="menu-drawer-backdrop" class="menu-drawer-backdrop" onclick="window.closeMenuDrawer && window.closeMenuDrawer()"></div>
<aside id="menu-drawer" class="menu-drawer" aria-hidden="true" aria-label="Menu principal">
    <div class="menu-drawer-inner">
        <!-- Header -->
        <div class="menu-drawer-header">
            <img src="<?= asset('brand/logo-azul.png') ?>" alt="Caminhos de Alagoas" class="h-10">
            <button type="button" class="menu-drawer-close" onclick="window.closeMenuDrawer && window.closeMenuDrawer()" aria-label="Fechar">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="menu-drawer-nav">
            <div class="menu-drawer-section-title">Navegação</div>
            <a href="<?= url('/') ?>" class="menu-drawer-link"><i data-lucide="home" class="w-4 h-4"></i>Home</a>
            <a href="<?= url('/passeios') ?>" class="menu-drawer-link"><i data-lucide="compass" class="w-4 h-4"></i>Passeios</a>
            <a href="<?= url('/pacotes') ?>" class="menu-drawer-link"><i data-lucide="package" class="w-4 h-4"></i>Pacotes</a>
            <a href="<?= url('/transfers') ?>" class="menu-drawer-link"><i data-lucide="car" class="w-4 h-4"></i>Transfers</a>
            <a href="<?= url('/sobre') ?>" class="menu-drawer-link"><i data-lucide="book-open" class="w-4 h-4"></i>Sobre nós</a>
            <a href="<?= url('/contato') ?>" class="menu-drawer-link"><i data-lucide="mail" class="w-4 h-4"></i>Contato</a>

            <div class="menu-drawer-section-title">Conta</div>
            <?php if (isCustomerLoggedIn()): ?>
                <a href="<?= url('/conta') ?>" class="menu-drawer-link"><i data-lucide="user-circle" class="w-4 h-4"></i>Minha conta</a>
                <a href="<?= url('/conta/reservas') ?>" class="menu-drawer-link"><i data-lucide="calendar-check" class="w-4 h-4"></i>Minhas reservas</a>
                <a href="<?= url('/conta/favoritos') ?>" class="menu-drawer-link"><i data-lucide="heart" class="w-4 h-4"></i>Favoritos</a>
                <a href="<?= url('/conta/sair') ?>" class="menu-drawer-link"><i data-lucide="log-out" class="w-4 h-4"></i>Sair</a>
            <?php else: ?>
                <a href="<?= url('/conta/login') ?>" class="menu-drawer-link"><i data-lucide="log-in" class="w-4 h-4"></i>Entrar</a>
                <a href="<?= url('/conta/cadastro') ?>" class="menu-drawer-link"><i data-lucide="user-plus" class="w-4 h-4"></i>Criar conta</a>
            <?php endif; ?>

            <div class="menu-drawer-section-title">Preferências</div>
            <div class="menu-drawer-prefs">
                <div class="menu-drawer-pref" x-data="{open:false}" @click.away="open=false">
                    <button type="button" @click="open=!open" class="menu-drawer-pref-btn">
                        <span class="inline-flex items-center gap-2"><img data-lang-current-flag src="<?= $flagUrl($currentLang) ?>" alt="" style="width:22px;height:16px;object-fit:cover;border-radius:2px;display:block"><span data-lang-current-name>Idioma</span><span data-lang-current-code class="text-[11px] font-bold px-1.5 py-0.5 rounded" style="background:var(--areia-light);color:var(--terracota-dark)"><?= e($siglas[$currentLang] ?? 'PT') ?></span></span>
                        <i data-lucide="chevron-down" class="w-4 h-4" :class="open?'rotate-180':''"></i>
                    </button>
                    <div x-show="open" x-transition x-cloak class="menu-drawer-pref-list">
                        <?php foreach ($langNames as $code=>$name): $activeLang = $currentLang === $code; ?>
                            <a href="<?= e(urlWithParam('lang', $code)) ?>" data-lang-switch="<?= e($code) ?>" data-lang-option="<?= e($code) ?>" data-lang-flag="<?= e($flagUrl($code)) ?>" data-lang-sigla="<?= e($siglas[$code]) ?>" data-lang-name="<?= e($name) ?>" aria-current="<?= $activeLang ? 'true' : 'false' ?>" class="lang-option inline-flex items-center gap-2 <?= $activeLang ? 'is-active' : '' ?>"><img src="<?= $flagUrl($code) ?>" alt="" style="width:20px;height:14px;object-fit:cover;border-radius:2px;display:block"><span translate="no"><?= e($name) ?></span><i data-lang-check data-lucide="check" class="w-3.5 h-3.5 ml-auto" style="<?= $activeLang ? '' : 'display:none' ?>"></i></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="menu-drawer-pref" x-data="{open:false}" @click.away="open=false">
                    <button type="button" @click="open=!open" class="menu-drawer-pref-btn">
                        <span><i data-lucide="coins" class="w-4 h-4 inline"></i> <?= e($currentCurrency) ?></span>
                        <i data-lucide="chevron-down" class="w-4 h-4" :class="open?'rotate-180':''"></i>
                    </button>
                    <div x-show="open" x-transition x-cloak class="menu-drawer-pref-list">
                        <?php foreach (['BRL'=>'R$ Real','USD'=>'US$ Dólar','EUR'=>'€ Euro','GBP'=>'£ Libra','ARS'=>'AR$ Peso'] as $code=>$name): ?>
                            <a href="?currency=<?= e($code) ?>"><?= e($name) ?> <?= $currentCurrency===$code ? '✓' : '' ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </nav>

        <!-- CTA + Socials -->
        <div class="menu-drawer-footer">
            <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>" target="_blank" class="menu-drawer-cta">
                <i data-lucide="message-circle" class="w-4 h-4"></i> Reservar via WhatsApp
            </a>
            <div class="menu-drawer-socials">
                <a href="<?= e(getSetting('social_instagram','https://instagram.com/caminhosdealagoas')) ?>" target="_blank" aria-label="Instagram">
                    <i data-lucide="instagram" class="w-4 h-4"></i>
                </a>
                <a href="<?= e(getSetting('social_facebook','https://facebook.com')) ?>" target="_blank" aria-label="Facebook">
                    <i data-lucide="facebook" class="w-4 h-4"></i>
                </a>
                <a href="<?= e(getSetting('social_youtube','https://youtube.com')) ?>" target="_blank" aria-label="YouTube">
                    <i data-lucide="youtube" class="w-4 h-4"></i>
                </a>
                <a href="<?= e(getSetting('social_tiktok','https://tiktok.com')) ?>" target="_blank" aria-label="TikTok">
                    <i data-lucide="music-2" class="w-4 h-4"></i>
                </a>
                <a href="mailto:<?= e(getSetting('contact_email','contato@caminhosdealagoas.com')) ?>" aria-label="Email">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </a>
            </div>
            <div class="menu-drawer-copy">© <?= date('Y') ?> Caminhos de Alagoas</div>
        </div>
    </div>
</aside>

<script>
window.openMenuDrawer = function(){
    document.getElementById('menu-drawer').classList.add('open');
    document.getElementById('menu-drawer-backdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
};
window.closeMenuDrawer = function(){
    document.getElementById('menu-drawer').classList.remove('open');
    document.getElementById('menu-drawer-backdrop').classList.remove('open');
    document.body.style.overflow = '';
};
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') window.closeMenuDrawer(); });
</script>
