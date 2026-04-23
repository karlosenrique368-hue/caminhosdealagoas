<?php
/**
 * Tradução automática via MyMemory (gratuito, sem API key).
 * POST /api/autotranslate   body: keys=JSON array of tkey strings
 * Lê o valor em pt-BR e preenche os idiomas faltantes (en, es, fr, de, it, zh).
 */
require_once __DIR__ . '/../../src/bootstrap.php';

if (!isAdmin()) { http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'Acesso negado']); exit; }
if (!csrfVerify()) { http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'CSRF inválido']); exit; }

header('Content-Type: application/json');

$keysJson = $_POST['keys'] ?? '[]';
$keys = json_decode($keysJson, true);
if (!is_array($keys) || empty($keys)) { echo json_encode(['ok'=>false,'msg'=>'Lista de chaves vazia']); exit; }
$keys = array_slice(array_values(array_unique(array_filter(array_map('strval',$keys)))), 0, 50);

// Map dos idiomas de destino -> formato MyMemory
$langPairs = [
    'en'    => 'en-US',
    'es'    => 'es-ES',
    'fr'    => 'fr-FR',
    'de'    => 'de-DE',
    'it'    => 'it-IT',
    'zh'    => 'zh-CN',
];
$source = 'pt-BR';

function mmTranslate(string $text, string $from, string $to): ?string {
    if ($text === '') return '';
    $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
        'q' => $text,
        'langpair' => "$from|$to",
        'de' => 'contato@caminhos.com',
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_USERAGENT => 'caminhosdealagoas/1.0',
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$resp) return null;
    $j = json_decode($resp, true);
    $tr = $j['responseData']['translatedText'] ?? null;
    if (!$tr || stripos($tr, 'MYMEMORY WARNING') !== false) return null;
    return html_entity_decode($tr, ENT_QUOTES|ENT_HTML5, 'UTF-8');
}

$in = implode(',', array_fill(0, count($keys), '?'));
$rows = dbAll("SELECT tkey,lang,value FROM translations WHERE tkey IN ($in)", $keys);
$grid = [];
foreach ($rows as $r) $grid[$r['tkey']][$r['lang']] = $r['value'];

$filled = 0;
$errors = 0;

foreach ($keys as $k) {
    $src = $grid[$k][$source] ?? '';
    if ($src === '') continue; // nao temos origem para traduzir

    foreach ($langPairs as $langCode => $mmCode) {
        if (!empty($grid[$k][$langCode])) continue; // ja existe
        $tr = mmTranslate($src, 'pt-BR', $mmCode);
        if ($tr === null) { $errors++; continue; }
        dbExec(
            'INSERT INTO translations (lang,tkey,value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)',
            [$langCode, $k, $tr]
        );
        $filled++;
        // ritmo p/ nao bater limite do MyMemory (100 req/sec é ok)
        usleep(150000); // 0.15s
    }
}

echo json_encode(['ok'=>true,'filled'=>$filled,'errors'=>$errors]);
