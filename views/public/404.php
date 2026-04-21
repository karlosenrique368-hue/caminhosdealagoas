<?php
$pageTitle = '404 — Página não encontrada';
$solidNav = true;
include VIEWS_DIR . '/partials/public_head.php';
?>
<section class="pt-40 pb-20 min-h-screen flex items-center">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <div class="font-display text-[10rem] font-bold leading-none mb-4" style="background:linear-gradient(135deg,var(--horizonte),var(--terracota));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">404</div>
        <h1 class="font-display text-3xl md:text-4xl font-bold mb-4" style="color:var(--sepia)">Página não encontrada</h1>
        <p class="text-lg mb-10" style="color:var(--text-secondary)">A página que você procurava não existe ou foi movida.</p>
        <a href="<?= url('/') ?>" class="btn-primary"><i data-lucide="home" class="w-5 h-5"></i>Voltar ao início</a>
    </div>
</section>
<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
