<?php
/**
 * DOB picker — substitui <input type="date"> por trio de selects (dia / mês / ano).
 *
 * Uso:
 *   <?php
 *     $dobModel = 'form.birth_date';   // expressão Alpine que recebe YYYY-MM-DD
 *     include __DIR__ . '/dob_picker.php';
 *   ?>
 *
 * O usuário pode abrir uma lista de anos imediatamente, sem clicar "voltar"
 * dezenas de vezes (problema do native date picker). Anos: 1920 → ano atual − 5.
 */
$model = $dobModel ?? 'form.birth_date';
$id = 'dob_' . substr(md5($model . microtime(true)), 0, 6);
$yearMax = (int) date('Y') - 5;
$yearMin = 1920;
?>
<div class="dob-picker grid grid-cols-3 gap-2" x-data="{ d:'', m:'', y:'',
    syncFromValue() {
        const v = (<?= $model ?>) || '';
        const parts = String(v).split('-');
        if (parts.length === 3) { this.y = parts[0]; this.m = parts[1]; this.d = parts[2]; }
        else { this.y=''; this.m=''; this.d=''; }
    },
    update() {
        if (this.y && this.m && this.d) {
            <?= $model ?> = this.y + '-' + this.m + '-' + this.d;
        } else {
            <?= $model ?> = '';
        }
    }
}" x-init="syncFromValue()">
    <select x-model="d" @change="update()" class="admin-input w-full" aria-label="Dia">
        <option value="">Dia</option>
        <?php for ($i = 1; $i <= 31; $i++): $v = str_pad($i, 2, '0', STR_PAD_LEFT); ?>
            <option value="<?= $v ?>"><?= $v ?></option>
        <?php endfor; ?>
    </select>
    <select x-model="m" @change="update()" class="admin-input w-full" aria-label="Mês">
        <option value="">Mês</option>
        <?php
        $months = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        foreach ($months as $idx => $name):
            $v = str_pad($idx + 1, 2, '0', STR_PAD_LEFT);
        ?>
            <option value="<?= $v ?>"><?= $v ?> · <?= $name ?></option>
        <?php endforeach; ?>
    </select>
    <select x-model="y" @change="update()" class="admin-input w-full" aria-label="Ano">
        <option value="">Ano</option>
        <?php for ($i = $yearMax; $i >= $yearMin; $i--): ?>
            <option value="<?= $i ?>"><?= $i ?></option>
        <?php endfor; ?>
    </select>
</div>
