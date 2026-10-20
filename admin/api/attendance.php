<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../backend/config.php';
require_once __DIR__ . '/../../backend/rbac_schema.php';
require_once __DIR__ . '/../../backend/rbac_middleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin authentication
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'list') {
    require_permission('attendance.view');
    
    try {
        ensure_attendance_table();
        ensure_students_table();
        
        $attendance = db_query(
            "SELECT a.id, a.student_id, a.absence_date, a.session_label, a.absence_subject, 
                    a.justified, a.notes, s.student_code, s.first_name, s.last_name, s.class_name
             FROM attendance a
             JOIN students s ON a.student_id = s.id
             ORDER BY a.absence_date DESC
             LIMIT 100"
        )->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب سجلات الغياب بنجاح',
            'data' => ['attendance' => $attendance]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'create') {
    require_permission('attendance.create');
    
    $studentCode = trim((string) ($_POST['student_code'] ?? ''));
    $absenceDate = trim((string) ($_POST['absence_date'] ?? ''));
    $sessionLabel = trim((string) ($_POST['session_label'] ?? ''));
    $absenceSubject = trim((string) ($_POST['absence_subject'] ?? ''));
    $justified = (int) ($_POST['justified'] ?? 0);
    $notes = trim((string) ($_POST['notes'] ?? ''));
    
    if ($studentCode === '' || $absenceDate === '' || $sessionLabel === '') {
        echo json_encode(['success' => false, 'message' => 'كود الطالب والتاريخ والحصة إلزامية'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        ensure_attendance_table();
        ensure_students_table();
        
        // Get student ID
        $student = db_query(
            'SELECT id FROM students WHERE student_code = :student_code LIMIT 1',
            ['student_code' => $studentCode]
        )->fetch();
        
        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'الطالب غير موجود'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        db_query(
            "INSERT INTO attendance (student_id, absence_date, session_label, absence_subject, justified, notes)
             VALUES (:student_id, :absence_date, :session_label, :absence_subject, :justified, :notes)",
            [
                'student_id' => (int) $student['id'],
                'absence_date' => $absenceDate,
                'session_label' => $sessionLabel,
                'absence_subject' => $absenceSubject ?: null,
                'justified' => $justified,
                'notes' => $notes ?: null
            ]
        );
        
        echo json_encode(['success' => true, 'message' => 'تم تسجيل الغياب بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في تسجيل الغياب'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'update') {
    require_permission('attendance.edit');
    
    $attendanceId = (int) ($_POST['attendance_id'] ?? 0);
    $justified = (int) ($_POST['justified'] ?? 0);
    $notes = trim((string) ($_POST['notes'] ?? ''));
    
    if ($attendanceId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الغياب غير صالح'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        ensure_attendance_table();
        
        db_query(
            "UPDATE attendance 
             SET justified = :justified, notes = :notes
             WHERE id = :id",
            [
                'justified' => $justified,
                'notes' => $notes ?: null,
                'id' => $attendanceId
            ]
        );
        
        echo json_encode(['success' => true, 'message' => 'تم تحديث الغياب بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في تحديث الغياب'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'delete') {
    require_permission('attendance.delete');
    
    $attendanceId = (int) ($_POST['attendance_id'] ?? 0);
    
    if ($attendanceId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الغياب غير صالح'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        ensure_attendance_table();
        
        db_query('DELETE FROM attendance WHERE id = :id', ['id' => $attendanceId]);
        
        echo json_encode(['success' => true, 'message' => 'تم حذف الغياب بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في حذف الغياب'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
}
