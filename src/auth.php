<?php
/**
 * Auth — admin login, session, permissions
 */

function adminLogin(string $email, string $password): bool {
    $user = dbOne('SELECT * FROM admin_users WHERE email = ? AND active = 1', [$email]);
    if (!$user || !password_verify($password, $user['password_hash'])) return false;

    $_SESSION['admin_id']    = (int) $user['id'];
    $_SESSION['admin_name']  = $user['name'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['admin_role']  = $user['role'];

    dbExec('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
    return true;
}

function adminLogout(): void {
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role']);
    session_regenerate_id(true);
}

function isAdmin(): bool {
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        if (isAjax()) jsonResponse(['ok' => false, 'msg' => 'Não autenticado.'], 401);
        flash('error', 'Faça login para continuar.');
        redirect('/admin/login');
    }
}

function currentAdmin(): ?array {
    if (!isAdmin()) return null;
    return [
        'id'    => $_SESSION['admin_id'],
        'name'  => $_SESSION['admin_name'],
        'email' => $_SESSION['admin_email'],
        'role'  => $_SESSION['admin_role'],
    ];
}
