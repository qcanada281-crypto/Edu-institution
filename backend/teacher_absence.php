<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_teacher_absence_table();

ensure_post_request();

$teacherId = require_teacher_access();
$action = clean_input((string) ($_POST['action'] ?? 'submit'));

// Submit new absence
if ($action === 'submit') {
    $missing = require_fields($_POST, ['absence_date', 'absence_type', 'reason']);
    if (!empty($missing)) {
        json_response(false, 'المرجو تعبئة جميع الحقول الإلزامية.', [], 422);
    }

    $absenceDate = $_POST['absence_date'] ?? '';
    $absenceType = clean_input((string) ($_POST['absence_type'] ?? ''));
    $reason = clean_input((string) ($_POST['reason'] ?? ''));
    $isUrgent = (int) ($_POST['is_urgent'] ?? 0);
    
    // Auto-detect urgency if absence date is today or tomorrow
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    if ($absenceDate === $today || $absenceDate === $tomorrow) {
        $isUrgent = 1;
    }
    
    // Validate date
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $absenceDate)) {
        json_response(false, 'تاريخ غير صالح.', [], 422);
    }
    
    // Validate type
    $validTypes = ['sick', 'family', 'admin', 'other'];
    if (!in_array($absenceType, $validTypes, true)) {
        json_response(false, 'نوع الغياب غير صالح.', [], 422);
    }
    
    // Handle file upload
    $documentPath = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = upload_teacher_file($_FILES['document'], 'absences');
        if ($uploadResult['success']) {
            $documentPath = $uploadResult['path'];
        }
    }
    
    try {
        db_query(
            'INSERT INTO teacher_absences (teacher_id, absence_date, absence_type, reason, document_path, is_urgent, status)
             VALUES (:teacher_id, :absence_date, :absence_type, :reason, :document_path, :is_urgent, "pending")',
            [
                'teacher_id' => $teacherId,
                'absence_date' => $absenceDate,
                'absence_type' => $absenceType,
                'reason' => $reason,
                'document_path' => $documentPath,
                'is_urgent' => $isUrgent,
            ]
        );
        
        json_response(true, 'تم إرسال إشعار الغياب بنجاح. سيتم مراجعته من الإدارة.');
    } catch (Throwable $exception) {
        error_log('Teacher Absence Submit Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء إرسال الإشعار.', [], 500);
    }
}

// List teacher absences
if ($action === 'list') {
    try {
        $rows = db_query(
            'SELECT id, absence_date, absence_type, reason, document_path, is_urgent, status, admin_notes, created_at
             FROM teacher_absences
             WHERE teacher_id = :teacher_id
             ORDER BY absence_date DESC',
            ['teacher_id' => $teacherId]
        )->fetchAll();
        
        $absences = [];
        $typeLabels = [
            'sick' => 'مرضي',
            'family' => 'عائلي',
            'admin' => 'إداري',
            'other' => 'آخر'
        ];
        $statusLabels = [
            'pending' => 'قيد المراجعة',
            'approved' => 'مقبول',
            'rejected' => 'مرفوض'
        ];
        
        foreach ($rows as $row) {
            $absences[] = [
                'id' => (int) $row['id'],
                'absence_date' => (string) $row['absence_date'],
                'type' => $typeLabels[$row['absence_type']] ?? $row['absence_type'],
                'reason' => (string) $row['reason'],
                'document_path' => (string) ($row['document_path'] ?? ''),
                'is_urgent' => (int) ($row['is_urgent'] ?? 0) === 1,
                'status' => $statusLabels[$row['status']] ?? $row['status'],
                'admin_notes' => (string) ($row['admin_notes'] ?? ''),
            ];
        }
        
        json_response(true, 'تم جلب سجل الغيابات.', ['absences' => $absences]);
    } catch (Throwable $exception) {
        error_log('Teacher Absence List Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب البيانات.', [], 500);
    }
}

json_response(false, 'إجراء غير صالح.', [], 400);

// Upload helper function
function upload_teacher_file(array $file, string $folder): array {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes, true)) {
        return ['success' => false, 'error' => 'نوع الملف غير مسموح به.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'حجم الملف كبير جداً (الحد: 5MB).'];
    }
    
    $uploadDir = __DIR__ . '/../uploads/' . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => 'uploads/' . $folder . '/' . $filename];
    }
    
    return ['success' => false, 'error' => 'فشل رفع الملف.'];
}
