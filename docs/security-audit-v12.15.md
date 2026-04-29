# Auditoria v12.15 - Segurança, Financeiro e Deploy

Data: 2026-04-29

## Corrigido nesta rodada

- Configuração por ambiente: `APP_ENV`, `APP_BASE_PATH`, `APP_URL`, `DATABASE_URL`, `MYSQL*` e `DB_*`.
- Produção sem `display_errors`; erros passam a ir para `storage/logs/php-error.log`.
- Sessão com `use_strict_mode`, cookie `HttpOnly`, `SameSite=Lax` e `Secure` em produção.
- Regeneração de sessão em login admin, cliente e parceiro.
- Proteção contra open redirect no login de cliente.
- Rate limit por sessão/IP para login, contato, newsletter, waitlist e flush de autotradução.
- Storage público com bloqueio de path traversal e arquivos sensíveis.
- Área do cliente corrigida para reservas ligadas por `customer_id` e compatível com `customer_user_id` legado.
- Cadastro de cliente agora ativa conta quando a reserva já criou o email sem senha.
- Reembolso corrigido para usar status do schema (`em_analise`) e para atualizar a reserva para `refunded` quando pago.
- Avaliação/reembolso corrigidos para reservas pagas do cliente real.
- Checkout passou a revalidar cupom e vagas dentro de transação com lock antes de gravar reserva.
- Reversão de comissão ajusta também vagas-cortesia quando há cancelamento/reembolso de reserva paga.
- Deploy Railway preparado com `Dockerfile`, `railway.json`, `.dockerignore`, `.env.example` e `.gitignore`.

## Fluxo financeiro auditado

- Reserva cria cliente, snapshot do produto, totais, cupom, datas, forma de pagamento e origem/parceiro.
- Pagamento externo fica pré-preparado via `prepareBookingPayment()` e webhook assinado altera status.
- Webhook exige `payment_webhook_secret` quando `production_mode` está ativo.
- Transição para `paid` credita comissão uma única vez.
- Transição de `paid` para `refunded`, `cancelled` ou `failed` revoga comissão.
- Reembolso pago pelo admin sincroniza reserva como `refunded` e dispara hooks financeiros.

## Pendências que dependem de credenciais reais

- Configurar provedor de pagamento real PagSeguro/PagBank e segredo do webhook.
- Configurar email transacional real: Resend, SendGrid ou outro provedor.
- Configurar notificações operacionais: webhook/WhatsApp Cloud API.
- Trocar senha padrão do admin no banco de produção antes de divulgar o domínio.
- Ativar `production_mode`, `security_headers_enabled` e `hsts_enabled` no painel.
- Configurar backups Railway/volume ou rotina externa para banco e uploads.

## Validações executadas

- `php -l` em todos os arquivos PHP: passou.
- `git diff --check`: passou.
- Diagnóstico do VS Code: sem erros.
- MySQL local estava offline, então smoke dinâmico com banco não foi executado localmente nesta rodada.
