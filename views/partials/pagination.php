<?php /** @var array $pag from paginate() */ ?>
<?php if (!empty($pag)): ?>
<div class="pagination-bar">
    <div class="pagination-info">
        <span class="hidden sm:inline">Mostrando</span>
        <strong><?= min($pag['total'], $pag['offset'] + 1) ?></strong>
        <span>–</span>
        <strong><?= min($pag['total'], $pag['offset'] + count($pag['rows'])) ?></strong>
        <span>de</span>
        <strong><?= $pag['total'] ?></strong>
        <span class="hidden sm:inline">registros</span>
    </div>

    <form method="GET" class="pagination-per-form" onchange="this.submit()">
        <?php foreach ($_GET as $k => $v): if ($k === $pag['per_param'] || $k === $pag['page_param']) continue; if (is_array($v)) continue; ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
        <?php endforeach; ?>
        <label class="pagination-per-label">
            <span>Mostrar</span>
            <select name="<?= e($pag['per_param']) ?>" class="pagination-per-select">
                <?php foreach ($pag['allowed'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $pag['per']===$opt?'selected':'' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
            <span>por página</span>
        </label>
    </form>

    <nav class="pagination-nav">
        <?php
            $base = $pag['base_qs'] ? '?' . $pag['base_qs'] . '&' : '?';
            $linkFor = fn($p) => $base . $pag['page_param'] . '=' . $p . '&' . $pag['per_param'] . '=' . $pag['per'];
        ?>
        <a href="<?= $pag['page']>1 ? e($linkFor(1)) : '#' ?>" class="pagination-btn <?= $pag['page']<=1?'is-disabled':'' ?>" aria-label="Primeira página">
            <i data-lucide="chevrons-left" class="w-4 h-4"></i>
        </a>
        <a href="<?= $pag['page']>1 ? e($linkFor($pag['page']-1)) : '#' ?>" class="pagination-btn <?= $pag['page']<=1?'is-disabled':'' ?>" aria-label="Anterior">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </a>
        <?php
            $start = max(1, $pag['page'] - 2);
            $end   = min($pag['pages'], $pag['page'] + 2);
            if ($start > 1): ?>
                <a href="<?= e($linkFor(1)) ?>" class="pagination-btn">1</a>
                <?php if ($start > 2): ?><span class="pagination-dots">…</span><?php endif; ?>
        <?php endif; ?>
        <?php for ($p = $start; $p <= $end; $p++): ?>
            <a href="<?= e($linkFor($p)) ?>" class="pagination-btn <?= $p===$pag['page']?'is-active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($end < $pag['pages']): ?>
            <?php if ($end < $pag['pages']-1): ?><span class="pagination-dots">…</span><?php endif; ?>
            <a href="<?= e($linkFor($pag['pages'])) ?>" class="pagination-btn"><?= $pag['pages'] ?></a>
        <?php endif; ?>
        <a href="<?= $pag['page']<$pag['pages'] ? e($linkFor($pag['page']+1)) : '#' ?>" class="pagination-btn <?= $pag['page']>=$pag['pages']?'is-disabled':'' ?>" aria-label="Próxima">
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </a>
        <a href="<?= $pag['page']<$pag['pages'] ? e($linkFor($pag['pages'])) : '#' ?>" class="pagination-btn <?= $pag['page']>=$pag['pages']?'is-disabled':'' ?>" aria-label="Última página">
            <i data-lucide="chevrons-right" class="w-4 h-4"></i>
        </a>
    </nav>
</div>
<?php endif; ?>
