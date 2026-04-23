<?php
/**
 * Auto-tradução de conteúdo dinâmico (títulos, descrições, regras de passeios/pacotes).
 *
 * tAuto($texto) -> traduz pt-BR → idioma atual.
 * Cache em DB (tabela auto_translations). Memoização por request.
 * Se não houver cache e a API falhar, retorna o texto original (nunca quebra).
 *
 * Budget: máx. 5 novas traduções por request para não engargalar a primeira visita.
 * As restantes ficam em fila para o endpoint /api/autotranslate-flush ser chamado em background.
 */

const AUTOTR_PER_REQUEST_BUDGET = 5;
const AUTOTR_TIMEOUT_SECONDS    = 3;

function tAuto(string $text, ?string $lang = null): string {
    $text = trim($text);
    if ($text === '') return $text;
    $lang = $lang ?: currentLang();
    if ($lang === 'pt-BR') return $text;

    static $memo = [];
    static $dbCache = null;
    static $budgetUsed = 0;
    static $queued = [];

    $mk = $lang . ':' . md5($text);
    if (isset($memo[$mk])) return $memo[$mk];

    if ($dbCache === null) {
        $dbCache = [];
        try {
            // Na primeira vez do request, varremos todas as traduções daquele idioma para evitar 1 SELECT por chamada.
            foreach (dbAll('SELECT source_hash, translated_text FROM auto_translations WHERE lang=?', [$lang]) as $r) {
                $dbCache[$r['source_hash']] = $r['translated_text'];
            }
        } catch (\Throwable $e) {}
    }

    $hash = md5($text);
    if (isset($dbCache[$hash])) return $memo[$mk] = $dbCache[$hash];

    // Gastou o budget? enfileira para flush assíncrono e retorna texto original
    if ($budgetUsed >= AUTOTR_PER_REQUEST_BUDGET) {
        $queued[$hash] = $text;
        autotrRegisterPending($hash, $text, $lang);
        return $memo[$mk] = $text;
    }

    $budgetUsed++;
    $tr = autotrCallApi($text, 'pt-BR', $lang);
    if ($tr === null) {
        autotrRegisterPending($hash, $text, $lang);
        return $memo[$mk] = $text;
    }

    try {
        dbExec('INSERT INTO auto_translations (source_hash, lang, source_text, translated_text) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE translated_text=VALUES(translated_text)',
            [$hash, $lang, $text, $tr]);
    } catch (\Throwable $e) {}
    $dbCache[$hash] = $tr;
    return $memo[$mk] = $tr;
}

/** Memoriza itens que não deu tempo de traduzir neste request — a pagina emite um script que chama o flush via AJAX. */
function autotrRegisterPending(string $hash, string $text, string $lang): void {
    if (!isset($GLOBALS['__autotr_pending'])) $GLOBALS['__autotr_pending'] = [];
    $GLOBALS['__autotr_pending'][$hash] = ['text'=>$text,'lang'=>$lang];
}

function autotrPending(): array {
    return $GLOBALS['__autotr_pending'] ?? [];
}

/** Chama MyMemory Free (sem API key). Mapeia locales. */
function autotrCallApi(string $text, string $from, string $to): ?string {
    $map = ['en'=>'en-US','es'=>'es-ES','fr'=>'fr-FR','de'=>'de-DE','it'=>'it-IT','zh'=>'zh-CN'];
    $mm  = $map[$to] ?? null;
    if (!$mm) return null;
    if (mb_strlen($text) > 480) $text = mb_substr($text, 0, 480); // MyMemory limita ~500 chars

    $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
        'q' => $text, 'langpair' => "$from|$mm", 'de' => 'contato@caminhos.com',
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => AUTOTR_TIMEOUT_SECONDS,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_USERAGENT      => 'caminhosdealagoas/1.0',
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
