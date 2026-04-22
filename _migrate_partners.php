<?php
require_once __DIR__ . '/src/bootstrap.php';

$alters = [
    "institutions" => [
        "ADD COLUMN partner_type ENUM('individual','familia','grupo','instituicao','revendedor') NOT NULL DEFAULT 'individual' AFTER type",
        "ADD COLUMN referral_code VARCHAR(16) NULL",
        "ADD UNIQUE KEY uniq_referral_code (referral_code)",
        "ADD COLUMN cpf VARCHAR(20) NULL",
        "ADD COLUMN rg VARCHAR(20) NULL",
        "ADD COLUMN birth_date DATE NULL",
        "ADD COLUMN whatsapp VARCHAR(30) NULL",
        "ADD COLUMN bookings_threshold INT UNSIGNED NOT NULL DEFAULT 10",
        "ADD COLUMN free_spots_earned INT UNSIGNED NOT NULL DEFAULT 0",
        "ADD COLUMN free_spots_used INT UNSIGNED NOT NULL DEFAULT 0",
        "ADD COLUMN commission_pending DECIMAL(10,2) NOT NULL DEFAULT 0",
        "ADD COLUMN commission_paid DECIMAL(10,2) NOT NULL DEFAULT 0",
        "ADD COLUMN bookings_count_paid INT UNSIGNED NOT NULL DEFAULT 0",
    ],
    "bookings" => [
        "ADD COLUMN referral_code VARCHAR(16) NULL",
        "ADD COLUMN source VARCHAR(40) NULL",
        "ADD COLUMN source_detail VARCHAR(200) NULL",
        "ADD COLUMN comorbidity TEXT NULL",
        "ADD COLUMN booking_answers JSON NULL",
        "ADD COLUMN commission_value DECIMAL(10,2) NOT NULL DEFAULT 0",
        "ADD COLUMN commission_credited TINYINT(1) NOT NULL DEFAULT 0",
        "ADD INDEX idx_bookings_ref (referral_code)",
    ],
    "customers" => [
        "ADD COLUMN rg VARCHAR(20) NULL",
        "ADD COLUMN birth_date DATE NULL",
    ],
];

$pdo = db();
$ok = 0; $skip = 0; $err = 0;
foreach ($alters as $table => $changes) {
    foreach ($changes as $c) {
        try {
            $pdo->exec("ALTER TABLE `$table` $c");
            echo "[OK ] $table :: $c\n";
            $ok++;
        } catch (PDOException $e) {
            $m = $e->getMessage();
            if (stripos($m, 'Duplicate') !== false || stripos($m, 'exists') !== false) {
                echo "[SKIP] $table :: $c\n";
                $skip++;
            } else {
                echo "[ERR ] $table :: $c\n       $m\n";
                $err++;
            }
        }
    }
}

// gera referral_code aos parceiros sem codigo
$pdo->exec("UPDATE institutions SET referral_code = UPPER(SUBSTRING(MD5(CONCAT(id,name,RAND(),NOW())),1,8)) WHERE referral_code IS NULL OR referral_code = ''");
echo "\n-- Codigos de indicacao atribuidos a parceiros pre-existentes --\n";

echo "\nResumo: $ok ok / $skip skip / $err erros\n";
