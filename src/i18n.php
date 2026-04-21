<?php
/**
 * i18n — tradução + conversão de moedas
 * Persiste escolha do usuário em sessão + cookie (1 ano).
 * Taxas de câmbio editáveis via platform_settings (fallback aos valores fixos).
 */

const I18N_SUPPORTED_LANGS = [
    'pt-BR' => ['name'=>'Português','native'=>'Português','flag'=>'🇧🇷','html'=>'pt-BR'],
    'en'    => ['name'=>'English',   'native'=>'English',   'flag'=>'🇺🇸','html'=>'en'],
    'es'    => ['name'=>'Espanhol',  'native'=>'Español',   'flag'=>'🇪🇸','html'=>'es'],
    'fr'    => ['name'=>'Francês',   'native'=>'Français',  'flag'=>'🇫🇷','html'=>'fr'],
    'de'    => ['name'=>'Alemão',    'native'=>'Deutsch',   'flag'=>'🇩🇪','html'=>'de'],
    'it'    => ['name'=>'Italiano',  'native'=>'Italiano',  'flag'=>'🇮🇹','html'=>'it'],
    'zh'    => ['name'=>'Chinês',    'native'=>'中文',      'flag'=>'🇨🇳','html'=>'zh'],
];

const I18N_SUPPORTED_CURRENCIES = [
    'BRL' => ['name'=>'Real',      'symbol'=>'R$',  'flag'=>'🇧🇷'],
    'USD' => ['name'=>'Dólar',     'symbol'=>'US$', 'flag'=>'🇺🇸'],
    'EUR' => ['name'=>'Euro',      'symbol'=>'€',   'flag'=>'🇪🇺'],
    'GBP' => ['name'=>'Libra',     'symbol'=>'£',   'flag'=>'🇬🇧'],
    'ARS' => ['name'=>'Peso Arg.', 'symbol'=>'AR$', 'flag'=>'🇦🇷'],
];

function currentLang(): string {
    $l = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'pt-BR';
    return isset(I18N_SUPPORTED_LANGS[$l]) ? $l : 'pt-BR';
}

function currentCurrency(): string {
    $c = $_SESSION['currency'] ?? $_COOKIE['currency'] ?? 'BRL';
    return isset(I18N_SUPPORTED_CURRENCIES[$c]) ? $c : 'BRL';
}

function setLang(string $lang): void {
    if (!isset(I18N_SUPPORTED_LANGS[$lang])) return;
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, [
        'expires'  => time() + 86400 * 365,
        'path'     => '/',
        'samesite' => 'Lax',
    ]);
}

function setCurrency(string $currency): void {
    if (!isset(I18N_SUPPORTED_CURRENCIES[$currency])) return;
    $_SESSION['currency'] = $currency;
    setcookie('currency', $currency, [
        'expires'  => time() + 86400 * 365,
        'path'     => '/',
        'samesite' => 'Lax',
    ]);
}

/**
 * Traduz chave. Cadeia de fallback: idioma atual → pt-BR → chave literal.
 * Cache por request.
 */
function t(string $key, array $vars = []): string {
    static $cache = [];
    $lang = currentLang();
    if (!isset($cache[$lang])) {
        $cache[$lang] = [];
        try {
            foreach (dbAll('SELECT tkey, value FROM translations WHERE lang=?', [$lang]) as $r) {
                $cache[$lang][$r['tkey']] = $r['value'];
            }
        } catch (\Throwable $e) { /* tabela pode não existir ainda */ }
    }
    $val = $cache[$lang][$key] ?? null;
    if ($val === null && $lang !== 'pt-BR') {
        if (!isset($cache['pt-BR'])) {
            $cache['pt-BR'] = [];
            try {
                foreach (dbAll('SELECT tkey, value FROM translations WHERE lang=?', ['pt-BR']) as $r) {
                    $cache['pt-BR'][$r['tkey']] = $r['value'];
                }
            } catch (\Throwable $e) {}
        }
        $val = $cache['pt-BR'][$key] ?? $key;
    } elseif ($val === null) {
        $val = $key;
    }
    foreach ($vars as $k=>$v) $val = str_replace(':'.$k, (string)$v, $val);
    return $val;
}

/**
 * Taxas de câmbio (1 BRL = X em outra moeda).
 * Editáveis em platform_settings (chave currency_rates = JSON).
 * Fallback a valores padrão.
 */
function currencyRates(): array {
    static $cached = null;
    if ($cached !== null) return $cached;
    $defaults = ['BRL'=>1.00,'USD'=>0.19,'EUR'=>0.17,'GBP'=>0.15,'ARS'=>195.00];
    try {
        $raw = getSetting('currency_rates', null);
        if ($raw) {
            $dec = json_decode($raw, true);
            if (is_array($dec)) {
                $merged = array_merge($defaults, array_map('floatval', $dec));
                $merged['BRL'] = 1.00; // base imutável
                $cached = $merged;
                return $cached;
            }
        }
    } catch (\Throwable $e) {}
    $cached = $defaults;
    return $cached;
}

function convertPrice(float $brl, ?string $to = null): float {
    $to = $to ?: currentCurrency();
    $rates = currencyRates();
    $rate = $rates[$to] ?? 1.00;
    return round($brl * $rate, 2);
}

/**
 * Formata preço na moeda atual (ou na passada). Passa SEMPRE o valor em BRL.
 */
function formatPrice(float $brl, ?string $to = null): string {
    $to = $to ?: currentCurrency();
    $val = convertPrice($brl, $to);
    $symbols = ['BRL'=>'R$','USD'=>'US$','EUR'=>'€','GBP'=>'£','ARS'=>'AR$'];
    $sym = $symbols[$to] ?? '';
    if ($to === 'BRL' || $to === 'EUR') return $sym . ' ' . number_format($val, 2, ',', '.');
    return $sym . ' ' . number_format($val, 2, '.', ',');
}
