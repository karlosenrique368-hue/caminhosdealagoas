<?php
$pageTitle = 'Reserva segura';
$solidNav = true;

$roteiroId = (int)($_GET['roteiro'] ?? 0);
$pacoteId  = (int)($_GET['pacote'] ?? 0);
$item = null;
$type = null;

if (!$roteiroId && !$pacoteId && !empty($_SESSION['cart'])) {
    $first = reset($_SESSION['cart']);
    if ($first['type'] === 'roteiro') $roteiroId = (int)$first['id'];
    elseif ($first['type'] === 'pacote') $pacoteId = (int)$first['id'];
}
if ($roteiroId) { $item = dbOne("SELECT * FROM roteiros WHERE id=? AND status='published'", [$roteiroId]); $type = 'roteiro'; }
elseif ($pacoteId) { $item = dbOne("SELECT * FROM pacotes WHERE id=? AND status='published'", [$pacoteId]); $type = 'pacote'; }
if (!$item) { redirect('/roteiros'); }

$refCode = currentReferralCode();
$refPartner = $refCode ? partnerByCode($refCode) : null;

include VIEWS_DIR . '/partials/public_head.php';
?>

<style>
.wiz-step-dot{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;transition:all .35s cubic-bezier(.4,0,.2,1);flex-shrink:0;border:2px solid var(--border-default);background:var(--bg-card);color:var(--text-muted)}
.wiz-step-dot.done{background:var(--maresia);border-color:var(--maresia);color:#fff}
.wiz-step-dot.active{background:var(--terracota);border-color:var(--terracota);color:#fff;box-shadow:0 0 0 6px rgba(201,107,74,.15);transform:scale(1.08)}
.wiz-step-bar{flex:1;height:3px;background:var(--border-default);position:relative;border-radius:3px;overflow:hidden}
.wiz-step-bar::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,var(--maresia),var(--terracota));transform:scaleX(0);transform-origin:left;transition:transform .5s ease}
.wiz-step-bar.filled::after{transform:scaleX(1)}
.wiz-radio{display:block;cursor:pointer;transition:all .2s}
.wiz-radio-inner{padding:16px;border-radius:12px;border:2px solid var(--border-default);background:var(--bg-card);transition:all .2s;display:flex;align-items:center;gap:12px}
.wiz-radio.active .wiz-radio-inner{border-color:var(--terracota);background:linear-gradient(135deg,rgba(201,107,74,.06),rgba(201,107,74,.02));box-shadow:0 6px 20px rgba(201,107,74,.12)}
.wiz-radio .wiz-radio-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--bg-surface);color:var(--terracota);flex-shrink:0;transition:all .2s}
.wiz-radio.active .wiz-radio-icon{background:var(--terracota);color:#fff}
.wiz-chip{padding:10px 14px;border-radius:10px;border:2px solid var(--border-default);background:var(--bg-card);cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px;font-weight:600;font-size:13px;color:var(--text-secondary)}
.wiz-chip.active{border-color:var(--terracota);background:rgba(201,107,74,.08);color:var(--terracota)}
.wiz-chip:hover{border-color:var(--text-muted)}
.wiz-card{animation:wizFade .4s ease}
@keyframes wizFade{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.lot-card{position:relative;padding:20px;border-radius:16px;border:2px solid var(--border-default);background:var(--bg-card);cursor:pointer;transition:all .25s}
.lot-card:hover{border-color:var(--text-muted)}
.lot-card.active{border-color:var(--terracota);background:linear-gradient(135deg,#fff,rgba(201,107,74,.04));box-shadow:0 10px 30px rgba(201,107,74,.15)}
.lot-ribbon{position:absolute;top:-10px;right:16px;background:linear-gradient(135deg,var(--maresia),var(--maresia-dark));color:#fff;padding:4px 12px;border-radius:999px;font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase}
.summary-drawer{transition:all .3s}
@media (max-width:1023px){.summary-drawer{position:fixed;bottom:0;left:0;right:0;z-index:30;border-radius:20px 20px 0 0;max-height:80vh;overflow-y:auto;box-shadow:0 -8px 40px rgba(0,0,0,.15)}}
</style>

<section class="pt-28 pb-16" style="background:linear-gradient(180deg,var(--bg-surface) 0%,var(--bg-page) 100%);min-height:100vh">
<div class="max-w-6xl mx-auto px-4 sm:px-6" x-data="checkoutWizard()" x-init="init()">

    <!-- Back -->
    <a href="<?= url($type==='roteiro' ? '/passeios' : '/pacotes') ?>" class="inline-flex items-center gap-1 text-sm mb-4" style="color:var(--horizonte)">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Voltar ao catálogo
    </a>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="font-display text-3xl sm:text-4xl font-bold" style="color:var(--sepia)">Vamos garantir sua vaga</h1>
        <p class="text-sm sm:text-base mt-1" style="color:var(--text-secondary)">Em <b>4 passos</b> você finaliza sua reserva. Seus dados ficam seguros.</p>
    </div>

    <!-- Stepper -->
    <div class="flex items-center gap-2 sm:gap-3 mb-8">
        <template x-for="(s, idx) in steps" :key="idx">
            <div class="flex items-center flex-1 last:flex-initial gap-2 sm:gap-3">
                <div class="wiz-step-dot" :class="idx < step ? 'done' : idx === step ? 'active' : ''">
                    <i x-show="idx < step" data-lucide="check" class="w-5 h-5"></i>
                    <span x-show="idx >= step" x-text="idx + 1"></span>
                </div>
                <div class="hidden sm:block flex-1">
                    <div class="text-[10px] uppercase tracking-wider font-bold" :style="idx <= step ? 'color:var(--terracota)' : 'color:var(--text-muted)'" x-text="'Passo ' + (idx+1)"></div>
                    <div class="text-xs font-semibold" :style="idx <= step ? 'color:var(--sepia)' : 'color:var(--text-muted)'" x-text="s"></div>
                </div>
                <div class="wiz-step-bar" :class="idx < step ? 'filled' : ''" x-show="idx < steps.length - 1"></div>
            </div>
        </template>
    </div>

    <div class="grid lg:grid-cols-[1fr_380px] gap-6">

        <!-- ========== CARD ========== -->
        <div class="admin-card p-6 sm:p-8">

            <!-- STEP 0: Dados pessoais -->
            <div class="wiz-card" x-show="step === 0" x-cloak :key="'s0'+step">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:rgba(201,107,74,.1);color:var(--terracota)"><i data-lucide="user-circle-2" class="w-6 h-6"></i></div>
                    <div><div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Passo 1 de 4</div><h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Seus dados</h2></div>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2"><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">Nome completo *</label><input x-model="form.name" class="admin-input w-full" placeholder="Como está no documento"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">CPF *</label><input x-model="form.document" class="admin-input w-full cpf-mask" placeholder="000.000.000-00"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">RG *</label><input x-model="form.rg" class="admin-input w-full" placeholder="00.000.000-0"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">Data de nascimento *</label><input type="date" x-model="form.birth_date" class="admin-input w-full"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">E-mail *</label><input type="email" x-model="form.email" class="admin-input w-full" placeholder="voce@email.com"></div>
                    <div class="sm:col-span-2"><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">WhatsApp *</label><input type="tel" x-model="form.phone" class="admin-input w-full phone-mask" placeholder="(00) 00000-0000"></div>
                </div>
                <div class="mt-4 p-3 rounded-xl flex items-start gap-2" style="background:rgba(122,157,110,.08);border:1px solid rgba(122,157,110,.2)">
                    <i data-lucide="shield-check" class="w-4 h-4 mt-0.5" style="color:var(--maresia-dark)"></i>
                    <span class="text-xs" style="color:var(--maresia-dark)">Criptografia SSL. Seus dados nunca são compartilhados.</span>
                </div>
            </div>

            <!-- STEP 1: Detalhes da viagem -->
            <div class="wiz-card" x-show="step === 1" x-cloak :key="'s1'+step">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:rgba(58,107,138,.1);color:var(--horizonte)"><i data-lucide="map-pin" class="w-6 h-6"></i></div>
                    <div><div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Passo 2 de 4</div><h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Sua viagem</h2></div>
                </div>

                <div class="grid sm:grid-cols-3 gap-4 mb-6">
                    <div class="sm:col-span-3"><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">Data preferida *</label><input type="date" x-model="form.travel_date" :min="today" class="admin-input w-full"></div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">Adultos *</label>
                        <div class="flex items-center gap-2 admin-input">
                            <button type="button" @click="form.adults = Math.max(1, form.adults-1)" class="px-2 font-bold text-lg" style="color:var(--terracota)">−</button>
                            <input type="number" min="1" x-model.number="form.adults" class="flex-1 text-center border-0 bg-transparent outline-none font-semibold">
                            <button type="button" @click="form.adults++" class="px-2 font-bold text-lg" style="color:var(--terracota)">+</button>
                        </div>
                    </div>
                    <div><label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">Crianças</label>
                        <div class="flex items-center gap-2 admin-input">
                            <button type="button" @click="form.children = Math.max(0, form.children-1)" class="px-2 font-bold text-lg" style="color:var(--terracota)">−</button>
                            <input type="number" min="0" x-model.number="form.children" class="flex-1 text-center border-0 bg-transparent outline-none font-semibold">
                            <button type="button" @click="form.children++" class="px-2 font-bold text-lg" style="color:var(--terracota)">+</button>
                        </div>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2" style="color:var(--text-secondary)">Alguém do grupo tem comorbidade? *</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" @click="form.has_comorbidity='nao'; form.comorbidity=''" class="wiz-chip justify-center" :class="form.has_comorbidity==='nao'?'active':''"><i data-lucide="check-circle" class="w-4 h-4"></i>Não</button>
                        <button type="button" @click="form.has_comorbidity='sim'" class="wiz-chip justify-center" :class="form.has_comorbidity==='sim'?'active':''"><i data-lucide="heart-pulse" class="w-4 h-4"></i>Sim</button>
                    </div>
                    <div x-show="form.has_comorbidity==='sim'" x-transition class="mt-3" x-cloak>
                        <textarea x-model="form.comorbidity" rows="2" class="admin-input w-full" placeholder="Descreva brevemente para cuidarmos melhor de você."></textarea>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2" style="color:var(--text-secondary)">Como você nos conheceu? *</label>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                        <template x-for="s in sources" :key="s.id">
                            <button type="button" @click="form.source=s.id" class="wiz-chip justify-center flex-col py-3" :class="form.source===s.id?'active':''">
                                <i :data-lucide="s.icon" class="w-4 h-4"></i>
                                <span x-text="s.label" class="text-[11px]"></span>
                            </button>
                        </template>
                    </div>
                    <div x-show="form.source==='indicacao'" x-transition class="mt-3" x-cloak>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color:var(--text-secondary)">Quem indicou? *</label>
                        <input x-model="form.source_detail" class="admin-input w-full" placeholder="Nome de quem indicou">
                        <?php if ($refPartner): ?>
                            <p class="text-xs mt-1 flex items-center gap-1" style="color:var(--maresia-dark)"><i data-lucide="check-circle-2" class="w-3 h-3"></i> Indicação registrada automaticamente via <b><?= e($refPartner['name']) ?></b>.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Forma de pagamento -->
            <div class="wiz-card" x-show="step === 2" x-cloak :key="'s2'+step">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:rgba(122,157,110,.1);color:var(--maresia-dark)"><i data-lucide="wallet" class="w-6 h-6"></i></div>
                    <div><div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Passo 3 de 4</div><h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Como prefere pagar?</h2></div>
                </div>

                <div class="grid sm:grid-cols-2 gap-3 mb-4">
                    <template x-for="pm in paymentMethods" :key="pm.id">
                        <label class="wiz-radio" :class="form.payment_method===pm.id?'active':''">
                            <input type="radio" x-model="form.payment_method" :value="pm.id" class="sr-only">
                            <div class="wiz-radio-inner">
                                <div class="wiz-radio-icon"><i :data-lucide="pm.icon" class="w-5 h-5"></i></div>
                                <div class="flex-1">
                                    <div class="font-semibold text-sm" style="color:var(--sepia)" x-text="pm.label"></div>
                                    <div class="text-[11px]" style="color:var(--text-muted)" x-text="pm.hint"></div>
                                </div>
                                <div x-show="pm.badge" class="text-[10px] font-bold px-2 py-1 rounded-full" style="background:var(--maresia);color:#fff" x-text="pm.badge"></div>
                            </div>
                        </label>
                    </template>
                </div>

                <!-- Lotes promocionais (quando PIX disponível) -->
                <div x-show="form.payment_method==='pix'" x-transition>
                    <div class="text-[10px] uppercase tracking-widest font-bold mt-5 mb-3" style="color:var(--terracota)">Selecione o lote</div>
                    <div class="grid gap-3">
                        <label class="lot-card" :class="form.price_option==='promo'?'active':''" x-show="pricePix > 0 && pricePix < price">
                            <span class="lot-ribbon">Econômico · PIX</span>
                            <input type="radio" x-model="form.price_option" value="promo" class="sr-only">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold" style="color:var(--sepia)">Lote promocional</div>
                                    <div class="text-xs mt-1" style="color:var(--text-muted)">Pagamento via PIX instantâneo.</div>
                                </div>
                                <div class="text-right"><div class="font-display text-2xl font-bold" style="color:var(--terracota)" x-text="'R$ ' + pricePix.toFixed(2).replace('.',',')"></div><div class="text-[10px]" style="color:var(--text-muted)">por adulto</div></div>
                            </div>
                        </label>
                        <label class="lot-card" :class="form.price_option==='regular'?'active':''">
                            <input type="radio" x-model="form.price_option" value="regular" class="sr-only">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold" style="color:var(--sepia)">Lote regular</div>
                                    <div class="text-xs mt-1" style="color:var(--text-muted)">Preço padrão sem desconto.</div>
                                </div>
                                <div class="text-right"><div class="font-display text-2xl font-bold" style="color:var(--sepia)" x-text="'R$ ' + price.toFixed(2).replace('.',',')"></div><div class="text-[10px]" style="color:var(--text-muted)">por adulto</div></div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Revisão -->
            <div class="wiz-card" x-show="step === 3" x-cloak :key="'s3'+step">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center" style="background:rgba(201,107,74,.1);color:var(--terracota)"><i data-lucide="clipboard-check" class="w-6 h-6"></i></div>
                    <div><div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Passo 4 de 4</div><h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Revisar e confirmar</h2></div>
                </div>

                <div class="space-y-3 mb-5">
                    <div class="p-4 rounded-xl flex justify-between items-start gap-3" style="background:var(--bg-surface)">
                        <div><div class="text-[10px] uppercase tracking-wider font-bold mb-1" style="color:var(--text-muted)">Titular</div><div class="font-semibold text-sm" style="color:var(--sepia)" x-text="form.name"></div><div class="text-xs" style="color:var(--text-secondary)" x-text="form.email + ' · ' + form.phone"></div></div>
                        <button type="button" @click="step=0" class="text-xs font-semibold" style="color:var(--horizonte)">Editar</button>
                    </div>
                    <div class="p-4 rounded-xl flex justify-between items-start gap-3" style="background:var(--bg-surface)">
                        <div><div class="text-[10px] uppercase tracking-wider font-bold mb-1" style="color:var(--text-muted)">Viagem</div><div class="font-semibold text-sm" style="color:var(--sepia)" x-text="formatDate(form.travel_date) + ' · ' + form.adults + ' adulto(s)' + (form.children>0 ? ' + ' + form.children + ' criança(s)' : '')"></div></div>
                        <button type="button" @click="step=1" class="text-xs font-semibold" style="color:var(--horizonte)">Editar</button>
                    </div>
                    <div class="p-4 rounded-xl flex justify-between items-start gap-3" style="background:var(--bg-surface)">
                        <div><div class="text-[10px] uppercase tracking-wider font-bold mb-1" style="color:var(--text-muted)">Pagamento</div><div class="font-semibold text-sm" style="color:var(--sepia)" x-text="labelPayment()"></div></div>
                        <button type="button" @click="step=2" class="text-xs font-semibold" style="color:var(--horizonte)">Editar</button>
                    </div>
                </div>

                <label class="flex items-start gap-3 p-4 rounded-xl cursor-pointer" :style="form.accept_terms ? 'background:rgba(122,157,110,.08);border:1.5px solid rgba(122,157,110,.4)' : 'background:var(--bg-surface);border:1.5px solid var(--border-default)'">
                    <input type="checkbox" x-model="form.accept_terms" class="mt-1 w-5 h-5" style="accent-color:var(--terracota)">
                    <span class="text-sm" style="color:var(--text-secondary)">
                        Li e concordo com a <a href="<?= url('/politica-desistencia') ?>" target="_blank" class="font-bold underline" style="color:var(--sepia)">política de desistência</a>: em caso de cancelamento pelo passageiro, aplicam-se as condições previstas em contrato. *
                    </span>
                </label>
            </div>

            <!-- NAV BUTTONS -->
            <div class="mt-8 pt-6 border-t flex items-center justify-between gap-3" style="border-color:var(--border-default)">
                <button type="button" @click="prev()" x-show="step > 0" class="admin-btn admin-btn-secondary"><i data-lucide="arrow-left" class="w-4 h-4"></i>Voltar</button>
                <div x-show="step === 0"></div>
                <button type="button" @click="next()" x-show="step < steps.length - 1" class="btn-primary" :disabled="!canProceed()" :class="!canProceed()&&'opacity-60 cursor-not-allowed'">
                    <span>Continuar</span><i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
                <button type="button" @click="submit()" x-show="step === steps.length - 1" class="btn-primary" :disabled="loading || !form.accept_terms" :class="(loading||!form.accept_terms)&&'opacity-60'">
                    <i data-lucide="lock" class="w-4 h-4"></i><span x-text="loading ? 'Processando...' : 'Confirmar reserva'"></span>
                </button>
            </div>
        </div>

        <!-- ========== SUMMARY ========== -->
        <aside class="summary-drawer">
            <div class="admin-card p-5 sm:p-6 lg:sticky lg:top-24">
                <div class="flex gap-3 mb-4">
                    <?php if ($item['cover_image']): ?>
                        <img src="<?= storageUrl($item['cover_image']) ?>" class="w-20 h-20 rounded-xl object-cover">
                    <?php else: ?>
                        <div class="w-20 h-20 rounded-xl img-placeholder"><span class="text-2xl"><?= e(mb_substr($item['title'],0,1)) ?></span></div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--terracota)"><?= e($type) ?></div>
                        <div class="font-semibold leading-snug" style="color:var(--sepia)"><?= e($item['title']) ?></div>
                        <?php if (!empty($item['duration'])): ?><div class="text-xs flex items-center gap-1 mt-1" style="color:var(--text-muted)"><i data-lucide="clock" class="w-3 h-3"></i><?= e($item['duration']) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="py-4 border-t border-b space-y-2" style="border-color:var(--border-default)">
                    <div class="flex justify-between text-sm"><span style="color:var(--text-secondary)" x-text="'Adultos × ' + form.adults"></span><span style="color:var(--sepia)" x-text="formatBRL(effectivePrice() * form.adults)"></span></div>
                    <div class="flex justify-between text-sm" x-show="form.children > 0"><span style="color:var(--text-secondary)" x-text="'Crianças × ' + form.children"></span><span style="color:var(--sepia)" x-text="formatBRL(effectivePrice() * 0.5 * form.children)"></span></div>
                    <div class="flex justify-between text-sm" x-show="form.payment_method==='pix' && form.price_option==='promo'"><span style="color:var(--maresia-dark)"><i data-lucide="zap" class="w-3 h-3 inline"></i> Desconto PIX</span><span class="font-semibold" style="color:var(--maresia-dark)" x-text="'− ' + formatBRL(discount())"></span></div>
                </div>
                <div class="flex justify-between items-end pt-4">
                    <span class="text-sm font-semibold" style="color:var(--text-secondary)">Total</span>
                    <span class="font-display text-3xl font-bold" style="color:var(--terracota)" x-text="formatBRL(total())"></span>
                </div>
                <?php if ($refPartner): ?>
                <div class="mt-4 p-3 rounded-lg text-xs flex items-start gap-2" style="background:rgba(201,107,74,.06);border:1px solid rgba(201,107,74,.2)">
                    <i data-lucide="handshake" class="w-4 h-4 mt-0.5" style="color:var(--terracota)"></i>
                    <div style="color:var(--text-secondary)">Reserva indicada por <b style="color:var(--sepia)"><?= e($refPartner['name']) ?></b>.</div>
                </div>
                <?php endif; ?>
                <div class="mt-4 text-[11px] flex items-center gap-1.5" style="color:var(--text-muted)">
                    <i data-lucide="lock" class="w-3 h-3"></i> Pagamento 100% seguro · SSL / PIX / Cartão
                </div>
            </div>
        </aside>
    </div>
</div>
</section>

<script>
function checkoutWizard() {
    return {
        step: 0,
        loading: false,
        today: new Date().toISOString().split('T')[0],
        steps: ['Seus dados', 'Sua viagem', 'Pagamento', 'Revisão'],
        form: {
            name:'', document:'', rg:'', birth_date:'',
            email:'', phone:'',
            travel_date:'', adults:1, children:0,
            has_comorbidity:'nao', comorbidity:'',
            source:'', source_detail:<?= json_encode($refPartner['name'] ?? '') ?>,
            payment_method:'pix', price_option:'promo',
            accept_terms:false,
            ref_code:<?= json_encode($refCode ?? '') ?>,
            entity_type:'<?= $type ?>', entity_id:<?= (int)$item['id'] ?>
        },
        sources: [
            {id:'instagram', label:'Instagram', icon:'instagram'},
            {id:'whatsapp', label:'WhatsApp', icon:'message-circle'},
            {id:'indicacao', label:'Indicação', icon:'user-check'},
            {id:'google', label:'Google', icon:'search'},
            {id:'outro', label:'Outro', icon:'more-horizontal'}
        ],
        paymentMethods: [
            {id:'pix', label:'PIX', hint:'Confirmação em segundos', icon:'qr-code', badge:'Recomendado'},
            {id:'credit_card', label:'Cartão de crédito', hint:'Parcele em até 12×', icon:'credit-card', badge:''},
            {id:'boleto', label:'Boleto bancário', hint:'Compensação em 1-3 dias úteis', icon:'file-text', badge:''},
            {id:'pix_caixinha', label:'Combinar no WhatsApp', hint:'Fale direto com nossa equipe', icon:'message-square-heart', badge:''}
        ],
        price: <?= (float)$item['price'] ?>,
        pricePix: <?= (float)($item['price_pix'] ?: $item['price']) ?>,
        init() { if (window.lucide) window.lucide.createIcons(); },
        effectivePrice() { return (this.form.payment_method==='pix' && this.form.price_option==='promo') ? this.pricePix : this.price; },
        subtotal() { const p = this.effectivePrice(); return p * Math.max(1, this.form.adults) + p * 0.5 * Math.max(0, this.form.children); },
        discount() { if (this.form.payment_method!=='pix' || this.form.price_option!=='promo') return 0; const delta = this.price - this.pricePix; return delta * this.form.adults + delta * 0.5 * this.form.children; },
        total() { return this.subtotal(); },
        formatBRL(v) { return 'R$ ' + (v||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); },
        formatDate(s) { if (!s) return '—'; const d = new Date(s+'T12:00:00'); return d.toLocaleDateString('pt-BR',{day:'2-digit',month:'long',year:'numeric'}); },
        labelPayment() {
            const m = this.paymentMethods.find(p=>p.id===this.form.payment_method);
            let s = m ? m.label : '—';
            if (this.form.payment_method==='pix') s += ' · ' + (this.form.price_option==='promo' ? 'Lote promocional' : 'Lote regular');
            return s;
        },
        canProceed() {
            if (this.step === 0) return this.form.name.trim() && this.form.document.trim() && this.form.rg.trim() && this.form.birth_date && this.form.email.includes('@') && this.form.phone.trim();
            if (this.step === 1) return this.form.travel_date && this.form.adults >= 1 && this.form.source && (this.form.has_comorbidity!=='sim' || this.form.comorbidity.trim()) && (this.form.source!=='indicacao' || this.form.source_detail.trim());
            if (this.step === 2) return this.form.payment_method;
            return true;
        },
        next() {
            if (!this.canProceed()) { showToast('Complete os campos obrigatórios.', 'error'); return; }
            if (this.step < this.steps.length - 1) { this.step++; window.scrollTo({top:0, behavior:'smooth'}); setTimeout(()=>window.lucide && window.lucide.createIcons(), 50); }
        },
        prev() { if (this.step > 0) { this.step--; window.scrollTo({top:0, behavior:'smooth'}); setTimeout(()=>window.lucide && window.lucide.createIcons(), 50); } },
        async submit() {
            if (!this.form.accept_terms) { showToast('Aceite a política de desistência.', 'error'); return; }
            this.loading = true;
            const payload = { ...this.form, accept_terms:'1' };
            const res = await caminhosApi('<?= url('/api/booking') ?>', { method:'POST', data: payload });
            showToast(res.msg || (res.ok ? 'Reserva criada!' : 'Erro ao processar.'), res.ok ? 'success' : 'error');
            if (res.ok && res.redirect) window.location = res.redirect;
            this.loading = false;
        }
    }
}
</script>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
<?php
$pageTitle = 'Checkout';
$solidNav = true;

$roteiroId = (int)($_GET['roteiro'] ?? 0);
$pacoteId  = (int)($_GET['pacote'] ?? 0);
$item = null;
$type = null;

// Fallback: use first cart item if no query params
if (!$roteiroId && !$pacoteId && !empty($_SESSION['cart'])) {
    $first = reset($_SESSION['cart']);
    if ($first['type'] === 'roteiro') $roteiroId = (int)$first['id'];
    elseif ($first['type'] === 'pacote') $pacoteId = (int)$first['id'];
}

if ($roteiroId) {
    $item = dbOne("SELECT * FROM roteiros WHERE id=? AND status='published'", [$roteiroId]);
    $type = 'roteiro';
} elseif ($pacoteId) {
    $item = dbOne("SELECT * FROM pacotes WHERE id=? AND status='published'", [$pacoteId]);
    $type = 'pacote';
}

if (!$item) { redirect('/roteiros'); }

// Referral autofill
$refCode = currentReferralCode();
$refPartner = $refCode ? partnerByCode($refCode) : null;

include VIEWS_DIR . '/partials/public_head.php';
?>
<section class="pt-32 pb-16" style="background:var(--bg-surface)">
    <div class="max-w-6xl mx-auto px-6">
        <h1 class="font-display text-3xl md:text-4xl font-bold mb-8 text-center" style="color:var(--sepia)">Finalizar reserva</h1>

        <div class="grid lg:grid-cols-3 gap-8" x-data="checkoutApp()" @submit.prevent="submit">
            <!-- Form -->
            <form class="lg:col-span-2 space-y-6">
                <div class="admin-card p-6">
                    <h3 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Seus dados</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Nome completo *</label><input x-model="form.name" required class="admin-input"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">CPF *</label><input x-model="form.document" required class="admin-input cpf-mask" placeholder="000.000.000-00"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">RG *</label><input x-model="form.rg" required class="admin-input" placeholder="00.000.000-0"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Data de nascimento *</label><input type="date" x-model="form.birth_date" required class="admin-input"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">E-mail *</label><input type="email" x-model="form.email" required class="admin-input"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">WhatsApp *</label><input type="tel" x-model="form.phone" required class="admin-input phone-mask" placeholder="(00) 00000-0000"></div>
                    </div>
                </div>

                <div class="admin-card p-6">
                    <h3 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Informações de saúde</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-semibold mb-2" style="color:var(--sepia)">Possui alguma comorbidade? *</label>
                            <div class="flex gap-2">
                                <button type="button" @click="form.has_comorbidity='nao'; form.comorbidity=''" class="flex-1 p-3 rounded-xl border-2 text-center transition font-semibold text-sm" :style="form.has_comorbidity==='nao' ? 'border-color:var(--maresia);background:rgba(122,157,110,0.08);color:var(--maresia-dark)' : 'border-color:var(--border-default);color:var(--text-secondary)'">Não</button>
                                <button type="button" @click="form.has_comorbidity='sim'" class="flex-1 p-3 rounded-xl border-2 text-center transition font-semibold text-sm" :style="form.has_comorbidity==='sim' ? 'border-color:var(--terracota);background:rgba(201,107,74,0.06);color:var(--terracota)' : 'border-color:var(--border-default);color:var(--text-secondary)'">Sim</button>
                            </div>
                        </div>
                        <div x-show="form.has_comorbidity==='sim'" x-cloak x-transition>
                            <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Qual? *</label>
                            <textarea x-model="form.comorbidity" rows="2" :required="form.has_comorbidity==='sim'" class="admin-input" placeholder="Descreva a comorbidade para que possamos cuidar melhor de você."></textarea>
                        </div>
                    </div>
                </div>

                <div class="admin-card p-6">
                    <h3 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Como você chegou até a gente?</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-semibold mb-2" style="color:var(--sepia)">Como teve ciência do passeio? *</label>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                                <template x-for="s in sources">
                                    <button type="button" @click="form.source=s.id" class="p-3 rounded-xl border-2 transition flex flex-col items-center gap-1" :style="form.source===s.id ? 'border-color:var(--terracota);background:rgba(201,107,74,0.05)' : 'border-color:var(--border-default)'">
                                        <i :data-lucide="s.icon" class="w-4 h-4" style="color:var(--terracota)"></i>
                                        <span class="text-xs font-semibold" style="color:var(--sepia)" x-text="s.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div x-show="form.source==='indicacao'" x-cloak x-transition>
                            <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Quem indicou? *</label>
                            <input x-model="form.source_detail" :required="form.source==='indicacao'" class="admin-input" placeholder="Nome de quem indicou">
                            <?php if ($refPartner): ?>
                                <p class="text-xs mt-1" style="color:var(--maresia-dark)"><i data-lucide="check-circle-2" class="w-3 h-3 inline -mt-0.5"></i> Indicação registrada automaticamente via <b><?= e($refPartner['name']) ?></b>.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="admin-card p-6">
                    <h3 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Detalhes da viagem</h3>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Data</label><input type="date" x-model="form.travel_date" class="admin-input"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Adultos</label><input type="number" min="1" x-model.number="form.adults" class="admin-input"></div>
                        <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Crianças</label><input type="number" min="0" x-model.number="form.children" class="admin-input"></div>
                    </div>
                </div>

                <div class="admin-card p-6">
                    <h3 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Forma de pagamento</h3>
                    <div class="grid grid-cols-3 gap-3">
                        <template x-for="pm in paymentMethods">
                            <button type="button" @click="form.payment_method=pm.id" class="p-4 rounded-xl border-2 text-center transition" :style="form.payment_method===pm.id ? 'border-color:var(--terracota);background:rgba(201,107,74,0.05)' : 'border-color:var(--border-default)'">
                                <i :data-lucide="pm.icon" class="w-6 h-6 mx-auto mb-2" style="color:var(--terracota)"></i>
                                <div class="text-xs font-semibold" style="color:var(--sepia)" x-text="pm.label"></div>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="admin-card p-6">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" x-model="form.accept_terms" required class="mt-1 w-5 h-5 rounded" style="accent-color:var(--terracota)">
                        <span class="text-sm" style="color:var(--text-secondary)">
                            Li e concordo com a <b style="color:var(--sepia)">política de desistência</b>: em caso de cancelamento pelo passageiro, aplicam-se as condições previstas em contrato e o valor poderá não ser reembolsável conforme o prazo. *
                        </span>
                    </label>
                </div>

                <button type="submit" :disabled="loading || !canSubmit()" class="btn-primary w-full" :class="(loading||!canSubmit())&&'opacity-60'">
                    <i data-lucide="lock" class="w-5 h-5"></i>
                    <span x-text="loading?'Processando...':'Finalizar reserva'">Finalizar reserva</span>
                </button>
            </form>

            <!-- Summary -->
            <aside>
                <div class="admin-card p-6 lg:sticky lg:top-28">
                    <h3 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Resumo</h3>
                    <div class="flex gap-3 mb-4">
                        <?php if ($item['cover_image']): ?>
                            <img src="<?= storageUrl($item['cover_image']) ?>" class="w-20 h-20 rounded-lg object-cover">
                        <?php else: ?>
                            <div class="w-20 h-20 rounded-lg img-placeholder"><span class="text-xl"><?= e(mb_substr($item['title'],0,1)) ?></span></div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <div class="text-[10px] uppercase tracking-wider font-semibold" style="color:var(--terracota)"><?= e($type) ?></div>
                            <div class="text-sm font-semibold leading-snug" style="color:var(--sepia)"><?= e($item['title']) ?></div>
                        </div>
                    </div>
                    <div class="py-4 border-t border-b space-y-2" style="border-color:var(--border-default)">
                        <div class="flex justify-between text-sm"><span style="color:var(--text-secondary)">Subtotal</span><span class="font-semibold" style="color:var(--sepia)" x-text="'R$ ' + subtotal().toFixed(2).replace('.',',')"></span></div>
                        <div class="flex justify-between text-sm"><span style="color:var(--text-secondary)">Desconto PIX</span><span class="font-semibold" style="color:var(--maresia-dark)" x-text="form.payment_method==='pix' ? '- R$ ' + discount().toFixed(2).replace('.',','): 'R$ 0,00'"></span></div>
                    </div>
                    <div class="flex justify-between items-end pt-4">
                        <span class="text-sm" style="color:var(--text-secondary)">Total</span>
                        <span class="font-display text-3xl font-bold" style="color:var(--terracota)" x-text="'R$ ' + total().toFixed(2).replace('.',',')"></span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
function checkoutApp() {
    return {
        loading: false,
        form: {
            name:'', document:'', rg:'', birth_date:'',
            email:'', phone:'',
            has_comorbidity:'nao', comorbidity:'',
            source:'', source_detail:<?= json_encode($refPartner['name'] ?? '') ?>,
            accept_terms:false,
            ref_code:<?= json_encode($refCode ?? '') ?>,
            travel_date:'', adults:1, children:0,
            payment_method:'pix',
            entity_type: '<?= $type ?>',
            entity_id: <?= (int)$item['id'] ?>
        },
        sources: [
            {id:'instagram', label:'Instagram', icon:'instagram'},
            {id:'whatsapp', label:'WhatsApp', icon:'message-circle'},
            {id:'indicacao', label:'Indicação', icon:'user-check'},
            {id:'google', label:'Google', icon:'search'},
            {id:'outro', label:'Outro', icon:'more-horizontal'}
        ],
        paymentMethods: [
            {id:'pix', label:'PIX (desconto)', icon:'qr-code'},
            {id:'credit_card', label:'Cartão', icon:'credit-card'},
            {id:'boleto', label:'Boleto', icon:'file-text'}
        ],
        price: <?= (float)$item['price'] ?>,
        pricePix: <?= (float)($item['price_pix'] ?: $item['price']) ?>,
        subtotal() { return this.price * Math.max(1, this.form.adults) + this.price * 0.5 * Math.max(0, this.form.children); },
        discount() { return this.form.payment_method === 'pix' ? this.subtotal() - (this.pricePix * this.form.adults + this.pricePix * 0.5 * this.form.children) : 0; },
        total() { return this.subtotal() - this.discount(); },
        canSubmit() { return this.form.accept_terms && this.form.source && (this.form.has_comorbidity !== 'sim' || this.form.comorbidity.trim().length > 0); },
        async submit() {
            if (!this.canSubmit()) { showToast('Preencha todos os campos obrigatórios.', 'error'); return; }
            this.loading = true;
            const payload = { ...this.form, accept_terms: this.form.accept_terms ? '1' : '0' };
            const res = await caminhosApi('<?= url('/api/booking') ?>', { method:'POST', data: payload });
            showToast(res.msg, res.ok ? 'success' : 'error');
            if (res.ok && res.redirect) window.location = res.redirect;
            this.loading = false;
        }
    }
}
</script>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
