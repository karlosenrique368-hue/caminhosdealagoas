<?php
/**
 * Auto-tradução de conteúdo dinâmico — 100% ASSÍNCRONA (zero latência no render).
 *
 * tAuto():
 *   1) Cache em DB? retorna instantâneo.
 *   2) Sem cache? retorna texto original + enfileira para o endpoint async traduzir em background.
 *   3) NUNCA chama API durante o render. Próxima visita já sai do cache.
 */

function tAuto(string $text, ?string $lang = null): string {
    $text = trim($text);
    if ($text === '') return $text;
    $lang = $lang ?: currentLang();
    if ($lang === 'pt-BR') return $text;

    static $memo = [];
    static $dbCache = null;

    $mk = $lang . ':' . md5($text);
    if (isset($memo[$mk])) return $memo[$mk];

    if ($dbCache === null) {
        $dbCache = [];
        try {
            foreach (dbAll('SELECT source_hash, translated_text FROM auto_translations WHERE lang=?', [$lang]) as $r) {
                $dbCache[$r['source_hash']] = $r['translated_text'];
            }
        } catch (\Throwable $e) {}
    }

    $hash = md5($text);
    if (isset($dbCache[$hash])) return $memo[$mk] = $dbCache[$hash];

    autotrRegisterPending($hash, $text, $lang);
    return $memo[$mk] = $text;
}

function autotrRegisterPending(string $hash, string $text, string $lang): void {
    if (!isset($GLOBALS['__autotr_pending'])) $GLOBALS['__autotr_pending'] = [];
    $GLOBALS['__autotr_pending'][$hash] = ['text'=>$text,'lang'=>$lang];
}

function autotrPending(): array {
    return $GLOBALS['__autotr_pending'] ?? [];
}

/** MyMemory Free — usado APENAS pelo endpoint /api/autotranslate-flush. */
function autotrCallApi(string $text, string $from, string $to): ?string {
    $map = ['en'=>'en-US','es'=>'es-ES','fr'=>'fr-FR','de'=>'de-DE','it'=>'it-IT','zh'=>'zh-CN'];
    $mm  = $map[$to] ?? null;
    if (!$mm) return null;
    if (mb_strlen($text) > 480) $text = mb_substr($text, 0, 480);

    $url = 'https://api.mymemory.translated.net/get?' . http_build_query([
        'q' => $text, 'langpair' => "$from|$mm", 'de' => 'contato@caminhos.com',
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
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
