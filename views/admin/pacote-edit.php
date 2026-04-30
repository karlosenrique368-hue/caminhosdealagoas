<?php
requireAdmin();
$id = (int) ($_GET['id'] ?? 0);
$pacote = $id ? dbOne("SELECT * FROM pacotes WHERE id=?", [$id]) : null;
$isNew = !$pacote;
$pageTitle = $isNew ? 'Novo Pacote' : 'Editar Pacote';
$categories = dbAll("SELECT * FROM categories WHERE type='pacote' AND active=1 ORDER BY sort_order");

$error = null;
if (isPost()) {
    if (!csrfVerify()) {
        $error = 'Token inválido.';
    } else {
    $linesToJson = function($txt) {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)$txt)), fn($v) => $v !== ''));
        return $lines ? json_encode($lines, JSON_UNESCAPED_UNICODE) : null;
    };
    $itineraryToJson = function($titles, $descs) {
        $out = [];
        foreach ((array)$titles as $i => $t) {
            $t = trim((string)$t);
            $d = trim((string)((array)$descs)[$i] ?? '');
            if ($t === '' && $d === '') continue;
            $out[] = ['title' => $t, 'description' => $d];
        }
        return $out ? json_encode($out, JSON_UNESCAPED_UNICODE) : null;
    };
    $availModes = ['fixed','open','on_request'];
    $data = [
        'category_id'      => (int)($_POST['category_id'] ?? 0) ?: null,
        'title'            => trim($_POST['title'] ?? ''),
        'short_desc'       => trim($_POST['short_desc'] ?? ''),
        'description'      => trim($_POST['description'] ?? ''),
        'highlights'       => $linesToJson($_POST['highlights_text'] ?? ''),
        'itinerary'        => $itineraryToJson($_POST['itinerary_title'] ?? [], $_POST['itinerary_desc'] ?? []),
        'includes'         => $linesToJson($_POST['includes_text'] ?? ''),
        'excludes'         => $linesToJson($_POST['excludes_text'] ?? ''),
        'destination'      => trim($_POST['destination'] ?? ''),
        'latitude'         => ($_POST['latitude'] ?? '') !== '' && is_numeric($_POST['latitude']) ? (float)$_POST['latitude'] : null,
        'longitude'        => ($_POST['longitude'] ?? '') !== '' && is_numeric($_POST['longitude']) ? (float)$_POST['longitude'] : null,
        'duration_days'    => (int)($_POST['duration_days'] ?? 1),
        'duration_nights'  => (int)($_POST['duration_nights'] ?? 0),
        'price'            => parseBRL($_POST['price'] ?? '0'),
        'price_pix'        => parseBRL($_POST['price_pix'] ?? '0') ?: null,
        'commission_percent'=> $_POST['commission_percent'] !== '' && is_numeric($_POST['commission_percent'] ?? '') ? (float)$_POST['commission_percent'] : null,
        'bookings_threshold'=> (int)($_POST['bookings_threshold'] ?? 0) ?: null,
        'installments'     => (int)($_POST['installments'] ?? 1),
        'availability_mode'=> in_array($_POST['availability_mode'] ?? 'fixed', $availModes, true) ? $_POST['availability_mode'] : 'fixed',
        'status'           => $_POST['status'] ?? 'draft',
        'featured'         => isset($_POST['featured']) ? 1 : 0,
    ];
    $data['slug'] = $pacote['slug'] ?? slugify($data['title']);
    if (($_POST['remove_cover_image'] ?? '') === '1') {
        $data['cover_image'] = null;
    }
    $warnings = [];
    if (!empty($_FILES['cover_image']['name'])) {
        $path = handleImageUpload($_FILES['cover_image'], 'pacotes');
        if ($path) {
            $data['cover_image'] = $path;
        } else {
            $warnings[] = 'Imagem de capa não salva (erro ' . (int)($_FILES['cover_image']['error'] ?? 0) . '). Use JPG/PNG/WebP até 20MB.';
        }
    }

    // Galeria
    $existingGallery = [];
    if (!empty($pacote['gallery'])) { $d = json_decode($pacote['gallery'], true); if (is_array($d)) $existingGallery = $d; }
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
    $hasNewGalleryFiles = !empty($_FILES['gallery_new']['name'][0] ?? null);
    if ($hasNewGalleryFiles) {
        $newPaths = handleMultipleImageUpload($_FILES['gallery_new'], 'pacotes');
        $keptGallery = array_merge($keptGallery, $newPaths);
        if (!$newPaths) {
            $warnings[] = 'Fotos da galeria não salvas. Use JPG/PNG/WebP até 20MB cada.';
        }
    }
    $data['gallery'] = $keptGallery ? json_encode(array_values($keptGallery)) : null;

    if (!$error && !$data['title']) $error = 'O título é obrigatório.';
    else if (!$error) {
        $warnSuffix = !empty($warnings) ? ' Aviso: ' . implode(' | ', $warnings) : '';
        if ($isNew) {
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            $newId = dbInsert("INSERT INTO pacotes (".implode(',', array_map(fn($f)=>"`$f`", $fields)).") VALUES (".implode(',', $placeholders).")", array_values($data));
            flash('success', 'Pacote criado.' . $warnSuffix);
            redirect('/admin/pacotes/'.$newId);
        } else {
            $sets = []; foreach ($data as $k=>$_) $sets[] = "`$k`=?";
            $values = array_values($data); $values[] = $id;
            dbExec("UPDATE pacotes SET ".implode(',', $sets)." WHERE id=?", $values);
            flash('success', 'Pacote atualizado.' . $warnSuffix);
            redirect('/admin/pacotes/'.$id);
        }
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
                <div class="admin-map-picker" x-data="mapPicker({lat:'<?= e($pacote['latitude'] ?? '') ?>',lng:'<?= e($pacote['longitude'] ?? '') ?>'})" x-init="init()">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <label class="block text-sm font-semibold" style="color:var(--sepia)">Ponto no mapa</label>
                        <button type="button" @click="clear()" class="text-xs font-semibold" style="color:var(--terracota)">Limpar ponto</button>
                    </div>
                    <div x-ref="map" style="height:280px;border-radius:14px;overflow:hidden;border:1px solid var(--border-default)"></div>
                    <div class="grid md:grid-cols-2 gap-3 mt-3">
                        <input type="number" step="0.0000001" name="latitude" x-model="lat" @input.debounce.400ms="syncFromInputs()" class="admin-input" placeholder="Latitude">
                        <input type="number" step="0.0000001" name="longitude" x-model="lng" @input.debounce.400ms="syncFromInputs()" class="admin-input" placeholder="Longitude">
                    </div>
                    <p class="text-[11px] mt-2" style="color:var(--text-muted)">Clique no mapa para marcar o ponto vermelho que aparece na página pública.</p>
                </div>
            </div>

            <?php
                $hLines = '';
                if (!empty($pacote['highlights'])) { $d=json_decode($pacote['highlights'],true); if(is_array($d)) $hLines=implode("\n",$d); }
                $iLines = '';
                if (!empty($pacote['includes'])) { $d=json_decode($pacote['includes'],true); if(is_array($d)) $iLines=implode("\n",$d); }
                $eLines = '';
                if (!empty($pacote['excludes'])) { $d=json_decode($pacote['excludes'],true); if(is_array($d)) $eLines=implode("\n",$d); }
                $itArr = [];
                if (!empty($pacote['itinerary'])) { $d=json_decode($pacote['itinerary'],true); if(is_array($d)) $itArr=$d; }
            ?>
            <div class="admin-card p-6 space-y-5">
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Destaques, incluso e não incluso</h3>
                <p class="text-xs -mt-2" style="color:var(--text-muted)"><b>Um item por linha</b>.</p>
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">✨ Destaques</label>
                    <textarea name="highlights_text" rows="5" class="admin-input resize-y" placeholder="Ex: Hospedagem 4 estrelas com café&#10;Passeios privativos&#10;Traslado aeroporto incluso"><?= e($hLines) ?></textarea>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--maresia-dark)">✅ Incluso</label><textarea name="includes_text" rows="6" class="admin-input resize-y"><?= e($iLines) ?></textarea></div>
                    <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--terracota-dark)">❌ Não incluso</label><textarea name="excludes_text" rows="6" class="admin-input resize-y"><?= e($eLines) ?></textarea></div>
                </div>
            </div>

            <div class="admin-card p-6 space-y-4" x-data="itineraryBuilder(<?= htmlspecialchars(json_encode($itArr ?: [['title'=>'Dia 1','description'=>'']]), ENT_QUOTES) ?>)">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Itinerário dia a dia</h3>
                    <button type="button" @click="add()" class="admin-btn admin-btn-secondary"><i data-lucide="plus" class="w-4 h-4"></i>Adicionar dia</button>
                </div>
                <template x-for="(step, idx) in steps" :key="idx">
                    <div class="flex gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white flex-shrink-0" style="background:linear-gradient(135deg,var(--horizonte),var(--horizonte-light))" x-text="idx+1"></div>
                        <div class="flex-1 space-y-2">
                            <input type="text" name="itinerary_title[]" :value="step.title" @input="step.title=$event.target.value" class="admin-input" placeholder="Ex: Dia 1 — Chegada e boas-vindas">
                            <textarea name="itinerary_desc[]" rows="2" class="admin-input resize-y" :value="step.description" @input="step.description=$event.target.value" x-text="step.description" placeholder="O que acontece neste dia"></textarea>
                        </div>
                        <button type="button" @click="remove(idx)" class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="color:#B91C1C;background:rgba(239,68,68,0.08)"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </div>
                </template>
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
                <div>
                    <label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Disponibilidade</label>
                    <select name="availability_mode" class="admin-input">
                        <?php $curMode = $pacote['availability_mode'] ?? 'fixed'; foreach (['fixed'=>'Só datas cadastradas','open'=>'Todas as datas abertas','on_request'=>'Sob consulta'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= $curMode===$k?'selected':'' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!$isNew): ?>
                <a href="<?= url('/admin/departures?type=pacote&entity_id='.$id) ?>" class="admin-btn admin-btn-secondary w-full justify-center"><i data-lucide="calendar-plus" class="w-4 h-4"></i>Gerenciar datas</a>
                <?php endif; ?>
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
            <div class="admin-card p-6 space-y-4">
                <div>
                    <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Programa de parceria</h3>
                    <p class="text-[11px] mt-0.5" style="color:var(--text-muted)">Sobrescreve valores padrão do parceiro para este pacote.</p>
                </div>
                <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Comissão % <span class="text-xs font-normal" style="color:var(--text-muted)">(em branco = padrão)</span></label><input type="number" step="0.01" min="0" max="100" name="commission_percent" value="<?= $pacote && $pacote['commission_percent'] !== null ? e($pacote['commission_percent']) : '' ?>" class="admin-input" placeholder="ex: 10.00"></div>
                <div><label class="block text-sm font-semibold mb-1.5" style="color:var(--sepia)">Meta p/ gratuidade</label><input type="number" step="1" min="0" name="bookings_threshold" value="<?= $pacote && $pacote['bookings_threshold'] ? e($pacote['bookings_threshold']) : '' ?>" class="admin-input" placeholder="ex: 10"></div>
            </div>
            <div class="admin-card p-6 space-y-3">
                <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Imagem de capa</h3>
                <?php if (!empty($pacote['cover_image'])): ?><img src="<?= storageUrl($pacote['cover_image']) ?>" class="w-full aspect-[16/10] object-cover rounded-xl" style="border:1px solid var(--border-default)"><?php endif; ?>
                <?php if (!empty($pacote['cover_image'])): ?>
                <label class="flex items-center gap-2 text-sm font-semibold cursor-pointer" style="color:#B91C1C">
                    <input type="checkbox" name="remove_cover_image" value="1" class="w-4 h-4 rounded" style="accent-color:#DC2626">
                    Remover imagem de capa atual
                </label>
                <?php endif; ?>
                <label class="upload-zone block">
                    <input type="file" name="cover_image" accept="image/*">
                    <div class="upload-zone-icon"><i data-lucide="image-plus" class="w-6 h-6"></i></div>
                    <div class="upload-zone-title"><?= !empty($pacote['cover_image']) ? 'Trocar imagem de capa' : 'Arraste ou clique para enviar' ?></div>
                    <div class="upload-zone-hint">JPG, PNG ou WebP · Máx 5MB · Recomendado 1600×900px</div>
                </label>
            </div>

            <div class="admin-card p-6 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-lg font-bold" style="color:var(--sepia)">Galeria de imagens</h3>
                    <?php
                        $existingPG = [];
                        if (!empty($pacote['gallery'])) { $d = json_decode($pacote['gallery'], true); if (is_array($d)) $existingPG = $d; }
                    ?>
                    <span class="text-[11px] font-semibold" style="color:var(--text-muted)"><?= count($existingPG) ?> foto<?= count($existingPG)===1?'':'s' ?></span>
                </div>
                <?php if ($existingPG): ?>
                <div class="gallery-editor-grid">
                    <input type="hidden" name="gallery_keep_present" value="1">
                    <?php foreach ($existingPG as $img): ?>
                    <div class="gallery-editor-item" data-gallery-item>
                        <input type="hidden" name="gallery_keep[]" value="<?= e($img) ?>">
                        <img src="<?= storageUrl($img) ?>" alt="">
                        <button type="button" class="gallery-editor-remove" onclick="this.closest('[data-gallery-item]').remove()" title="Remover">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if (!$existingPG): ?><input type="hidden" name="gallery_keep_present" value="1"><?php endif; ?>
                <label class="upload-zone block">
                    <input type="file" name="gallery_new[]" accept="image/*" multiple>
                    <div class="upload-zone-icon"><i data-lucide="images" class="w-6 h-6"></i></div>
                    <div class="upload-zone-title">Adicionar mais fotos</div>
                    <div class="upload-zone-hint">Várias imagens · JPG, PNG ou WebP · Máx 5MB cada</div>
                </label>
            </div>
        </div>
    </div>
    <div class="flex items-center justify-end gap-3 sticky bottom-0 admin-card p-4 admin-sticky-actions">
        <a href="<?= url('/admin/pacotes') ?>" class="admin-btn">Cancelar</a>
        <button type="submit" class="admin-btn admin-btn-primary"><i data-lucide="save" class="w-4 h-4"></i><?= $isNew ? 'Criar Pacote' : 'Salvar Alterações' ?></button>
    </div>
</form>

<script>
function itineraryBuilder(initial) {
    return {
        steps: (Array.isArray(initial) && initial.length ? initial : [{title:'',description:''}]).map(s => ({
            title: s.title || s.name || '',
            description: s.description || s.desc || (typeof s === 'string' ? s : '')
        })),
        add() { this.steps.push({title:'',description:''}); this.$nextTick(()=>window.lucide && window.lucide.createIcons()); },
        remove(i) { this.steps.splice(i,1); if(!this.steps.length) this.add(); }
    }
}
function mapPicker(initial){
    return {
        lat: initial.lat || '', lng: initial.lng || '', map:null, marker:null,
        init(){ this.$nextTick(() => { if (typeof L === 'undefined') return; const hasPoint = this.lat && this.lng; const center = hasPoint ? [parseFloat(this.lat), parseFloat(this.lng)] : [-9.6658,-35.7353]; this.map = L.map(this.$refs.map,{scrollWheelZoom:false}).setView(center, hasPoint ? 15 : 11); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap',maxZoom:19}).addTo(this.map); this.map.on('click', e => this.setPoint(e.latlng.lat, e.latlng.lng)); if (hasPoint) this.setPoint(parseFloat(this.lat), parseFloat(this.lng), false); setTimeout(()=>this.map.invalidateSize(),250); }); },
        setPoint(lat,lng,move=true){ this.lat = Number(lat).toFixed(7); this.lng = Number(lng).toFixed(7); if (!this.marker) this.marker = L.marker([lat,lng]).addTo(this.map); else this.marker.setLatLng([lat,lng]); if (move) this.map.setView([lat,lng],15); },
        syncFromInputs(){ if (!this.map || !this.lat || !this.lng) return; const lat=parseFloat(this.lat), lng=parseFloat(this.lng); if (isFinite(lat) && isFinite(lng)) this.setPoint(lat,lng); },
        clear(){ this.lat=''; this.lng=''; if (this.marker) { this.map.removeLayer(this.marker); this.marker=null; } },
    }
}
</script>
<?php require VIEWS_DIR . '/partials/admin_foot.php'; ?>
