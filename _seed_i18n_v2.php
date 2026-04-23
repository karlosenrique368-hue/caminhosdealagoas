<?php
/**
 * Seed i18n v2 — expansao: home.* + fr/de/it/zh.
 * Rodar: php _seed_i18n_v2.php  ou  ?run=1
 */
require_once __DIR__ . '/src/bootstrap.php';
if (!defined('STDIN') && empty($_GET['run'])) { header('Content-Type: text/plain; charset=utf-8'); echo "?run=1"; exit; }
if (!defined('STDIN')) header('Content-Type: text/plain; charset=utf-8');

$t = [
    // HOME HERO
    'home.hero.badge'   => ['pt-BR'=>'Turismo Premium em Alagoas','en'=>'Premium Tourism in Alagoas','es'=>'Turismo Premium en Alagoas','fr'=>'Tourisme Premium à Alagoas','de'=>'Premium-Tourismus in Alagoas','it'=>'Turismo Premium ad Alagoas','zh'=>'阿拉戈斯高端旅游'],
    'home.hero.t1'      => ['pt-BR'=>'Chegue como','en'=>'Arrive as a','es'=>'Llega como','fr'=>'Arrivez en','de'=>'Komm als','it'=>'Arriva come','zh'=>'作为'],
    'home.hero.visitor' => ['pt-BR'=>'visitante','en'=>'visitor','es'=>'visitante','fr'=>'visiteur','de'=>'Besucher','it'=>'visitatore','zh'=>'游客'],
    'home.hero.t2'      => ['pt-BR'=>'Volte se sentindo','en'=>'Leave feeling','es'=>'Vuelve sintiéndote','fr'=>'Repartez en vous sentant','de'=>'Geh mit dem Gefühl','it'=>'Torna sentendoti','zh'=>'带着'],
    'home.hero.athome'  => ['pt-BR'=>'de casa','en'=>'at home','es'=>'en casa','fr'=>'chez vous','de'=>'zu Hause','it'=>'a casa','zh'=>'归属感离开'],
    'home.hero.sub'     => ['pt-BR'=>'Deixe a gente cuidar do seu roteiro enquanto você vive o melhor de Alagoas — hospedagem, transporte e passeios criados por quem é daqui.','en'=>'Let us handle your itinerary while you enjoy the best of Alagoas — stay, transport and tours curated by locals.','es'=>'Déjanos cuidar tu itinerario mientras disfrutas lo mejor de Alagoas — alojamiento, transporte y excursiones diseñados por locales.','fr'=>"Laissez-nous nous occuper de votre itinéraire pendant que vous profitez du meilleur d'Alagoas — hébergement, transport et excursions conçus par les locaux.",'de'=>'Überlasst uns eure Reiseplanung, während ihr das Beste von Alagoas erlebt — Unterkunft, Transport und Touren von Einheimischen gestaltet.','it'=>"Lasciate che ci occupiamo del vostro itinerario mentre vivete il meglio di Alagoas — alloggio, trasporti ed escursioni curati da locali.",'zh'=>'让我们为您安排行程，您尽情享受阿拉戈斯的精彩 — 住宿、交通和当地人策划的行程。'],
    'home.hero.cta1'    => ['pt-BR'=>'Explorar Passeios','en'=>'Explore Tours','es'=>'Explorar Excursiones','fr'=>'Explorer les Excursions','de'=>'Touren erkunden','it'=>'Esplora Escursioni','zh'=>'探索行程'],
    'home.hero.cta2'    => ['pt-BR'=>'Ver Destaques','en'=>'See Highlights','es'=>'Ver Destacados','fr'=>'Voir les Coups de Cœur','de'=>'Highlights ansehen','it'=>'Vedi in Evidenza','zh'=>'查看精选'],
    'home.search.title' => ['pt-BR'=>'Seu destino é aqui','en'=>'Your destination is here','es'=>'Tu destino está aquí','fr'=>'Votre destination est ici','de'=>'Dein Ziel ist hier','it'=>'La tua destinazione è qui','zh'=>'您的目的地在这里'],
    'home.search.date'  => ['pt-BR'=>'Data','en'=>'Date','es'=>'Fecha','fr'=>'Date','de'=>'Datum','it'=>'Data','zh'=>'日期'],
    'home.search.find'  => ['pt-BR'=>'Procurar','en'=>'Search','es'=>'Buscar','fr'=>'Rechercher','de'=>'Suchen','it'=>'Cerca','zh'=>'搜索'],
    'home.search.type'  => ['pt-BR'=>'Tipo','en'=>'Type','es'=>'Tipo','fr'=>'Type','de'=>'Art','it'=>'Tipo','zh'=>'类型'],
    'home.search.all'   => ['pt-BR'=>'Todos','en'=>'All','es'=>'Todos','fr'=>'Tous','de'=>'Alle','it'=>'Tutti','zh'=>'全部'],
    'home.search.ph'    => ['pt-BR'=>'Ex: Maragogi, trilha...','en'=>'Ex: Maragogi, trail...','es'=>'Ej: Maragogi, sendero...','fr'=>'Ex: Maragogi, sentier...','de'=>'Z.B: Maragogi, Trail...','it'=>'Es: Maragogi, sentiero...','zh'=>'例如：马拉戈吉、步道...'],
];

