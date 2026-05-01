<?php
/**
 * Institution Auth — login separado para usuários de instituições parceiras
 */

function institutionLogin(string $email, string $password): bool {
    $u = dbOne('SELECT * FROM institution_users WHERE email=? AND active=1', [$email]);
    if (!$u || !password_verify($password, $u['password_hash'])) return false;
    $inst = dbOne('SELECT * FROM institutions WHERE id=? AND active=1', [$u['institution_id']]);
    if (!$inst) return false;

    session_regenerate_id(true);
    $_SESSION['inst_user_id']     = (int)$u['id'];
    $_SESSION['inst_user_name']   = $u['name'];
    $_SESSION['inst_user_email']  = $u['email'];
    $_SESSION['inst_user_role']   = $u['role'];
    $_SESSION['inst_id']          = (int)$inst['id'];
    $_SESSION['inst_name']        = $inst['name'];
    $_SESSION['inst_type']        = $inst['type'];
    $_SESSION['inst_program']     = $inst['program'] ?? 'parceiros';
    $_SESSION['inst_discount']    = (float)$inst['discount_percent'];

    dbExec('UPDATE institution_users SET last_login_at=NOW() WHERE id=?', [$u['id']]);
    return true;
}

function institutionLogout(): void {
    foreach (['inst_user_id','inst_user_name','inst_user_email','inst_user_role','inst_id','inst_name','inst_type','inst_program','inst_discount'] as $k) {
        unset($_SESSION[$k]);
    }
    session_regenerate_id(true);
}

function isInstitutionUser(): bool {
    return !empty($_SESSION['inst_user_id']) && !empty($_SESSION['inst_id']);
}

function requireInstitution(): void {
    if (!isInstitutionUser()) {
        if (isAjax()) jsonResponse(['ok'=>false,'msg'=>'Não autenticado.'], 401);
        flash('error','Faça login para continuar.');
        redirect(str_starts_with(currentPath(), '/macaiok') ? '/macaiok/login' : '/parceiro/login');
    }
    $path = currentPath();
    $program = $_SESSION['inst_program'] ?? 'parceiros';
    if (str_starts_with($path, '/macaiok') && $program !== 'macaiok') redirect('/parceiro/dashboard');
    if ((str_starts_with($path, '/parceiro') || str_starts_with($path, '/instituicao')) && $program === 'macaiok') redirect('/macaiok/dashboard');
}

function currentInstitution(): ?array {
    if (!isInstitutionUser()) return null;
    return [
        'user_id'   => $_SESSION['inst_user_id'],
        'user_name' => $_SESSION['inst_user_name'],
        'user_email'=> $_SESSION['inst_user_email'],
        'role'      => $_SESSION['inst_user_role'],
        'id'        => $_SESSION['inst_id'],
        'name'      => $_SESSION['inst_name'],
        'type'      => $_SESSION['inst_type'],
        'program'   => $_SESSION['inst_program'] ?? 'parceiros',
        'discount'  => $_SESSION['inst_discount'] ?? 0.0,
    ];
}

function institutionPortalProgram(?array $institution = null): string {
    $path = currentPath();
    if (str_starts_with($path, '/macaiok')) return 'macaiok';
    $program = $institution['program'] ?? ($_SESSION['inst_program'] ?? 'parceiros');
    return $program === 'macaiok' ? 'macaiok' : 'parceiros';
}

function institutionPortalBasePath(?array $institution = null): string {
    return institutionPortalProgram($institution) === 'macaiok' ? '/macaiok' : '/parceiro';
}

function institutionRoleCan(string $perm): bool {
    $role = $_SESSION['inst_user_role'] ?? 'viewer';
    $map = [
        'owner'   => ['view','book','manage_users','request_quote','export'],
        'manager' => ['view','book','request_quote','export'],
        'viewer'  => ['view'],
    ];
    return in_array($perm, $map[$role] ?? [], true);
}
