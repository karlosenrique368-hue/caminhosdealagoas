<?php
require_once __DIR__ . '/../../src/bootstrap.php';

requireAdmin();
if (!isPost()) jsonResponse(['ok' => false, 'msg' => 'Método inválido.'], 405);
if (!csrfVerify()) jsonResponse(['ok' => false, 'msg' => 'Token inválido.'], 403);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'test_email':
        $admin = currentAdmin();
        $to = $admin['email'] ?? integrationSetting('contact_email', APP_EMAIL);
        $result = sendTransactionalEmail(
            $to,
            'Teste de email transacional · Caminhos de Alagoas',
            '<p>Teste enviado pelo painel de integrações.</p><p>Se você recebeu esta mensagem, o provedor configurado está operacional.</p>',
            'Teste enviado pelo painel de integrações.'
        );
        jsonResponse(['ok' => !empty($result['ok']), 'msg' => $result['msg'] ?? (!empty($result['ok']) ? 'Teste processado.' : 'Falha no teste.'), 'data' => $result]);

    case 'test_webhook':
        $result = notifyOperations('integration_test', ['message' => 'Teste de webhook operacional pelo painel admin.']);
        jsonResponse(['ok' => true, 'msg' => 'Teste operacional registrado.', 'data' => $result]);

    case 'test_backup':
        $path = integrationSetting('backup_path', ROOT_DIR . '/storage/backups');
        if ($path === '') $path = ROOT_DIR . '/storage/backups';
        if (!is_dir($path)) @mkdir($path, 0775, true);
        $ok = is_dir($path) && is_writable($path);
        jsonResponse(['ok' => $ok, 'msg' => $ok ? 'Diretório de backup gravável.' : 'Diretório de backup não está gravável.', 'path' => $path]);

    case 'payment_webhook_info':
        jsonResponse(['ok' => true, 'msg' => 'Webhook pronto para receber eventos.', 'url' => paymentWebhookUrl(), 'secret_set' => integrationSetting('payment_webhook_secret', '') !== '']);
}

jsonResponse(['ok' => false, 'msg' => 'Ação inválida.'], 404);
