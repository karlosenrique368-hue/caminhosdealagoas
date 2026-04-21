<?php
$pageTitle = 'Depoimentos';

if (isPost() && csrfVerify()) {
    requireAdmin();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $existing = $id ? dbOne("SELECT * FROM testimonials WHERE id=?", [$id]) : null;
        $data = [
            'name'      => trim($_POST['name'] ?? ''),
            'location'  => trim($_POST['location'] ?? ''),
            'author_url'=> trim($_POST['author_url'] ?? '') ?: null,
            'rating'    => (int)($_POST['rating'] ?? 5),
            'content'   => trim($_POST['comment'] ?? ''),
            'active'    => ($_POST['status'] ?? 'approved') === 'approved' ? 1 : 0,
            'featured'  => isset($_POST['featured']) ? 1 : 0,
        ];
        // Avatar upload
        if (!empty($_FILES['avatar']['name'])) {
            $avPath = handleImageUpload($_FILES['avatar'], 'testimonials');
            if ($avPath) $data['avatar'] = $avPath;
        } elseif (!empty($_POST['avatar_keep']) && $existing) {
            // mantem existente — nao incluir no update
        } elseif (!empty($_POST['avatar_remove']) && $existing) {
            $data['avatar'] = null;
        }
        if ($id) {
            $sets = []; foreach ($data as $k=>$_) $sets[] = "`$k`=?";
            $values = array_values($data); $values[] = $id;
            dbExec("UPDATE testimonials SET ".implode(',',$sets)." WHERE id=?", $values);
        } else {
            $fields = array_keys($data);
            dbInsert("INSERT INTO testimonials (".implode(',', array_map(fn($f)=>"`$f`", $fields)).") VALUES (".implode(',', array_fill(0,count($fields),'?')).")", array_values($data));
        }
        flash('success', 'Depoimento salvo.');
    }
    if ($action === 'delete') {
        dbExec("DELETE FROM testimonials WHERE id=?", [(int)$_POST['id']]);
        flash('success', 'Depoimento excluído.');
    }
    if ($action === 'approve') {
        dbExec("UPDATE testimonials SET active=1 WHERE id=?", [(int)$_POST['id']]);
        flash('success', 'Aprovado.');
    }
    redirect('/admin/depoimentos');
}

require VIEWS_DIR . '/partials/admin_head.php';
$pag = paginate("SELECT COUNT(*) AS c FROM testimonials", "SELECT * FROM testimonials ORDER BY created_at DESC");
$testimonials = $pag['rows'];
$msg = flash('success');
?>

<?php if ($msg): ?><div class="mb-6 p-4 rounded-xl flex items-center gap-3" style="background:rgba(122,157,110,0.08);border:1px solid rgba(122,157,110,0.3)"><i data-lucide="check-circle" class="w-5 h-5" style="color:var(--maresia-dark)"></i><span class="text-sm" style="color:var(--maresia-dark)"><?= e($msg) ?></span></div><?php endif; ?>

