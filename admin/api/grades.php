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
    require_permission('grades.view');
    
    try {
        ensure_grades_table();
        ensure_students_table();
        
        $grades = db_query(
            "SELECT g.id, g.student_id, g.subject_name, g.continuous_score, g.exam_score, 
                    g.coefficient, g.semester, s.student_code, s.first_name, s.last_name, s.class_name
             FROM grades g
             JOIN students s ON g.student_id = s.id
             ORDER BY g.created_at DESC
             LIMIT 100"
        )->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب النقط بنجاح',
            'data' => ['grades' => $grades]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'create') {
    require_permission('grades.create');
    
    $studentCode = trim((string) ($_POST['student_code'] ?? ''));
    $subjectName = trim((string) ($_POST['subject_name'] ?? ''));
    $continuousScore = (float) ($_POST['continuous_score'] ?? 0);
    $examScore = (float) ($_POST['exam_score'] ?? 0);
    $coefficient = (float) ($_POST['coefficient'] ?? 1);
    $semester = trim((string) ($_POST['semester'] ?? 'S1'));
    
    if ($studentCode === '' || $subjectName === '') {
        echo json_encode(['success' => false, 'message' => 'كود الطالب والمادة إلزاميان'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        ensure_grades_table();
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
            "INSERT INTO grades (student_id, subject_name, continuous_score, exam_score, coefficient, semester)
             VALUES (:student_id, :subject_name, :continuous_score, :exam_score, :coefficient, :semester)",
            [
                'student_id' => (int) $student['id'],
                'subject_name' => $subjectName,
                'continuous_score' => $continuousScore,
                'exam_score' => $examScore,
                'coefficient' => $coefficient,
                'semester' => $semester
            ]
        );
        
        echo json_encode(['success' => true, 'message' => 'تمت إضافة النقطة بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في إضافة النقطة'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'update') {
    require_permission('grades.edit');
    
    $gradeId = (int) ($_POST['grade_id'] ?? 0);
    $continuousScore = (float) ($_POST['continuous_score'] ?? 0);
    $examScore = (float) ($_POST['exam_score'] ?? 0);
    $coefficient = (float) ($_POST['coefficient'] ?? 1);
    
    if ($gradeId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف النقطة غير صالح'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        ensure_grades_table();
        
        db_query(
            "UPDATE grades 
             SET continuous_score = :continuous_score, 
                 exam_score = :exam_score, 
                 coefficient = :coefficient
             WHERE id = :id",
            [
                'continuous_score' => $continuousScore,
                'exam_score' => $examScore,
                'coefficient' => $coefficient,
                'id' => $gradeId
            ]
        );
        
        echo json_encode(['success' => true, 'message' => 'تم تحديث النقطة بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في تحديث النقطة'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'delete') {
    require_permission('grades.delete');
    
    $gradeId = (int) ($_POST['grade_id'] ?? 0);
    
    if ($gradeId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف النقطة غير صالح'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        ensure_grades_table();
        
        db_query('DELETE FROM grades WHERE id = :id', ['id' => $gradeId]);
        
        echo json_encode(['success' => true, 'message' => 'تم حذف النقطة بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في حذف النقطة'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
}
