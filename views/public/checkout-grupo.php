<?php
/**
 * Checkout em grupo — fluxo para parceiros tipo instituicao (escolas, igrejas, empresas).
 * Formulario similar ao Google Forms, com lista de participantes (alunos/membros) e dados do responsavel.
 */
requireInstitution();
$i = currentInstitution();
$partner = dbOne('SELECT * FROM institutions WHERE id = ?', [$i['id']]);
if (!$partner || (int)$partner['allow_group_checkout'] !== 1) {
    flash('error', 'Seu perfil de parceiro não tem o modo grupo habilitado. Fale com o administrador.');
    redirect('/parceiro/dashboard');
}

$pageTitle = 'Reserva em grupo';
$solidNav = true;

$roteiroId = (int)($_GET['roteiro'] ?? 0);
$pacoteId  = (int)($_GET['pacote']  ?? 0);
$item = null; $type = null;
if ($roteiroId) { $item = dbOne("SELECT * FROM roteiros WHERE id=? AND status='published'", [$roteiroId]); $type='roteiro'; }
elseif ($pacoteId) { $item = dbOne("SELECT * FROM pacotes WHERE id=? AND status='published'", [$pacoteId]); $type='pacote'; }

if (!$item) {
    // mostra catalogo basico para escolher
    $roteiros = dbAll("SELECT id,title,price,price_pix,cover_image FROM roteiros WHERE status='published' ORDER BY featured DESC, title ASC LIMIT 24");
    $pacotes  = dbAll("SELECT id,title,price,price_pix,cover_image FROM pacotes  WHERE status='published' ORDER BY featured DESC, title ASC LIMIT 24");
    include VIEWS_DIR . '/partials/public_head.php';
    ?>
    <section class="pt-32 pb-16 min-h-screen" style="background:var(--bg-surface)">
        <div class="max-w-6xl mx-auto px-6">
            <div class="mb-8 text-center">
                <span class="inline-block text-[11px] font-bold uppercase tracking-widest px-3 py-1 rounded-full" style="background:var(--terracota);color:#fff">Reserva em grupo</span>
                <h1 class="font-display text-3xl sm:text-4xl font-bold mt-3" style="color:var(--sepia)">Escolha o passeio ou pacote para o grupo</h1>
                <p class="text-sm mt-2" style="color:var(--text-secondary)">Disponível para parceiros do tipo <b>instituição</b>. Preencha os alunos/participantes no próximo passo.</p>
            </div>
            <h2 class="font-display text-lg font-bold mb-3" style="color:var(--sepia)">Passeios</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
                <?php foreach ($roteiros as $r): ?>
                    <a href="<?= url('/checkout/grupo?roteiro='.$r['id']) ?>" class="admin-card p-3 hover:shadow-lg transition block">
                        <?php if ($r['cover_image']): ?><img src="<?= storageUrl($r['cover_image']) ?>" class="w-full aspect-[4/3] rounded-lg object-cover mb-3"><?php endif; ?>
                        <div class="font-semibold text-sm" style="color:var(--sepia)"><?= e($r['title']) ?></div>
                        <div class="text-xs mt-1" style="color:var(--terracota)"><?= formatBRL($r['price_pix'] ?: $r['price']) ?> PIX</div>
                    </a>
                <?php endforeach; ?>
            </div>
            <h2 class="font-display text-lg font-bold mb-3" style="color:var(--sepia)">Pacotes</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php foreach ($pacotes as $p): ?>
                    <a href="<?= url('/checkout/grupo?pacote='.$p['id']) ?>" class="admin-card p-3 hover:shadow-lg transition block">
                        <?php if ($p['cover_image']): ?><img src="<?= storageUrl($p['cover_image']) ?>" class="w-full aspect-[4/3] rounded-lg object-cover mb-3"><?php endif; ?>
                        <div class="font-semibold text-sm" style="color:var(--sepia)"><?= e($p['title']) ?></div>
                        <div class="text-xs mt-1" style="color:var(--terracota)"><?= formatBRL($p['price_pix'] ?: $p['price']) ?> PIX</div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    include VIEWS_DIR . '/partials/public_foot.php';
    return;
}

include VIEWS_DIR . '/partials/public_head.php';
?>

