<?php
/**
 * Bootstrap — load everything
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/customer_auth.php';
require_once __DIR__ . '/institution_auth.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/autotranslate.php';
require_once __DIR__ . '/partners.php';
require_once __DIR__ . '/integrations.php';

ensureMigrations();
applyProductionRuntimeSettings();

// Tracking de indicacao: ?ref=CODE ou ?parceiro=CODE -> cookie 30 dias + sessao, limpa da URL
$incomingReferralParam = $_GET['ref'] ?? null;
if (!$incomingReferralParam && !empty($_GET['parceiro']) && !ctype_digit((string)$_GET['parceiro'])) {
    $incomingReferralParam = $_GET['parceiro'];
}
if (!empty($incomingReferralParam)) {
    trackReferral((string)$incomingReferralParam);
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $qs = [];
    if (!empty($parts['query'])) parse_str($parts['query'], $qs);
    unset($qs['ref']);
    if (!empty($qs['parceiro']) && !ctype_digit((string)$qs['parceiro'])) unset($qs['parceiro']);
    $clean = ($parts['path'] ?? '/') . ($qs ? ('?' . http_build_query($qs)) : '');
    header('Location: ' . $clean);
    exit;
}

// Troca de idioma/moeda via query string (?lang=en&currency=USD). Persiste em cookie.
if (!empty($_GET['lang']) || !empty($_GET['currency'])) {
    if (!empty($_GET['lang']))     setLang((string)$_GET['lang']);
    if (!empty($_GET['currency'])) setCurrency((string)$_GET['currency']);
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $qs = [];
    if (!empty($parts['query'])) parse_str($parts['query'], $qs);
    unset($qs['lang'], $qs['currency']);
    $clean = ($parts['path'] ?? '/') . ($qs ? ('?' . http_build_query($qs)) : '');
    header('Location: ' . $clean);
    exit;
}
