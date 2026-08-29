<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_admins_table();

ensure_post_request();

$missing = require_fields($_POST, ['email', 'code']);
if (!empty($missing)) {
    json_response(false, 'البريد الإلكتروني والكود إلزاميان.');
}

$email = clean_input((string) $_POST['email']);
$code = (string) $_POST['code'];
$requestedRole = normalize_admin_role(clean_input((string) ($_POST['role'] ?? 'director')));
if ($requestedRole === '') {
    $requestedRole = 'director';
}

$loginProfile = get_admin_login_profile($requestedRole);
if (!$loginProfile) {
    json_response(false, 'نوع الإدارة غير صالح.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'البريد الإلكتروني غير صالح.');
}

// Rate Limiting: max 5 login attempts per minute per IP to prevent brute force
if (!check_rate_limit('admin_login', 5, 60)) {
    json_response(false, 'تم تجاوز الحد الأقصى لمحاولات الدخول. يرجى الانتظار دقيقة واحدة والمحاولة مجدداً.', [], 429);
}

try {
    $emailLower = strtolower($email);
    $roleEmail = strtolower((string) ($loginProfile['email'] ?? ''));
    $roleCode = (string) ($loginProfile['code'] ?? '');
    $roleLabel = (string) ($loginProfile['label'] ?? 'الإدارة');
    $roleName = (string) ($loginProfile['full_name'] ?? 'Admin');
    $roleValue = normalize_admin_role((string) ($loginProfile['role'] ?? 'director'));

    if ($emailLower !== $roleEmail) {
        json_response(false, 'البريد الإلكتروني لا يطابق حساب ' . $roleLabel . '.', [], 401);
    }

    // Keep known credentials for each role available for recovery.
    $defaultRoleHash = password_hash($roleCode, PASSWORD_DEFAULT);
    $existingRoleAccount = db_query(
        'SELECT id FROM admins WHERE email = :email LIMIT 1',
        ['email' => $roleEmail]
    )->fetch();

    if ($existingRoleAccount) {
        db_query(
            'UPDATE admins
             SET full_name = :full_name, code = :code, status = :status
             WHERE id = :id',
            [
                'id' => (int) $existingRoleAccount['id'],
                'full_name' => $roleName,
                'code' => $defaultRoleHash,
                'status' => 'active',
            ]
        );
    } else {
        db_query(
            'INSERT INTO admins (full_name, email, code, status)
             VALUES (:full_name, :email, :code, :status)',
            [
                'full_name' => $roleName,
                'email' => $roleEmail,
                'code' => $defaultRoleHash,
                'status' => 'active',
            ]
        );
    }

    $admin = db_query(
        'SELECT id, full_name, email, code, status
         FROM admins
         WHERE email = :email
         LIMIT 1',
        ['email' => $roleEmail]
    )->fetch();

    if (!$admin || !password_verify($code, (string) ($admin['code'] ?? ''))) {
        json_response(false, 'معلومات الدخول غير صحيحة.', [], 401);
    }

    if ($admin['status'] !== 'active') {
        json_response(false, 'الحساب الإداري غير مفعل حالياً.', [], 403);
    }

    db_query(
        'UPDATE admins SET last_login = NOW() WHERE id = :id',
        ['id' => (int) $admin['id']]
    );

    secure_session_regenerate();

    $_SESSION['user_id'] = (int) $admin['id'];
    $_SESSION['user_name'] = $admin['full_name'];
    $_SESSION['user_email'] = $admin['email'];
    $_SESSION['user_role'] = $roleValue;
    $_SESSION['logged_in'] = true;

    json_response(true, 'تم تسجيل دخول ' . $roleLabel . ' بنجاح.', [
        'user' => [
            'id' => (int) $admin['id'],
            'full_name' => $admin['full_name'],
            'email' => $admin['email'],
            'role' => $roleValue,
        ],
    ]);
} catch (Throwable $exception) {
    error_log('Admin Auth Error: ' . $exception->getMessage());
    json_response(false, 'وقع خطأ أثناء تسجيل الدخول.', [], 500);
}