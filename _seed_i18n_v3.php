<?php
// Seed v3 — footer + novas chaves. Idempotente.
require_once __DIR__ . '/src/bootstrap.php';

$rows = [
    // Footer
    ['pt-BR', 'foot.tagline',          'Roteiros, passeios e pacotes autênticos para quem quer viver Alagoas de verdade.'],
    ['en',    'foot.tagline',          'Authentic tours, trips and packages for those who want to truly experience Alagoas.'],
    ['es',    'foot.tagline',          'Rutas, paseos y paquetes auténticos para quienes quieren vivir Alagoas de verdad.'],
    ['fr',    'foot.tagline',          'Itinéraires, excursions et forfaits authentiques pour vivre Alagoas en profondeur.'],
    ['de',    'foot.tagline',          'Authentische Touren und Pakete, um Alagoas wirklich zu erleben.'],
    ['it',    'foot.tagline',          'Itinerari, escursioni e pacchetti autentici per vivere davvero Alagoas.'],
    ['zh',    'foot.tagline',          '为想要真正体验阿拉戈斯州的旅客提供的地道行程与套餐。'],

    ['pt-BR', 'foot.support',          'Atendimento'],
    ['en',    'foot.support',          'Support'],
    ['es',    'foot.support',          'Atención'],
    ['fr',    'foot.support',          'Support'],
    ['de',    'foot.support',          'Kundenservice'],
    ['it',    'foot.support',          'Assistenza'],
    ['zh',    'foot.support',          '客户服务'],

    ['pt-BR', 'foot.email',            'E-mail'],
    ['en',    'foot.email',            'Email'],
    ['es',    'foot.email',            'Correo'],
    ['fr',    'foot.email',            'E-mail'],
    ['de',    'foot.email',            'E-Mail'],
    ['it',    'foot.email',            'E-mail'],
    ['zh',    'foot.email',            '电子邮件'],

    ['pt-BR', 'foot.hours',            'Horário'],
    ['en',    'foot.hours',            'Hours'],
    ['es',    'foot.hours',            'Horario'],
    ['fr',    'foot.hours',            'Horaires'],
    ['de',    'foot.hours',            'Öffnungszeiten'],
    ['it',    'foot.hours',            'Orari'],
    ['zh',    'foot.hours',            '营业时间'],

    ['pt-BR', 'foot.hours_value',      'Seg a Sex · 08h30 às 18h'],
    ['en',    'foot.hours_value',      'Mon to Fri · 8:30am to 6pm'],
    ['es',    'foot.hours_value',      'Lun a Vie · 8:30 a 18h'],
    ['fr',    'foot.hours_value',      'Lun à Ven · 8h30 à 18h'],
    ['de',    'foot.hours_value',      'Mo bis Fr · 8:30 bis 18 Uhr'],
    ['it',    'foot.hours_value',      'Lun a Ven · 8:30 alle 18'],
    ['zh',    'foot.hours_value',      '周一至周五 · 8:30 - 18:00'],

    ['pt-BR', 'foot.browse',           'Navegue'],
    ['en',    'foot.browse',           'Browse'],
    ['es',    'foot.browse',           'Explorar'],
    ['fr',    'foot.browse',           'Explorer'],
    ['de',    'foot.browse',           'Navigation'],
    ['it',    'foot.browse',           'Esplora'],
    ['zh',    'foot.browse',           '浏览'],

    ['pt-BR', 'foot.newsletter_title', 'Receba inspiração'],
    ['en',    'foot.newsletter_title', 'Get inspiration'],
    ['es',    'foot.newsletter_title', 'Recibe inspiración'],
    ['fr',    'foot.newsletter_title', 'Recevez de l\'inspiration'],
    ['de',    'foot.newsletter_title', 'Inspiration erhalten'],
    ['it',    'foot.newsletter_title', 'Ricevi ispirazione'],
    ['zh',    'foot.newsletter_title', '获取灵感'],

    ['pt-BR', 'foot.newsletter_sub',   'Roteiros exclusivos, ofertas e dicas de Alagoas.'],
    ['en',    'foot.newsletter_sub',   'Exclusive tours, offers and tips from Alagoas.'],
    ['es',    'foot.newsletter_sub',   'Rutas exclusivas, ofertas y consejos de Alagoas.'],
    ['fr',    'foot.newsletter_sub',   'Itinéraires exclusifs, offres et astuces d\'Alagoas.'],
    ['de',    'foot.newsletter_sub',   'Exklusive Touren, Angebote und Tipps aus Alagoas.'],
    ['it',    'foot.newsletter_sub',   'Itinerari esclusivi, offerte e consigli di Alagoas.'],
    ['zh',    'foot.newsletter_sub',   '阿拉戈斯的独家行程、优惠与贴士。'],

    ['pt-BR', 'foot.copyright',        'Caminhos de Alagoas · Todos os direitos reservados.'],
    ['en',    'foot.copyright',        'Caminhos de Alagoas · All rights reserved.'],
    ['es',    'foot.copyright',        'Caminhos de Alagoas · Todos los derechos reservados.'],
    ['fr',    'foot.copyright',        'Caminhos de Alagoas · Tous droits réservés.'],
    ['de',    'foot.copyright',        'Caminhos de Alagoas · Alle Rechte vorbehalten.'],
    ['it',    'foot.copyright',        'Caminhos de Alagoas · Tutti i diritti riservati.'],
    ['zh',    'foot.copyright',        'Caminhos de Alagoas · 版权所有。'],

    ['pt-BR', 'foot.privacy',          'Política de Privacidade'],
    ['en',    'foot.privacy',          'Privacy Policy'],
    ['es',    'foot.privacy',          'Política de Privacidad'],
    ['fr',    'foot.privacy',          'Politique de confidentialité'],
    ['de',    'foot.privacy',          'Datenschutz'],
    ['it',    'foot.privacy',          'Informativa sulla privacy'],
    ['zh',    'foot.privacy',          '隐私政策'],

    ['pt-BR', 'foot.terms',            'Termos de Uso'],
    ['en',    'foot.terms',            'Terms of Use'],
    ['es',    'foot.terms',            'Términos de Uso'],
    ['fr',    'foot.terms',            'Conditions d\'utilisation'],
    ['de',    'foot.terms',            'Nutzungsbedingungen'],
    ['it',    'foot.terms',            'Termini di utilizzo'],
    ['zh',    'foot.terms',            '使用条款'],
];

$n = 0;
foreach ($rows as [$lang, $k, $v]) {
    dbExec('INSERT INTO translations (lang,tkey,value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$lang,$k,$v]);
    $n++;
}
echo "OK - $n chaves rodadas.\n";
