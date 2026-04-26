<?php
/**
 * Tradução em lote — usado pelo scanner JS no frontend.
 * Recebe: { lang: 'en'|..., texts: [string, ...] }
 * Retorna: { ok:true, translations: [string|null, ...] } na MESMA ordem.
 *
 * Cache hits: respondem instantâneo via auto_translations.
 * Cache miss: chama MyMemory em paralelo (curl_multi) com timeout curto.
 */
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/autotranslate.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'msg'=>'POST only']); exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$lang = strtolower(trim((string)($body['lang'] ?? '')));
$texts = is_array($body['texts'] ?? null) ? $body['texts'] : [];

$supported = ['en','es','fr','de','it','zh'];
if (!in_array($lang, $supported, true) || !$texts) {
    echo json_encode(['ok'=>true,'translations'=>array_fill(0, count($texts), null)]); exit;
}

// Normaliza
$norm = array_map(function($t){
    $t = is_string($t) ? trim($t) : '';
    return $t;
}, $texts);

// Hashes únicos para query no DB
$hashes = [];
$hashIndex = []; // hash => [posições]
foreach ($norm as $i => $t) {
    if ($t === '' || mb_strlen($t) < 2) continue;
    // Pula se for só números/símbolos
    if (!preg_match('/[\p{L}]/u', $t)) continue;
    $h = md5($t);
    $hashes[$h] = $t;
    $hashIndex[$h][] = $i;
}

$translations = array_fill(0, count($norm), null);

if (!$hashes) { echo json_encode(['ok'=>true,'translations'=>$translations]); exit; }

// Busca cache em batch
try {
    $placeholders = implode(',', array_fill(0, count($hashes), '?'));
    $rows = dbAll(
        "SELECT source_hash, translated_text FROM auto_translations WHERE lang=? AND source_hash IN ($placeholders)",
        array_merge([$lang], array_keys($hashes))
    );
} catch (\Throwable $e) { $rows = []; }

$cached = [];
foreach ($rows as $row) {
    $cached[$row['source_hash']] = $row['translated_text'];
}

// Aplica hits
$missing = [];
foreach ($hashes as $h => $text) {
    if (isset($cached[$h])) {
        foreach ($hashIndex[$h] as $pos) $translations[$pos] = $cached[$h];
    } else {
        $missing[$h] = $text;
    }
}

// Cache miss: chama MyMemory em paralelo (limite 12 por request pra não estourar timeout)
if ($missing) {
    $map = ['en'=>'en-US','es'=>'es-ES','fr'=>'fr-FR','de'=>'de-DE','it'=>'it-IT','zh'=>'zh-CN'];
    $mm  = $map[$lang] ?? null;
    $batch = array_slice($missing, 0, 12, true);
    if ($mm && function_exists('curl_multi_init')) {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($batch as $h => $text) {
            $q = mb_substr($text, 0, 480);
            $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
                'q' => $q, 'langpair' => "pt-BR|$mm", 'de' => 'contato@caminhos.com',
            ]);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_USERAGENT      => 'caminhosdealagoas/1.0',
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$h] = $ch;
        }
        $running = null;
        do { curl_multi_exec($mh, $running); curl_multi_select($mh, 0.1); } while ($running > 0);

        foreach ($handles as $h => $ch) {
            $resp = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            if ($code !== 200 || !$resp) continue;
            $j = json_decode($resp, true);
            $tr = $j['responseData']['translatedText'] ?? null;
            if (!$tr || stripos($tr, 'MYMEMORY WARNING') !== false) continue;
            $tr = html_entity_decode($tr, ENT_QUOTES|ENT_HTML5, 'UTF-8');
            // Salva no cache
            try {
                dbExec(
                    'INSERT INTO auto_translations (source_hash, lang, source_text, translated_text) VALUES (?,?,?,?)
                     ON DUPLICATE KEY UPDATE translated_text=VALUES(translated_text)',
                    [$h, $lang, $batch[$h], $tr]
                );
            } catch (\Throwable $e) { /* ignore */ }
            foreach ($hashIndex[$h] as $pos) $translations[$pos] = $tr;
        }
        curl_multi_close($mh);
    }
}

echo json_encode(['ok'=>true,'translations'=>$translations], JSON_UNESCAPED_UNICODE);