<div x-data="{open:false, editing:null}">
    <div class="flex justify-between items-center mb-6">
        <p class="text-sm" style="color:var(--text-secondary)"><?= count($testimonials) ?> depoimentos</p>
        <button @click="editing=null; open=true" class="admin-btn admin-btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Adicionar</button>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <?php foreach ($testimonials as $t): ?>
        <div class="admin-card p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <?php if (!empty($t['avatar'])): ?>
                        <img src="<?= storageUrl($t['avatar']) ?>" alt="<?= e($t['name']) ?>" class="w-10 h-10 rounded-full object-cover" style="border:2px solid var(--border-default)">
                    <?php else: ?>
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm" style="background:linear-gradient(135deg,var(--maresia),var(--maresia-dark));color:white"><?= e(mb_strtoupper(mb_substr($t['name'],0,2))) ?></div>
                    <?php endif; ?>
                    <div>
                        <div class="font-semibold text-sm flex items-center gap-1.5">
                            <?= e($t['name']) ?>
                            <?php if (!empty($t['author_url'])): ?><a href="<?= e($t['author_url']) ?>" target="_blank" rel="noopener" title="Abrir link" style="color:var(--horizonte)"><i data-lucide="external-link" class="w-3.5 h-3.5"></i></a><?php endif; ?>
                        </div>
                        <?php if ($t['location']): ?><div class="text-xs" style="color:var(--text-muted)"><?= e($t['location']) ?></div><?php endif; ?>
                    </div>
                </div>
                <span class="badge badge-<?= $t['active']?'success':'muted' ?>"><?= $t['active']?'Ativo':'Inativo' ?></span>
            </div>
            <div class="flex gap-0.5 mb-2">
                <?php for ($i=1;$i<=5;$i++): ?><i data-lucide="star" class="w-3.5 h-3.5" style="color:<?= $i<=$t['rating']?'#F59E0B':'#E5E7EB' ?>;fill:<?= $i<=$t['rating']?'#F59E0B':'transparent' ?>"></i><?php endfor; ?>
                <?php if ($t['featured']): ?><span class="ml-2 text-[10px] font-bold uppercase tracking-wider" style="color:var(--terracota)">Destaque</span><?php endif; ?>
            </div>
            <p class="text-sm italic mb-4" style="color:var(--text-secondary)">"<?= e(truncate($t['content'], 200)) ?>"</p>
            <div class="flex gap-2">
                <?php if (!$t['active']): ?>
                <form method="post" class="inline"><?= csrfField() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="text-xs font-semibold hover:underline" style="color:var(--maresia-dark)">Ativar</button></form>
                <?php endif; ?>
                <button @click="editing=<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>; open=true" class="text-xs font-semibold hover:underline" style="color:var(--horizonte)">Editar</button>
                <form method="post" class="inline" onsubmit="return confirm('Excluir?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="text-xs font-semibold hover:underline text-red-500">Excluir</button></form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$testimonials): ?>
        <div class="md:col-span-2 admin-card p-12 text-center">
            <i data-lucide="message-square" class="w-16 h-16 mx-auto mb-4" style="color:var(--text-muted)"></i>
            <h3 class="font-semibold" style="color:var(--sepia)">Nenhum depoimento</h3>
        </div>
        <?php endif; ?>
    </div>

    <div x-show="open" x-cloak @keydown.escape.window="open=false" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,0.5)">
        <div @click.outside="open=false" class="w-full max-w-lg rounded-2xl p-6" style="background:var(--bg-card);max-height:90vh;overflow-y:auto">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-display text-xl font-bold" style="color:var(--sepia)" x-text="editing?'Editar depoimento':'Novo depoimento'"></h3>
                <button @click="open=false" style="color:var(--text-muted)"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="post" enctype="multipart/form-data" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" :value="editing?editing.id:''">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Nome *</label><input name="name" required :value="editing?editing.name:''" class="admin-input"></div>
                    <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Localização</label><input name="location" :value="editing?editing.location:''" class="admin-input" placeholder="Ex: São Paulo, SP"></div>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Link do autor <span class="text-xs font-normal" style="color:var(--text-muted)">(Instagram, perfil, site)</span></label>
                    <div class="form-input-group">
                        <i data-lucide="link" class="form-input-icon w-4 h-4"></i>
                        <input type="url" name="author_url" :value="editing?(editing.author_url||''):''" class="form-input" placeholder="https://instagram.com/usuario">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Avatar</label>
                    <template x-if="editing && editing.avatar">
                        <div class="flex items-center gap-3 mb-2">
                            <img :src="'<?= e(url('storage/')) ?>' + editing.avatar" class="w-14 h-14 rounded-full object-cover" style="border:2px solid var(--border-default)">
                            <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="avatar_remove" value="1"><span style="color:var(--text-secondary)">Remover avatar atual</span></label>
                            <input type="hidden" name="avatar_keep" value="1">
                        </div>
                    </template>
                    <label class="upload-zone block">
                        <input type="file" name="avatar" accept="image/*">
                        <div class="upload-zone-icon"><i data-lucide="user-circle" class="w-5 h-5"></i></div>
                        <div class="upload-zone-title text-sm">Trocar / enviar avatar</div>
                        <div class="upload-zone-hint">JPG, PNG ou WebP · 1:1 recomendado</div>
                    </label>
                </div>
                <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Nota</label>
                    <select name="rating" class="admin-input" :value="editing?editing.rating:5">
                        <option value="5">5 ⭐</option><option value="4">4 ⭐</option><option value="3">3 ⭐</option><option value="2">2 ⭐</option><option value="1">1 ⭐</option>
                    </select>
                </div>
                <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Comentário *</label><textarea name="comment" required rows="4" class="admin-input" x-text="editing?editing.content:''"></textarea></div>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Status</label>
                    <select name="status" class="admin-input" :value="editing?(editing.active==1?'approved':'hidden'):'approved'">
                        <option value="approved">Ativo</option><option value="hidden">Oculto</option>
                    </select>
                </div>
                <label class="flex items-center gap-2"><input type="checkbox" name="featured" value="1" :checked="editing?editing.featured==1:false" style="accent-color:var(--terracota)"><span class="text-sm font-semibold" style="color:var(--sepia)">Destacar na home</span></label>
                <div class="flex gap-2 pt-2"><button type="button" @click="open=false" class="admin-btn admin-btn-secondary flex-1">Cancelar</button><button type="submit" class="admin-btn admin-btn-primary flex-1">Salvar</button></div>
            </form>
        </div>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/pagination.php'; ?>
<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
