<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_teacher_homework_table();

ensure_post_request();

$teacherId = require_teacher_access();
$action = clean_input((string) ($_POST['action'] ?? 'create'));

// Create new homework
if ($action === 'create') {
    $missing = require_fields($_POST, ['title', 'class_name', 'subject', 'due_date', 'description']);
    if (!empty($missing)) {
        json_response(false, 'المرجو تعبئة جميع الحقول الإلزامية.', [], 422);
    }

    $title = clean_input((string) ($_POST['title'] ?? ''));
    $className = clean_input((string) ($_POST['class_name'] ?? ''));
    $subject = clean_input((string) ($_POST['subject'] ?? ''));
    $dueDate = $_POST['due_date'] ?? '';
    $description = clean_input((string) ($_POST['description'] ?? ''));
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) {
        json_response(false, 'تاريخ غير صالح.', [], 422);
    }
    
    // Handle file upload
    $filePath = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = upload_homework_file($_FILES['file']);
        if ($uploadResult['success']) {
            $filePath = $uploadResult['path'];
        }
    }
    
    try {
        db_query(
            'INSERT INTO teacher_homework (teacher_id, title, class_name, subject, due_date, description, file_path)
             VALUES (:teacher_id, :title, :class_name, :subject, :due_date, :description, :file_path)',
            [
                'teacher_id' => $teacherId,
                'title' => $title,
                'class_name' => $className,
                'subject' => $subject,
                'due_date' => $dueDate,
                'description' => $description,
                'file_path' => $filePath,
            ]
        );
        
        json_response(true, 'تم نشر الواجب بنجاح.');
    } catch (Throwable $exception) {
        error_log('Teacher Homework Create Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء نشر الواجب.', [], 500);
    }
}

// List teacher homework
if ($action === 'list') {
    try {
        $rows = db_query(
            'SELECT id, title, class_name, subject, due_date, description, file_path, created_at
             FROM teacher_homework
             WHERE teacher_id = :teacher_id
             ORDER BY due_date ASC, created_at DESC',
            ['teacher_id' => $teacherId]
        )->fetchAll();
        
        $homework = [];
        foreach ($rows as $row) {
            $homework[] = [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'class_name' => (string) $row['class_name'],
                'subject' => (string) $row['subject'],
                'due_date' => (string) $row['due_date'],
                'description' => (string) $row['description'],
                'has_file' => !empty($row['file_path']),
                'file_path' => (string) ($row['file_path'] ?? ''),
            ];
        }
        
        json_response(true, 'تم جلب الواجبات.', ['homework' => $homework]);
    } catch (Throwable $exception) {
        error_log('Teacher Homework List Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب الواجبات.', [], 500);
    }
}

// Delete homework
if ($action === 'delete') {
    $hwId = (int) ($_POST['id'] ?? 0);
    if ($hwId <= 0) {
        json_response(false, 'معرّف غير صالح.', [], 422);
    }
    
    try {
        $stmt = db_query(
            'DELETE FROM teacher_homework WHERE id = :id AND teacher_id = :teacher_id',
            ['id' => $hwId, 'teacher_id' => $teacherId]
        );
        
        if ($stmt->rowCount() === 0) {
            json_response(false, 'لم يتم العثور على الواجب.', [], 404);
        }
        
        json_response(true, 'تم حذف الواجب بنجاح.');
    } catch (Throwable $exception) {
        error_log('Teacher Homework Delete Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء الحذف.', [], 500);
    }
}

json_response(false, 'إجراء غير صالح.', [], 400);

// Upload helper function
function upload_homework_file(array $file): array {
    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/jpg',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowedTypes, true)) {
        return ['success' => false, 'error' => 'نوع الملف غير مسموح به.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'حجم الملف كبير جداً (الحد: 10MB).'];
    }
    
    $uploadDir = __DIR__ . '/../uploads/homework/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => 'uploads/homework/' . $filename];
    }
    
    return ['success' => false, 'error' => 'فشل رفع الملف.'];
}
