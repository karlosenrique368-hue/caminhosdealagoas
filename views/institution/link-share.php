<?php
requireInstitution();
$i = currentInstitution();
$isMacaiok = institutionPortalProgram($i) === 'macaiok';
$pageTitle = $isMacaiok ? 'Links para responsáveis' : 'Meu link de indicação';

$partner = dbOne('SELECT * FROM institutions WHERE id=?', [$i['id']]);
$code = $partner['referral_code'] ?? '';
$escolaSlug = (string)($partner['slug'] ?? '');
$shareHome = referralShareUrl($code, $isMacaiok ? '/macaiok' : '/');
$sharePassis = referralShareUrl($code, $isMacaiok ? '/macaiok' : '/passeios');
$sharePacotes = referralShareUrl($code, $isMacaiok ? '/macaiok' : '/pacotes');

// Passeios populares para gerar link direto (Macaiok mostra só os curados)
$roteiros = dbAll("SELECT id, title, slug, cover_image, price, price_pix FROM roteiros WHERE status='published'" . ($isMacaiok ? " AND macaiok_featured=1" : "") . " ORDER BY featured DESC, views DESC LIMIT 6");

include VIEWS_DIR . '/partials/institution_head.php';
?>

<div class="mb-6">
    <p class="text-sm sm:text-base max-w-2xl" style="color:var(--text-secondary)"><?= $isMacaiok ? 'Envie o link certo aos responsáveis. Cada pagamento entra ligado à escola, permitindo acompanhar quem pagou e quem ainda está pendente.' : 'Compartilhe qualquer um destes links. Quando alguém comprar, a gente registra automaticamente que foi via você.' ?></p>
</div>

<!-- Links gerais -->
<div class="admin-card p-5 sm:p-6 mb-6">
    <h2 class="font-display text-lg font-bold mb-4 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="globe" class="w-5 h-5" style="color:var(--horizonte)"></i> Links gerais</h2>
    <div class="space-y-3" x-data="{copied: null}">
        <?php foreach ([
            [$isMacaiok ? 'Portal Macaiok' : 'Site inteiro', $shareHome, $isMacaiok ? 'Apresentação institucional para coordenação e famílias' : 'Página inicial — funciona para tudo'],
            [$isMacaiok ? 'Vivências de 1 dia' : 'Todos os passeios', $sharePassis, $isMacaiok ? 'Use quando a escola ainda vai escolher a vivência' : 'Catálogo de passeios'],
            [$isMacaiok ? 'Programas completos' : 'Todos os pacotes', $sharePacotes, $isMacaiok ? 'Pacotes pedagógicos com mais de uma etapa' : 'Catálogo de pacotes'],
        ] as $idx => $l): ?>
        <div class="p-4 rounded-xl flex items-center gap-3 flex-wrap sm:flex-nowrap" style="background:var(--bg-surface);border:1px solid var(--border-default)">
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-sm mb-1" style="color:var(--sepia)"><?= e($l[0]) ?></div>
                <div class="text-[11px] mb-1" style="color:var(--text-muted)"><?= e($l[2]) ?></div>
                <div class="font-mono text-[11px] break-all" style="color:var(--horizonte)"><?= e($l[1]) ?></div>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <button type="button" @click="navigator.clipboard.writeText('<?= e(addslashes($l[1])) ?>'); copied=<?= $idx ?>; setTimeout(()=>copied=null,2000)" class="admin-btn admin-btn-primary flex-1 sm:flex-initial justify-center">
                    <i data-lucide="copy" class="w-4 h-4" x-show="copied!==<?= $idx ?>"></i>
                    <i data-lucide="check" class="w-4 h-4" x-show="copied===<?= $idx ?>" x-cloak></i>
                    <span x-text="copied===<?= $idx ?> ? 'Copiado!' : 'Copiar'"></span>
                </button>
                <a href="https://wa.me/?text=<?= urlencode(($isMacaiok ? 'Olá! Segue o link da vivência pedagógica para pagamento e dados do responsável: ' : 'Vem viver Alagoas com a gente! ') . $l[1]) ?>" target="_blank" class="admin-btn admin-btn-secondary flex-1 sm:flex-initial justify-center">
                    <i data-lucide="send" class="w-4 h-4"></i> Enviar
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Links diretos para passeios -->
<?php if ($roteiros): ?>
<div class="admin-card p-5 sm:p-6 mb-6">
    <h2 class="font-display text-lg font-bold mb-4 flex items-center gap-2" style="color:var(--sepia)"><i data-lucide="sparkles" class="w-5 h-5" style="color:var(--terracota)"></i> <?= $isMacaiok ? 'Links de pagamento por vivência' : 'Links de passeios populares' ?></h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4" x-data="{c:null}">
        <?php foreach ($roteiros as $idx => $r):
            $url = referralShareUrl($code, $isMacaiok ? ('/macaiok/responsaveis?escola=' . urlencode($escolaSlug) . '&vivencia=' . $r['id'] . '&tipo=roteiro') : '/passeios/'.$r['slug']);
        ?>
        <div class="rounded-xl overflow-hidden" style="background:var(--bg-surface);border:1px solid var(--border-default)">
            <div class="aspect-video bg-center bg-cover" style="background-image:url('<?= $r['cover_image']?e(storageUrl($r['cover_image'])):'' ?>')"></div>
            <div class="p-4">
                <h3 class="font-display font-bold text-sm mb-2 line-clamp-2" style="color:var(--sepia)"><?= e($r['title']) ?></h3>
                <div class="flex gap-1.5">
                    <button type="button" @click="navigator.clipboard.writeText('<?= e(addslashes($url)) ?>'); c=<?= $idx ?>; setTimeout(()=>c=null,2000)" class="admin-btn admin-btn-secondary flex-1 justify-center text-xs">
                        <i data-lucide="copy" class="w-3.5 h-3.5" x-show="c!==<?= $idx ?>"></i>
                        <i data-lucide="check" class="w-3.5 h-3.5" x-show="c===<?= $idx ?>" x-cloak></i>
                        <span x-text="c===<?= $idx ?>?'Copiado':'Copiar'"></span>
                    </button>
                    <a href="https://wa.me/?text=<?= urlencode(($isMacaiok ? 'Pagamento da vivência ' : '') . $r['title'] . ' - ' . $url) ?>" target="_blank" class="admin-btn admin-btn-secondary justify-center text-xs" style="padding-left:10px;padding-right:10px"><i data-lucide="send" class="w-3.5 h-3.5"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Dicas -->
