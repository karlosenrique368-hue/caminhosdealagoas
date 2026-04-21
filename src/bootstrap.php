<?php
/**
 * Bootstrap — load everything
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/customer_auth.php';
require_once __DIR__ . '/i18n.php';

// Handle language switch
if (!empty($_GET['lang'])) {
    $langs = ['pt-BR','en','es','fr','de','it','zh'];
    if (in_array($_GET['lang'], $langs, true)) {
        $_SESSION['lang'] = $_GET['lang'];
        $clean = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $clean);
        exit;
    }
}
// Handle currency switch
if (!empty($_GET['currency'])) {
    $currencies = ['BRL','USD','EUR','GBP','ARS'];
    if (in_array($_GET['currency'], $currencies, true)) {
        $_SESSION['currency'] = $_GET['currency'];
        $clean = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $clean);
        exit;
    }
}
