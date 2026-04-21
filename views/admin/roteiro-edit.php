<?php
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
$roteiro = $id ? dbOne("SELECT * FROM roteiros WHERE id=?", [$id]) : null;
$isNew = !$roteiro;
$pageTitle = $isNew ? 'Novo Passeio' : 'Editar Passeio';

$categories = dbAll("SELECT * FROM categories WHERE type='roteiro' AND active=1 ORDER BY sort_order");

$error = null;
if (isPost()) {
    if (!csrfVerify()) {
        $error = 'Token inválido.';
    } else {
        $data = [
            'category_id'    => (int) ($_POST['category_id'] ?? 0) ?: null,
            'title'          => trim($_POST['title'] ?? ''),
            'short_desc'     => trim($_POST['short_desc'] ?? ''),
            'description'    => trim($_POST['description'] ?? ''),
            'duration_hours' => (int) ($_POST['duration_hours'] ?? 0) ?: null,
            'min_people'     => (int) ($_POST['min_people'] ?? 1),
            'max_people'     => (int) ($_POST['max_people'] ?? 50),
            'price'          => parseBRL($_POST['price'] ?? '0'),
            'price_pix'      => parseBRL($_POST['price_pix'] ?? '0') ?: null,
            'location'       => trim($_POST['location'] ?? ''),
            'meeting_point'  => trim($_POST['meeting_point'] ?? ''),
            'status'         => $_POST['status'] ?? 'draft',
            'featured'       => isset($_POST['featured']) && $_POST['featured'] === '1' ? 1 : 0,
        ];
        $data['slug'] = $roteiro['slug'] ?? slugify($data['title']);

        // Cover upload
        if (!empty($_FILES['cover_image']['name'])) {
            $path = handleImageUpload($_FILES['cover_image'], 'roteiros');
            if ($path) $data['cover_image'] = $path;
        }

        if (!$data['title']) {
            $error = 'O título é obrigatório.';
        } else {
            if ($isNew) {
                $fields = array_keys($data);
                $placeholders = array_fill(0, count($fields), '?');
                $newId = dbInsert("INSERT INTO roteiros (".implode(',', array_map(fn($f)=>"`$f`", $fields)).") VALUES (".implode(',', $placeholders).")", array_values($data));
                flash('success', 'Passeio criado com sucesso.');
                redirect('/admin/roteiros/' . $newId);
            } else {
                $sets = [];
                foreach ($data as $k => $_) $sets[] = "`$k` = ?";
                $values = array_values($data);
                $values[] = $id;
                dbExec("UPDATE roteiros SET ".implode(',', $sets)." WHERE id = ?", $values);
                flash('success', 'Passeio atualizado com sucesso.');
                redirect('/admin/roteiros/' . $id);
            }
        }
    }
}

require VIEWS_DIR . '/partials/admin_head.php';
$msg = flash('success');
?>

<?php if ($msg): ?>
<div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)">
    <i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i>
    <span class="text-sm" style="color:var(--maresia-dark)"><?= e($msg) ?></span>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2)">
    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500"></i>
    <span class="text-sm text-red-700"><?= e($error) ?></span>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="space-y-6">
    <?= csrfField() ?>

    <div class="flex items-center justify-between">
        <a href="<?= url('/admin/roteiros') ?>" class="inline-flex items-center gap-1 text-sm hover:underline" style="color:var(--text-secondary)">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>Voltar
        </a>
        <div class="flex gap-2">
            <?php if (!$isNew): ?>
            <a href="<?= url('/roteiros/' . ($roteiro['slug'] ?? '')) ?>" target="_blank" class="admin-btn admin-btn-secondary">
                <i data-lucide="external-link" class="w-4 h-4"></i>Ver
            </a>
            <?php endif; ?>
            <button type="submit" class="admin-btn admin-btn-primary">
                <i data-lucide="save" class="w-4 h-4"></i><?= $isNew ? 'Criar' : 'Salvar' ?>
            </button>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main -->
        <div class="lg:col-span-2 space-y-6">
            <div class="admin-card p-6 space-y-5">
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Título *</label>
                    <input name="title" required value="<?= e($roteiro['title'] ?? '') ?>" class="admin-input" placeholder="Ex: Tour Histórico em Marechal">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Descrição curta</label>
                    <textarea name="short_desc" rows="2" class="admin-input" maxlength="500" placeholder="Uma frase que resume a experiência"><?= e($roteiro['short_desc'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Descrição completa</label>
                    <textarea name="description" rows="8" class="admin-input resize-y" placeholder="Descreva o passeio com detalhes..."><?= e($roteiro['description'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="admin-card p-6 space-y-5">
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Detalhes logísticos</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Local</label>
                        <input name="location" value="<?= e($roteiro['location'] ?? '') ?>" class="admin-input" placeholder="Ex: Maragogi, AL">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Duração (horas)</label>
                        <input type="number" name="duration_hours" value="<?= e($roteiro['duration_hours'] ?? '') ?>" class="admin-input" min="1">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Mín. pessoas</label>
                        <input type="number" name="min_people" value="<?= e($roteiro['min_people'] ?? 1) ?>" class="admin-input" min="1">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Máx. pessoas</label>
                        <input type="number" name="max_people" value="<?= e($roteiro['max_people'] ?? 50) ?>" class="admin-input" min="1">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Ponto de encontro</label>
                    <input name="meeting_point" value="<?= e($roteiro['meeting_point'] ?? '') ?>" class="admin-input" placeholder="Ex: Hotel do cliente em Maceió">
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status -->
            <div class="admin-card p-6 space-y-4">
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Publicação</h3>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Status</label>
                    <select name="status" class="admin-input">
                        <?php foreach (['draft'=>'Rascunho','published'=>'Publicado','archived'=>'Arquivado'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= ($roteiro['status'] ?? 'draft') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="featured" value="1" <?= !empty($roteiro['featured']) ? 'checked' : '' ?> class="w-4 h-4 rounded" style="accent-color:var(--terracota)">
                    <span class="text-sm font-semibold" style="color:var(--sepia)">Destacar na home</span>
                </label>
            </div>

            <!-- Categoria -->
            <div class="admin-card p-6">
                <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Categoria</label>
                <select name="category_id" class="admin-input">
                    <option value="">Sem categoria</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($roteiro['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Preço -->
            <div class="admin-card p-6 space-y-4">
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Preço</h3>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Preço cartão *</label>
                    <input name="price" required value="<?= $roteiro ? formatBRL($roteiro['price']) : '' ?>" class="admin-input brl-mask" placeholder="R$ 0,00">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Preço PIX <span class="text-xs font-normal" style="color:var(--text-muted)">(desconto)</span></label>
                    <input name="price_pix" value="<?= $roteiro && $roteiro['price_pix'] ? formatBRL($roteiro['price_pix']) : '' ?>" class="admin-input brl-mask" placeholder="R$ 0,00">
                </div>
            </div>

            <!-- Imagem -->
            <div class="admin-card p-6 space-y-3">
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Imagem de capa</h3>
                <?php if (!empty($roteiro['cover_image'])): ?>
                    <img src="<?= storageUrl($roteiro['cover_image']) ?>" class="w-full aspect-[4/3] object-cover rounded-lg">
                <?php endif; ?>
                <input type="file" name="cover_image" accept="image/*" class="admin-input text-xs">
                <p class="text-xs" style="color:var(--text-muted)">JPG, PNG ou WebP. Máx 5MB. Recomendado 1200x800px.</p>
            </div>
        </div>
    </div>
</form>

<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
