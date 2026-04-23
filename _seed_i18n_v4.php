<?php
require_once __DIR__ . '/src/bootstrap.php';

$rows = [
    ['pt-BR','home.testimonials.see_all','Ver todos os depoimentos'],
    ['en',   'home.testimonials.see_all','See all testimonials'],
    ['es',   'home.testimonials.see_all','Ver todos los testimonios'],
    ['fr',   'home.testimonials.see_all','Voir tous les témoignages'],
    ['de',   'home.testimonials.see_all','Alle Bewertungen ansehen'],
    ['it',   'home.testimonials.see_all','Vedi tutte le testimonianze'],
    ['zh',   'home.testimonials.see_all','查看全部评价'],

    ['pt-BR','depoimentos.badge','Depoimentos reais'],
    ['en',   'depoimentos.badge','Real reviews'],
    ['es',   'depoimentos.badge','Testimonios reales'],
    ['fr',   'depoimentos.badge','Témoignages réels'],
    ['de',   'depoimentos.badge','Echte Bewertungen'],
    ['it',   'depoimentos.badge','Recensioni reali'],
    ['zh',   'depoimentos.badge','真实评价'],

    ['pt-BR','depoimentos.title','Histórias de quem viveu Alagoas'],
    ['en',   'depoimentos.title','Stories from those who experienced Alagoas'],
    ['es',   'depoimentos.title','Historias de quienes vivieron Alagoas'],
    ['fr',   'depoimentos.title','Histoires de ceux qui ont vécu Alagoas'],
    ['de',   'depoimentos.title','Geschichten derer, die Alagoas erlebt haben'],
    ['it',   'depoimentos.title','Storie di chi ha vissuto Alagoas'],
    ['zh',   'depoimentos.title','亲历阿拉戈斯的故事'],

    ['pt-BR','depoimentos.sub','Opiniões autênticas dos nossos viajantes — sem filtros, sem edição.'],
    ['en',   'depoimentos.sub','Authentic feedback from our travelers — unfiltered and unedited.'],
    ['es',   'depoimentos.sub','Opiniones auténticas de nuestros viajeros — sin filtros ni ediciones.'],
    ['fr',   'depoimentos.sub','Avis authentiques de nos voyageurs — sans filtre, sans montage.'],
    ['de',   'depoimentos.sub','Echtes Feedback unserer Reisenden — ungefiltert und unbearbeitet.'],
    ['it',   'depoimentos.sub','Opinioni autentiche dei nostri viaggiatori — senza filtri né modifiche.'],
    ['zh',   'depoimentos.sub','来自真实旅客的评价——未经筛选与编辑。'],

    ['pt-BR','depoimentos.k_reviews','Avaliações'],
    ['en',   'depoimentos.k_reviews','Reviews'],
    ['es',   'depoimentos.k_reviews','Opiniones'],
    ['fr',   'depoimentos.k_reviews','Avis'],
    ['de',   'depoimentos.k_reviews','Bewertungen'],
    ['it',   'depoimentos.k_reviews','Recensioni'],
    ['zh',   'depoimentos.k_reviews','评价数'],

    ['pt-BR','depoimentos.k_avg','Nota média'],
    ['en',   'depoimentos.k_avg','Average rating'],
    ['es',   'depoimentos.k_avg','Nota media'],
    ['fr',   'depoimentos.k_avg','Note moyenne'],
    ['de',   'depoimentos.k_avg','Durchschnitt'],
    ['it',   'depoimentos.k_avg','Voto medio'],
    ['zh',   'depoimentos.k_avg','平均评分'],

    ['pt-BR','depoimentos.k_five','5 estrelas'],
    ['en',   'depoimentos.k_five','5-star'],
    ['es',   'depoimentos.k_five','5 estrellas'],
    ['fr',   'depoimentos.k_five','5 étoiles'],
    ['de',   'depoimentos.k_five','5 Sterne'],
    ['it',   'depoimentos.k_five','5 stelle'],
    ['zh',   'depoimentos.k_five','五星'],

    ['pt-BR','depoimentos.featured','Destaque'],
    ['en',   'depoimentos.featured','Featured'],
    ['es',   'depoimentos.featured','Destacado'],
    ['fr',   'depoimentos.featured','À la une'],
    ['de',   'depoimentos.featured','Hervorgehoben'],
    ['it',   'depoimentos.featured','In evidenza'],
    ['zh',   'depoimentos.featured','精选'],

    ['pt-BR','depoimentos.empty_title','Ainda não há depoimentos publicados'],
    ['en',   'depoimentos.empty_title','No reviews published yet'],
    ['es',   'depoimentos.empty_title','Aún no hay testimonios publicados'],
    ['fr',   'depoimentos.empty_title','Aucun témoignage publié pour le moment'],
    ['de',   'depoimentos.empty_title','Noch keine Bewertungen veröffentlicht'],
    ['it',   'depoimentos.empty_title','Nessuna recensione pubblicata'],
    ['zh',   'depoimentos.empty_title','尚未发布评价'],

    ['pt-BR','depoimentos.empty_sub','Volte em breve — seus futuros colegas de aventura estão escrevendo agora.'],
    ['en',   'depoimentos.empty_sub','Check back soon — your future travel companions are writing now.'],
    ['es',   'depoimentos.empty_sub','Vuelve pronto — tus futuros compañeros están escribiendo ahora.'],
    ['fr',   'depoimentos.empty_sub','Revenez bientôt — vos futurs compagnons de voyage rédigent en ce moment.'],
    ['de',   'depoimentos.empty_sub','Bald wieder vorbeischauen — Ihre künftigen Reisegefährten schreiben gerade.'],
    ['it',   'depoimentos.empty_sub','Torna presto — i tuoi futuri compagni di viaggio stanno scrivendo.'],
    ['zh',   'depoimentos.empty_sub','请稍后再来——您未来的旅伴正在撰写中。'],

    ['pt-BR','depoimentos.cta_title','Pronto para sua própria história?'],
    ['en',   'depoimentos.cta_title','Ready to write your own story?'],
    ['es',   'depoimentos.cta_title','¿Listo para tu propia historia?'],
    ['fr',   'depoimentos.cta_title','Prêt à écrire votre propre histoire ?'],
    ['de',   'depoimentos.cta_title','Bereit für Ihre eigene Geschichte?'],
    ['it',   'depoimentos.cta_title','Pronto per la tua storia?'],
    ['zh',   'depoimentos.cta_title','准备好书写属于你的故事吗？'],

    ['pt-BR','depoimentos.cta_sub','Escolha um passeio, embarque na aventura e volte com a sua melhor recordação de Alagoas.'],
    ['en',   'depoimentos.cta_sub','Pick a tour, embark on the adventure and come back with your best Alagoas memory.'],
    ['es',   'depoimentos.cta_sub','Elige un paseo, sube a la aventura y vuelve con tu mejor recuerdo de Alagoas.'],
    ['fr',   'depoimentos.cta_sub','Choisissez une excursion, vivez l\'aventure et repartez avec votre plus beau souvenir d\'Alagoas.'],
    ['de',   'depoimentos.cta_sub','Wählen Sie eine Tour, gehen Sie auf Abenteuer und kommen Sie mit Ihrer schönsten Erinnerung an Alagoas zurück.'],
    ['it',   'depoimentos.cta_sub','Scegli un\'escursione, parti per l\'avventura e torna con il tuo miglior ricordo di Alagoas.'],
    ['zh',   'depoimentos.cta_sub','选择行程，开启探险，带回你最美好的阿拉戈斯记忆。'],
];

$n = 0;
foreach ($rows as [$lang, $k, $v]) {
    dbExec('INSERT INTO translations (lang,tkey,value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)', [$lang,$k,$v]);
    $n++;
}
echo "OK - $n chaves inseridas/atualizadas.\n";