// FR/DE/IT/ZH para chaves ja existentes (preenche gaps)
$fill = [
    'nav.home'=>['fr'=>'Accueil','de'=>'Start','it'=>'Home','zh'=>'首页'],
    'nav.tours'=>['fr'=>'Excursions','de'=>'Touren','it'=>'Escursioni','zh'=>'行程'],
    'nav.packages'=>['fr'=>'Forfaits','de'=>'Pakete','it'=>'Pacchetti','zh'=>'套餐'],
    'nav.about'=>['fr'=>'À propos','de'=>'Über uns','it'=>'Chi siamo','zh'=>'关于'],
    'nav.contact'=>['fr'=>'Contact','de'=>'Kontakt','it'=>'Contatto','zh'=>'联系'],
    'nav.account'=>['fr'=>'Mon compte','de'=>'Mein Konto','it'=>'Il mio account','zh'=>'我的账户'],
    'nav.login'=>['fr'=>'Se connecter','de'=>'Einloggen','it'=>'Accedi','zh'=>'登录'],
    'nav.book_now'=>['fr'=>'Réserver','de'=>'Buchen','it'=>'Prenota','zh'=>'立即预订'],
    'nav.cart'=>['fr'=>'Panier','de'=>'Warenkorb','it'=>'Carrello','zh'=>'购物车'],
    'price.from'=>['fr'=>'À partir de','de'=>'Ab','it'=>'A partire da','zh'=>'起价'],
    'price.per_person'=>['fr'=>'par personne','de'=>'pro Person','it'=>'a persona','zh'=>'每人'],
    'book.title'=>['fr'=>'Réserver','de'=>'Buchen','it'=>'Prenota','zh'=>'预订'],
    'book.adults'=>['fr'=>'Adultes','de'=>'Erwachsene','it'=>'Adulti','zh'=>'成人'],
    'book.children'=>['fr'=>'Enfants','de'=>'Kinder','it'=>'Bambini','zh'=>'儿童'],
    'book.total'=>['fr'=>'Total','de'=>'Gesamt','it'=>'Totale','zh'=>'总计'],
    'book.continue'=>['fr'=>'Continuer','de'=>'Weiter','it'=>'Continua','zh'=>'继续'],
    'detail.about'=>['fr'=>'À propos','de'=>'Über','it'=>'Info','zh'=>'介绍'],
    'detail.highlights'=>['fr'=>'Points forts','de'=>'Highlights','it'=>'Punti salienti','zh'=>'亮点'],
    'detail.itinerary'=>['fr'=>'Itinéraire','de'=>'Reiseplan','it'=>'Itinerario','zh'=>'行程'],
    'detail.included'=>['fr'=>'Ce qui est inclus','de'=>'Inklusive','it'=>'Incluso','zh'=>'包含'],
    'detail.excluded'=>['fr'=>'Non inclus','de'=>'Nicht inklusive','it'=>'Non incluso','zh'=>'不包含'],
    'foot.about_us'=>['fr'=>'À propos de nous','de'=>'Über uns','it'=>'Chi siamo','zh'=>'关于我们'],
    'foot.follow_us'=>['fr'=>'Suivez-nous','de'=>'Folge uns','it'=>'Seguici','zh'=>'关注我们'],
    'foot.newsletter'=>['fr'=>'Recevoir les actualités','de'=>'Neuigkeiten erhalten','it'=>'Ricevi news','zh'=>'订阅新闻'],
    'foot.rights'=>['fr'=>'Tous droits réservés','de'=>'Alle Rechte vorbehalten','it'=>'Tutti i diritti riservati','zh'=>'版权所有'],
    'ui.loading'=>['fr'=>'Chargement…','de'=>'Lädt…','it'=>'Caricamento…','zh'=>'加载中…'],
    'ui.search'=>['fr'=>'Rechercher','de'=>'Suchen','it'=>'Cerca','zh'=>'搜索'],
    'ui.send'=>['fr'=>'Envoyer','de'=>'Senden','it'=>'Invia','zh'=>'发送'],
    'ui.save'=>['fr'=>'Sauvegarder','de'=>'Speichern','it'=>'Salva','zh'=>'保存'],
    'ui.cancel'=>['fr'=>'Annuler','de'=>'Abbrechen','it'=>'Annulla','zh'=>'取消'],
];

$count = 0;
foreach ($t as $key => $langs) {
    foreach ($langs as $lang => $val) {
        dbExec('INSERT INTO translations (lang,tkey,value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$lang, $key, $val]);
        $count++;
    }
}
foreach ($fill as $key => $langs) {
    foreach ($langs as $lang => $val) {
        dbExec('INSERT INTO translations (lang,tkey,value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$lang, $key, $val]);
        $count++;
    }
}
echo "OK - $count chaves inseridas/atualizadas.\n";
