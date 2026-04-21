<?php
/**
 * Seed ALL tables with sample data for testing the Premium Platform.
 * Idempotent-ish: uses INSERT IGNORE / checks before inserting.
 * Run via browser: /caminhosdealagoas/_seed_all.php
 */
require_once __DIR__ . '/src/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(60);

function seed(string $label, callable $fn): void {
    try { $n = $fn(); echo "✓ $label" . ($n !== null ? " ($n)" : '') . "\n"; }
    catch (\Throwable $e) { echo "✗ $label — " . $e->getMessage() . "\n"; }
}

echo "=== SEED PREMIUM DATA ===\n\n";

// ===== CUSTOMERS =====
seed('customers (accounts)', function() {
    $people = [
        ['Ana Paula Soares', 'ana@teste.com', '(82) 98811-1111', '123.456.789-00', 'Maceió', 'AL'],
        ['Carlos Eduardo Lima', 'carlos@teste.com', '(82) 98822-2222', '234.567.890-11', 'Arapiraca', 'AL'],
        ['Mariana Costa', 'mariana@teste.com', '(11) 99777-3333', '345.678.901-22', 'São Paulo', 'SP'],
        ['Roberto Silva', 'roberto@teste.com', '(21) 99666-4444', '456.789.012-33', 'Rio de Janeiro', 'RJ'],
        ['Juliana Mendes', 'juliana@teste.com', '(31) 99555-5555', '567.890.123-44', 'Belo Horizonte', 'MG'],
    ];
    $n = 0;
    foreach ($people as [$name,$email,$phone,$doc,$city,$state]) {
        if (dbOne('SELECT id FROM customers WHERE email=?', [$email])) continue;
        dbExec('INSERT INTO customers (name,email,phone,document,city,state,country,password_hash,verified) VALUES (?,?,?,?,?,?,?,?,1)',
            [$name,$email,$phone,$doc,$city,$state,'Brasil',password_hash('123456', PASSWORD_DEFAULT)]);
        $n++;
    }
    return $n;
});

// ===== INSTITUTIONS =====
seed('institutions', function() {
    $data = [
        ['Escola Estadual Deodoro','escola','12.345.678/0001-01','Diretora Marina','contato@eedeodoro.edu.br','(82) 3214-5500','https://eedeodoro.edu.br','Parceira educacional para passeios escolares'],
        ['Sebrae Alagoas','empresa','33.444.555/0001-02','Paulo Santos','parcerias@sebrae-al.com.br','(82) 2121-7000','https://al.sebrae.com.br','Apoio a empreendedores do turismo'],
        ['Instituto Tartarugas Marinhas','ong','22.333.444/0001-03','Dra. Fernanda','contato@tamar-al.org.br','(82) 3325-1234','https://tamar-al.org.br','Projetos de preservação'],
        ['Secretaria de Turismo de Alagoas','governo','','Secretário João','imprensa@turismo.al.gov.br','(82) 3315-6000','https://turismo.al.gov.br','Órgão público de fomento ao turismo'],
        ['Pousada Maragogi Beach','empresa','44.555.666/0001-04','Roberto Host','reservas@pousadamaragogi.com.br','(82) 3296-4040','https://pousadamaragogi.com.br','Parceira para hospedagem em pacotes'],
    ];
    $n = 0;
    foreach ($data as $d) {
        if (dbOne('SELECT id FROM institutions WHERE name=?', [$d[0]])) continue;
        dbExec('INSERT INTO institutions (name,type,cnpj,contact_name,contact_email,contact_phone,website,notes,active) VALUES (?,?,?,?,?,?,?,?,1)', $d);
        $n++;
    }
    return $n;
});

