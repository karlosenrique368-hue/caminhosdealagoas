<?php
/**
 * i18n — simple translation + currency conversion
 */

function currentLang(): string {
    return $_SESSION['lang'] ?? 'pt-BR';
}

function currentCurrency(): string {
    return $_SESSION['currency'] ?? 'BRL';
}

/**
 * Translate key. Falls back to key itself.
 * Loads from translations table: columns (lang, key, value)
 */
function t(string $key, array $vars = []): string {
    static $cache = [];
    $lang = currentLang();
    if (!isset($cache[$lang])) {
        $cache[$lang] = [];
        try {
            $rows = dbAll('SELECT tkey, value FROM translations WHERE lang=?', [$lang]);
            foreach ($rows as $r) $cache[$lang][$r['tkey']] = $r['value'];
        } catch (\Throwable $e) { /* table may not exist yet */ }
    }
    $val = $cache[$lang][$key] ?? $key;
    foreach ($vars as $k=>$v) $val = str_replace(':'.$k, (string)$v, $val);
    return $val;
}

/**
 * Currency conversion. Static rates (BRL base) — replace with API call in production.
 */
function currencyRates(): array {
    return [
        'BRL' => 1.00,
        'USD' => 0.19,
        'EUR' => 0.17,
        'GBP' => 0.15,
        'ARS' => 195.00,
    ];
}

function convertPrice(float $brl, ?string $to = null): float {
    $to = $to ?: currentCurrency();
    $rates = currencyRates();
    $rate = $rates[$to] ?? 1.00;
    return round($brl * $rate, 2);
}

function formatPrice(float $brl, ?string $to = null): string {
    $to = $to ?: currentCurrency();
    $val = convertPrice($brl, $to);
    $symbols = ['BRL'=>'R$','USD'=>'US$','EUR'=>'€','GBP'=>'£','ARS'=>'AR$'];
    $sym = $symbols[$to] ?? '';
    if ($to === 'BRL') return 'R$ ' . number_format($val, 2, ',', '.');
    return $sym . ' ' . number_format($val, 2, '.', ',');
}
