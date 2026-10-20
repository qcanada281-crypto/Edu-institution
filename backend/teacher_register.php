<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_teachers_table();
ensure_teacher_requests_table();

ensure_post_request();

$missing = require_fields($_POST, ['full_name', 'subject_name', 'email', 'phone', 'code']);
if (!empty($missing)) {
    json_response(false, 'المرجو تعبئة جميع الحقول الإلزامية.', [], 422);
}

$fullName = clean_input((string) $_POST['full_name']);
$subjectName = clean_input((string) $_POST['subject_name']);
$email = strtolower(clean_input((string) $_POST['email']));
$phone = clean_input((string) $_POST['phone']);
$code = (string) $_POST['code'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'البريد الإلكتروني غير صالح.', [], 422);
}

if (!str_ends_with($email, '@gmail.com')) {
    json_response(false, 'مرجو استعمال حساب Gmail للتسجيل كأستاذ.', [], 422);
}

if (!preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
    json_response(false, 'رقم الهاتف غير صالح.', [], 422);
}

if (mb_strlen($code) < 6) {
    json_response(false, 'الكود السري يجب أن يتكون من 6 أحرف على الأقل.', [], 422);
}

try {
    $duplicate = db_query(
        'SELECT id FROM teachers WHERE email = :email LIMIT 1',
        ['email' => $email]
    )->fetch();

    if ($duplicate) {
        json_response(false, 'هذا البريد الإلكتروني مسجل مسبقا كأستاذ.', [], 409);
    }

    $duplicateRequest = db_query(
        'SELECT id FROM teacher_requests WHERE email = :email LIMIT 1',
        ['email' => $email]
    )->fetch();

    if ($duplicateRequest) {
        json_response(false, 'طلب التسجيل بهذا البريد الإلكتروني موجود مسبقا.', [], 409);
    }

    db_query(
        'INSERT INTO teacher_requests (full_name, email, code, phone, subject_name, status)
         VALUES (:full_name, :email, :code, :phone, :subject_name, :status)',
        [
            'full_name' => $fullName,
            'email' => $email,
            'code' => password_hash($code, PASSWORD_DEFAULT),
            'phone' => $phone,
            'subject_name' => $subjectName,
            'status' => 'pending', // Key part: requires admin approval
        ]
    );

    json_response(true, 'تم استلام طلب التسجيل بنجاح، وهو الآن قيد المراجعة من طرف الإدارة.', [
        'teacher_id' => (int) db_connection()->lastInsertId(),
    ]);
} catch (Throwable $exception) {
    error_log('Teacher Register Error: ' . $exception->getMessage());
    $sysMsg = strtolower($exception->getMessage());
    $userMsg = 'وقع خطأ أثناء تسجيل طلب الأستاذ.';
    
    if (strpos($sysMsg, 'duplicate') !== false || strpos($sysMsg, '1062') !== false || $exception->getCode() == 23000) {
        $userMsg = 'خطأ: البريد الإلكتروني مسجل مسبقاً في النظام.';
    }
    
    json_response(false, $userMsg, [], 500);
}
