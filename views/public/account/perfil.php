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

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="form-field sm:col-span-1">
                    <label class="form-field-label">Cidade</label>
                    <input type="text" name="city" value="<?= e($cust['city']) ?>" class="form-input">
                </div>
                <div class="form-field">
                    <label class="form-field-label">UF</label>
                    <input type="text" name="state" value="<?= e($cust['state']) ?>" maxlength="2" class="form-input" style="text-transform:uppercase">
                </div>
                <div class="form-field">
                    <label class="form-field-label">País</label>
                    <input type="text" name="country" value="<?= e($cust['country']) ?>" class="form-input">
                </div>
            </div>

            <button type="submit" class="btn-primary w-full justify-center">
                <span class="btn-content"><i data-lucide="save" class="w-4 h-4"></i> Salvar alterações</span>
            </button>
        </form>
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
                <label class="form-field-label">Nova senha</label>
                <div class="form-input-group" x-data="{show:false}">
                    <i data-lucide="lock" class="form-input-icon w-4 h-4"></i>
                    <input :type="show?'text':'password'" name="new_password" minlength="6" required class="form-input pr-12" placeholder="Mínimo 6 caracteres">
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
