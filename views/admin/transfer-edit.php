<?php
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
$row = $id ? dbOne("SELECT * FROM transfers WHERE id=?", [$id]) : null;
$isNew = !$row;
$pageTitle = $isNew ? 'Novo Transfer' : 'Editar Transfer';

$error = null;
if (isPost()) {
    if (!csrfVerify()) {
        $error = 'Token inválido.';
    } else {
        $linesToJson = function($txt) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)$txt)), fn($v) => $v !== ''));
            return $lines ? json_encode($lines, JSON_UNESCAPED_UNICODE) : null;
        };
        $data = [
            'title'             => trim($_POST['title'] ?? ''),
            'short_desc'        => trim($_POST['short_desc'] ?? ''),
            'description'       => trim($_POST['description'] ?? ''),
            'location_from'     => trim($_POST['location_from'] ?? ''),
            'location_to'       => trim($_POST['location_to'] ?? ''),
            'vehicle_type'      => trim($_POST['vehicle_type'] ?? ''),
            'capacity'          => (int) ($_POST['capacity'] ?? 4),
            'duration_minutes'  => (int) ($_POST['duration_minutes'] ?? 0) ?: null,
            'distance_km'       => (int) ($_POST['distance_km'] ?? 0) ?: null,
            'price'             => parseBRL($_POST['price'] ?? '0'),
            'price_pix'         => parseBRL($_POST['price_pix'] ?? '0') ?: null,
            'one_way'           => isset($_POST['one_way']) && $_POST['one_way'] === '1' ? 1 : 0,
            'includes'          => $linesToJson($_POST['includes_text'] ?? ''),
            'tags'              => trim($_POST['tags'] ?? '') ?: null,
            'meta_title'        => trim($_POST['meta_title'] ?? '') ?: null,
            'meta_desc'         => trim($_POST['meta_desc'] ?? '') ?: null,
            'status'            => in_array($_POST['status'] ?? 'draft', ['draft','published','archived'], true) ? $_POST['status'] : 'draft',
            'featured'          => isset($_POST['featured']) && $_POST['featured'] === '1' ? 1 : 0,
        ];
        $data['slug'] = $row['slug'] ?? slugify($data['title']);

        if (($_POST['remove_cover_image'] ?? '') === '1') {
            $data['cover_image'] = null;
        }

        if (!empty($_FILES['cover_image']['name'])) {
            $path = handleImageUpload($_FILES['cover_image'], 'transfers');
            if ($path) $data['cover_image'] = $path;
        }
        $existingGallery = [];
        if (!empty($row['gallery'])) {
            $dec = json_decode($row['gallery'], true);
            if (is_array($dec)) $existingGallery = $dec;
        }
        $galleryKeepPresent = isset($_POST['gallery_keep_present']) && $_POST['gallery_keep_present'] === '1';
        $keep = $_POST['gallery_keep'] ?? [];
        if (!is_array($keep)) $keep = [];
        // Se o marcador não veio no POST, preserva a galeria inteira para evitar limpeza acidental.
        if (!$galleryKeepPresent) $keep = $existingGallery;
        // Preserva ordem e quantidade (array_intersect por valor nao funciona com duplicadas)
        $keepCounts = [];
        foreach ($keep as $k) $keepCounts[$k] = ($keepCounts[$k] ?? 0) + 1;
        $keptGallery = [];
        foreach ($existingGallery as $img) {
            if (($keepCounts[$img] ?? 0) > 0) {
                $keptGallery[] = $img;
                $keepCounts[$img]--;
            }
        }
        if (!empty($_FILES['gallery_new']['name'][0] ?? null)) {
            $newPaths = handleMultipleImageUpload($_FILES['gallery_new'], 'transfers');
            $keptGallery = array_merge($keptGallery, $newPaths);
        }
        $data['gallery'] = $keptGallery ? json_encode(array_values($keptGallery)) : null;

        if (!$data['title']) {
            $error = 'O título é obrigatório.';
        } else {
            if ($isNew) {
                $fields = array_keys($data);
                $placeholders = array_fill(0, count($fields), '?');
                $newId = dbInsert("INSERT INTO transfers (".implode(',', array_map(fn($f)=>"`$f`", $fields)).") VALUES (".implode(',', $placeholders).")", array_values($data));
                flash('success', 'Transfer criado com sucesso.');
                redirect('/admin/transfers/' . $newId);
            } else {
                $sets = [];
                foreach ($data as $k => $_) $sets[] = "`$k` = ?";
                $values = array_values($data);
                $values[] = $id;
                dbExec("UPDATE transfers SET ".implode(',', $sets)." WHERE id = ?", $values);
                flash('success', 'Transfer atualizado com sucesso.');
                redirect('/admin/transfers/' . $id);
            }
        }
    }
}

