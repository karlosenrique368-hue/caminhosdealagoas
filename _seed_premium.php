<?php
/**
 * Seed premium — enriquece TODOS os roteiros e pacotes com
 * highlights, includes, excludes, itinerary completos (JSON) +
 * gera saídas/departures para os próximos 3 meses.
 *
 * USO: php _seed_premium.php   (ou via browser em localhost)
 */
require_once __DIR__ . '/src/bootstrap.php';

if (!defined('STDIN') && empty($_GET['run'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Rode com ?run=1 ou via CLI.\n"; exit;
}
$isCli = defined('STDIN');
$line = fn($s) => print($s . ($isCli ? "\n" : "<br>") . "\n");
if (!$isCli) { header('Content-Type: text/plain; charset=utf-8'); }

$line("=== SEED PREMIUM — iniciando ===");

// ============ ROTEIROS ============
$roteirosData = [
    'tour-historico-marechal-la-rue' => [
        'short_desc' => 'Viagem no tempo por Marechal Deodoro — a primeira capital de Alagoas — e visita ao Mirante La Rue.',
        'description' => "Uma imersão guiada pela cidade que deu o primeiro presidente do Brasil. Caminhe pelas ruelas coloniais, visite o Convento de São Francisco, a Casa de Floriano Peixoto e termine em um mirante privilegiado com vista para a Lagoa Manguaba e para o oceano.\n\nO passeio é conduzido por guia local certificado e inclui lanche regional e fotos profissionais em pontos estratégicos.",
        'highlights' => ["Guia local certificado e bilíngue","Almoço regional em restaurante parceiro","Transporte climatizado ida e volta","Fotos profissionais inclusas","Entrada em todos os museus"],
        'includes' => ["Transporte com ar-condicionado","Guia turístico bilíngue","Almoço típico","Ingressos de museus","Seguro viagem","Água mineral"],
        'excludes' => ["Bebidas alcoólicas","Compras pessoais","Gorjetas","Transporte do hotel para o ponto de encontro"],
        'itinerary' => [
            ['title'=>'08h · Saída de Maceió','description'=>'Retirada dos passageiros em pontos previamente combinados na orla.'],
            ['title'=>'09h · Convento de São Francisco','description'=>'Visita guiada ao conjunto arquitetônico do séc. XVII com acervo sacro.'],
            ['title'=>'10h30 · Casa Museu Marechal Deodoro','description'=>'Conheça a história da proclamação da República pelos olhos do alagoano.'],
            ['title'=>'12h · Almoço regional','description'=>'Buffet de peixes, moquecas e iguarias da lagoa.'],
            ['title'=>'14h · Mirante La Rue','description'=>'Tempo livre para fotos com vista 360° da lagoa e do mar.'],
            ['title'=>'16h · Retorno','description'=>'Volta para Maceió com parada em loja de artesanato.'],
        ],
        'meeting_point' => 'Orla de Pajuçara em frente ao quiosque 7 (próx. ao Iate Clube)',
        'price_pix' => 150.00,
        'duration_hours' => 8,
        'availability_mode' => 'fixed',
    ],
    'croas-sao-bento-maragogi' => [
        'short_desc' => 'Piscinas naturais cristalinas em Maragogi — um dos 3 maiores recifes do Brasil.',
        'description' => "Navegue até as famosas Croas de São Bento, a 5km da costa, onde a água baixa revela piscinas naturais de coral com peixes coloridos. Snorkel incluso, catamarã confortável com bar aberto.\n\nO passeio acontece apenas em dias de maré seca — garantimos a data ou remarcamos sem custo.",
        'highlights' => ["Catamarã com bar aberto de caipirinha","Snorkel + colete salva-vidas inclusos","Parada em 2 piscinas naturais diferentes","Fotos aéreas com drone","Garantia de maré seca ou remarcação"],
        'includes' => ["Transporte Maceió-Maragogi ida/volta","Catamarã privativo","Equipamento de snorkel","Colete salva-vidas","Caipirinha e água à vontade","Taxa de preservação ambiental"],
        'excludes' => ["Almoço (restaurantes à parte na praia)","Fotos profissionais extras","Gorjeta para a tripulação"],
        'itinerary' => [
            ['title'=>'06h · Saída de Maceió','description'=>'Transporte executivo até Maragogi (2h30 de estrada).'],
            ['title'=>'09h · Embarque no catamarã','description'=>'Check-in na base náutica, briefing de segurança.'],
            ['title'=>'10h · Primeira piscina','description'=>'Ancoragem nas Croas de São Bento, 1h30 de snorkel.'],
            ['title'=>'12h · Almoço na praia (opcional)','description'=>'Restaurantes na orla — recomendação do guia.'],
            ['title'=>'14h · Segunda piscina','description'=>'Barra Grande, recifes mais isolados.'],
            ['title'=>'17h · Retorno','description'=>'Volta para Maceió por volta das 19h30.'],
        ],
        'meeting_point' => 'Shopping Maceió — portão principal (saída única)',
        'price_pix' => 235.00,
        'duration_hours' => 13,
        'availability_mode' => 'fixed',
    ],
    'memorial-quilombo-palmares' => [
        'short_desc' => 'Parque Memorial de Zumbi dos Palmares — história, natureza e ancestralidade em União dos Palmares.',
        'description' => "Viagem histórica ao Parque Memorial da Serra da Barriga, onde ficava o maior quilombo das Américas. Trilha guiada pelo Centro de Cultura, tumba simbólica de Zumbi, vista do pico de onde os palmarinos resistiram.\n\nInclui bate-papo com historiador local e degustação de comida quilombola.",
        'highlights' => ["Historiador quilombola como guia","Trilha leve até o pico da serra","Degustação de comida ancestral","Visita ao Centro de Referência Afro","Certificado de visitação"],
        'includes' => ["Transporte ida/volta (3h cada sentido)","Guia historiador","Almoço com pratos quilombolas","Ingressos do memorial","Água e lanche de trilha"],
        'excludes' => ["Souvenirs no centro cultural","Bebidas alcoólicas","Passeio a cavalo (opcional no local)"],
        'itinerary' => [
            ['title'=>'06h · Saída de Maceió','description'=>'Embarque na orla com café da manhã servido no ônibus.'],
            ['title'=>'09h · Chegada ao Parque','description'=>'Briefing e início da caminhada pela trilha principal.'],
            ['title'=>'10h · Mirante do Ocá','description'=>'Vista panorâmica + roda de conversa sobre a resistência.'],
            ['title'=>'12h · Almoço ancestral','description'=>'Galinha de capoeira, feijão quilombola e beijus.'],
            ['title'=>'14h · Centro de Cultura','description'=>'Exposições, apresentação de maculelê.'],
            ['title'=>'17h · Retorno','description'=>'Volta com cinematográficas paradas para fotos.'],
        ],
        'meeting_point' => 'Ponto de Encontro na Jatiúca, em frente ao Edf. Atlântico',
        'price_pix' => 250.00,
        'duration_hours' => 13,
        'availability_mode' => 'fixed',
    ],
    'trilha-fernao-velho' => [
        'short_desc' => 'Trilha leve, cachoeira e café da manhã no pé da cachoeira — ideal para famílias.',
        'description' => "Trilha de 2km (leve) até a cachoeira da Fábrica, em Fernão Velho. Banho no poço natural, café da manhã saudável servido na beira d'água e retorno pela trilha alternativa pela mata atlântica preservada.",
        'highlights' => ["Trilha fácil, adequada para crianças +6","Café da manhã saudável no poço","Guia ambiental certificado","Grupos pequenos (máx 12 pessoas)","Fotos no celular inclusas"],
        'includes' => ["Guia ambiental","Café da manhã (frutas, pães, sucos naturais)","Água mineral","Taxa da trilha","Kit primeiros socorros"],
        'excludes' => ["Transporte até Fernão Velho (ponto combinado)","Trajes de banho","Repelente"],
        'itinerary' => [
            ['title'=>'07h · Briefing','description'=>'Alongamento e instruções de segurança no ponto de encontro.'],
            ['title'=>'07h30 · Início da trilha','description'=>'Caminhada de 45min pela mata.'],
            ['title'=>'08h15 · Cachoeira','description'=>'Banho livre, café servido no mirante.'],
            ['title'=>'09h30 · Trilha de volta','description'=>'Rota alternativa com observação de fauna.'],
            ['title'=>'10h30 · Encerramento','description'=>'Retorno ao ponto de encontro.'],
        ],
        'meeting_point' => 'Portal de entrada de Fernão Velho (próx. à lagoa)',
        'price_pix' => 55.00,
        'duration_hours' => 4,
        'availability_mode' => 'open',
    ],
    'praia-gunga-massagueira' => [
        'short_desc' => 'Praia do Gunga + vilarejo de Massagueira — o combo perfeito de praia e cultura.',
        'description' => "Dia completo entre a praia mais fotografada de Alagoas e o vilarejo pesqueiro de Massagueira. Barco lagunar, falésias, almoço de peixe fresco com vista para o encontro do rio com o mar.",
        'highlights' => ["Travessia de barco pela lagoa","Mirante das falésias coloridas","Almoço de peixe fresco incluso","Parada em Massagueira para artesanato","Tempo livre na praia do Gunga"],
        'includes' => ["Transporte ida/volta","Barco lagunar","Almoço de peixe (prato do dia)","Guia local","Colete salva-vidas"],
        'excludes' => ["Bebidas","Aluguel de cadeira na praia (R$20 no local)","Passeio de quadriciclo (opcional)"],
        'itinerary' => [
            ['title'=>'08h · Saída','description'=>'Retirada na orla de Maceió.'],
            ['title'=>'09h30 · Massagueira','description'=>'Caminhada pelo vilarejo pesqueiro, compras de artesanato.'],
            ['title'=>'11h · Barco para o Gunga','description'=>'Travessia cênica de 30min pela lagoa do Roteiro.'],
            ['title'=>'12h · Almoço','description'=>'Restaurante na beira da praia, peixe fresco do dia.'],
            ['title'=>'14h · Tempo livre','description'=>'Praia do Gunga — banho de mar, caiaque opcional.'],
            ['title'=>'17h · Retorno','description'=>'Volta para Maceió via Barra de São Miguel.'],
        ],
        'meeting_point' => 'Praia de Pajuçara — em frente ao Hotel Ritz',
        'price_pix' => 195.00,
        'duration_hours' => 10,
        'availability_mode' => 'fixed',
    ],
    'piscinas-pajucara' => [
        'short_desc' => 'Jangada até as piscinas naturais de Pajuçara — rápido, seguro e imperdível.',
        'description' => "A 2km da praia de Pajuçara, bancos de areia formam piscinas naturais quando a maré desce. Travessia de jangada tradicional (15min), 2h nas piscinas com snorkel e retorno tranquilo.\n\nSai todos os dias conforme tabela de marés.",
        'highlights' => ["Jangada tradicional alagoana","Snorkel incluso","2h de tempo nas piscinas","Grupos de no máximo 15 pessoas","Sai da orla de Maceió"],
        'includes' => ["Jangada","Colete salva-vidas","Máscara e snorkel","Água","Taxa de embarque"],
        'excludes' => ["Almoço","Bebidas","Transporte até Pajuçara","Fotos subaquáticas"],
        'itinerary' => [
            ['title'=>'09h · Check-in na praia','description'=>'Retirar coletes e equipamento.'],
            ['title'=>'09h30 · Embarque','description'=>'Jangada até a formação coralina (15min).'],
            ['title'=>'10h · Piscinas naturais','description'=>'Snorkel livre, peixes tropicais, água cristalina.'],
            ['title'=>'12h · Retorno','description'=>'Jangada de volta, desembarque e encerramento.'],
        ],
        'meeting_point' => 'Praia de Pajuçara — quiosque Jangadas de Pajuçara',
        'price_pix' => 70.00,
        'duration_hours' => 3,
        'availability_mode' => 'open',
    ],
    'rota-gastronomica-maceio' => [
        'short_desc' => 'Três restaurantes, três experiências gastronômicas da culinária alagoana — com transporte.',
        'description' => "Tour noturno gastronômico: sunda na praia de Ponta Verde, restaurante de peixes em Jaraguá e sobremesa típica na Cepilho. Três paradas com degustação guiada por chef local.",
        'highlights' => ["3 restaurantes premium","Chef local como guia","Vinho harmonizado em cada parada","Transporte executivo entre restaurantes","Maridagem cultural com música ao vivo"],
        'includes' => ["Entrada + prato principal + sobremesa","Harmonização com vinhos","Transporte entre paradas","Chef guiando degustação","Taxa de serviço"],
        'excludes' => ["Bebidas extras","Opções vegetarianas sob consulta","Gorjeta extra"],
        'itinerary' => [
            ['title'=>'18h · Encontro em Ponta Verde','description'=>'Welcome drink — caipirinha de tamarindo.'],
            ['title'=>'19h · Entradas regionais','description'=>'Sururu na moranga, bolinho de aratu.'],
            ['title'=>'20h30 · Prato principal','description'=>'Peixe grelhado com pirão de camarão.'],
            ['title'=>'22h · Sobremesa típica','description'=>'Bolo de rolo, cocada preta e cachaça especial.'],
            ['title'=>'23h · Despedida','description'=>'Brinde final e transporte ao hotel.'],
        ],
        'meeting_point' => 'Restaurante Wanchako — Ponta Verde',
        'price_pix' => 175.00,
        'duration_hours' => 5,
        'availability_mode' => 'fixed',
    ],
    'canion-xingo' => [
        'short_desc' => 'Cânions do Xingó em lancha — Grand Canyon brasileiro com águas verdes.',
        'description' => "Expedição de dia inteiro aos Cânions do Xingó. Travessia de lancha rápida pelas águas verdes do Rio São Francisco, banho nos paredões de 70m de altura, almoço de peixe ao estilo ribeirinho.",
        'highlights' => ["Lancha exclusiva para o grupo","Banho nos paredões","Almoço no flutuante","Visita à Usina de Xingó","Pausa em Piranhas (cidade histórica)"],
        'includes' => ["Transporte Maceió-Canindé (3h)","Lancha expedicionária","Almoço ribeirinho","Guia de navegação","Seguro aquático"],
        'excludes' => ["Bebidas","Fotos com drone (opcional)","Passeio de buggy extra"],
        'itinerary' => [
            ['title'=>'05h · Saída','description'=>'Madrugada em Maceió — café da manhã servido no ônibus.'],
            ['title'=>'08h · Chegada em Canindé','description'=>'Briefing no porto, colete de segurança.'],
            ['title'=>'09h · Navegação','description'=>'Lancha pelos cânions, paradas para fotos.'],
            ['title'=>'11h · Banho nos paredões','description'=>'Ancoragem em enseadas isoladas.'],
            ['title'=>'13h · Almoço ribeirinho','description'=>'Peixe frito, baião, farofa doce no flutuante.'],
            ['title'=>'15h · Cidade de Piranhas','description'=>'Caminhada pela arquitetura colonial.'],
            ['title'=>'17h · Retorno','description'=>'Volta para Maceió chegando por volta das 21h.'],
        ],
        'meeting_point' => 'Shopping Pátio — entrada norte',
        'price_pix' => 290.00,
        'duration_hours' => 16,
        'availability_mode' => 'fixed',
    ],
];

$rCount = 0;
foreach ($roteirosData as $slug => $d) {
    $r = dbOne("SELECT id FROM roteiros WHERE slug=?", [$slug]);
    if (!$r) { $line("  ⚠ SKIP roteiro sem registro: $slug"); continue; }
    dbExec("UPDATE roteiros SET
        short_desc=?, description=?, highlights=?, includes=?, excludes=?, itinerary=?,
        meeting_point=?, price_pix=?, duration_hours=?, availability_mode=?
     WHERE id=?", [
        $d['short_desc'], $d['description'],
        json_encode($d['highlights'], JSON_UNESCAPED_UNICODE),
        json_encode($d['includes'], JSON_UNESCAPED_UNICODE),
        json_encode($d['excludes'], JSON_UNESCAPED_UNICODE),
        json_encode($d['itinerary'], JSON_UNESCAPED_UNICODE),
        $d['meeting_point'], $d['price_pix'], $d['duration_hours'], $d['availability_mode'],
        $r['id'],
    ]);
    $rCount++;
    $line("  ✓ Roteiro enriquecido: $slug");
}

// ============ PACOTES ============
$pacotesData = [
    'chapada-diamantina-lencois-mucuge' => [
        'short_desc' => 'Expedição de 5 dias pelos pontos mais icônicos da Chapada — Vale do Pati, Poço Azul, Cachoeira da Fumaça.',
        'description' => "Roteiro premium pela Chapada Diamantina com hospedagem em pousadas charmosas de Lençóis e Mucugê, guias locais em cada trilha e tempo para realmente contemplar cada paisagem.",
        'highlights' => ["5 dias, 4 noites em pousadas 4★","Todos os traslados internos","Guias de trilha certificados","Refeições principais inclusas","Grupos pequenos (máx 12)","Seguro viagem completo"],
        'includes' => ["Hospedagem 4 noites","Café da manhã + almoço nos trekkings","Traslados aeroporto","Guias locais","Ingressos de todos os atrativos","Seguro viagem"],
        'excludes' => ["Voos para Salvador","Jantares (vilarejos têm ótimas opções)","Bebidas alcoólicas","Gorjetas"],
        'itinerary' => [
            ['title'=>'Dia 1 — Chegada em Lençóis','description'=>'Traslado, check-in na pousada, jantar livre pela cidade histórica.'],
            ['title'=>'Dia 2 — Cachoeira do Sossego','description'=>'Trilha moderada de 6h até a cachoeira mais fotogênica da Chapada.'],
            ['title'=>'Dia 3 — Poço Azul + Poço Encantado','description'=>'Dia de contemplação nos poços subterrâneos com raio de sol entrando.'],
            ['title'=>'Dia 4 — Vale do Pati (overview)','description'=>'Mirantes do vale, almoço em comunidade ribeirinha.'],
            ['title'=>'Dia 5 — Morro do Pai Inácio + retorno','description'=>'Subida para o nascer do sol, café + retorno a Salvador.'],
        ],
        'duration_days' => 5,
        'duration_nights' => 4,
        'price_pix' => 2490.00,
        'installments' => 10,
        'availability_mode' => 'fixed',
    ],
    'jericoacoara-ceara-premium' => [
        'short_desc' => 'Jeri em 4 dias no modo premium — pousada na rua principal, buggy privativo, sunset no Pôr do Sol.',
        'description' => "Os melhores passeios de Jericoacoara sem stress — Lagoa do Paraíso, Árvore da Preguiça, Duna do Pôr do Sol, Tatajuba. Buggy privativo e motorista bilíngue.",
        'highlights' => ["Pousada premium na Rua Principal","Buggy privativo com motorista","Lagoa do Paraíso com caixa de som privativa","Ingresso VIP no Pôr do Sol","Experiência gastronômica no Tamarindo"],
        'includes' => ["4 noites com café","Transfer aeroporto Jericoacoara","Buggy 2 dias privativo","Guia local","Welcome drink","Taxa de preservação"],
        'excludes' => ["Voos para Fortaleza","Almoço e jantar","Kite/wake surf (opcionais)","Compras pessoais"],
        'itinerary' => [
            ['title'=>'Dia 1 — Chegada','description'=>'Jumping 4x4 Fortaleza-Jeri, check-in, pôr do sol na Duna.'],
            ['title'=>'Dia 2 — Lagoa do Paraíso','description'=>'Dia completo na Lagoa Azul, redes na água, almoço.'],
            ['title'=>'Dia 3 — Tatajuba','description'=>'Tour pelas dunas móveis, mangue, barco no Rio Guriú.'],
            ['title'=>'Dia 4 — Manhã livre + retorno','description'=>'Última manhã livre, traslado de volta.'],
        ],
        'duration_days' => 4,
        'duration_nights' => 3,
        'price_pix' => 2980.00,
        'installments' => 10,
        'availability_mode' => 'fixed',
    ],
    'sao-miguel-milagres-rota-ecologica' => [
        'short_desc' => 'Rota Ecológica de AL — piscinas naturais, foz do Rio Tatuamunha, peixe-boi e pousada pé-na-areia.',
        'description' => "3 dias na Rota Ecológica dos Milagres com hospedagem pé-na-areia, santuário dos peixes-boi, piscinas naturais e jantar pôr-do-sol no restaurante local mais premiado.",
        'highlights' => ["Pousada pé-na-areia em São Miguel","Santuário do Peixe-Boi com biólogo","Piscinas naturais de Antunes","Jantar degustação no Tom na Mesa","Rota de bike pela Barra de Camaragibe"],
        'includes' => ["3 noites com café colonial","Passeio de jangada às piscinas","Visita ao santuário","Bikes para uso livre","Transporte dos passeios"],
        'excludes' => ["Voos/ônibus Maceió-São Miguel","Jantares (alguns restaurantes parceiros com desconto)","Atividades náuticas extras"],
        'itinerary' => [
            ['title'=>'Dia 1 — Chegada e praia livre','description'=>'Check-in, welcome drink, tarde na praia de São Miguel.'],
            ['title'=>'Dia 2 — Piscinas de Antunes','description'=>'Jangada 9h, piscinas cristalinas, almoço na Praia do Toque.'],
            ['title'=>'Dia 3 — Peixe-boi + retorno','description'=>'Santuário de manhã, brunch, traslado.'],
        ],
        'duration_days' => 3,
        'duration_nights' => 2,
        'price_pix' => 1750.00,
        'installments' => 8,
        'availability_mode' => 'fixed',
    ],
    'buenos-aires-essencial' => [
        'short_desc' => 'Buenos Aires em 5 dias — Palermo, San Telmo, Recoleta, tango, parrilla e um dia no Tigre.',
        'description' => "Pacote urbano com hotel boutique em Palermo, guia em PT-BR, jantares de parrilla, show de tango, city tour completo e um dia extra no Delta do Tigre.",
        'highlights' => ["Hotel boutique em Palermo","Guia brasileiro","Jantar parrilla + show tango","Dia no Delta do Tigre","Feira de San Telmo aos domingos"],
        'includes' => ["5 noites com café","Transfer aeroporto","City tour completo","Show de tango + jantar","Day trip Tigre","Guia bilíngue"],
        'excludes' => ["Passagem aérea","Seguro viagem (opcional)","Almoços diários","Compras pessoais"],
        'itinerary' => [
            ['title'=>'Dia 1 — Chegada + Palermo','description'=>'Check-in, volta a pé por Palermo Soho, jantar livre.'],
            ['title'=>'Dia 2 — City tour','description'=>'Casa Rosada, Plaza de Mayo, La Boca, Caminito, Recoleta.'],
            ['title'=>'Dia 3 — Tigre','description'=>'Day trip de trem, navegação no Delta, almoço no Puerto de Frutos.'],
            ['title'=>'Dia 4 — San Telmo','description'=>'Feira de antiguidades, tango de rua, parrilla à noite + show.'],
            ['title'=>'Dia 5 — Manhã livre + retorno','description'=>'Últimas compras, traslado para Ezeiza.'],
        ],
        'duration_days' => 5,
        'duration_nights' => 4,
        'price_pix' => 5400.00,
        'installments' => 12,
        'availability_mode' => 'fixed',
    ],
];

$pCount = 0;
foreach ($pacotesData as $slug => $d) {
    $p = dbOne("SELECT id FROM pacotes WHERE slug=?", [$slug]);
    if (!$p) { $line("  ⚠ SKIP pacote sem registro: $slug"); continue; }
    dbExec("UPDATE pacotes SET
        short_desc=?, description=?, highlights=?, includes=?, excludes=?, itinerary=?,
        duration_days=?, duration_nights=?, price_pix=?, installments=?, availability_mode=?
     WHERE id=?", [
        $d['short_desc'], $d['description'],
        json_encode($d['highlights'], JSON_UNESCAPED_UNICODE),
        json_encode($d['includes'], JSON_UNESCAPED_UNICODE),
        json_encode($d['excludes'], JSON_UNESCAPED_UNICODE),
        json_encode($d['itinerary'], JSON_UNESCAPED_UNICODE),
        $d['duration_days'], $d['duration_nights'], $d['price_pix'],
        $d['installments'], $d['availability_mode'], $p['id'],
    ]);
    $pCount++;
    $line("  ✓ Pacote enriquecido: $slug");
}

