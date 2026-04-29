<?php
/**
 * Router — entry point
 */// Allow PHP built-in server to serve real files directly (bypass router/bootstrap)
if (PHP_SAPI === 'cli-server') {
    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $candidate = __DIR__ . $reqPath;
    if ($reqPath !== '/' && $reqPath !== '/index.php' && is_file($candidate)) {
        if (substr($reqPath, -4) === '.php') { require $candidate; return; }
        return false;
    }
}require_once __DIR__ . '/../src/bootstrap.php';

$path = currentPath();

// Static storage pass-through (fallback se .htaccess não rodar)
if (preg_match('#^/storage/(.+)$#', $path, $m)) {
    $storageRoot = realpath(STORAGE_DIR);
    $file = realpath(STORAGE_DIR . '/' . $m[1]);
    if ($storageRoot && $file && str_starts_with($file, $storageRoot . DIRECTORY_SEPARATOR) && is_file($file)) {
        $mime = mime_content_type($file) ?: 'application/octet-stream';
        if (preg_match('/\.(php|phtml|phar|sql|env|ini|log)$/i', $file)) {
            http_response_code(403);
            exit;
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
}

switch (true) {
    // ============== PUBLIC ==============
    case $path === '/' || $path === '':
        require VIEWS_DIR . '/public/home.php';
        break;

    case $path === '/roteiros':
    case $path === '/passeios':
        require VIEWS_DIR . '/public/roteiros.php';
        break;

    case preg_match('#^/roteiros/([a-z0-9-]+)$#', $path, $m):
    case preg_match('#^/passeios/([a-z0-9-]+)$#', $path, $m):
        $_GET['slug'] = $m[1];
        require VIEWS_DIR . '/public/roteiro-detail.php';
        break;

    case $path === '/pacotes':
        require VIEWS_DIR . '/public/pacotes.php';
        break;

    case preg_match('#^/pacotes/([a-z0-9-]+)$#', $path, $m):
        $_GET['slug'] = $m[1];
        require VIEWS_DIR . '/public/pacote-detail.php';
        break;

    case $path === '/sobre':
        require VIEWS_DIR . '/public/sobre.php';
        break;

    case $path === '/contato':
        require VIEWS_DIR . '/public/contato.php';
        break;

    case $path === '/depoimentos':
        require VIEWS_DIR . '/public/depoimentos.php';
        break;

    case $path === '/transfers':
        require VIEWS_DIR . '/public/transfers.php';
        break;

    case preg_match('#^/transfers/([a-z0-9-]+)$#', $path, $m):
        $_GET['slug'] = $m[1];
        require VIEWS_DIR . '/public/transfer-detail.php';
        break;

    case $path === '/checkout':
        require VIEWS_DIR . '/public/checkout.php';
        break;

    case $path === '/checkout/grupo':
        require VIEWS_DIR . '/public/checkout-grupo.php';
        break;

    // ============== CUSTOMER ACCOUNT ==============
    case $path === '/conta/login':
        require VIEWS_DIR . '/public/account/login.php';
        break;
    case $path === '/conta/registrar':
        require VIEWS_DIR . '/public/account/register.php';
        break;
    case $path === '/conta/sair':
        customerLogout();
        redirect('/');
    case $path === '/conta' || $path === '/conta/dashboard':
        require VIEWS_DIR . '/public/account/dashboard.php';
        break;
    case $path === '/conta/reservas':
        require VIEWS_DIR . '/public/account/reservas.php';
        break;
    case $path === '/conta/favoritos':
        require VIEWS_DIR . '/public/account/favoritos.php';
        break;
    case $path === '/conta/reembolso':
        require VIEWS_DIR . '/public/account/reembolso.php';
        break;
    case $path === '/conta/perfil':
        require VIEWS_DIR . '/public/account/perfil.php';
        break;
    case preg_match('#^/voucher/([A-Z0-9-]+)$#i', $path, $m):
        $_GET['code'] = $m[1];
        require VIEWS_DIR . '/public/voucher.php';
        break;

    // ============== INSTITUIÇÃO (portal parceiro) ==============
    case $path === '/instituicao' || $path === '/instituicao/':
        redirect(isInstitutionUser() ? '/instituicao/dashboard' : '/instituicao/login');

    case $path === '/instituicao/login':
        require VIEWS_DIR . '/institution/login.php';
        break;

    case $path === '/instituicao/logout':
        institutionLogout();
        redirect('/instituicao/login');

    case $path === '/instituicao/dashboard':
        require VIEWS_DIR . '/institution/dashboard.php';
        break;

    case $path === '/instituicao/reservas':
        require VIEWS_DIR . '/institution/reservas.php';
        break;

    case $path === '/instituicao/cotacao':
        require VIEWS_DIR . '/institution/cotacao.php';
        break;

    case $path === '/instituicao/catalogo':
        require VIEWS_DIR . '/institution/catalogo.php';
        break;

    case $path === '/instituicao/perfil':
        require VIEWS_DIR . '/institution/perfil.php';
        break;

    // ============== PARCEIRO (alias publico + cadastro aberto) ==============
    case $path === '/parceiro' || $path === '/parceiro/' || $path === '/parceiros':
        require VIEWS_DIR . '/public/parceiro-landing.php';
        break;

    case $path === '/parceiro/cadastro':
        require VIEWS_DIR . '/public/parceiro-cadastro.php';
        break;

    case $path === '/parceiro/login':
        require VIEWS_DIR . '/institution/login.php';
        break;

    case $path === '/parceiro/logout':
        institutionLogout();
        redirect('/parceiro/login');

    case $path === '/parceiro/dashboard':
        require VIEWS_DIR . '/institution/dashboard.php';
        break;

    case $path === '/parceiro/reservas':
        require VIEWS_DIR . '/institution/reservas.php';
        break;

    case $path === '/parceiro/link':
        require VIEWS_DIR . '/institution/link-share.php';
        break;

    case $path === '/parceiro/perfil':
        require VIEWS_DIR . '/institution/perfil.php';
        break;

    case $path === '/parceiro/catalogo':
        require VIEWS_DIR . '/institution/catalogo.php';
        break;

    // ============== ADMIN ==============
    case $path === '/admin' || $path === '/admin/':
        redirect('/admin/dashboard');

    case $path === '/admin/login':
        require VIEWS_DIR . '/admin/login.php';
        break;

    case $path === '/admin/logout':
        adminLogout();
        redirect('/admin/login');

    case $path === '/admin/dashboard':
        require VIEWS_DIR . '/admin/dashboard.php';
        break;

    case $path === '/admin/roteiros':
        require VIEWS_DIR . '/admin/roteiros.php';
        break;

    case $path === '/admin/roteiros/novo':
    case preg_match('#^/admin/roteiros/(\d+)$#', $path, $m):
        if (isset($m[1])) $_GET['id'] = $m[1];
        require VIEWS_DIR . '/admin/roteiro-edit.php';
        break;

    case $path === '/admin/pacotes':
        require VIEWS_DIR . '/admin/pacotes.php';
        break;

    case $path === '/admin/pacotes/novo':
    case preg_match('#^/admin/pacotes/(\d+)$#', $path, $m):
        if (isset($m[1])) $_GET['id'] = $m[1];
        require VIEWS_DIR . '/admin/pacote-edit.php';
        break;

    case $path === '/admin/transfers':
        require VIEWS_DIR . '/admin/transfers.php';
        break;

    case $path === '/admin/transfers/novo':
    case preg_match('#^/admin/transfers/(\d+)$#', $path, $m):
        if (isset($m[1])) $_GET['id'] = $m[1];
        require VIEWS_DIR . '/admin/transfer-edit.php';
        break;

    case $path === '/admin/reservas':
        require VIEWS_DIR . '/admin/reservas.php';
        break;

    case preg_match('#^/admin/reservas/(\d+)$#', $path, $m):
        $_GET['id'] = $m[1];
        require VIEWS_DIR . '/admin/reserva-detail.php';
        break;

    case $path === '/admin/departures':
    case $path === '/admin/datas':
        require VIEWS_DIR . '/admin/departures.php';
        break;

    case $path === '/admin/clientes':
        require VIEWS_DIR . '/admin/clientes.php';
        break;

    case $path === '/admin/cupons':
        require VIEWS_DIR . '/admin/cupons.php';
        break;

    case $path === '/admin/depoimentos':
        require VIEWS_DIR . '/admin/depoimentos.php';
        break;

    case $path === '/admin/mensagens':
        require VIEWS_DIR . '/admin/mensagens.php';
        break;

    case $path === '/admin/reviews':
        require VIEWS_DIR . '/admin/reviews.php';
        break;
    case $path === '/admin/reembolsos':
        require VIEWS_DIR . '/admin/reembolsos.php';
        break;
    case $path === '/admin/waitlist':
        require VIEWS_DIR . '/admin/waitlist.php';
        break;
    case $path === '/admin/instituicoes':
        require VIEWS_DIR . '/admin/instituicoes.php';
        break;
    case $path === '/admin/traducoes':
        require VIEWS_DIR . '/admin/traducoes.php';
        break;

    case $path === '/admin/integracoes':
        require VIEWS_DIR . '/admin/integracoes.php';
        break;

    case $path === '/admin/configuracoes':
        require VIEWS_DIR . '/admin/configuracoes.php';
        break;

    // ============== API ==============
    case preg_match('#^/api/([a-z0-9_-]+)$#', $path, $m):
        $api = PUBLIC_DIR . '/api/' . $m[1] . '.php';
        if (is_file($api)) {
            require $api;
            break;
        }
        jsonResponse(['ok' => false, 'msg' => 'Endpoint não encontrado.'], 404);

    // ============== 404 ==============
    default:
        http_response_code(404);
        require VIEWS_DIR . '/public/404.php';
}
