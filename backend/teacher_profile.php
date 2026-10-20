<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_teachers_table();

ensure_post_request();

$teacherId = require_teacher_access();
$action = clean_input((string) ($_POST['action'] ?? 'get'));

if ($action === 'get') {
    try {
        $teacher = db_query(
            'SELECT id, full_name, email, phone, subject_name, bio, specialty, avatar, rating, status, last_login, created_at, updated_at
             FROM teachers
             WHERE id = :id
             LIMIT 1',
            ['id' => $teacherId]
        )->fetch();

        if (!$teacher) {
            json_response(false, 'تعذر العثور على حساب الأستاذ.', [], 404);
        }

        json_response(true, 'تم جلب معلومات الأستاذ.', [
            'teacher' => [
                'id' => (int) $teacher['id'],
                'full_name' => (string) $teacher['full_name'],
                'email' => (string) $teacher['email'],
                'phone' => (string) ($teacher['phone'] ?? ''),
                'subject_name' => (string) ($teacher['subject_name'] ?? ''),
                'bio' => (string) ($teacher['bio'] ?? ''),
                'specialty' => (string) ($teacher['specialty'] ?? ''),
                'avatar' => (string) ($teacher['avatar'] ?? ''),
                'rating' => (string) ($teacher['rating'] ?? '5.0'),
                'status' => (string) ($teacher['status'] ?? 'active'),
                'last_login' => $teacher['last_login'],
            ],
        ]);
    } catch (Throwable $exception) {
        error_log('Teacher Profile Get Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب معلومات الأستاذ.', [], 500);
    }
}

if ($action === 'update') {
    $fullName = clean_input((string) ($_POST['full_name'] ?? ''));
    $phone = clean_input((string) ($_POST['phone'] ?? ''));
    $subjectName = clean_input((string) ($_POST['subject_name'] ?? ''));
    $bio = clean_input((string) ($_POST['bio'] ?? ''));

    if ($fullName === '') {
        json_response(false, 'الاسم الكامل إلزامي.', [], 422);
    }

    if (mb_strlen($fullName) > 120) {
        json_response(false, 'الاسم الكامل طويل جدا.', [], 422);
    }

    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
        json_response(false, 'رقم الهاتف غير صالح.', [], 422);
    }

    if (mb_strlen($subjectName) > 120) {
        json_response(false, 'اسم المادة طويل جدا.', [], 422);
    }

    if (mb_strlen($bio) > 2000) {
        json_response(false, 'نبذة الأستاذ طويلة جدا.', [], 422);
    }

    try {
        db_query(
            'UPDATE teachers
             SET full_name = :full_name,
                 phone = :phone,
                 subject_name = :subject_name,
                 bio = :bio
             WHERE id = :id',
            [
                'id' => $teacherId,
                'full_name' => $fullName,
                'phone' => $phone === '' ? null : $phone,
                'subject_name' => $subjectName === '' ? null : $subjectName,
                'bio' => $bio === '' ? null : $bio,
            ]
        );

        $_SESSION['user_name'] = $fullName;

        json_response(true, 'تم تحديث معلومات الأستاذ بنجاح.', [
            'teacher' => [
                'id' => $teacherId,
                'full_name' => $fullName,
                'phone' => $phone,
                'subject_name' => $subjectName,
                'bio' => $bio,
            ],
        ]);
    } catch (Throwable $exception) {
        error_log('Teacher Profile Update Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء تحديث معلومات الأستاذ.', [], 500);
    }
}

if ($action === 'change_code') {
    $oldCode = (string) ($_POST['old_code'] ?? '');
    $newCode = (string) ($_POST['new_code'] ?? '');
    $confirmCode = (string) ($_POST['confirm_code'] ?? '');

    if ($oldCode === '' || $newCode === '' || $confirmCode === '') {
        json_response(false, 'جميع الحقول إلزامية لتغيير الكود.', [], 422);
    }

    if ($newCode !== $confirmCode) {
        json_response(false, 'تأكيد الكود غير مطابق.', [], 422);
    }

    if (mb_strlen($newCode) < 6) {
        json_response(false, 'الكود الجديد يجب أن يتكون من 6 أحرف على الأقل.', [], 422);
    }

    try {
        $teacher = db_query(
            'SELECT id, code FROM teachers WHERE id = :id LIMIT 1',
            ['id' => $teacherId]
        )->fetch();

        if (!$teacher || !password_verify($oldCode, (string) ($teacher['code'] ?? ''))) {
            json_response(false, 'الكود الحالي غير صحيح.', [], 401);
        }

        db_query(
            'UPDATE teachers SET code = :code WHERE id = :id',
            [
                'id' => $teacherId,
                'code' => password_hash($newCode, PASSWORD_DEFAULT),
            ]
        );

        json_response(true, 'تم تغيير الكود بنجاح.');
    } catch (Throwable $exception) {
        error_log('Teacher Profile Change Code Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء تغيير الكود.', [], 500);
    }
}

// Upload Avatar
if ($action === 'upload_avatar') {
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        json_response(false, 'لم يتم اختيار ملف أو وقع خطأ في الرفع.', [], 422);
    }
    
    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowedTypes, true)) {
        json_response(false, 'نوع الملف غير مسموح به. فقط JPG و PNG.', [], 422);
    }
    
    if ($file['size'] > $maxSize) {
        json_response(false, 'حجم الملف كبير جداً (الحد: 2MB).', [], 422);
    }
    
    try {
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'teacher_' . $teacherId . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $avatarUrl = 'uploads/avatars/' . $filename;
            
            // Update database
            db_query(
                'UPDATE teachers SET avatar = :avatar WHERE id = :id',
                ['id' => $teacherId, 'avatar' => $avatarUrl]
            );
            
            json_response(true, 'تم رفع الصورة بنجاح.', ['avatar_url' => $avatarUrl]);
        } else {
            json_response(false, 'فشل رفع الملف.', [], 500);
        }
    } catch (Throwable $exception) {
        error_log('Teacher Avatar Upload Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء رفع الصورة.', [], 500);
    }
}

json_response(false, 'إجراء غير صالح.', [], 400);

