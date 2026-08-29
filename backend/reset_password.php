<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'طريقة الطلب غير صالحة.', [], 405);
}

$studentCode = clean_input((string) ($_POST['student_code'] ?? ''));
$email = clean_input((string) ($_POST['email'] ?? ''));
$phone = clean_input((string) ($_POST['phone'] ?? ''));

if ($studentCode === '' || $email === '' || $phone === '') {
    json_response(false, 'المرجو ملء جميع الحقول المطلوبة (الكود، البريد الهاتفي، ورقم الهاتف).', [], 400);
}

try {
    ensure_students_table();
    ensure_messages_table();
    // البحث عن التلميذ في قاعدة البيانات للتأكد من وجوده وجلب بياناته الكاملة للمدير
    $student = db_query(
        "SELECT student_code, first_name, last_name, class_name, level, birth_date 
         FROM students 
         WHERE student_code = :code OR email = :email OR guardian_email = :email OR phone = :phone OR guardian_phone = :phone 
         LIMIT 1",
        ['code' => $studentCode, 'email' => $email, 'phone' => $phone]
    )->fetch(PDO::FETCH_ASSOC);

    $extraInfo = "";
    if ($student) {
        $fullName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
        $extraInfo = "\n\n📌 [بيانات التلميذ المسجلة بالمؤسسة]:\n" .
                     "- الاسم الكامل: " . $fullName . "\n" .
                     "- الكود الرسمي: " . ($student['student_code'] ?? $studentCode) . "\n" .
                     "- القسم والمستوى: " . ($student['class_name'] ?? '') . " (" . ($student['level'] ?? '') . ")\n" .
                     "- تاريخ الازدياد: " . ($student['birth_date'] ?? 'غير محدد');
    } else {
        $extraInfo = "\n\n⚠️ (ملاحظة: لم يتم العثور على مطابقة تلقائية بهذا الكود أو الهاتف في قاعدة البيانات، يرجى البحث في لائحة التلاميذ باسم ولي الأمر أو الهاتف).";
    }

    $subject = "طلب استعادة الوصول ورقم السري: " . $studentCode;
    $body = "قام التلميذ/ولي الأمر بطلب استعادة كلمة المرور والدخول:\n" .
            "- الكود المدخل: " . $studentCode . "\n" .
            "- البريد الإلكتروني: " . $email . "\n" .
            "- رقم الهاتف: " . $phone .
            $extraInfo;

    db_query(
        'INSERT INTO messages (name, email, phone, subject, message, status, created_at)
         VALUES (:name, :email, :phone, :subject, :message, "new", NOW())',
        [
            'name' => 'طلب استعادة (' . $studentCode . ')',
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $body,
        ]
    );

    json_response(true, 'تم إرسال طلب استعادة كلمة المرور بنجاح، ووصلت لإدارة المؤسسة وسيتم التواصل معكم قريباً.');
} catch (Throwable $e) {
    error_log('Reset Password Endpoint Error: ' . $e->getMessage());
    json_response(false, 'وقع خطأ أثناء إرسال الطلب، المرجو المحاولة لاحقاً.', ['debug' => $e->getMessage()], 500);
}