// ============ DEPARTURES ============
// Para roteiros "fixed" — saídas nos próximos 90 dias em dias específicos.
// Para pacotes "fixed" — uma saída mensal.
// Para modo "open" — não precisa cadastrar datas (qualquer dia vale)
$line("\n=== Gerando saídas dos próximos 90 dias ===");

$depCount = 0;
$roteiroSchedule = [
    'tour-historico-marechal-la-rue' => [2, 5],     // terça e sexta
    'croas-sao-bento-maragogi'       => [3, 6],     // quarta e sábado
    'memorial-quilombo-palmares'     => [4],        // quinta
    'praia-gunga-massagueira'        => [0, 6],     // dom e sáb
    'rota-gastronomica-maceio'       => [5],        // sexta
    'canion-xingo'                   => [0],        // domingo
];

foreach ($roteiroSchedule as $slug => $dows) {
    $r = dbOne("SELECT id, price_pix FROM roteiros WHERE slug=?", [$slug]);
    if (!$r) continue;
    $cursor = time();
    $end = strtotime('+90 days');
    while ($cursor <= $end) {
        if (in_array((int)date('w', $cursor), $dows, true)) {
            $date = date('Y-m-d', $cursor);
            $dup = dbOne("SELECT id FROM departures WHERE entity_type='roteiro' AND entity_id=? AND departure_date=?", [$r['id'], $date]);
            if (!$dup) {
                $seatsTotal = rand(15, 30);
                $seatsSold = rand(0, (int)($seatsTotal * 0.6));
                dbExec("INSERT INTO departures (entity_type, entity_id, departure_date, departure_time, seats_total, seats_sold, status) VALUES ('roteiro',?,?,'07:30:00',?,?,'open')",
                    [$r['id'], $date, $seatsTotal, $seatsSold]);
                $depCount++;
            }
        }
        $cursor = strtotime('+1 day', $cursor);
    }
}