<style>
.stu-card{border:1.5px solid var(--border-default);border-radius:14px;padding:16px;background:var(--bg-card);transition:all .2s;position:relative}
.stu-card:hover{border-color:var(--horizonte)}
.stu-num{position:absolute;top:-10px;left:16px;background:var(--terracota);color:#fff;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:700}
.stu-remove{position:absolute;top:10px;right:10px;width:26px;height:26px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(239,68,68,.1);color:#EF4444;cursor:pointer;transition:all .15s}
.stu-remove:hover{background:#EF4444;color:#fff}
</style>

<section class="pt-28 pb-16 min-h-screen" style="background:linear-gradient(180deg,var(--bg-surface) 0%,var(--bg-page) 100%)">
<div class="max-w-6xl mx-auto px-4 sm:px-6" x-data="grupoCheckout()" x-init="init()">

    <a href="<?= url('/checkout/grupo') ?>" class="inline-flex items-center gap-1 text-sm mb-4" style="color:var(--horizonte)"><i data-lucide="arrow-left" class="w-4 h-4"></i> Trocar produto</a>

    <div class="mb-6">
        <span class="inline-block text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full" style="background:var(--horizonte);color:#fff">Reserva em grupo • <?= e($partner['name']) ?></span>
        <h1 class="font-display text-3xl sm:text-4xl font-bold mt-2" style="color:var(--sepia)"><?= e($item['title']) ?></h1>
        <p class="text-sm mt-1" style="color:var(--text-secondary)">Preencha os dados dos participantes e do responsável. A cobrança é única para o grupo.</p>
    </div>

    <form @submit.prevent="submit()" class="grid lg:grid-cols-[1fr_380px] gap-6">
        <div class="space-y-6">

            <!-- Participantes -->
            <div class="admin-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(58,107,138,.1);color:var(--horizonte)"><i data-lucide="users" class="w-5 h-5"></i></div>
                        <div><div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Passo 1</div><h2 class="font-display text-lg font-bold" style="color:var(--sepia)">Participantes (<span x-text="participants.length"></span>)</h2></div>
                    </div>
                    <button type="button" @click="addParticipant()" class="btn-primary text-sm"><i data-lucide="plus" class="w-4 h-4"></i>Adicionar</button>
                </div>

                <div class="space-y-4">
                    <template x-for="(p, idx) in participants" :key="idx">
                        <div class="stu-card">
                            <div class="stu-num" x-text="'Aluno #' + (idx+1)"></div>
                            <button type="button" x-show="participants.length > 1" @click="participants.splice(idx,1)" class="stu-remove"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
                            <div class="grid sm:grid-cols-2 gap-3 mt-2">
                                <div class="sm:col-span-2"><label class="block text-[11px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-secondary)">Nome completo *</label><input x-model="p.name" class="admin-input w-full" placeholder="Nome completo do aluno"></div>
                                <div><label class="block text-[11px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-secondary)">CPF *</label><input x-model="p.cpf" class="admin-input w-full" data-mask="cpf" placeholder="000.000.000-00"></div>
                                <div><label class="block text-[11px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-secondary)">Data de nascimento *</label><?php $dobModel='p.birth_date'; include __DIR__.'/../partials/dob_picker.php'; ?></div>
                                <div><label class="block text-[11px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-secondary)">Ano / Turma *</label><input x-model="p.class" class="admin-input w-full" placeholder="ex: 3º Ano B"></div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-secondary)">Alergia / comorbidade / necessidade especial *</label>
                                    <div class="flex gap-2">
                                        <button type="button" @click="p.has_special='nao'" class="flex-1 px-3 py-2 rounded-lg text-xs font-semibold border-2" :style="p.has_special==='nao' ? 'border-color:var(--maresia);background:rgba(122,157,110,.08);color:var(--maresia-dark)' : 'border-color:var(--border-default);color:var(--text-muted)'">Não</button>
                                        <button type="button" @click="p.has_special='sim'" class="flex-1 px-3 py-2 rounded-lg text-xs font-semibold border-2" :style="p.has_special==='sim' ? 'border-color:var(--terracota);background:rgba(201,107,74,.08);color:var(--terracota)' : 'border-color:var(--border-default);color:var(--text-muted)'">Sim</button>
                                    </div>
                                </div>
                                <div x-show="p.has_special==='sim'" x-transition class="sm:col-span-2"><label class="block text-[11px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-secondary)">Descreva *</label><textarea x-model="p.special_desc" rows="2" class="admin-input w-full" placeholder="Alergias, medicamentos, cuidados especiais..."></textarea></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Responsavel -->
            <div class="admin-card p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(201,107,74,.1);color:var(--terracota)"><i data-lucide="user-check" class="w-5 h-5"></i></div>
                    <div><div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Passo 2</div><h2 class="font-display text-lg font-bold" style="color:var(--sepia)">Responsável pelo grupo</h2></div>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2"><label class="block text-[11px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-secondary)">Nome completo *</label><input x-model="responsible.name" class="admin-input w-full"></div>
                    <div><label class="block text-[11px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-secondary)">CPF *</label><input x-model="responsible.cpf" class="admin-input w-full" data-mask="cpf" placeholder="000.000.000-00"></div>
                    <div><label class="block text-[11px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-secondary)">Telefone *</label><input x-model="responsible.phone" class="admin-input w-full" data-mask="phone" placeholder="(00) 00000-0000"></div>
                    <div class="sm:col-span-2"><label class="block text-[11px] font-bold uppercase tracking-wider mb-1" style="color:var(--text-secondary)">E-mail *</label><input type="email" x-model="responsible.email" class="admin-input w-full" placeholder="email@exemplo.com"></div>
                </div>
            </div>

            <!-- Pagamento -->
            <div class="admin-card p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(122,157,110,.1);color:var(--maresia-dark)"><i data-lucide="wallet" class="w-5 h-5"></i></div>
                    <div><div class="text-[10px] uppercase tracking-widest font-bold" style="color:var(--text-muted)">Passo 3</div><h2 class="font-display text-lg font-bold" style="color:var(--sepia)">Forma de pagamento</h2></div>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <label class="cursor-pointer p-4 rounded-xl border-2 transition" :style="payment==='pix' ? 'border-color:var(--terracota);background:rgba(201,107,74,.05)' : 'border-color:var(--border-default)'">
                        <input type="radio" x-model="payment" value="pix" class="sr-only">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="payment==='pix' ? 'background:var(--terracota);color:#fff' : 'background:var(--bg-surface);color:var(--terracota)'"><i data-lucide="qr-code" class="w-5 h-5"></i></div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm" style="color:var(--sepia)">PIX</div>
                                <div class="text-xs" style="color:var(--text-muted)"><?= formatBRL($item['price_pix'] ?: $item['price']) ?> por pessoa</div>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer p-4 rounded-xl border-2 transition" :style="payment==='credit_card' ? 'border-color:var(--terracota);background:rgba(201,107,74,.05)' : 'border-color:var(--border-default)'">
                        <input type="radio" x-model="payment" value="credit_card" class="sr-only">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="payment==='credit_card' ? 'background:var(--terracota);color:#fff' : 'background:var(--bg-surface);color:var(--terracota)'"><i data-lucide="credit-card" class="w-5 h-5"></i></div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm" style="color:var(--sepia)">Cartão (taxa da operadora)</div>
                                <div class="text-xs" style="color:var(--text-muted)"><?= formatBRL($item['price']) ?> por pessoa</div>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="mt-4 p-3 rounded-lg flex items-start gap-2" style="background:rgba(58,107,138,.05);border:1px solid rgba(58,107,138,.15)">
                    <i data-lucide="info" class="w-4 h-4 mt-0.5" style="color:var(--horizonte)"></i>
                    <p class="text-xs" style="color:var(--text-secondary)">
                        Após confirmar, enviaremos instruções de pagamento via WhatsApp para o responsável.
                        Chave PIX: <b style="color:var(--sepia)"><?= e(getSetting('pix_key', '28422778000108 (CNPJ)')) ?></b>
                    </p>
                </div>
            </div>

            <!-- Termos -->
            <div class="admin-card p-5">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" x-model="accept" class="mt-1 w-5 h-5" style="accent-color:var(--terracota)">
                    <span class="text-sm" style="color:var(--text-secondary)">Li e concordo com a <a href="<?= url('/politica-desistencia') ?>" target="_blank" class="font-bold underline" style="color:var(--sepia)">política de desistência</a> do grupo. *</span>
                </label>
            </div>
        </div>

        <!-- Resumo -->
        <aside>
            <div class="admin-card p-5 lg:sticky lg:top-24">
                <div class="flex gap-3 mb-4">
                    <?php if ($item['cover_image']): ?><img src="<?= storageUrl($item['cover_image']) ?>" class="w-20 h-20 rounded-xl object-cover"><?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="text-[10px] uppercase font-bold tracking-widest" style="color:var(--terracota)"><?= e($type) ?> • grupo</div>
                        <div class="font-semibold leading-snug" style="color:var(--sepia)"><?= e($item['title']) ?></div>
                    </div>
                </div>
                <div class="py-4 border-t border-b space-y-2" style="border-color:var(--border-default)">
                    <div class="flex justify-between text-sm"><span style="color:var(--text-secondary)" x-text="'Participantes × ' + participants.length"></span><span style="color:var(--sepia)" x-text="formatBRL(total())"></span></div>
                </div>
                <div class="flex justify-between items-end pt-4 mb-4">
                    <span class="text-sm font-semibold" style="color:var(--text-secondary)">Total do grupo</span>
                    <span class="font-display text-3xl font-bold" style="color:var(--terracota)" x-text="formatBRL(total())"></span>
                </div>
                <button type="submit" class="btn-primary w-full justify-center" :disabled="loading||!accept"><i data-lucide="lock" class="w-4 h-4"></i><span x-text="loading ? 'Enviando...' : 'Confirmar reserva do grupo'"></span></button>
                <div class="mt-3 text-[11px] text-center flex items-center justify-center gap-1" style="color:var(--text-muted)"><i data-lucide="shield-check" class="w-3 h-3"></i> Pagamento único • desconto de grupo aplicado</div>
            </div>
        </aside>
    </form>
</div>
</section>

<script>
function grupoCheckout() {
    return {
        participants: [ this.blankParticipant() ],
        responsible: { name:<?= json_encode($i['user_name'] ?? '') ?>, cpf:'', phone:<?= json_encode($partner['contact_phone'] ?? '') ?>, email:<?= json_encode($i['user_email'] ?? '') ?> },
        payment: 'pix',
        accept: false,
        loading: false,
        pricePix: <?= (float)($item['price_pix'] ?: $item['price']) ?>,
        priceCard: <?= (float)$item['price'] ?>,
        blankParticipant() { return { name:'', cpf:'', birth_date:'', class:'', has_special:'nao', special_desc:'' }; },
        addParticipant() { this.participants.push(this.blankParticipant()); this.$nextTick(()=>window.lucide && window.lucide.createIcons()); },
        init() { if (window.lucide) window.lucide.createIcons(); },
        unit() { return this.payment==='pix' ? this.pricePix : this.priceCard; },
        total() { return this.unit() * this.participants.length; },
        formatBRL(v){ return 'R$ ' + (v||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.'); },
        validate() {
            if (!this.participants.length) return 'Adicione pelo menos 1 participante.';
            for (const [i,p] of this.participants.entries()) {
                if (!p.name.trim()||!p.cpf.trim()||!p.birth_date||!p.class.trim()) return `Preencha todos os campos do aluno #${i+1}.`;
                if (p.has_special==='sim' && !p.special_desc.trim()) return `Descreva a comorbidade do aluno #${i+1}.`;
            }
            if (!this.responsible.name.trim()||!this.responsible.cpf.trim()||!this.responsible.phone.trim()||!this.responsible.email.includes('@')) return 'Complete os dados do responsável.';
            if (!this.accept) return 'Aceite a política de desistência.';
            return null;
        },
        async submit() {
            const err = this.validate();
            if (err) { showToast(err, 'error'); return; }
            this.loading = true;
            const payload = {
                booking_mode: 'grupo_instituicao',
                entity_type: '<?= $type ?>', entity_id: <?= (int)$item['id'] ?>,
                payment_method: this.payment,
                adults: this.participants.length, children: 0,
                participants: JSON.stringify(this.participants),
                name: this.responsible.name, email: this.responsible.email, phone: this.responsible.phone,
                cpf: this.responsible.cpf, accept_terms:'1',
                responsible_name: this.responsible.name, responsible_cpf: this.responsible.cpf, responsible_phone: this.responsible.phone,
                institution_partner_id: <?= (int)$partner['id'] ?>,
                ref_code: <?= json_encode($partner['referral_code']) ?>,
            };
            const res = await caminhosApi('<?= url('/api/booking') ?>', { method:'POST', data: payload });
            showToast(res.msg || (res.ok?'Reserva do grupo criada!':'Erro.'), res.ok?'success':'error');
            if (res.ok && res.redirect) window.location = res.redirect;
            this.loading = false;
        }
    };
}
</script>

<?php include VIEWS_DIR . '/partials/public_foot.php'; ?>