require VIEWS_DIR . '/partials/admin_head.php';
$msg = flash('success');

$includesArr = !empty($row['includes']) ? (json_decode($row['includes'], true) ?: []) : [];
$gallery = !empty($row['gallery']) ? (json_decode($row['gallery'], true) ?: []) : [];
?>

<?php if ($msg): ?>
<div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)">
    <i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i>
    <span class="text-sm" style="color:var(--maresia-dark)"><?= e($msg) ?></span>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.3)">
    <i data-lucide="alert-circle" class="w-5 h-5" style="color:#DC2626"></i>
    <span class="text-sm" style="color:#DC2626"><?= e($error) ?></span>
</div>
<?php endif; ?>

<div class="flex items-center justify-between mb-6">
    <a href="<?= url('/admin/transfers') ?>" class="text-sm font-semibold inline-flex items-center gap-1" style="color:var(--horizonte)"><i data-lucide="arrow-left" class="w-4 h-4"></i>Voltar</a>
    <?php if (!$isNew): ?>
    <a href="<?= url('/transfers/' . $row['slug']) ?>" target="_blank" class="text-sm font-semibold inline-flex items-center gap-1" style="color:var(--terracota)"><i data-lucide="external-link" class="w-4 h-4"></i>Ver no site</a>
    <?php endif; ?>
</div>

