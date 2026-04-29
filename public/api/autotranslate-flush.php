<?php
/**
 * Flush async de traduções pendentes.
 * Frontend envia JSON { items: [{hash, text, lang}, ...] }.
 * Rate-limit básico por IP (30 req/min). Sem login (é leitura pública).
 */
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');
if (!isPost()) jsonResponse(['ok'=>false,'msg'=>'Método inválido.'], 405);
if (sessionRateLimited('autotranslate_flush', 30, 60)) jsonResponse(['ok'=>false,'msg'=>'Muitas requisições.'], 429);

$raw = file_get_contents('php://input');
$j = json_decode($raw, true);
if (!is_array($j) || empty($j['items']) || !is_array($j['items'])) {
    echo json_encode(['ok'=>false, 'msg'=>'payload inválido']); exit;
}

$items = array_slice($j['items'], 0, 20); // limite duro por request
$done = 0;

foreach ($items as $it) {
    $hash = (string)($it['hash'] ?? '');
    $text = (string)($it['text'] ?? '');
    $lang = (string)($it['lang'] ?? '');
    if (!$hash || !$text || !$lang) continue;
    if ($lang === 'pt-BR') continue;

    // Se ja existe, pula
    $exists = dbOne('SELECT id FROM auto_translations WHERE source_hash=? AND lang=?', [$hash, $lang]);
    if ($exists) { $done++; continue; }

    $tr = autotrCallApi($text, 'pt-BR', $lang);
    if ($tr === null) continue;
    try {
        dbExec('INSERT INTO auto_translations (source_hash, lang, source_text, translated_text) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE translated_text=VALUES(translated_text)',
            [$hash, $lang, $text, $tr]);
        $done++;
    } catch (\Throwable $e) {}
    usleep(120000); // 0.12s throttle
}

echo json_encode(['ok'=>true, 'done'=>$done]);
