<?php
/**
 * Seed traduções UI essenciais (PT/EN/ES).
 * Rodar: php _seed_translations.php  ou  ?run=1 no browser
 */
require_once __DIR__ . '/src/bootstrap.php';
if (!defined('STDIN') && empty($_GET['run'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Use ?run=1 ou CLI.\n"; exit;
}
$isCli = defined('STDIN');
$line = fn($s)=>print($s . ($isCli?"\n":"<br>"));
if (!$isCli) header('Content-Type: text/plain; charset=utf-8');

$t = [
    // navegação
    'nav.home'        => ['pt-BR'=>'Home',            'en'=>'Home',          'es'=>'Inicio'],
    'nav.tours'       => ['pt-BR'=>'Passeios',        'en'=>'Tours',         'es'=>'Excursiones'],
    'nav.packages'    => ['pt-BR'=>'Pacotes',         'en'=>'Packages',      'es'=>'Paquetes'],
    'nav.about'       => ['pt-BR'=>'Sobre',           'en'=>'About',         'es'=>'Sobre'],
    'nav.contact'     => ['pt-BR'=>'Contato',         'en'=>'Contact',       'es'=>'Contacto'],
    'nav.account'     => ['pt-BR'=>'Minha conta',     'en'=>'My account',    'es'=>'Mi cuenta'],
    'nav.login'       => ['pt-BR'=>'Entrar',          'en'=>'Sign in',       'es'=>'Entrar'],
    'nav.register'    => ['pt-BR'=>'Criar conta',     'en'=>'Sign up',       'es'=>'Registrarse'],
    'nav.book_now'    => ['pt-BR'=>'Reservar',        'en'=>'Book now',      'es'=>'Reservar'],
    'nav.cart'        => ['pt-BR'=>'Carrinho',        'en'=>'Cart',          'es'=>'Carrito'],
    'nav.menu'        => ['pt-BR'=>'Menu',            'en'=>'Menu',          'es'=>'Menú'],
    'nav.language'    => ['pt-BR'=>'Idioma',          'en'=>'Language',      'es'=>'Idioma'],
    'nav.currency'    => ['pt-BR'=>'Moeda',           'en'=>'Currency',      'es'=>'Moneda'],
    // hero / home
    'home.hero.title' => ['pt-BR'=>'Descubra os caminhos de Alagoas','en'=>'Discover the paths of Alagoas','es'=>'Descubre los caminos de Alagoas'],
    'home.hero.sub'   => ['pt-BR'=>'Passeios, pacotes e experiências autênticas com curadoria local.','en'=>'Tours, packages and authentic experiences curated by locals.','es'=>'Excursiones, paquetes y experiencias auténticas con curaduría local.'],
    'home.cta.explore'=> ['pt-BR'=>'Explorar passeios','en'=>'Explore tours','es'=>'Explorar excursiones'],
    // preços / reserva
    'price.from'      => ['pt-BR'=>'A partir de',     'en'=>'From',          'es'=>'Desde'],
    'price.per_person'=> ['pt-BR'=>'por pessoa',      'en'=>'per person',    'es'=>'por persona'],
    'price.installments'=>['pt-BR'=>'em :n x sem juros','en'=>'in :n x interest-free','es'=>'en :n cuotas sin interés'],
    'book.title'      => ['pt-BR'=>'Reservar',        'en'=>'Book',          'es'=>'Reservar'],
    'book.select_date'=> ['pt-BR'=>'Selecione a data','en'=>'Select a date', 'es'=>'Selecciona la fecha'],
    'book.adults'     => ['pt-BR'=>'Adultos',         'en'=>'Adults',        'es'=>'Adultos'],
    'book.children'   => ['pt-BR'=>'Crianças',        'en'=>'Children',      'es'=>'Niños'],
    'book.total'      => ['pt-BR'=>'Total',           'en'=>'Total',         'es'=>'Total'],
    'book.continue'   => ['pt-BR'=>'Continuar',       'en'=>'Continue',      'es'=>'Continuar'],
    'book.whatsapp'   => ['pt-BR'=>'Consultar no WhatsApp','en'=>'Ask via WhatsApp','es'=>'Consultar por WhatsApp'],
    // detalhes do passeio/pacote
    'detail.about'    => ['pt-BR'=>'Sobre',           'en'=>'Overview',      'es'=>'Descripción'],
    'detail.highlights'=>['pt-BR'=>'Destaques',       'en'=>'Highlights',    'es'=>'Destacados'],
    'detail.itinerary'=> ['pt-BR'=>'Itinerário',      'en'=>'Itinerary',     'es'=>'Itinerario'],
    'detail.included' => ['pt-BR'=>'O que está incluso','en'=>'What\'s included','es'=>'Qué incluye'],
    'detail.excluded' => ['pt-BR'=>'Não incluso',     'en'=>'Not included',  'es'=>'No incluido'],
    'detail.meeting'  => ['pt-BR'=>'Ponto de encontro','en'=>'Meeting point','es'=>'Punto de encuentro'],
    'detail.duration' => ['pt-BR'=>'Duração',         'en'=>'Duration',      'es'=>'Duración'],
    'detail.group_size'=>['pt-BR'=>'Tamanho do grupo','en'=>'Group size',    'es'=>'Tamaño del grupo'],
    'detail.reviews'  => ['pt-BR'=>'Avaliações',      'en'=>'Reviews',       'es'=>'Opiniones'],
    'detail.related'  => ['pt-BR'=>'Você também pode gostar','en'=>'You might also like','es'=>'También te puede gustar'],
    'detail.availability'=>['pt-BR'=>'Próximas datas','en'=>'Available dates','es'=>'Próximas fechas'],
    'detail.seats_left'=>['pt-BR'=>':n vagas',        'en'=>':n seats',      'es'=>':n plazas'],
    'detail.sold_out' => ['pt-BR'=>'Esgotado',        'en'=>'Sold out',      'es'=>'Agotado'],
    // rodapé
    'foot.about_us'   => ['pt-BR'=>'Sobre nós',       'en'=>'About us',      'es'=>'Sobre nosotros'],
    'foot.follow_us'  => ['pt-BR'=>'Nos siga',        'en'=>'Follow us',     'es'=>'Síguenos'],
    'foot.newsletter' => ['pt-BR'=>'Receba novidades','en'=>'Get news',      'es'=>'Recibe novedades'],
    'foot.rights'     => ['pt-BR'=>'Todos os direitos reservados','en'=>'All rights reserved','es'=>'Todos los derechos reservados'],
    // mensagens comuns
    'ui.loading'      => ['pt-BR'=>'Carregando…',     'en'=>'Loading…',      'es'=>'Cargando…'],
    'ui.search'       => ['pt-BR'=>'Buscar',          'en'=>'Search',        'es'=>'Buscar'],
    'ui.filter'       => ['pt-BR'=>'Filtrar',         'en'=>'Filter',        'es'=>'Filtrar'],
    'ui.clear'        => ['pt-BR'=>'Limpar',          'en'=>'Clear',         'es'=>'Limpiar'],
    'ui.more'         => ['pt-BR'=>'Ver mais',        'en'=>'See more',      'es'=>'Ver más'],
    'ui.send'         => ['pt-BR'=>'Enviar',          'en'=>'Send',          'es'=>'Enviar'],
    'ui.save'         => ['pt-BR'=>'Salvar',          'en'=>'Save',          'es'=>'Guardar'],
    'ui.cancel'       => ['pt-BR'=>'Cancelar',        'en'=>'Cancel',        'es'=>'Cancelar'],
    'ui.empty'        => ['pt-BR'=>'Nada por aqui ainda.','en'=>'Nothing here yet.','es'=>'Aún no hay nada.'],
];

$count = 0;
foreach ($t as $key => $langs) {
    foreach ($langs as $lang => $val) {
        dbExec('INSERT INTO translations (lang,tkey,value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$lang, $key, $val]);
        $count++;
    }
}
$line("✓ $count traduções sincronizadas em " . count(['pt-BR','en','es']) . " idiomas.");

// Seed taxas de câmbio iniciais em platform_settings
$defaultRates = json_encode(['USD'=>0.19,'EUR'=>0.17,'GBP'=>0.15,'ARS'=>195.00]);
if (!getSetting('currency_rates')) {
    setSetting('currency_rates', $defaultRates);
    $line("✓ currency_rates inicializado com valores padrão.");
} else {
    $line("· currency_rates já existe — mantido.");
}

$line("=== Seed i18n concluído ===");
