<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_post_request();

$missing = require_fields($_POST, ['email', 'password']);
if (!empty($missing)) {
    json_response(false, 'البريد الإلكتروني وكلمة المرور إلزاميان.');
}

$email = clean_input((string) $_POST['email']);
$password = (string) $_POST['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'البريد الإلكتروني غير صالح.');
}

// Rate Limiting: max 5 login attempts per minute per IP
if (!check_rate_limit('portal_login', 5, 60)) {
    json_response(false, 'تم تجاوز الحد الأقصى لمحاولات الدخول. يرجى الانتظار دقيقة واحدة والمحاولة مجدداً.', [], 429);
}

try {
    $user = db_query(
        'SELECT id, full_name, email, password, role, status
         FROM users
         WHERE email = :email
         LIMIT 1',
        ['email' => $email]
    )->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        json_response(false, 'معلومات الدخول غير صحيحة.', [], 401);
    }

    if ($user['status'] !== 'active') {
        json_response(false, 'الحساب غير مفعل حاليا.', [], 403);
    }

    db_query(
        'UPDATE users SET last_login = NOW() WHERE id = :id',
        ['id' => (int) $user['id']]
    );

    secure_session_regenerate();

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in'] = true;

    json_response(true, 'تم تسجيل الدخول بنجاح.', [
        'user' => [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ],
    ]);
} catch (Throwable $exception) {
    error_log('Login Error: ' . $exception->getMessage());
    json_response(false, 'وقع خطأ أثناء تسجيل الدخول.', [], 500);
}

