<?php
$slug = $_GET['slug'] ?? '';
$r = dbOne("SELECT * FROM transfers WHERE slug=? AND status='published'", [$slug]);
if (!$r) { http_response_code(404); require VIEWS_DIR . '/public/404.php'; return; }
dbExec("UPDATE transfers SET views=views+1 WHERE id=?", [$r['id']]);

$related = dbAll("SELECT * FROM transfers WHERE status='published' AND id<>? ORDER BY RAND() LIMIT 4", [$r['id']]);
$includesArr = !empty($r['includes']) ? (json_decode($r['includes'], true) ?: []) : [];

$departuresAll = dbAll("SELECT * FROM departures WHERE entity_type='transfer' AND entity_id=? AND departure_date>=CURDATE() ORDER BY departure_date", [$r['id']]);
$departures = array_values(array_filter($departuresAll, fn($d) => $d['status'] === 'open'));
$availabilityMap = [];
foreach ($departuresAll as $d) {
    $availabilityMap[$d['departure_date']] = [
        'status' => $d['status'],
        'seats'  => max(0, (int)$d['seats_total'] - (int)$d['seats_sold']),
        'price'  => $d['price_override'] !== null ? (float)$d['price_override'] : (float)($r['price_pix'] ?: $r['price']),
        'time'   => $d['departure_time'],
    ];
}

$gallery = [];
if (!empty($r['cover_image'])) $gallery[] = storageUrl($r['cover_image']);
if (!empty($r['gallery'])) {
    $dec = json_decode($r['gallery'], true);
    if (is_array($dec)) foreach ($dec as $g) if ($g) $gallery[] = storageUrl($g);
}
$gallery = array_values(array_unique($gallery));

$pageTitle = $r['title'];
$pageDesc = $r['short_desc'] ?? '';

include VIEWS_DIR . '/partials/public_head.php';
?>

<section class="relative h-[55vh] min-h-[360px] overflow-hidden" style="margin-top:-80px">
    <?php if (!empty($r['cover_image'])): ?>
        <img src="<?= storageUrl($r['cover_image']) ?>" class="absolute inset-0 w-full h-full object-cover" alt="<?= e($r['title']) ?>">
    <?php else: ?>
        <div class="absolute inset-0" style="background:linear-gradient(135deg,var(--horizonte),var(--horizonte-dark))"></div>
    <?php endif; ?>
    <div class="absolute inset-0" style="background:linear-gradient(180deg,rgba(30,58,82,0.25) 0%, rgba(30,58,82,0.85) 100%)"></div>
    <div class="relative z-10 h-full flex items-end pb-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 w-full text-white">
            <a href="<?= url('/transfers') ?>" class="inline-flex items-center gap-1 text-sm text-white/80 hover:text-white mb-4"><i data-lucide="arrow-left" class="w-4 h-4"></i> Todos os transfers</a>
            <span class="inline-block text-[10px] uppercase tracking-widest font-bold px-3 py-1 rounded-full mb-3" style="background:var(--horizonte);color:#fff"><i data-lucide="car" class="w-3 h-3 inline"></i> Transfer privativo</span>
            <h1 class="font-display text-3xl sm:text-5xl md:text-6xl font-bold leading-tight mb-4 max-w-4xl"><?= e(tAuto($r['title'])) ?></h1>
            <div class="flex flex-wrap gap-5 text-sm text-white/85">
                <?php if ($r['location_from']): ?><div class="flex items-center gap-2"><i data-lucide="map-pin" class="w-4 h-4"></i><?= e(tAuto($r['location_from'])) ?> → <?= e(tAuto($r['location_to'])) ?></div><?php endif; ?>
                <?php if ($r['duration_minutes']): ?><div class="flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4"></i><?= (int)$r['duration_minutes'] ?> min</div><?php endif; ?>
                <div class="flex items-center gap-2"><i data-lucide="users" class="w-4 h-4"></i>Até <?= (int)$r['capacity'] ?> passageiros</div>
            </div>
        </div>
    </div>
</section>

