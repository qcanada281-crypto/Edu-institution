<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_teachers_table();

// Handle logout
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    session_destroy();
    json_response(true, 'تم تسجيل الخروج بنجاح.');
}

ensure_post_request();

$missing = require_fields($_POST, ['email', 'code']);
if (!empty($missing)) {
    json_response(false, 'البريد الإلكتروني والكود إلزاميان.', [], 422);
}

$email = strtolower(clean_input((string) $_POST['email']));
$code = (string) ($_POST['code'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'البريد الإلكتروني غير صالح.', [], 422);
}

if (!str_ends_with($email, '@gmail.com')) {
    json_response(false, 'مرجو استعمال حساب Gmail لتسجيل دخول الأساتذة.', [], 422);
}

try {
    $teacher = db_query(
        'SELECT id, full_name, email, code, status, phone, subject_name
         FROM teachers
         WHERE email = :email
         LIMIT 1',
        ['email' => $email]
    )->fetch();

    if (!$teacher || !password_verify($code, (string) ($teacher['code'] ?? ''))) {
        json_response(false, 'معلومات الدخول غير صحيحة.', [], 401);
    }

    $status = $teacher['status'] ?? 'active';
    if ($status === 'pending') {
        json_response(false, 'حسابك قيد المراجعة من طرف الإدارة.', [], 403);
    } elseif ($status !== 'active') {
        json_response(false, 'حساب الأستاذ غير مفعل حاليا.', [], 403);
    }

    db_query(
        'UPDATE teachers SET last_login = NOW() WHERE id = :id',
        ['id' => (int) $teacher['id']]
    );

    $_SESSION['user_id'] = (int) $teacher['id'];
    $_SESSION['user_name'] = (string) $teacher['full_name'];
    $_SESSION['user_email'] = (string) $teacher['email'];
    $_SESSION['user_role'] = 'teacher';
    $_SESSION['logged_in'] = true;

    json_response(true, 'تم تسجيل الدخول بنجاح.', [
        'user' => [
            'id' => (int) $teacher['id'],
            'full_name' => (string) $teacher['full_name'],
            'email' => (string) $teacher['email'],
            'role' => 'teacher',
            'role_label' => 'الأستاذ',
            'phone' => (string) ($teacher['phone'] ?? ''),
            'subject_name' => (string) ($teacher['subject_name'] ?? ''),
        ],
    ]);
} catch (Throwable $exception) {
    error_log('Teacher Auth Error: ' . $exception->getMessage());
    json_response(false, 'وقع خطأ أثناء تسجيل دخول الأستاذ.', [], 500);
}

