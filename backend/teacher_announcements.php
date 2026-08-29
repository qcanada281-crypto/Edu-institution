<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_post_request();
$teacherId = require_teacher_access();
ensure_announcements_table();

$action = clean_input((string) ($_POST['action'] ?? ''));

if ($action === 'list') {
    try {
        $announcements = db_query(
            "SELECT id, title, content, type, class_name, file_path, created_at 
             FROM announcements 
             WHERE teacher_id = :teacher_id 
             ORDER BY created_at DESC",
            ['teacher_id' => $teacherId]
        )->fetchAll(PDO::FETCH_ASSOC);

        json_response(true, 'تم جلب الإعلانات بنجاح.', ['announcements' => $announcements]);
    } catch (Throwable $e) {
        error_log('Teacher Announcements List Error: ' . $e->getMessage());
        json_response(false, 'تعذر جلب الإعلانات.', [], 500);
    }
}

if ($action === 'create') {
    $title = clean_input((string) ($_POST['title'] ?? ''));
    $content = clean_input((string) ($_POST['content'] ?? ''));
    $type = clean_input((string) ($_POST['type'] ?? 'normal'));
    $className = clean_input((string) ($_POST['class_name'] ?? 'all'));

    if ($title === '' || $content === '') {
        json_response(false, 'العنوان والمحتوى حقول إجبارية.');
    }

    if (!in_array($type, ['normal', 'urgent'], true)) {
        $type = 'normal';
    }

    if ($className === 'all') {
        $className = null;
    }

    // Handle optional file attachment
    $filePath = null;
    if (!empty($_FILES['file']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
        $maxSize = 10 * 1024 * 1024; // 10 MB

        $originalName = basename($_FILES['file']['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed, true)) {
            json_response(false, 'نوع الملف غير مسموح به. المسموح: صور، PDF، Word، Excel، PowerPoint.');
        }

        if ($_FILES['file']['size'] > $maxSize) {
            json_response(false, 'حجم الملف كبير جداً. الحد الأقصى 10 ميغابايت.');
        }

        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            json_response(false, 'وقع خطأ أثناء رفع الملف.');
        }

        $uploadDir = __DIR__ . '/../uploads/announcements/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uniqueName = 'ann_' . $teacherId . '_' . uniqid() . '.' . $ext;
        $destPath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
            json_response(false, 'تعذر حفظ الملف على الخادم.');
        }

        $filePath = 'uploads/announcements/' . $uniqueName;
    }

    try {
        db_query(
            "INSERT INTO announcements (title, content, type, class_name, teacher_id, file_path) 
             VALUES (:title, :content, :type, :class_name, :teacher_id, :file_path)",
            [
                'title' => $title,
                'content' => $content,
                'type' => $type,
                'class_name' => $className,
                'teacher_id' => $teacherId,
                'file_path' => $filePath,
            ]
        );
        json_response(true, 'تم نشر الإعلان بنجاح.');
    } catch (Throwable $e) {
        error_log('Teacher Announcements Create Error: ' . $e->getMessage());
        json_response(false, 'تعذر نشر الإعلان.', [], 500);
    }
}

if ($action === 'delete') {
    $id = (int) ($_POST['announcement_id'] ?? 0);
    if ($id <= 0) {
        json_response(false, 'رقم الإعلان غير صحيح.');
    }

    try {
        $existing = db_query(
            "SELECT id, file_path FROM announcements WHERE id = :id AND teacher_id = :teacher_id LIMIT 1",
            ['id' => $id, 'teacher_id' => $teacherId]
        )->fetch();

        if (!$existing) {
            json_response(false, 'الإعلان غير موجود أو لا تملك صلاحية حذفه.');
        }

        // Delete associated file if it exists
        if (!empty($existing['file_path'])) {
            $fullPath = __DIR__ . '/../' . $existing['file_path'];
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        db_query("DELETE FROM announcements WHERE id = :id", ['id' => $id]);
        json_response(true, 'تم حذف الإعلان بنجاح.');
    } catch (Throwable $e) {
        error_log('Teacher Announcements Delete Error: ' . $e->getMessage());
        json_response(false, 'تعذر حذف الإعلان.', [], 500);
    }
}

json_response(false, 'الإجراء غير معروف.', [], 400);

