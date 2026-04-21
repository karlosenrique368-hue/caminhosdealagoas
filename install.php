<?php
/**
 * Instalador automático — cria o banco, importa schema e seed.
 * Acesse: http://localhost/caminhosdealagoas/install.php
 */

require_once __DIR__ . '/src/config.php';

if (!function_exists('e')) {
    function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

$messages = [];
$errors   = [];
$done     = false;

if (isset($_POST['install'])) {
    try {
        // 1. Conectar sem banco para criar
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $messages[] = '✅ Banco de dados criado/verificado.';

        // 2. Usar o banco
        $pdo->exec("USE `" . DB_NAME . "`");

        // 3. Importar schema
        $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
        $pdo->exec($schema);
        $messages[] = '✅ Schema importado (13 tabelas).';

        // 4. Importar seed
        $seed = file_get_contents(__DIR__ . '/sql/seed.sql');
        $pdo->exec($seed);
        $messages[] = '✅ Dados de exemplo inseridos.';

        // 5. Criar pastas de storage
        $dirs = [
            __DIR__ . '/storage',
            __DIR__ . '/storage/uploads',
            __DIR__ . '/storage/uploads/roteiros',
            __DIR__ . '/storage/uploads/pacotes',
            __DIR__ . '/storage/uploads/general',
        ];
        foreach ($dirs as $d) {
            if (!is_dir($d)) mkdir($d, 0775, true);
        }
        $messages[] = '✅ Diretórios de storage criados.';

        $done = true;
    } catch (PDOException $e) {
        $errors[] = 'Erro: ' . $e->getMessage();
    }
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Instalador — Caminhos de Alagoas</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:system-ui,-apple-system,sans-serif}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6" style="background:linear-gradient(135deg,#3A6B8A 0%,#1E3A52 100%)">
<div class="w-full max-w-xl bg-white rounded-3xl shadow-2xl p-8">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl mb-4" style="background:linear-gradient(135deg,#3A6B8A,#C96B4A)">
            <span class="text-white text-3xl font-serif italic">CA</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Caminhos de Alagoas</h1>
        <p class="text-sm text-gray-500 mt-1">Instalação do sistema</p>
    </div>

    <?php if ($errors): ?>
        <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200">
            <?php foreach ($errors as $msg): ?><p class="text-sm text-red-700"><?= e($msg) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($messages): ?>
        <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 space-y-1">
            <?php foreach ($messages as $msg): ?><p class="text-sm text-emerald-700"><?= e($msg) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($done): ?>
        <div class="p-5 rounded-xl text-center" style="background:linear-gradient(135deg,#7A9D6E15,#3A6B8A15)">
            <p class="font-semibold text-gray-900 mb-2">🎉 Instalação concluída!</p>
            <p class="text-sm text-gray-600 mb-4">Você já pode acessar a plataforma.</p>
            <div class="flex flex-col gap-2">
                <a href="<?= BASE_PATH ?>/" class="block px-5 py-3 rounded-xl font-semibold text-white" style="background:#C96B4A">Acessar Site</a>
                <a href="<?= BASE_PATH ?>/admin/login" class="block px-5 py-3 rounded-xl font-semibold border-2" style="color:#3A6B8A;border-color:#3A6B8A">Admin (admin@caminhosdealagoas.com / admin123)</a>
            </div>
        </div>
    <?php else: ?>
        <form method="post" class="space-y-4">
            <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 text-sm space-y-1">
                <p><strong>Host:</strong> <?= e(DB_HOST) ?>:<?= e(DB_PORT) ?></p>
                <p><strong>Banco:</strong> <?= e(DB_NAME) ?></p>
                <p><strong>Usuário:</strong> <?= e(DB_USER) ?></p>
            </div>
            <button type="submit" name="install" value="1" class="w-full px-5 py-3 rounded-xl font-semibold text-white shadow-lg transition hover:opacity-90" style="background:linear-gradient(135deg,#3A6B8A,#C96B4A)">
                Instalar Agora
            </button>
            <p class="text-xs text-center text-gray-500">O instalador irá criar o banco, tabelas e dados iniciais.</p>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
