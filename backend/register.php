<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_post_request();

$missing = require_fields($_POST, ['full_name', 'email', 'password', 'confirm_password']);
if (!empty($missing)) {
    json_response(false, 'المرجو تعبئة جميع الحقول الإلزامية.');
}

$fullName = clean_input((string) $_POST['full_name']);
$email = clean_input((string) $_POST['email']);
$phone = clean_input((string) ($_POST['phone'] ?? ''));
$role = clean_input((string) ($_POST['role'] ?? 'parent'));
$password = (string) $_POST['password'];
$confirmPassword = (string) $_POST['confirm_password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'البريد الإلكتروني غير صالح.');
}

if (mb_strlen($password) < 8) {
    json_response(false, 'كلمة المرور يجب أن تتكون من 8 أحرف على الأقل.');
}

if ($password !== $confirmPassword) {
    json_response(false, 'تأكيد كلمة المرور غير مطابق.');
}

if (!in_array($role, ['admin', 'teacher', 'parent', 'student'], true)) {
    $role = 'parent';
}

try {
    $exists = db_query(
        'SELECT id FROM users WHERE email = :email LIMIT 1',
        ['email' => $email]
    )->fetch();

    if ($exists) {
        json_response(false, 'هذا البريد الإلكتروني مسجل مسبقا.');
    }

    db_query(
        'INSERT INTO users (full_name, email, phone, password, role, status)
         VALUES (:full_name, :email, :phone, :password, :role, :status)',
        [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone === '' ? null : $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'status' => 'active',
        ]
    );

    json_response(true, 'تم إنشاء الحساب بنجاح.');
} catch (Throwable $exception) {
    error_log('Register Error: ' . $exception->getMessage());
    json_response(false, 'وقع خطأ أثناء إنشاء الحساب.', [], 500);
}