<form method="post" enctype="multipart/form-data" class="space-y-6">
    <?= csrfField() ?>

    <div class="admin-card p-6">
        <h3 class="font-display font-bold text-lg mb-4" style="color:var(--sepia)">Informações principais</h3>
        <div class="grid md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="admin-label">Título <span style="color:#DC2626">*</span></label>
                <input type="text" name="title" value="<?= e($row['title'] ?? '') ?>" required class="admin-input" placeholder="Ex.: Aeroporto MCZ → Maragogi">
            </div>
            <div>
                <label class="admin-label">Origem</label>
                <input type="text" name="location_from" value="<?= e($row['location_from'] ?? '') ?>" class="admin-input" placeholder="Aeroporto Zumbi dos Palmares">
            </div>
            <div>
                <label class="admin-label">Destino</label>
                <input type="text" name="location_to" value="<?= e($row['location_to'] ?? '') ?>" class="admin-input" placeholder="Maragogi - AL">
            </div>
            <div>
                <label class="admin-label">Tipo de veículo</label>
                <input type="text" name="vehicle_type" value="<?= e($row['vehicle_type'] ?? '') ?>" class="admin-input" placeholder="SUV, Van executiva...">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="admin-label">Capacidade</label><input type="number" name="capacity" value="<?= (int)($row['capacity'] ?? 4) ?>" min="1" class="admin-input"></div>
                <div><label class="admin-label">Duração (min)</label><input type="number" name="duration_minutes" value="<?= (int)($row['duration_minutes'] ?? 0) ?>" class="admin-input"></div>
                <div><label class="admin-label">Distância (km)</label><input type="number" name="distance_km" value="<?= (int)($row['distance_km'] ?? 0) ?>" class="admin-input"></div>
            </div>
            <div class="md:col-span-2">
                <label class="admin-label">Resumo (até 500 caracteres)</label>
                <textarea name="short_desc" rows="2" maxlength="500" class="admin-input"><?= e($row['short_desc'] ?? '') ?></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="admin-label">Descrição completa</label>
                <textarea name="description" rows="6" class="admin-input"><?= e($row['description'] ?? '') ?></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="admin-label">O que está incluído (uma linha cada)</label>
                <textarea name="includes_text" rows="4" class="admin-input" placeholder="Motorista bilíngue&#10;Água gelada&#10;Wi-Fi a bordo"><?= e(implode("\n", $includesArr)) ?></textarea>
            </div>
        </div>
    </div>

    <div class="admin-card p-6">
        <h3 class="font-display font-bold text-lg mb-4" style="color:var(--sepia)">Preço & opções</h3>
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="admin-label">Preço cartão</label>
                <input type="text" name="price" value="<?= e(formatBRL($row['price'] ?? 0)) ?>" class="admin-input brl-mask">
            </div>
            <div>
                <label class="admin-label">Preço PIX</label>
                <input type="text" name="price_pix" value="<?= !empty($row['price_pix']) ? e(formatBRL($row['price_pix'])) : '' ?>" class="admin-input brl-mask">
            </div>
            <div>
                <label class="admin-label inline-flex items-center gap-2 mt-7">
                    <input type="checkbox" name="one_way" value="1" <?= !empty($row['one_way']) ? 'checked' : '' ?>>
                    Apenas ida (sem retorno)
                </label>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div class="admin-card p-6 space-y-3">
            <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Imagem de capa</h3>
            <?php if (!empty($row['cover_image'])): ?>
                <img src="<?= storageUrl($row['cover_image']) ?>" class="w-full aspect-[16/10] object-cover rounded-xl" style="border:1px solid var(--border-default)">
                <label class="flex items-center gap-2 text-sm font-semibold cursor-pointer" style="color:#B91C1C">
                    <input type="checkbox" name="remove_cover_image" value="1" class="w-4 h-4 rounded" style="accent-color:#DC2626">
                    Remover imagem de capa atual
                </label>
            <?php endif; ?>
            <label class="upload-zone block">
                <input type="file" name="cover_image" accept="image/*">
                <div class="upload-zone-icon"><i data-lucide="image-plus" class="w-6 h-6"></i></div>
                <div class="upload-zone-title"><?= !empty($row['cover_image']) ? 'Trocar imagem de capa' : 'Arraste ou clique para enviar' ?></div>
                <div class="upload-zone-hint">JPG, PNG ou WebP · Máx 5MB · Recomendado 1600×900px</div>
            </label>
        </div>

        <div class="admin-card p-6 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Galeria de imagens</h3>
                <span class="text-[11px] font-semibold" style="color:var(--text-muted)"><?= count($gallery) ?> foto<?= count($gallery)===1?'':'s' ?></span>
            </div>
            <?php if ($gallery): ?>
            <div class="gallery-editor-grid">
                <input type="hidden" name="gallery_keep_present" value="1">
                <?php foreach ($gallery as $g): ?>
                <div class="gallery-editor-item" data-gallery-item>
                    <input type="hidden" name="gallery_keep[]" value="<?= e($g) ?>">
                    <img src="<?= storageUrl($g) ?>" alt="">
                    <button type="button" class="gallery-editor-remove" onclick="this.closest('[data-gallery-item]').remove()" title="Remover">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!$gallery): ?><input type="hidden" name="gallery_keep_present" value="1"><?php endif; ?>
            <label class="upload-zone block">
                <input type="file" name="gallery_new[]" accept="image/*" multiple>
                <div class="upload-zone-icon"><i data-lucide="images" class="w-6 h-6"></i></div>
                <div class="upload-zone-title">Adicionar mais fotos</div>
                <div class="upload-zone-hint">Selecione ou arraste várias imagens · JPG, PNG ou WebP · Máx 5MB cada</div>
            </label>
        </div>
    </div>

    <div class="admin-card p-6">
        <h3 class="font-display font-bold text-lg mb-4" style="color:var(--sepia)">SEO & publicação</h3>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="admin-label">Meta title</label>
                <input type="text" name="meta_title" value="<?= e($row['meta_title'] ?? '') ?>" class="admin-input">
            </div>
            <div>
                <label class="admin-label">Tags (separadas por vírgula)</label>
                <input type="text" name="tags" value="<?= e($row['tags'] ?? '') ?>" class="admin-input">
            </div>
            <div class="md:col-span-2">
                <label class="admin-label">Meta description</label>
                <textarea name="meta_desc" rows="2" class="admin-input"><?= e($row['meta_desc'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="admin-label">Status</label>
                <select name="status" class="admin-input">
                    <?php foreach (['draft'=>'Rascunho','published'=>'Publicado','archived'=>'Arquivado'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= ($row['status'] ?? 'draft') === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="admin-label inline-flex items-center gap-2 mt-7">
                    <input type="checkbox" name="featured" value="1" <?= !empty($row['featured']) ? 'checked' : '' ?>>
                    Destacar este transfer
                </label>
            </div>
            <?php if (!$isNew): ?>
            <div class="md:col-span-2">
                <a href="<?= url('/admin/departures?type=transfer&entity_id=' . $id) ?>" class="admin-btn admin-btn-secondary w-full justify-center"><i data-lucide="calendar-plus" class="w-4 h-4"></i>Gerenciar datas deste transfer</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 sticky bottom-0 admin-card p-4">
        <a href="<?= url('/admin/transfers') ?>" class="admin-btn">Cancelar</a>
        <button type="submit" class="admin-btn admin-btn-primary"><i data-lucide="save" class="w-4 h-4"></i><?= $isNew ? 'Criar Transfer' : 'Salvar Alterações' ?></button>
    </div>
</form>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