// Pacotes — 1 saída por mês nos próximos 4 meses
foreach ($pacotesData as $slug => $d) {
    $p = dbOne("SELECT id FROM pacotes WHERE slug=?", [$slug]);
    if (!$p) continue;
    for ($i = 1; $i <= 4; $i++) {
        $date = date('Y-m-d', strtotime("+$i months first day of month"));
        // Move para a primeira sexta do mês
        $dow = (int)date('w', strtotime($date));
        $offset = (5 - $dow + 7) % 7;
        $date = date('Y-m-d', strtotime("$date +$offset days"));
        $dup = dbOne("SELECT id FROM departures WHERE entity_type='pacote' AND entity_id=? AND departure_date=?", [$p['id'], $date]);
        if (!$dup) {
            dbExec("INSERT INTO departures (entity_type, entity_id, departure_date, seats_total, seats_sold, status) VALUES ('pacote',?,?,?,?,'open')",
                [$p['id'], $date, 12, rand(0, 6)]);
            $depCount++;
        }
    }
}

$line("  ✓ $depCount novas saídas criadas");

// ============ DEPOIMENTOS ============
$testimonials = [
    ['Ana Paula Rocha','Recife, PE',5,'Fiz o Croas de São Bento em família e foi sensacional. Equipe pontual, guia atenciosa, catamarã lindo. Recomendo demais!'],
    ['Roberto Mendes','São Paulo, SP',5,'O tour histórico em Marechal superou expectativas. O guia conhecia cada detalhe e a comida no almoço era impecável.'],
    ['Juliana Alves','Porto Alegre, RS',4,'Chapada Diamantina foi de outro mundo. Hospedagem boa, guias incríveis. Só não dei 5 porque o café do Dia 3 podia ser melhor.'],
    ['Carlos Nogueira','Belo Horizonte, MG',5,'Xingó foi o ápice da viagem. A lancha privativa fez toda a diferença. Voltarei com a família completa.'],
    ['Mariana Silva','Brasília, DF',5,'Contratei o pacote de Milagres para meu aniversário. Pousada pé-na-areia mágica. Vocês são demais!'],
];
foreach ($testimonials as $t) {
    $dup = dbOne("SELECT id FROM testimonials WHERE name=? AND content=?", [$t[0], $t[3]]);
    if (!$dup) {
        dbExec("INSERT INTO testimonials (name, location, rating, content, featured, active) VALUES (?,?,?,?,1,1)", $t);
    }
}
$line("  ✓ Depoimentos sincronizados");

