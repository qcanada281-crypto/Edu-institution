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
    require_permission('classes.view');
    
    try {
        // Get unique classes from students table
        $classes = db_query(
            "SELECT DISTINCT class_name, level, COUNT(*) as student_count
             FROM students 
             GROUP BY class_name, level
             ORDER BY class_name"
        )->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب الأقسام بنجاح',
            'data' => ['classes' => $classes]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'create') {
    require_permission('classes.create');
    
    $className = trim((string) ($_POST['class_name'] ?? ''));
    $level = trim((string) ($_POST['level'] ?? ''));
    
    if ($className === '' || $level === '') {
        echo json_encode(['success' => false, 'message' => 'القسم والمستوى إلزاميان'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        // Classes are derived from students, so we just return success
        // In a real system, you might have a separate classes table
        echo json_encode(['success' => true, 'message' => 'الأقسام تُستمد من بيانات التلاميذ'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في إنشاء القسم'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'delete') {
    require_permission('classes.delete');
    
    $className = trim((string) ($_POST['class_name'] ?? ''));
    
    if ($className === '') {
        echo json_encode(['success' => false, 'message' => 'اسم القسم إلزامي'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        // Check if class has students
        $hasStudents = db_query(
            'SELECT COUNT(*) as count FROM students WHERE class_name = :class_name',
            ['class_name' => $className]
        )->fetch();
        
        if ((int) $hasStudents['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'لا يمكن حذف قسم به تلاميذ'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        echo json_encode(['success' => true, 'message' => 'تم حذف القسم بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في حذف القسم'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
}
