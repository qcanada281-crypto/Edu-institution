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

$action = $_GET['action'] ?? '';

if ($action === 'stats') {
    try {
        ensure_students_table();
        ensure_teachers_table();
        ensure_grades_table();
        ensure_attendance_table();
        ensure_registration_requests_table();
        
        // Get total students
        $totalStudents = db_query('SELECT COUNT(*) as count FROM students')->fetch()['count'] ?? 0;
        
        // Get total teachers
        $totalTeachers = db_query('SELECT COUNT(*) as count FROM teachers')->fetch()['count'] ?? 0;
        
        // Get total classes
        $totalClasses = db_query('SELECT COUNT(DISTINCT class_name) as count FROM students')->fetch()['count'] ?? 0;
        
        // Get total grades
        $totalGrades = db_query('SELECT COUNT(*) as count FROM grades')->fetch()['count'] ?? 0;
        
        // Get total attendance records
        $totalAttendance = db_query('SELECT COUNT(*) as count FROM attendance')->fetch()['count'] ?? 0;
        
        // Get pending registrations
        $pendingRegistrations = db_query('SELECT COUNT(*) as count FROM registration_requests WHERE status = "pending"')->fetch()['count'] ?? 0;
        
        // Get attendance rate
        $attendanceRate = 0;
        if ($totalAttendance > 0) {
            $justifiedAttendance = db_query('SELECT COUNT(*) as count FROM attendance WHERE justified = 1')->fetch()['count'] ?? 0;
            $attendanceRate = round(($justifiedAttendance / $totalAttendance) * 100, 1);
        }
        
        // Get average grades
        $averageGrades = 0;
        if ($totalGrades > 0) {
            $avgResult = db_query('SELECT AVG((continuous_score + exam_score) / 2) as avg FROM grades')->fetch();
            $averageGrades = round((float) ($avgResult['avg'] ?? 0), 2);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب الإحصائيات بنجاح',
            'data' => [
                'stats' => [
                    'total_students' => (int) $totalStudents,
                    'total_teachers' => (int) $totalTeachers,
                    'total_classes' => (int) $totalClasses,
                    'total_grades' => (int) $totalGrades,
                    'total_attendance' => (int) $totalAttendance,
                    'pending_registrations' => (int) $pendingRegistrations,
                    'attendance_rate' => (float) $attendanceRate,
                    'average_grades' => (float) $averageGrades
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب الإحصائيات'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'recent_activity') {
    try {
        // Get recent student registrations
        $recentStudents = db_query(
            'SELECT first_name, last_name, created_at FROM students ORDER BY created_at DESC LIMIT 5'
        )->fetchAll();
        
        // Get recent grades
        $recentGrades = db_query(
            'SELECT g.subject_name, s.first_name, s.last_name, g.created_at 
             FROM grades g 
             JOIN students s ON g.student_id = s.id 
             ORDER BY g.created_at DESC LIMIT 5'
        )->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب النشاط الأخير بنجاح',
            'data' => [
                'recent_students' => $recentStudents,
                'recent_grades' => $recentGrades
            ]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب النشاط'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
}