<?php if (count($gallery) > 1): ?>
<section class="detail-gallery-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="detail-slider" data-slider>
            <div class="detail-slider-main slider-wrap">
                <?php foreach ($gallery as $idx => $img): ?>
                    <div class="slide<?= $idx===0?' active':'' ?>" style="background-image:url('<?= e($img) ?>')"></div>
                <?php endforeach; ?>
                <button type="button" class="slider-arrow prev" aria-label="Foto anterior"><i data-lucide="chevron-left" class="w-5 h-5"></i></button>
                <button type="button" class="slider-arrow next" aria-label="Próxima foto"><i data-lucide="chevron-right" class="w-5 h-5"></i></button>
            </div>
            <div class="slider-thumbs" aria-label="Miniaturas da galeria">
                <?php foreach ($gallery as $idx => $img): ?>
                    <button type="button" class="thumb<?= $idx===0?' active':'' ?>" aria-label="Ver foto <?= $idx+1 ?>"><img src="<?= e($img) ?>" alt="Foto <?= $idx+1 ?> de <?= e($r['title']) ?>" loading="<?= $idx===0?'eager':'lazy' ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="detail-content-section">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="grid lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2 space-y-8">
                <div class="admin-card p-6 sm:p-8">
                    <h2 class="font-display text-2xl font-bold mb-4" style="color:var(--sepia)">Sobre este transfer</h2>
                    <div class="text-[15px] leading-relaxed" style="color:var(--text-secondary)"><?= nl2br(e(tAuto($r['description'] ?? $r['short_desc'] ?? ''))) ?></div>
                </div>

                <?php if ($includesArr): ?>
                <div class="admin-card p-6 sm:p-8">
                    <h2 class="font-display text-2xl font-bold mb-4" style="color:var(--sepia)">O que está incluído</h2>
                    <ul class="space-y-2">
                        <?php foreach ($includesArr as $inc): ?>
                        <li class="flex items-start gap-2 text-sm" style="color:var(--text-secondary)"><i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0" style="color:var(--maresia)"></i><?= e(tAuto($inc)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div id="calendario" class="admin-card p-6 sm:p-8" x-data="availabilityCalendar(<?= htmlspecialchars(json_encode([
                    'mode' => 'open',
                    'map' => $availabilityMap,
                    'basePrice' => (float)($r['price_pix'] ?: $r['price']),
                    'checkoutBase' => url('/checkout?transfer=' . $r['id']),
                ]), ENT_QUOTES) ?>)">
                    <div class="flex items-start justify-between flex-wrap gap-4 mb-5">
                        <div>
                            <h2 class="font-display text-2xl font-bold flex items-center gap-3" style="color:var(--sepia)"><i data-lucide="calendar-days" class="w-6 h-6" style="color:var(--terracota)"></i> Datas para o transfer</h2>
                            <p class="text-sm mt-1" style="color:var(--text-muted)">Escolha a data de embarque. Datas bloqueadas no admin ficam indisponíveis.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="prevMonth()" class="w-10 h-10 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                            <div class="min-w-[160px] text-center font-display font-bold text-base" style="color:var(--sepia)" x-text="monthLabel"></div>
                            <button type="button" @click="nextMonth()" class="w-10 h-10 rounded-lg border flex items-center justify-center" style="border-color:var(--border-default);color:var(--text-secondary)"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                    <div class="calendar-grid">
                        <template x-for="dow in ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb']" :key="dow"><div class="text-center text-[11px] font-bold uppercase tracking-wider py-2" style="color:var(--text-muted)" x-text="dow"></div></template>
                        <template x-for="cell in cells" :key="cell.key">
                            <button type="button" :disabled="!cell.available" @click="cell.available && select(cell)" class="calendar-cell" :class="{'empty':cell.empty,'past':cell.past,'available':cell.available&&!cell.lowSeats,'low':cell.available&&cell.lowSeats,'blocked':cell.blocked,'selected':cell.iso&&isSelected(cell.iso)}">
                                <span class="cal-day" x-text="cell.day"></span>
                                <span class="cal-price" x-show="cell.available" x-text="cell.seats !== null ? cell.seats + ' vagas' : 'Livre'"></span>
                            </button>
                        </template>
                    </div>
                    <div x-show="selectedDates.length" x-cloak class="mt-6 p-5 rounded-xl flex items-center justify-between flex-wrap gap-4" style="background:rgba(201,107,74,0.08);border:1px solid rgba(201,107,74,0.25)">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color:var(--terracota)">Datas selecionadas</div>
                            <div class="font-display font-bold text-lg" style="color:var(--sepia)" x-text="selectedLabel"></div>
                            <div class="text-xs mt-0.5" style="color:var(--text-secondary)" x-text="selectedDetail"></div>
                        </div>
                        <a :href="selectedCheckoutUrl" class="btn-primary"><i data-lucide="calendar-check" class="w-5 h-5"></i> Reservar datas</a>
                    </div>
                </div>
            </div>

            <aside id="reservar" class="lg:sticky lg:top-28 lg:self-start space-y-5">
                <div class="admin-card p-6">
                    <div class="text-xs uppercase tracking-wider font-semibold mb-1" style="color:var(--text-muted)">A partir de</div>
                    <div class="font-display text-4xl font-bold mb-1" style="color:var(--terracota)"><?= formatPrice($r['price_pix'] ?: $r['price']) ?></div>
                    <?php if ($r['price_pix']): ?><div class="text-xs" style="color:var(--text-muted)">PIX · ou <?= formatPrice($r['price']) ?> no cartão</div><?php endif; ?>

                    <hr class="my-5" style="border-color:var(--border-default)">

                    <div class="space-y-3 text-sm mb-5" style="color:var(--text-secondary)">
                        <div class="flex items-center justify-between"><span class="inline-flex items-center gap-1.5" style="color:var(--text-muted)"><i data-lucide="car" class="w-4 h-4"></i>Veículo</span><strong style="color:var(--sepia)"><?= e(tAuto($r['vehicle_type'] ?? '—')) ?></strong></div>
                        <div class="flex items-center justify-between"><span class="inline-flex items-center gap-1.5" style="color:var(--text-muted)"><i data-lucide="users" class="w-4 h-4"></i>Capacidade</span><strong style="color:var(--sepia)">Até <?= (int)$r['capacity'] ?></strong></div>
                        <?php if ($r['distance_km']): ?>
                        <div class="flex items-center justify-between"><span class="inline-flex items-center gap-1.5" style="color:var(--text-muted)"><i data-lucide="route" class="w-4 h-4"></i>Distância</span><strong style="color:var(--sepia)"><?= (int)$r['distance_km'] ?> km</strong></div>
                        <?php endif; ?>
                        <div class="flex items-center justify-between"><span class="inline-flex items-center gap-1.5" style="color:var(--text-muted)"><i data-lucide="repeat" class="w-4 h-4"></i>Tipo</span><strong style="color:var(--sepia)"><?= !empty($r['one_way']) ? 'Apenas ida' : 'Ida e volta' ?></strong></div>
                    </div>

                    <a href="#calendario" onclick="event.preventDefault();document.getElementById('calendario').scrollIntoView({behavior:'smooth',block:'start'})" class="btn-primary w-full"><i data-lucide="calendar-check" class="w-5 h-5"></i> Reservar por data</a>
                    <a href="https://wa.me/<?= e(getSetting('contact_whatsapp','5582988220546')) ?>?text=Ol%C3%A1!%20Quero%20reservar%20o%20transfer%20<?= urlencode($r['title']) ?>" target="_blank" class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 font-semibold text-sm" style="color:var(--horizonte);border-color:var(--horizonte);background:rgba(58,107,138,0.05)"><i data-lucide="message-circle" class="w-4 h-4"></i> Falar no WhatsApp</a>
                </div>
            </aside>
        </div>

        <?php if ($related): ?>
        <div class="mt-20">
            <h2 class="font-display text-3xl font-bold mb-8 text-center" style="color:var(--sepia)">Outros transfers</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <?php foreach ($related as $rel): ?>
                <a href="<?= url('/transfers/'.$rel['slug']) ?>" class="roteiro-card group">
                    <div class="img-wrap" style="aspect-ratio:4/3">
                        <?php if (!empty($rel['cover_image'])): ?>
                            <div class="slide active" style="background-image:url('<?= e(storageUrl($rel['cover_image'])) ?>')"></div>
                        <?php else: ?>
                            <div class="img-placeholder w-full h-full"><span><?= e(mb_substr($rel['title'],0,1)) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <div class="p-5">
                        <h3 class="font-display text-base font-bold leading-snug mb-2 line-clamp-2" style="color:var(--sepia)"><?= e(tAuto($rel['title'])) ?></h3>
                        <div class="font-display text-lg font-bold" style="color:var(--terracota)"><?= formatPrice($rel['price_pix'] ?: $rel['price']) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
