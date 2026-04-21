<?php
requireAdmin();
$id = (int) ($_GET['id'] ?? 0);
$pacote = $id ? dbOne("SELECT * FROM pacotes WHERE id=?", [$id]) : null;
$isNew = !$pacote;
$pageTitle = $isNew ? 'Novo Pacote' : 'Editar Pacote';
$categories = dbAll("SELECT * FROM categories WHERE type='pacote' AND active=1 ORDER BY sort_order");

$error = null;
if (isPost() && csrfVerify()) {
    $data = [
        'category_id'    => (int)($_POST['category_id'] ?? 0) ?: null,
        'title'          => trim($_POST['title'] ?? ''),
        'short_desc'     => trim($_POST['short_desc'] ?? ''),
        'description'    => trim($_POST['description'] ?? ''),
        'destination'    => trim($_POST['destination'] ?? ''),
        'duration_days'  => (int)($_POST['duration_days'] ?? 1),
        'duration_nights'=> (int)($_POST['duration_nights'] ?? 0),
        'price'          => parseBRL($_POST['price'] ?? '0'),
        'price_pix'      => parseBRL($_POST['price_pix'] ?? '0') ?: null,
        'installments'   => (int)($_POST['installments'] ?? 1),
        'status'         => $_POST['status'] ?? 'draft',
        'featured'       => isset($_POST['featured']) ? 1 : 0,
    ];
    $data['slug'] = $pacote['slug'] ?? slugify($data['title']);
    if (!empty($_FILES['cover_image']['name'])) {
        $path = handleImageUpload($_FILES['cover_image'], 'pacotes');
        if ($path) $data['cover_image'] = $path;
    }
    if (!$data['title']) $error = 'O título é obrigatório.';
    else {
        if ($isNew) {
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            $newId = dbInsert("INSERT INTO pacotes (".implode(',', array_map(fn($f)=>"`$f`", $fields)).") VALUES (".implode(',', $placeholders).")", array_values($data));
            flash('success', 'Pacote criado.');
            redirect('/admin/pacotes/'.$newId);
        } else {
            $sets = []; foreach ($data as $k=>$_) $sets[] = "`$k`=?";
            $values = array_values($data); $values[] = $id;
            dbExec("UPDATE pacotes SET ".implode(',', $sets)." WHERE id=?", $values);
            flash('success', 'Pacote atualizado.');
            redirect('/admin/pacotes/'.$id);
        }
    }
}

require VIEWS_DIR . '/partials/admin_head.php';
$msg = flash('success');
?>

<?php if ($msg): ?><div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)"><i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i><span class="text-sm" style="color:var(--maresia-dark)"><?= e($msg) ?></span></div><?php endif; ?>
<?php if ($error): ?><div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2)"><i data-lucide="alert-circle" class="w-5 h-5 text-red-500"></i><span class="text-sm text-red-700"><?= e($error) ?></span></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="space-y-6">
    <?= csrfField() ?>
    <div class="flex items-center justify-between">
        <a href="<?= url('/admin/pacotes') ?>" class="inline-flex items-center gap-1 text-sm hover:underline" style="color:var(--text-secondary)"><i data-lucide="arrow-left" class="w-4 h-4"></i>Voltar</a>
        <button type="submit" class="admin-btn admin-btn-primary"><i data-lucide="save" class="w-4 h-4"></i><?= $isNew?'Criar':'Salvar' ?></button>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="admin-card p-6 space-y-5">
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Título *</label>
                    <input name="title" required value="<?= e($pacote['title'] ?? '') ?>" class="admin-input">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Descrição curta</label>
                    <textarea name="short_desc" rows="2" class="admin-input" maxlength="500"><?= e($pacote['short_desc'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Descrição completa</label>
                    <textarea name="description" rows="8" class="admin-input resize-y"><?= e($pacote['description'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="admin-card p-6 space-y-5">
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Detalhes</h3>
                <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Destino</label><input name="destination" value="<?= e($pacote['destination'] ?? '') ?>" class="admin-input" placeholder="Ex: Lençóis, Bahia"></div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Dias</label><input type="number" name="duration_days" min="1" value="<?= e($pacote['duration_days'] ?? 1) ?>" class="admin-input"></div>
                    <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Noites</label><input type="number" name="duration_nights" min="0" value="<?= e($pacote['duration_nights'] ?? 0) ?>" class="admin-input"></div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="admin-card p-6 space-y-4">
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Publicação</h3>
                <select name="status" class="admin-input">
                    <?php foreach (['draft'=>'Rascunho','published'=>'Publicado','archived'=>'Arquivado'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= ($pacote['status'] ?? 'draft')===$k?'selected':'' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="featured" value="1" <?= !empty($pacote['featured'])?'checked':'' ?> class="w-4 h-4 rounded" style="accent-color:var(--terracota)"><span class="text-sm font-semibold" style="color:var(--sepia)">Destacar na home</span></label>
            </div>
            <div class="admin-card p-6">
                <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Categoria</label>
                <select name="category_id" class="admin-input">
                    <option value="">Sem categoria</option>
                    <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= ($pacote['category_id']??0)==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="admin-card p-6 space-y-4">
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Preço</h3>
                <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Preço cartão *</label><input name="price" required value="<?= $pacote?formatBRL($pacote['price']):'' ?>" class="admin-input brl-mask" placeholder="R$ 0,00"></div>
                <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Preço PIX</label><input name="price_pix" value="<?= $pacote && $pacote['price_pix']?formatBRL($pacote['price_pix']):'' ?>" class="admin-input brl-mask"></div>
                <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Parcelas</label><input type="number" name="installments" min="1" max="12" value="<?= e($pacote['installments'] ?? 1) ?>" class="admin-input"></div>
            </div>
            <div class="admin-card p-6 space-y-3">
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Imagem de capa</h3>
                <?php if (!empty($pacote['cover_image'])): ?><img src="<?= storageUrl($pacote['cover_image']) ?>" class="w-full aspect-[16/10] object-cover rounded-xl" style="border:1px solid var(--border-default)"><?php endif; ?>
                <label class="upload-zone block">
                    <input type="file" name="cover_image" accept="image/*">
                    <div class="upload-zone-icon"><i data-lucide="image-plus" class="w-6 h-6"></i></div>
                    <div class="upload-zone-title"><?= !empty($pacote['cover_image']) ? 'Trocar imagem de capa' : 'Arraste ou clique para enviar' ?></div>
                    <div class="upload-zone-hint">JPG, PNG ou WebP · Máx 5MB · Recomendado 1600×900px</div>
                </label>
            </div>
        </div>
    </div>
</form>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
