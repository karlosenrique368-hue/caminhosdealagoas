<?php
// Aplica a migration 015 idempotentemente, verificando colunas/tabelas via information_schema.
// Removido apos rodar em producao (NAO COMITAR este arquivo).
require __DIR__ . '/src/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');

function colExists(string $t, string $c): bool {
    $r = dbOne('SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', [$t, $c]);
    return (int)($r['c'] ?? 0) > 0;
}
function tabExists(string $t): bool {
    $r = dbOne('SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', [$t]);
    return (int)($r['c'] ?? 0) > 0;
}
function tryExec(string $label, string $sql): void {
    try { db()->exec($sql); echo "OK  $label\n"; } catch (Throwable $e) { echo "ERR $label: ".$e->getMessage()."\n"; }
}

if (!colExists('customers','avatar')) tryExec('customers.avatar', "ALTER TABLE customers ADD COLUMN avatar VARCHAR(255) NULL AFTER phone");
if (!colExists('institution_users','avatar')) tryExec('institution_users.avatar', "ALTER TABLE institution_users ADD COLUMN avatar VARCHAR(255) NULL AFTER email");
if (!colExists('roteiros','macaiok_featured')) tryExec('roteiros.macaiok_featured', "ALTER TABLE roteiros ADD COLUMN macaiok_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER featured");
if (!colExists('pacotes','macaiok_featured')) tryExec('pacotes.macaiok_featured', "ALTER TABLE pacotes ADD COLUMN macaiok_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER featured");
if (!colExists('transfers','macaiok_featured')) tryExec('transfers.macaiok_featured', "ALTER TABLE transfers ADD COLUMN macaiok_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER featured");

if (!tabExists('password_resets')) tryExec('password_resets', "CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scope ENUM('customer','institution','admin') NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    email VARCHAR(160) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token_hash),
    INDEX idx_scope_user (scope, user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$kv = [
    ['macaiok_color_sepia','#2F1607'],
    ['macaiok_color_terracota','#DA4A34'],
    ['macaiok_color_areia','#FFFACF'],
    ['macaiok_color_origem','#A9D750'],
    ['macaiok_color_mangue','#324500'],
    ['macaiok_logo_horizontal','/assets/img/macaiok/VerdeEscuro_Horizontal.png'],
    ['macaiok_logo_principal','/assets/img/macaiok/VerdeEscuro_Principal.png'],
    ['macaiok_logo_selo','/assets/img/macaiok/Adesivo7.png'],
];
foreach ($kv as [$k,$v]) dbExec('INSERT IGNORE INTO settings (`key`,value) VALUES (?,?)', [$k,$v]);
echo "settings OK\n";

echo "\n== STATE ==\n";
foreach (['customers'=>'avatar','institution_users'=>'avatar','roteiros'=>'macaiok_featured','pacotes'=>'macaiok_featured','transfers'=>'macaiok_featured'] as $t=>$c) {
    echo "$t.$c = ".(colExists($t,$c)?'EXISTS':'MISSING')."\n";
}
echo "password_resets = ".(tabExists('password_resets')?'EXISTS':'MISSING')."\n";
