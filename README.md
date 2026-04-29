# Caminhos de Alagoas — Plataforma Premium

Plataforma completa de turismo regional (Alagoas, BR): site institucional + dashboard admin + sistema de reservas.

## Stack
- **Backend**: PHP 8+, MySQL (PDO)
- **Frontend**: Tailwind CSS (CDN), Alpine.js 3.14, Lucide Icons, GSAP (homepage)
- **Fonts**: Playfair Display (editorial) + Inter (UI)
- **Servidor**: XAMPP / Apache
- **URL local**: http://localhost/caminhosdealagoas/public/
- **Produção**: Railway via `Dockerfile`, servindo sempre a pasta `public/`

## Instalação

1. Clone em `c:\xampp\htdocs\caminhosdealagoas`
2. Crie o banco via HeidiSQL: `CREATE DATABASE caminhosdealagoas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
3. Importe `sql/schema.sql` e `sql/seed.sql`
4. Ajuste credenciais por variáveis de ambiente ou copie `.env.example` para `.env` se usar loader local
5. Acesse `http://localhost/caminhosdealagoas/public/`

## Admin padrão
- URL: `/admin/login`
- Email: `admin@caminhosdealagoas.com`
- Senha: `admin123` (altere após primeiro login)

## Produção / Railway

Variáveis mínimas:

```env
APP_ENV=production
APP_DEBUG=0
APP_BASE_PATH=
APP_URL=https://seu-dominio.up.railway.app
SESSION_SECURE=1
```

Banco MySQL no Railway: o app lê automaticamente `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER` e `MYSQLPASSWORD`. Também aceita `DATABASE_URL` ou `DB_*`.

Checklist antes de publicar:
- Ativar `production_mode`, `security_headers_enabled` e `hsts_enabled` no painel de integrações.
- Configurar `payment_webhook_secret` antes de liberar webhook público.
- Trocar a senha padrão do admin e usar email real.
- Configurar credenciais reais de PagSeguro/PagBank, email transacional e notificações operacionais.

## Estrutura
```
src/         Core PHP (config, db, auth, helpers)
views/       Views por zona (public, admin, partials)
public/      Entry point + assets + API endpoints
sql/         Schema e seeds
storage/     Uploads
```

## Paleta
- **Horizonte** `#3A6B8A` — confiança/oceano
- **Terracota** `#C96B4A` — CTA/energia
- **Maresia** `#7A9D6E` — natureza/sucesso
- **Areia** `#F4E4C1` — calor/fundo
- **Sepia** `#3E2E1F` — texto editorial
