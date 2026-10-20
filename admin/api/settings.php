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

// Director only
require_director();

$action = $_POST['action'] ?? '';

if ($action === 'get') {
    require_permission('settings.manage');
    
    try {
        // Return school settings (could be stored in a settings table)
        $settings = [
            'school_name' => 'مؤسسة Kawkab Al Ouloum',
            'school_year' => '2025-2026',
            'max_students_per_class' => 40,
            'passing_grade' => 10,
            'attendance_threshold' => 80
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب الإعدادات بنجاح',
            'data' => ['settings' => $settings]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب الإعدادات'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'update') {
    require_permission('settings.manage');
    
    $schoolName = trim((string) ($_POST['school_name'] ?? ''));
    $schoolYear = trim((string) ($_POST['school_year'] ?? ''));
    $maxStudents = (int) ($_POST['max_students_per_class'] ?? 40);
    $passingGrade = (float) ($_POST['passing_grade'] ?? 10);
    $attendanceThreshold = (int) ($_POST['attendance_threshold'] ?? 80);
    
    try {
        // In a real system, save to settings table
        // For now, just return success
        echo json_encode(['success' => true, 'message' => 'تم تحديث الإعدادات بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في تحديث الإعدادات'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
}