function availabilityCalendar(config) {
    const today = new Date(); today.setHours(0,0,0,0);
    return {
        mode: config.mode || 'open', map: config.map || {}, basePrice: config.basePrice || 0, checkoutBase: config.checkoutBase,
        viewYear: today.getFullYear(), viewMonth: today.getMonth(), selectedIso: null,
        get monthLabel(){ const n=['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro']; return n[this.viewMonth]+' de '+this.viewYear; },
        pad(n){ return n<10?'0'+n:''+n; },
        iso(y,m,d){ return y+'-'+this.pad(m+1)+'-'+this.pad(d); },
        brl(v){ return 'R$ ' + Number(v||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); },
        get cells(){
            const first = new Date(this.viewYear,this.viewMonth,1);
            const start = first.getDay();
            const days = new Date(this.viewYear,this.viewMonth+1,0).getDate();
            const cells=[];
            for(let i=0;i<start;i++) cells.push({key:'e'+i,empty:true});
            for(let d=1;d<=days;d++){
                const dateObj = new Date(this.viewYear,this.viewMonth,d);
                const isoStr = this.iso(this.viewYear,this.viewMonth,d);
                const past = dateObj < today;
                const info = this.map[isoStr];
                let available=false, lowSeats=false, blocked=false, price=this.basePrice;
                if (!past && info) { available = info.status === 'open' && Number(info.seats||0) > 0; blocked = !available; lowSeats = available && Number(info.seats)<=3; price = info.price || price; }
                else if (!past && this.mode === 'open') available = true;
                cells.push({key:isoStr, iso:isoStr, day:d, empty:false, past, available, lowSeats, blocked, priceLabel:available?this.brl(price):'', seats:info?info.seats:null, price});
            }
            return cells;
        },
        prevMonth(){ if(this.viewMonth===0){this.viewMonth=11;this.viewYear--;}else this.viewMonth--; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        nextMonth(){ if(this.viewMonth===11){this.viewMonth=0;this.viewYear++;}else this.viewMonth++; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        select(c){ this.selectedIso=c.iso; this.$nextTick(()=>window.lucide&&window.lucide.createIcons()); },
        get selectedLabel(){ if(!this.selectedIso)return''; return new Date(this.selectedIso+'T12:00:00').toLocaleDateString('pt-BR',{day:'2-digit',month:'long',year:'numeric'}); },
        get selectedDetail(){ const c=this.cells.find(x=>x.iso===this.selectedIso); if(!c)return''; const parts=[this.brl(c.price)+' por veículo']; if(c.seats!==null) parts.push(c.seats+' vagas restantes'); return parts.join(' · '); },
        get selectedCheckoutUrl(){ return this.checkoutBase + '&date=' + (this.selectedIso || ''); },
    };
}
</script>

<!-- Sticky bottom bar mobile -->
<div class="mobile-book-bar md:hidden">
    <div class="flex-1 min-w-0">
        <div class="text-[10px] uppercase tracking-wider font-bold opacity-70">A partir de</div>
        <div class="font-display text-xl font-bold leading-none" style="color:var(--terracota)"><?= formatPrice($r['price_pix'] ?: $r['price']) ?></div>
    </div>
    <a href="#calendario" onclick="event.preventDefault();document.getElementById('calendario').scrollIntoView({behavior:'smooth',block:'start'})" class="btn-primary" style="white-space:nowrap"><i data-lucide="calendar-check" class="w-4 h-4"></i> Reservar</a>
</div>
<style>
.mobile-book-bar{position:fixed;bottom:0;left:0;right:0;z-index:60;background:var(--bg-card);border-top:1px solid var(--border-default);padding:12px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 -8px 24px -8px rgba(0,0,0,.15);padding-bottom:calc(12px + env(safe-area-inset-bottom))}
@media(min-width:768px){.mobile-book-bar{display:none !important}}
@media(max-width:767px){body{padding-bottom:88px}}
</style>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
