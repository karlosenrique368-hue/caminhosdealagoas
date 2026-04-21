-- Caminhos de Alagoas — Seed data

-- Admin padrão (senha: admin123)
INSERT INTO admin_users (name, email, password_hash, role, active) VALUES
('Administrador', 'admin@caminhosdealagoas.com', '$2y$10$aGvj1GQyNP92VoktFSLzGe1q567c6VWfkA2Txo8auTHdbqrwiB13C', 'super', 1);

-- Categorias
INSERT INTO categories (name, slug, type, icon, sort_order) VALUES
('Praias', 'praias', 'roteiro', 'waves', 1),
('Histórico e Cultural', 'historico-cultural', 'roteiro', 'landmark', 2),
('Natureza e Aventura', 'natureza-aventura', 'roteiro', 'mountain', 3),
('Gastronômico', 'gastronomico', 'roteiro', 'utensils', 4),
('Pacotes Nacionais', 'pacotes-nacionais', 'pacote', 'map', 1),
('Pacotes Internacionais', 'pacotes-internacionais', 'pacote', 'globe', 2),
('Traslados Urbanos', 'traslados-urbanos', 'transfer', 'car', 1);

-- Roteiros
INSERT INTO roteiros (category_id, title, slug, short_desc, description, duration_hours, min_people, max_people, price, price_pix, location, meeting_point, cover_image, status, featured) VALUES
(2, 'Tour Histórico e Cultural em Marechal + La Rue', 'tour-historico-marechal-la-rue', 'Descubra a história de Marechal Deodoro e a charmosa La Rue em um passeio que une patrimônio, arte e gastronomia.', 'Um mergulho profundo na rica história do primeiro presidente do Brasil, visitando casarões coloniais, igrejas barrocas e o bistrô francês La Rue.', 8, 2, 20, 170.00, 150.00, 'Marechal Deodoro, AL', 'Hotel do cliente em Maceió', NULL, 'published', 1),
(1, 'Croas de São Bento (Maragogi)', 'croas-sao-bento-maragogi', 'Passeio encantador pelas Croas de São Bento, onde o mar se transforma em um cenário de areia branca e águas cristalinas.', 'As Croas de São Bento são formações arenosas que surgem na maré baixa, criando piscinas naturais de águas cristalinas. Um dos cenários mais impressionantes de Alagoas.', 10, 2, 30, 260.00, 240.00, 'Maragogi, AL', 'Saída de Maceió', NULL, 'published', 1),
(2, 'Memorial Quilombo dos Palmares (União dos Palmares)', 'memorial-quilombo-palmares', 'Uma viagem histórica ao Memorial Quilombo dos Palmares, local que celebra a resistência e a herança afro-brasileira.', 'Conheça a Serra da Barriga, onde Zumbi liderou a maior resistência negra das Américas. Roteiro guiado com contação de histórias e experiência cultural.', 10, 2, 25, 280.00, 260.00, 'União dos Palmares, AL', 'Saída de Maceió', NULL, 'published', 1),
(3, 'Primeira Trilha do Ano — Fernão Velho 25/01', 'trilha-fernao-velho', 'Primeira Trilha do Ano 2026. Dia 25/01/26. Horário: 8h às 11h. Valores por pessoa: R$50,00 (PIX) a partir de 10 pessoas.', 'Trilha ecológica contemplativa pela mata atlântica de Fernão Velho, com cachoeiras, mirantes e área de banho em águas cristalinas.', 3, 5, 25, 60.00, 50.00, 'Fernão Velho, Maceió', 'Estacionamento da Igrejinha', NULL, 'published', 1),
(1, 'Praia do Gunga + Massagueira', 'praia-gunga-massagueira', 'O paraíso do Gunga combinado com a charmosa Massagueira: falésias, lagoa e almoço regional à beira-mar.', 'Um dos cartões-postais mais famosos de Alagoas. Saída de catamarã, tempo livre para banho e almoço especial em restaurante local.', 9, 2, 30, 220.00, 200.00, 'Barra de São Miguel, AL', 'Hotel do cliente', NULL, 'published', 0),
(1, 'Piscinas Naturais de Pajuçara', 'piscinas-pajucara', 'Clássico imperdível: piscinas naturais a 2km da praia, com peixinhos coloridos e águas mornas na maré baixa.', 'Saída em jangada tradicional alagoana para as piscinas naturais, um aquário natural com vida marinha abundante.', 3, 1, 15, 80.00, 70.00, 'Maceió, AL', 'Praia de Pajuçara', NULL, 'published', 1),
(4, 'Rota Gastronômica de Maceió', 'rota-gastronomica-maceio', 'Sabor, cultura e tradição em um passeio pelos melhores sabores regionais da capital alagoana.', 'Tour guiado por 4 estabelecimentos selecionados, com degustação de peixada, sururu, tapioca e cachaças artesanais.', 5, 2, 12, 195.00, 180.00, 'Maceió, AL', 'Orla de Pajuçara', NULL, 'published', 0),
(3, 'Cânion do Xingó', 'canion-xingo', 'Aventura pelo imponente Cânion do Xingó com passeio de catamarã pelo Rio São Francisco.', 'Um dos cânions mais belos do Brasil. Navegação pelas águas esmeraldas do Velho Chico entre paredões rochosos.', 12, 2, 40, 320.00, 290.00, 'Canindé do São Francisco, SE', 'Saída de Maceió 5h', NULL, 'published', 1);

