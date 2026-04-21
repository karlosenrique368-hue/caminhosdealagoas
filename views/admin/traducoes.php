<?php
$pageTitle = 'Traduções';

if (isPost() && csrfVerify()) {
    requireAdmin();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $lang = $_POST['lang'] ?? '';
        $key = trim($_POST['tkey'] ?? '');
        $val = trim($_POST['value'] ?? '');
        if ($lang && $key) {
            dbExec('INSERT INTO translations (lang,tkey,value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$lang,$key,$val]);
        }
    }
    if ($action === 'delete') dbExec('DELETE FROM translations WHERE id=?', [(int)$_POST['id']]);
    redirect('/admin/traducoes');
}

require VIEWS_DIR . '/partials/admin_head.php';
$filterLang = $_GET['lang'] ?? '';
$langs = ['pt-BR'=>'🇧🇷 Português','en'=>'🇺🇸 English','es'=>'🇪🇸 Español','fr'=>'🇫🇷 Français','de'=>'🇩🇪 Deutsch','it'=>'🇮🇹 Italiano','zh'=>'🇨🇳 中文'];
$rows = $filterLang
    ? dbAll('SELECT * FROM translations WHERE lang=? ORDER BY tkey', [$filterLang])
    : dbAll('SELECT * FROM translations ORDER BY lang, tkey');
?>

<div class="grid lg:grid-cols-[400px_1fr] gap-6">
    <div class="admin-card p-5">
        <h2 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Nova tradução</h2>
        <form method="POST" class="space-y-3">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save">
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Idioma</span>
                <select name="lang" required class="input-field w-full">
                    <?php foreach ($langs as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Chave</span><input type="text" name="tkey" required placeholder="ex: home.hero.title" class="input-field w-full"></label>
            <label class="block"><span class="text-xs font-semibold uppercase tracking-wider mb-1 block" style="color:var(--text-secondary)">Tradução</span><textarea name="value" rows="3" required class="input-field w-full"></textarea></label>
            <button class="btn-primary w-full justify-center">Salvar</button>
        </form>

        <div class="mt-6 pt-5 border-t" style="border-color:var(--border-default)">
            <p class="text-xs font-semibold uppercase tracking-wider mb-2" style="color:var(--text-muted)">Filtrar por idioma</p>
            <div class="flex flex-wrap gap-1">
                <a href="?" class="text-xs px-2 py-1 rounded-lg <?= !$filterLang?'font-bold':'' ?>" style="background:var(--areia-light)">Todos</a>
                <?php foreach ($langs as $k=>$v): ?>
                    <a href="?lang=<?= $k ?>" class="text-xs px-2 py-1 rounded-lg <?= $filterLang===$k?'font-bold':'' ?>" style="background:var(--areia-light)"><?= explode(' ', $v)[0] ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="admin-card overflow-hidden">
        <table class="w-full">
            <thead style="background:var(--areia-light)"><tr class="text-left text-xs font-bold uppercase tracking-wider" style="color:var(--text-secondary)"><th class="p-4">Idioma</th><th class="p-4">Chave</th><th class="p-4">Tradução</th><th class="p-4"></th></tr></thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="4" class="p-10 text-center" style="color:var(--text-muted)">Nenhuma tradução cadastrada.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr class="border-t" style="border-color:var(--border-default)">
                        <td class="p-4 text-xs font-bold"><?= e($r['lang']) ?></td>
                        <td class="p-4"><code class="text-xs px-2 py-0.5 rounded" style="background:var(--areia-light)"><?= e($r['tkey']) ?></code></td>
                        <td class="p-4 text-sm"><?= e($r['value']) ?></td>
                        <td class="p-4"><form method="POST" onsubmit="return confirm('Excluir?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn-secondary text-xs" style="color:#DC2626">×</button></form></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
