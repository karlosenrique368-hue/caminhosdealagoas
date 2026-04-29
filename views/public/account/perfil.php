<?php
$accountTitle = 'Perfil';
$accountTab = 'perfil';
include VIEWS_DIR . '/partials/account_layout.php';
$cust = currentCustomer();
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Dados pessoais -->
    <div class="glass-card p-6">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(201,107,74,0.1);color:var(--terracota)">
                <i data-lucide="user" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Dados pessoais</h2>
                <p class="text-xs" style="color:var(--text-muted)">Mantenha suas informações atualizadas</p>
            </div>
        </div>

        <form data-ajax action="<?= url('/api/profile') ?>" method="POST" class="space-y-4">
            <?= csrfField() ?>

            <div class="form-field">
                <label class="form-field-label">Nome completo</label>
                <div class="form-input-group">
                    <i data-lucide="user" class="form-input-icon w-4 h-4"></i>
                    <input type="text" name="name" value="<?= e($cust['name']) ?>" class="form-input" required>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="form-field">
                    <label class="form-field-label">Telefone</label>
                    <div class="form-input-group">
                        <i data-lucide="phone" class="form-input-icon w-4 h-4"></i>
                        <input type="tel" name="phone" value="<?= e($cust['phone']) ?>" class="form-input" placeholder="(82) 98800-0000">
                    </div>
                </div>
                <div class="form-field">
                    <label class="form-field-label">CPF</label>
                    <div class="form-input-group">
                        <i data-lucide="id-card" class="form-input-icon w-4 h-4"></i>
                        <input type="text" name="document" value="<?= e($cust['document']) ?>" class="form-input" placeholder="000.000.000-00">
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t" style="border-color:var(--border-default)">
                <div class="flex items-center gap-2 mb-3">
                    <i data-lucide="map-pin" class="w-4 h-4" style="color:var(--terracota)"></i>
                    <span class="text-xs font-bold uppercase tracking-[0.15em]" style="color:var(--text-secondary)">Endereço</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-[180px_1fr] gap-3">
                    <div class="form-field">
                        <label class="form-field-label">CEP</label>
                        <div class="form-input-group">
                            <i data-lucide="map" class="form-input-icon w-4 h-4"></i>
                            <input type="text" name="postal_code" id="cep-input" value="<?= e($cust['postal_code'] ?? '') ?>" class="form-input" placeholder="00000-000" maxlength="9" autocomplete="postal-code">
                        </div>
                        <p class="text-[11px] mt-1" style="color:var(--text-muted)" id="cep-hint">Digite para preencher automaticamente</p>
                    </div>
                    <div class="form-field">
                        <label class="form-field-label">Logradouro</label>
                        <div class="form-input-group">
                            <i data-lucide="home" class="form-input-icon w-4 h-4"></i>
                            <input type="text" name="address" id="address-input" value="<?= e($cust['address'] ?? '') ?>" class="form-input" placeholder="Rua, Avenida, Praça..." autocomplete="street-address">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-[120px_1fr_1fr] gap-3">
                    <div class="form-field">
                        <label class="form-field-label">Número</label>
                        <input type="text" name="address_number" value="<?= e($cust['address_number'] ?? '') ?>" class="form-input" placeholder="123">
                    </div>
                    <div class="form-field">
                        <label class="form-field-label">Bairro</label>
                        <input type="text" name="neighborhood" id="neighborhood-input" value="<?= e($cust['neighborhood'] ?? '') ?>" class="form-input" placeholder="Bairro">
                    </div>
                    <div class="form-field">
                        <label class="form-field-label">Complemento</label>
                        <input type="text" name="address_complement" value="<?= e($cust['address_complement'] ?? '') ?>" class="form-input" placeholder="Apto, bloco, referência (opcional)">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="form-field sm:col-span-1">
                        <label class="form-field-label">Cidade</label>
                        <input type="text" name="city" id="city-input" value="<?= e($cust['city']) ?>" class="form-input">
                    </div>
                    <div class="form-field">
                        <label class="form-field-label">UF</label>
                        <input type="text" name="state" id="state-input" value="<?= e($cust['state']) ?>" maxlength="2" class="form-input" style="text-transform:uppercase">
                    </div>
                    <div class="form-field">
                        <label class="form-field-label">País</label>
                        <input type="text" name="country" value="<?= e($cust['country'] ?: 'Brasil') ?>" class="form-input">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full justify-center">
                <span class="btn-content"><i data-lucide="save" class="w-4 h-4"></i> Salvar alterações</span>
            </button>
        </form>

        <script>
        (function(){
            const cep = document.getElementById('cep-input');
            if (!cep) return;
            const hint = document.getElementById('cep-hint');
            const fields = {
                logradouro: document.getElementById('address-input'),
                bairro: document.getElementById('neighborhood-input'),
                localidade: document.getElementById('city-input'),
                uf: document.getElementById('state-input'),
            };
            // Máscara CEP
            cep.addEventListener('input', e => {
                let v = e.target.value.replace(/\D/g, '').slice(0,8);
                if (v.length > 5) v = v.slice(0,5) + '-' + v.slice(5);
                e.target.value = v;
            });
            async function lookup() {
                const raw = cep.value.replace(/\D/g, '');
                if (raw.length !== 8) return;
                cep.classList.add('cep-loading');
                if (hint) hint.textContent = 'Buscando CEP...';
                try {
                    const res = await fetch('https://viacep.com.br/ws/' + raw + '/json/');
                    const j = await res.json();
                    if (j.erro) throw new Error('CEP não encontrado');
                    Object.keys(fields).forEach(k => { if (fields[k] && j[k]) fields[k].value = j[k]; });
                    cep.classList.remove('cep-error');
                    if (hint) hint.textContent = 'Endereço preenchido via ViaCEP';
                } catch (err) {
                    cep.classList.add('cep-error');
                    if (hint) hint.textContent = 'CEP inválido ou sem retorno';
                } finally {
                    cep.classList.remove('cep-loading');
                }
            }
            cep.addEventListener('blur', lookup);
            cep.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); lookup(); } });
        })();
        </script>
    </div>

    <!-- Senha -->
    <div class="glass-card p-6 h-fit">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(58,107,138,0.1);color:var(--horizonte)">
                <i data-lucide="shield" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="font-display text-xl font-bold" style="color:var(--sepia)">Alterar senha</h2>
                <p class="text-xs" style="color:var(--text-muted)">Use uma senha forte e única</p>
            </div>
        </div>

        <form data-ajax action="<?= url('/api/profile?action=password') ?>" method="POST" class="space-y-4">
            <?= csrfField() ?>
            <div class="form-field">
                <label class="form-field-label">Senha atual</label>
                <div class="form-input-group">
                    <i data-lucide="shield-check" class="form-input-icon w-4 h-4"></i>
                    <input type="password" name="current_password" required class="form-input" autocomplete="current-password">
                </div>
            </div>
            <div class="form-field">
                <label class="form-field-label">Nova senha</label>
                <div class="form-input-group" x-data="{show:false}">
                    <i data-lucide="lock" class="form-input-icon w-4 h-4"></i>
                    <input :type="show?'text':'password'" name="new_password" minlength="<?= PASSWORD_MIN_LENGTH ?>" required class="form-input pr-12" placeholder="Mínimo <?= PASSWORD_MIN_LENGTH ?> caracteres" autocomplete="new-password">
                    <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2" style="color:var(--text-muted)">
                        <i :data-lucide="show?'eye-off':'eye'" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-primary w-full justify-center">
                <span class="btn-content"><i data-lucide="key" class="w-4 h-4"></i> Atualizar senha</span>
            </button>
        </form>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/account_layout_end.php'; ?>
