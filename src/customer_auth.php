<?php
/**
 * Customer Auth — viajante login, register, session
 */

function customerRegister(string $name, string $email, string $password, string $phone = '', string $document = ''): array {
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok'=>false,'msg'=>'E-mail inválido.'];
    if (strlen($password) < PASSWORD_MIN_LENGTH) return ['ok'=>false,'msg'=>'Senha precisa ter ' . PASSWORD_MIN_LENGTH . '+ caracteres.'];
    $existing = dbOne('SELECT id,name,email,password_hash FROM customers WHERE email=?', [$email]);
    if ($existing && !empty($existing['password_hash'])) {
        return ['ok'=>false,'msg'=>'E-mail já cadastrado.'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($existing) {
        dbExec('UPDATE customers SET name=?, password_hash=?, phone=COALESCE(NULLIF(?, ""), phone), document=COALESCE(NULLIF(?, ""), document) WHERE id=?',
            [$name, $hash, $phone, $document, $existing['id']]);
        $id = (int)$existing['id'];
    } else {
        dbExec('INSERT INTO customers (name,email,password_hash,phone,document) VALUES (?,?,?,?,?)',
            [$name, $email, $hash, $phone, $document]);
        $id = (int)db()->lastInsertId();
    }
    customerSessionLogin($id, $name, $email);
    return ['ok'=>true,'id'=>$id];
}

function customerLogin(string $email, string $password): bool {
    $email = strtolower(trim($email));
    $u = dbOne('SELECT * FROM customers WHERE email=?', [$email]);
    if (!$u || empty($u['password_hash']) || !password_verify($password, $u['password_hash'])) return false;
    customerSessionLogin((int)$u['id'], $u['name'], $u['email']);
    return true;
}

function customerSessionLogin(int $id, string $name, string $email): void {
    session_regenerate_id(true);
    $_SESSION['customer_id'] = $id;
    $_SESSION['customer_name'] = $name;
    $_SESSION['customer_email'] = $email;
}

function customerLogout(): void {
    unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);
}

function isCustomerLoggedIn(): bool {
    return !empty($_SESSION['customer_id']);
}

function currentCustomer(): ?array {
    if (!isCustomerLoggedIn()) return null;
    return dbOne('SELECT id,name,email,phone,document,country,city,state,postal_code,address,birthdate,created_at FROM customers WHERE id=?', [$_SESSION['customer_id']]);
}

function currentCustomerId(): ?int {
    return $_SESSION['customer_id'] ?? null;
}

function requireCustomer(): void {
    if (!isCustomerLoggedIn()) {
        if (isAjax()) jsonResponse(['ok'=>false,'msg'=>'Faça login.'], 401);
        flash('error', 'Faça login para continuar.');
        redirect('/conta/login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
    }
}