-- Pacotes
INSERT INTO pacotes (category_id, title, slug, short_desc, description, destination, duration_days, duration_nights, price, price_pix, installments, cover_image, status, featured) VALUES
(5, 'Chapada Diamantina — Bahia (Lençóis e Mucugê)', 'chapada-diamantina-lencois-mucuge', 'Uma imersão completa na Chapada Diamantina, com poços de águas cristalinas, mirantes icônicos e cachoeiras impressionantes entre Lençóis e Mucugê.', 'Pacote de 5 dias incluindo hospedagem em Lençóis, transfers, guias especializados, café da manhã e passeios aos principais atrativos: Cachoeira da Fumaça, Poço Azul, Poço Encantado, Morro do Pai Inácio.', 'Lençóis, Bahia', 5, 4, 2670.00, 2470.00, 10, NULL, 'published', 1),
(5, 'Jericoacoara — Ceará Premium', 'jericoacoara-ceara-premium', 'Pacote premium com hospedagem em pousada 4 estrelas, buggy privativo e pôr do sol na Duna do Pôr do Sol.', 'Experiência completa em Jeri com Lagoa do Paraíso, Pedra Furada, Árvore da Preguiça e jantar à beira-mar.', 'Jericoacoara, Ceará', 4, 3, 3200.00, 2950.00, 10, NULL, 'published', 1),
(5, 'São Miguel dos Milagres — AL (Rota Ecológica)', 'sao-miguel-milagres-rota-ecologica', 'Pacote de 3 dias pela Rota Ecológica dos Milagres, com hospedagem em pousada pé-na-areia e piscinas naturais exclusivas.', 'Inclui transfer Maceió-Milagres, hospedagem com café, passeio de catamarã, piscinas naturais de Patacho e tour pelas praias.', 'São Miguel dos Milagres, AL', 3, 2, 1890.00, 1750.00, 6, NULL, 'published', 1),
(6, 'Buenos Aires Essencial — 5 dias', 'buenos-aires-essencial', 'Descubra a capital argentina com tour guiado em português, hotel 4★ no Centro e passeios pelos bairros mais charmosos.', 'Inclui aéreo, 4 noites de hotel, city tour, jantar com show de tango e visita a El Caminito.', 'Buenos Aires, Argentina', 5, 4, 5850.00, 5500.00, 12, NULL, 'published', 0);

-- Departures (saídas programadas)
INSERT INTO departures (entity_type, entity_id, departure_date, departure_time, seats_total, seats_sold, status) VALUES
('roteiro', 4, '2026-01-25', '08:00:00', 25, 8, 'open'),
('roteiro', 1, '2026-05-10', '07:30:00', 20, 3, 'open'),
('roteiro', 2, '2026-05-12', '06:00:00', 30, 12, 'open'),
('roteiro', 3, '2026-05-18', '06:30:00', 25, 5, 'open'),
('pacote', 1, '2026-06-15', NULL, 20, 6, 'open'),
('pacote', 3, '2026-05-20', NULL, 12, 4, 'open');

-- Testimonials
INSERT INTO testimonials (name, location, rating, content, featured, active) VALUES
('Gabriela Medonça', 'São Paulo, SP', 5, 'Hotel, prestatividade e dedicação que sem dúvida alguma deixaram a nossa viagem incrível. Simplesmente amamos tudo! É aprendemos muito com a equipe que nos acompanhou 2 dias, e contou tudo sobre a cultura dessa cidade e desse estado maravilhoso. Simplesmente foi a melhor viagem que fizemos! É voltaremos breve. É pode contar com a gente! É também divulgaremos o trabalho de vocês. Vocês foram 10!!', 1, 1),
('Beatriz Oliveira', 'Rio de Janeiro, RJ', 5, 'Certeza melhoraram nossa estadia em Maceió! Itinerário bem montado, guias atenciosos e hotéis impecáveis. Voltaremos em breve!', 1, 1),
('Rafael Torres', 'Belo Horizonte, MG', 5, 'Fizemos o pacote de Maragogi e foi simplesmente mágico. Atendimento impecável do início ao fim.', 0, 1),
('Mariana Souza', 'Curitiba, PR', 5, 'A Rota Ecológica dos Milagres foi a melhor experiência que já tivemos. Sensação de exclusividade em cada detalhe.', 1, 1);

-- Settings
INSERT INTO settings (`key`, `value`) VALUES
('site_name', 'Caminhos de Alagoas'),
('site_tagline', 'Chegue como visitante. Volte se sentindo de casa.'),
('contact_email', 'contato@caminhosdealagoas.com'),
('contact_phone', '82 98822-0546'),
('contact_whatsapp', '5582988220546'),
('address', 'Maceió, Alagoas — Brasil'),
('cnpj', '50.770.482/0001-37'),
('instagram_url', 'https://instagram.com/caminhosdealagoas'),
('facebook_url', 'https://facebook.com/caminhosdealagoas'),
('currency_default', 'BRL'),
('languages_enabled', 'pt,en,es'),
('stats_clients', '300+'),
('stats_destinations', '30+'),
('stats_packages', '20+'),
('stats_years', '10+');