<div class="admin-card p-5 sm:p-6">
    <h2 class="font-display text-lg font-bold mb-4" style="color:var(--sepia)">Dicas pra indicar mais</h2>
    <div class="grid sm:grid-cols-2 gap-3">
        <?php foreach ([
            ['message-circle',$isMacaiok ? 'Grupo da turma' : 'Grupos de WhatsApp',$isMacaiok ? 'Cole o link no grupo oficial dos responsáveis da turma.' : 'Cole o link nos grupos de família, trabalho, clube, igreja.'],
            ['clipboard-check',$isMacaiok ? 'Controle da secretaria' : 'Stories & bio',$isMacaiok ? 'Use a página de pagamentos para acompanhar pendentes e confirmados.' : 'Adicione o link na bio do seu Instagram/TikTok.'],
            ['image',$isMacaiok ? 'Material de apoio' : 'Prints dos passeios',$isMacaiok ? 'Envie o roteiro pedagógico junto com o link de pagamento.' : 'Salve fotos da nossa página e compartilhe com seu link junto.'],
            ['heart',$isMacaiok ? 'Responsáveis sem atrito' : 'Indicação pessoal',$isMacaiok ? 'O pai preenche os dados e paga direto, a escola acompanha no painel.' : 'Uma mensagem direta pra quem você sabe que vai amar.'],
        ] as $d): ?>
        <div class="flex gap-3 p-3 rounded-lg" style="background:var(--bg-surface)">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0" style="background:var(--maresia);color:#fff"><i data-lucide="<?= $d[0] ?>" class="w-4 h-4"></i></div>
            <div>
                <div class="font-semibold text-sm" style="color:var(--sepia)"><?= e($d[1]) ?></div>
                <div class="text-xs" style="color:var(--text-secondary)"><?= e($d[2]) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include VIEWS_DIR . '/partials/institution_foot.php';
