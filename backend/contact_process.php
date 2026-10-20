<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_post_request();

$required = ['name', 'email', 'message'];
$missing = require_fields($_POST, $required);
if (!empty($missing)) {
    json_response(false, 'الاسم والبريد والرسالة حقول إلزامية.');
}

$name = clean_input((string) $_POST['name']);
$email = clean_input((string) $_POST['email']);
$phone = clean_input((string) ($_POST['phone'] ?? ''));
$subject = clean_input((string) ($_POST['subject'] ?? ''));
$message = clean_input((string) $_POST['message']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'صيغة البريد الإلكتروني غير صحيحة.');
}

if (mb_strlen($message) < 8) {
    json_response(false, 'الرسالة قصيرة جدا.');
}

try {
    db_query(
        'INSERT INTO messages (name, email, phone, subject, message, status)
         VALUES (:name, :email, :phone, :subject, :message, :status)',
        [
            'name' => $name,
            'email' => $email,
            'phone' => $phone === '' ? null : $phone,
            'subject' => $subject === '' ? null : $subject,
            'message' => $message,
            'status' => 'new',
        ]
    );

    json_response(true, 'تم إرسال الرسالة بنجاح.');
} catch (Throwable $exception) {
    error_log('Contact Process Error: ' . $exception->getMessage());
    json_response(false, 'وقع خطأ أثناء إرسال الرسالة.', [], 500);
}