// ===== TRANSLATIONS =====
seed('translations', function() {
    $t = [
        ['pt-BR','nav.home','Início'],
        ['pt-BR','nav.tours','Passeios'],
        ['pt-BR','nav.packages','Pacotes'],
        ['pt-BR','home.hero.title','Descubra os Caminhos de Alagoas'],
        ['en','nav.home','Home'],
        ['en','nav.tours','Tours'],
        ['en','nav.packages','Packages'],
        ['en','home.hero.title','Discover the Paths of Alagoas'],
        ['es','nav.home','Inicio'],
        ['es','nav.tours','Paseos'],
        ['es','nav.packages','Paquetes'],
        ['es','home.hero.title','Descubre los Caminos de Alagoas'],
        ['fr','nav.home','Accueil'],
        ['fr','nav.tours','Visites'],
        ['fr','nav.packages','Forfaits'],
        ['fr','home.hero.title','Découvrez les Chemins d\'Alagoas'],
    ];
    $n = 0;
    foreach ($t as [$lang,$k,$v]) {
        dbExec('INSERT INTO translations (lang,tkey,value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$lang,$k,$v]);
        $n++;
    }
    return $n;
});

// ===== COUPONS =====
seed('coupons', function() {
    $data = [
        ['BEMVINDO10','percent',10.00,'Desconto de boas-vindas',100,null],
        ['FERIAS20','percent',20.00,'Promoção de férias',50,null],
        ['VOLTA50','fixed',50.00,'R$ 50 de volta',200,null],
    ];
    $n = 0;
    foreach ($data as [$code,$type,$val,$desc,$uses,$min]) {
        if (dbOne('SELECT id FROM coupons WHERE code=?', [$code])) continue;
        dbExec('INSERT INTO coupons (code,type,value,description,max_uses,min_purchase,active) VALUES (?,?,?,?,?,?,1)',
            [$code,$type,$val,$desc,$uses,$min]);
        $n++;
    }
    return $n;
});

// ===== ROTEIROS — update lat/lng/difficulty on existing =====
seed('roteiros geo update', function() {
    $rows = dbAll('SELECT id, title FROM roteiros LIMIT 20');
    // AL region roughly
    $coords = [['-9.6658','-35.7353'],['-9.0117','-35.2275'],['-8.9875','-35.5564'],['-9.7197','-36.0700'],['-9.4041','-35.8500']];
    $diffs = ['facil','moderado','dificil'];
    $n = 0;
    foreach ($rows as $r) {
        [$lat,$lng] = $coords[array_rand($coords)];
        $diff = $diffs[array_rand($diffs)];
        dbExec('UPDATE roteiros SET latitude=?, longitude=?, difficulty=? WHERE id=?', [$lat,$lng,$diff,$r['id']]);
        $n++;
    }
    return $n;
});
seed('pacotes geo update', function() {
    $rows = dbAll('SELECT id FROM pacotes LIMIT 20');
    $coords = [['-9.6658','-35.7353'],['-9.0117','-35.2275'],['-8.9875','-35.5564']];
    $n = 0;
    foreach ($rows as $r) {
        [$lat,$lng] = $coords[array_rand($coords)];
        dbExec('UPDATE pacotes SET latitude=?, longitude=? WHERE id=?', [$lat,$lng,$r['id']]);
        $n++;
    }
    return $n;
});

// ===== BOOKINGS (link some to customer accounts) =====
seed('bookings linked to customers', function() {
    $custs = dbAll('SELECT id FROM customers LIMIT 5');
    $roteiros = dbAll('SELECT id, title, price FROM roteiros LIMIT 5');
    if (empty($custs) || empty($roteiros)) return 0;
    $n = 0;
    $pstatus = ['pending','paid','paid','paid','cancelled'];
    for ($i=0; $i<8; $i++) {
        $c = $custs[array_rand($custs)];
        $r = $roteiros[array_rand($roteiros)];
        $st = $pstatus[array_rand($pstatus)];
        $tdate = date('Y-m-d', strtotime('+' . rand(5,60) . ' days'));
        $code = 'BK' . strtoupper(substr(md5(uniqid()), 0, 8));
        $adults = rand(1,4);
        $total = (float)$r['price'] * $adults;
        dbExec('INSERT INTO bookings (code,customer_id,customer_user_id,entity_type,entity_id,entity_title,adults,travel_date,subtotal,total,currency,payment_method,payment_status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())',
            [$code, $c['id'], $c['id'], 'roteiro', $r['id'], $r['title'], $adults, $tdate, $total, $total, 'BRL', 'pix', $st]);
        $n++;
    }
    return $n;
});

// ===== WISHLIST =====
seed('wishlist', function() {
    $custs = dbAll('SELECT id FROM customers LIMIT 5');
    $rots = dbAll('SELECT id FROM roteiros LIMIT 5');
    $pacs = dbAll('SELECT id FROM pacotes LIMIT 3');
    $n = 0;
    foreach ($custs as $c) {
        foreach (array_slice($rots, 0, rand(1,3)) as $r) {
            try { dbExec('INSERT IGNORE INTO wishlist (customer_id,entity_type,entity_id) VALUES (?,?,?)', [$c['id'],'roteiro',$r['id']]); $n++; } catch (\Throwable $e) {}
        }
        foreach (array_slice($pacs, 0, rand(0,2)) as $p) {
            try { dbExec('INSERT IGNORE INTO wishlist (customer_id,entity_type,entity_id) VALUES (?,?,?)', [$c['id'],'pacote',$p['id']]); $n++; } catch (\Throwable $e) {}
        }
    }
    return $n;
});

// ===== WAITLIST =====
seed('waitlist', function() {
    $rots = dbAll('SELECT id FROM roteiros LIMIT 3');
    if (empty($rots)) return 0;
    $emails = ['joao.esperando@teste.com','maria.lista@teste.com','pedro.vaga@teste.com'];
    $n = 0;
    foreach ($emails as $i=>$email) {
        $r = $rots[$i % count($rots)];
        dbExec('INSERT INTO waitlist (name,email,phone,entity_type,entity_id,desired_date,status,notes) VALUES (?,?,?,?,?,?,?,?)',
            [explode('.', explode('@',$email)[0])[0], $email, '(82) 99'.rand(100,999).'-'.rand(1000,9999), 'roteiro', $r['id'], date('Y-m-d', strtotime('+'.rand(10,90).' days')), ['waiting','notified','waiting'][$i], 'Quero reservar assim que houver vaga disponível']);
        $n++;
    }
    return $n;
});

// ===== REVIEWS =====
seed('reviews', function() {
    $custs = dbAll('SELECT id FROM customers LIMIT 5');
    $rots = dbAll('SELECT id FROM roteiros LIMIT 5');
    if (empty($custs) || empty($rots)) return 0;
    $comments = [
        'Experiência incrível! O guia foi atencioso e o passeio superou expectativas.',
        'Vale cada centavo. Paisagem de tirar o fôlego, recomendo muito.',
        'Passeio bem organizado, equipe profissional. Voltaremos com certeza.',
        'Foi bom mas esperávamos um pouco mais. Ainda assim, recomendo.',
        'Perfeito! Minha família adorou cada detalhe dessa jornada.',
    ];
    $n = 0;
    $statuses = ['approved','approved','approved','pending','approved'];
    foreach ($custs as $i=>$c) {
        $r = $rots[$i % count($rots)];
        $rating = rand(4,5);
        dbExec('INSERT INTO reviews (customer_id,entity_type,entity_id,rating,title,content,verified,status) VALUES (?,?,?,?,?,?,1,?)',
            [$c['id'],'roteiro',$r['id'],$rating,'Experiência memorável',$comments[$i],$statuses[$i]]);
        $n++;
    }
    // Update rating_avg on roteiros
    foreach ($rots as $r) {
        $s = dbOne("SELECT AVG(rating) a, COUNT(*) c FROM reviews WHERE entity_type='roteiro' AND entity_id=? AND status='approved'", [$r['id']]);
        dbExec('UPDATE roteiros SET rating_avg=?, rating_count=? WHERE id=?', [(float)($s['a']??0),(int)($s['c']??0),$r['id']]);
    }
    return $n;
});

// ===== REFUND REQUESTS =====
seed('refund_requests', function() {
    $bookings = dbAll("SELECT b.id, b.customer_user_id, b.total FROM bookings b WHERE b.customer_user_id IS NOT NULL AND b.payment_status='paid' LIMIT 3");
    $reasons = [
        'Mudança inesperada na agenda de trabalho, não consigo viajar na data.',
        'Questão de saúde na família, precisamos remarcar/reembolsar.',
        'Imprevisto financeiro, gostaria de solicitar o reembolso conforme política.',
    ];
    $n = 0;
    $sts = ['em_analise','aprovado','pago'];
    foreach ($bookings as $i=>$b) {
        dbExec('INSERT INTO refund_requests (booking_id,customer_id,reason,amount,status) VALUES (?,?,?,?,?)',
            [$b['id'], $b['customer_user_id'], $reasons[$i%3], (float)$b['total'], $sts[$i%3]]);
        $n++;
    }
    return $n;
});

// ===== VOUCHERS =====
seed('vouchers', function() {
    $bookings = dbAll("SELECT id FROM bookings WHERE payment_status='paid' LIMIT 5");
    $n = 0;
    foreach ($bookings as $b) {
        if (dbOne('SELECT id FROM vouchers WHERE booking_id=?', [$b['id']])) continue;
        $code = 'V' . strtoupper(substr(md5(uniqid('', true)), 0, 9));
        $qr = 'https://caminhosdealagoas.com.br/voucher/' . $code;
        dbExec('INSERT INTO vouchers (booking_id,code,qr_data) VALUES (?,?,?)', [$b['id'],$code,$qr]);
        $n++;
    }
    return $n;
});

// ===== NEWSLETTER =====
seed('newsletter', function() {
    $emails = ['subscriber1@email.com','subscriber2@email.com','viajante@email.com','turista@email.com','aventureiro@email.com'];
    $n = 0;
    foreach ($emails as $e) {
        try { dbExec('INSERT IGNORE INTO newsletter (email) VALUES (?)', [$e]); $n++; } catch (\Throwable $ex) {}
    }
    return $n;
});

// ===== SETTINGS =====
seed('settings (analytics + integrations)', function() {
    $s = [
        ['ga_id','G-XXXXXXXXXX'],
        ['fb_pixel_id',''],
        ['google_maps_key',''],
        ['social_instagram','https://instagram.com/caminhosdealagoas'],
        ['social_facebook','https://facebook.com/caminhosdealagoas'],
        ['social_youtube','https://youtube.com/@caminhosdealagoas'],
        ['social_whatsapp','5582988220546'],
    ];
    $n = 0;
    foreach ($s as [$k,$v]) {
        try { dbExec('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)', [$k,$v]); $n++; } catch (\Throwable $e) {}
    }
    return $n;
});

echo "\n=== SEED COMPLETE ===\n";
echo "\nContas de teste (senha: 123456):\n";
foreach (dbAll('SELECT email FROM customers WHERE password_hash IS NOT NULL LIMIT 5') as $c) {
    echo "  - " . $c['email'] . "\n";
}
