<?php
$pageTitle = 'Depoimentos · Caminhos de Alagoas';
$pageDesc  = 'Histórias reais de viajantes que descobriram Alagoas com a gente.';
$solidNav  = true;

$q = paginate(
    "SELECT COUNT(*) AS c FROM testimonials WHERE active=1",
    "SELECT * FROM testimonials WHERE active=1 ORDER BY featured DESC, created_at DESC",
    [],
    ['allowed'=>[12,24,48], 'default'=>12]
);
$rows = $q['rows'];

// Agregados para KPIs
$agg = dbOne("SELECT COUNT(*) n, COALESCE(AVG(rating),5) avg, COALESCE(SUM(rating=5),0) five FROM testimonials WHERE active=1");

include VIEWS_DIR . '/partials/public_head.php';
?>

<style>
.testi-hero{position:relative;padding:110px 0 60px;background:linear-gradient(135deg,var(--horizonte) 0%,var(--horizonte-dark) 55%,var(--terracota) 100%);color:#fff;overflow:hidden}
.testi-hero::before{content:"";position:absolute;inset:0;background-image:radial-gradient(circle at 15% 30%, rgba(255,255,255,.1) 0, transparent 55%),radial-gradient(circle at 85% 70%, rgba(201,107,74,.35) 0, transparent 55%)}
.testi-kpi{background:rgba(255,255,255,.08);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.15);border-radius:18px;padding:22px 24px}
.testi-card{background:var(--bg-card);border:1px solid var(--border-default);border-radius:22px;padding:30px;padding-top:46px;transition:all .3s;position:relative;overflow:hidden}
.testi-card:hover{transform:translateY(-4px);box-shadow:0 20px 45px -15px rgba(0,0,0,.15);border-color:var(--terracota-light)}
.testi-card::before{content:'"';position:absolute;top:-16px;right:20px;font-family:Georgia,serif;font-size:150px;color:var(--terracota);opacity:.1;line-height:1;z-index:0;pointer-events:none}
.testi-card>*{position:relative;z-index:1}
.testi-stars{display:inline-flex;gap:2px}
.testi-featured{position:absolute;top:14px;left:14px;background:var(--terracota);color:#fff;padding:4px 10px;border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;z-index:2}
.testi-avatar{width:52px;height:52px;border-radius:50%;object-fit:cover;border:3px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,.1)}
.testi-avatar-fallback{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:700;font-size:22px;color:#fff;background:linear-gradient(135deg,var(--horizonte),var(--terracota))}
</style>

<section class="testi-hero">
    <img src="<?= asset('brand/selo-branco.png') ?>" class="seal-rotate absolute z-0 hidden md:block" style="top:40px;right:40px;width:110px;opacity:0.25" alt="">
    <div class="relative z-10 max-w-7xl mx-auto px-6">
        <div class="max-w-3xl">
            <span class="inline-block text-[11px] font-bold uppercase tracking-[0.3em] px-3 py-1 rounded-full mb-4" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2)"><?= e(t('depoimentos.badge')) ?></span>
            <h1 class="font-brand text-5xl md:text-7xl leading-[0.95] mb-4"><?= e(t('depoimentos.title')) ?></h1>
            <p class="text-lg md:text-xl text-white/85 max-w-2xl mb-10"><?= e(t('depoimentos.sub')) ?></p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 max-w-xl">
                <div class="testi-kpi">
                    <div class="text-[10px] font-bold uppercase tracking-widest opacity-75 mb-1"><?= e(t('depoimentos.k_reviews')) ?></div>
                    <div class="font-display text-3xl font-bold"><?= (int)$agg['n'] ?></div>
                </div>
                <div class="testi-kpi">
                    <div class="text-[10px] font-bold uppercase tracking-widest opacity-75 mb-1"><?= e(t('depoimentos.k_avg')) ?></div>
                    <div class="font-display text-3xl font-bold flex items-center gap-1"><?= number_format((float)$agg['avg'],1,',','.') ?><i data-lucide="star" class="w-6 h-6 fill-current" style="color:#FBBF24"></i></div>
                </div>
                <div class="testi-kpi">
                    <div class="text-[10px] font-bold uppercase tracking-widest opacity-75 mb-1"><?= e(t('depoimentos.k_five')) ?></div>
                    <div class="font-display text-3xl font-bold"><?= (int)$agg['five'] ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-20" style="background:var(--bg-surface)">
    <div class="max-w-7xl mx-auto px-6">
        <?php if (!$rows): ?>
            <div class="text-center py-20">
                <i data-lucide="message-square-quote" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
                <h3 class="font-display text-xl font-bold mb-1" style="color:var(--sepia)"><?= e(t('depoimentos.empty_title')) ?></h3>
                <p class="text-sm" style="color:var(--text-muted)"><?= e(t('depoimentos.empty_sub')) ?></p>
            </div>
        <?php else: ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($rows as $t): ?>
                <article class="testi-card">
                    <?php if (!empty($t['featured'])): ?><span class="testi-featured"><?= e(t('depoimentos.featured')) ?></span><?php endif; ?>
                    <div class="testi-stars mb-4">
                        <?php for ($s=0; $s<(int)$t['rating']; $s++): ?>
                            <i data-lucide="star" class="w-4 h-4 fill-current" style="color:#F59E0B"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="text-[15px] leading-relaxed mb-6 relative" style="color:var(--text-secondary)">
                        "<?= e(tAuto($t['content'])) ?>"
                    </p>
                    <div class="flex items-center gap-3 pt-5 border-t" style="border-color:var(--border-default)">
                        <?php if (!empty($t['avatar'])): ?>
                            <img src="<?= storageUrl($t['avatar']) ?>" alt="<?= e($t['name']) ?>" class="testi-avatar">
                        <?php else: ?>
                            <div class="testi-avatar-fallback"><?= e(mb_substr($t['name'],0,1)) ?></div>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold flex items-center gap-1.5 truncate" style="color:var(--sepia)">
                                <?= e($t['name']) ?>
                                <?php if (!empty($t['author_url'])): ?><a href="<?= e($t['author_url']) ?>" target="_blank" rel="noopener" style="color:var(--horizonte)"><i data-lucide="external-link" class="w-3 h-3"></i></a><?php endif; ?>
                            </div>
                            <?php if (!empty($t['location'])): ?>
                                <div class="text-xs flex items-center gap-1" style="color:var(--text-muted)"><i data-lucide="map-pin" class="w-3 h-3"></i><?= e(tAuto($t['location'])) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($t['created_at'])): ?>
                            <div class="text-[10px] font-semibold uppercase tracking-widest" style="color:var(--text-muted)"><?= e(dateBR($t['created_at'], 'monthYear')) ?></div>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

            <?php $pag = $q; include VIEWS_DIR . '/partials/pagination.php'; ?>
        <?php endif; ?>
    </div>
</section>

<section class="py-20 relative overflow-hidden" style="background:linear-gradient(135deg,var(--sepia) 0%,#3a2b20 100%);color:#fff">
    <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
        <i data-lucide="sparkles" class="w-8 h-8 mx-auto mb-4 opacity-80"></i>
        <h2 class="font-brand text-4xl md:text-5xl mb-4"><?= e(t('depoimentos.cta_title')) ?></h2>
        <p class="text-lg text-white/80 mb-8 max-w-xl mx-auto"><?= e(t('depoimentos.cta_sub')) ?></p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= url('/passeios') ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm transition" style="background:var(--terracota);color:#fff"><i data-lucide="compass" class="w-5 h-5"></i><?= e(t('home.hero.cta1')) ?></a>
            <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>" target="_blank" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm transition" style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.3)"><i data-lucide="message-circle" class="w-5 h-5"></i>WhatsApp</a>
        </div>
    </div>
</section>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
