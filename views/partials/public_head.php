<?php
$pageTitle = $pageTitle ?? APP_NAME;
$pageDesc = $pageDesc ?? 'Descubra as belezas de Alagoas com roteiros, passeios e pacotes premium.';
$pageImage = $pageImage ?? asset('brand/logo-azul.png');
$pageSchema = $pageSchema ?? null; // optional JSON-LD injected by child page
$currentUrl = 'http' . (!empty($_SERVER['HTTPS'])?'s':'') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
$gaId = getSetting('ga_id', '');
$fbPixelId = getSetting('fb_pixel_id', '');
$currentLang = $_SESSION['lang'] ?? 'pt-BR';
$currentCurrency = $_SESSION['currency'] ?? 'BRL';
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
  "description": "Roteiros, passeios e pacotes autênticos em Alagoas.",
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

<?php if ($gaId): ?>
<!-- Google Analytics (GA4) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?= e($gaId) ?>');
</script>
<?php endif; ?>

<?php if ($fbPixelId): ?>
<!-- Meta Pixel -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?= e($fbPixelId) ?>');
fbq('track', 'PageView');
</script>
<?php endif; ?>

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

<!-- Lucide -->
<script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>

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
            <a href="<?= url('/') ?>" class="nav-link nav-link-tr text-sm">Home</a>
            <a href="<?= url('/passeios') ?>" class="nav-link nav-link-tr text-sm">Passeios</a>
            <a href="<?= url('/pacotes') ?>" class="nav-link nav-link-tr text-sm">Pacotes</a>
            <a href="<?= url('/sobre') ?>" class="nav-link nav-link-tr text-sm">Sobre</a>
            <a href="<?= url('/contato') ?>" class="nav-link nav-link-tr text-sm">Contato</a>
            <?php if (isCustomerLoggedIn()): ?>
                <a href="<?= url('/conta') ?>" class="nav-link nav-link-tr text-sm font-bold" style="color:var(--terracota)"><i data-lucide="user-circle" class="w-4 h-4 inline"></i> Minha conta</a>
            <?php else: ?>
                <a href="<?= url('/conta/login') ?>" class="nav-link nav-link-tr text-sm">Entrar</a>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-3">
            <!-- Language switcher -->
            <div class="relative hidden md:block" x-data="{open:false}" @click.away="open=false">
                <button @click="open=!open" class="flex items-center gap-1.5 px-2.5 py-2 rounded-xl nav-link-tr text-sm font-semibold" aria-label="Idioma">
                    <span style="font-size:18px;line-height:1"><?php
                        $flags = ['pt-BR'=>'🇧🇷','en'=>'🇺🇸','es'=>'🇪🇸','fr'=>'🇫🇷','de'=>'🇩🇪','it'=>'🇮🇹','zh'=>'🇨🇳'];
                        echo $flags[$currentLang] ?? '🇧🇷';
                    ?></span>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </button>
                <div x-show="open" x-transition class="absolute right-0 mt-2 w-44 rounded-xl shadow-xl border py-1.5 z-50" style="background:white;border-color:var(--border-default);display:none">
                    <?php foreach (['pt-BR'=>'Português','en'=>'English','es'=>'Español','fr'=>'Français','de'=>'Deutsch','it'=>'Italiano','zh'=>'中文'] as $code=>$name): ?>
                    <a href="?lang=<?= e($code) ?>" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50" style="color:var(--text-primary)">
                        <span style="font-size:18px"><?= $flags[$code] ?></span> <?= e($name) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Currency switcher -->
            <div class="relative hidden md:block" x-data="{open:false}" @click.away="open=false">
                <button @click="open=!open" class="flex items-center gap-1 px-2.5 py-2 rounded-xl nav-link-tr text-sm font-bold" aria-label="Moeda">
                    <?= e($currentCurrency) ?>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                </button>
                <div x-show="open" x-transition class="absolute right-0 mt-2 w-36 rounded-xl shadow-xl border py-1.5 z-50" style="background:white;border-color:var(--border-default);display:none">
                    <?php foreach (['BRL'=>'R$ Real','USD'=>'US$ Dólar','EUR'=>'€ Euro','GBP'=>'£ Libra','ARS'=>'AR$ Peso'] as $code=>$name): ?>
                    <a href="?currency=<?= e($code) ?>" class="flex items-center justify-between px-3 py-2 text-sm hover:bg-gray-50" style="color:var(--text-primary)">
                        <span><?= e($name) ?></span>
                        <?php if ($currentCurrency===$code): ?><i data-lucide="check" class="w-4 h-4" style="color:var(--terracota)"></i><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart button -->
            <button type="button" onclick="window.cart && window.cart.open()"
                    class="relative p-2.5 rounded-xl transition hover:scale-105 nav-link-tr"
                    aria-label="Carrinho">
                <i data-lucide="shopping-bag" class="w-5 h-5"></i>
                <span class="cart-badge" id="cart-count" style="display:none">0</span>
            </button>

            <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>" target="_blank"
               class="hidden md:inline-flex items-center gap-2 px-4 py-2 rounded-xl font-semibold text-sm text-white shadow-md hover:shadow-lg transition"
               style="background:linear-gradient(135deg,var(--terracota),var(--terracota-dark))">
                <i data-lucide="message-circle" class="w-4 h-4"></i> Reservar
            </a>
            <!-- Mobile menu button -->
            <button class="lg:hidden p-2 rounded-lg nav-link-tr"
                    x-data @click="document.getElementById('mobile-menu').classList.toggle('hidden')">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
        </div>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="hidden lg:hidden border-t" style="background:white;border-color:var(--border-default)">
        <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col gap-1">
            <a href="<?= url('/') ?>" class="px-3 py-3 rounded-lg text-sm font-medium hover:bg-gray-50">Home</a>
            <a href="<?= url('/passeios') ?>" class="px-3 py-3 rounded-lg text-sm font-medium hover:bg-gray-50">Passeios</a>
            <a href="<?= url('/pacotes') ?>" class="px-3 py-3 rounded-lg text-sm font-medium hover:bg-gray-50">Pacotes</a>
            <a href="<?= url('/sobre') ?>" class="px-3 py-3 rounded-lg text-sm font-medium hover:bg-gray-50">Sobre</a>
            <a href="<?= url('/contato') ?>" class="px-3 py-3 rounded-lg text-sm font-medium hover:bg-gray-50">Contato</a>
        </div>
    </div>
</nav>