// ============ INSTITUIÇÕES demo + usuário ============
$demoInst = dbOne("SELECT id FROM institutions WHERE slug=?", ['escola-modelo-al']);
if (!$demoInst) {
    dbExec("INSERT INTO institutions (name, type, cnpj, contact_name, contact_email, contact_phone, website, slug, discount_percent, commission_percent, active, notes) VALUES (?,?,?,?,?,?,?,?,?,?,1,?)",
        ['Escola Modelo Alagoas','escola','12.345.678/0001-90','Maria Diretora','contato@escolamodelo.com.br','(82) 99999-0001','https://escolamodelo.com.br','escola-modelo-al',10.00,5.00,'Instituição demo para testes do portal']);
    $demoInstId = (int)dbOne("SELECT id FROM institutions WHERE slug='escola-modelo-al'")['id'];
} else {
    $demoInstId = (int)$demoInst['id'];
}

$existsUser = dbOne("SELECT id FROM institution_users WHERE email=?", ['escola@caminhosdealagoas.com']);
if (!$existsUser) {
    dbExec("INSERT INTO institution_users (institution_id, name, email, password_hash, role, active) VALUES (?,?,?,?,?,1)",
        [$demoInstId, 'Maria Diretora', 'escola@caminhosdealagoas.com', password_hash('escola123', PASSWORD_DEFAULT), 'owner']);
    $line("  ✓ Usuário institucional criado: escola@caminhosdealagoas.com / escola123");
} else {
    $line("  · Usuário institucional já existe: escola@caminhosdealagoas.com");
}

$line("\n=== SEED CONCLUÍDO ===");
$line("Resumo: $rCount roteiros enriquecidos · $pCount pacotes enriquecidos · $depCount saídas criadas");
