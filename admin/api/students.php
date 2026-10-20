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
    require_permission('students.view');
    
    try {
        ensure_students_table();
        $students = db_query(
            'SELECT id, student_code, first_name, last_name, class_name, level, guardian_name, created_at 
             FROM students 
             ORDER BY created_at DESC'
        )->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب التلاميذ بنجاح',
            'data' => ['students' => $students]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'create') {
    require_permission('students.create');
    
    // TODO: Implement student creation
    echo json_encode(['success' => false, 'message' => 'قيد التطوير'], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'update') {
    require_permission('students.edit');
    
    // TODO: Implement student update
    echo json_encode(['success' => false, 'message' => 'قيد التطوير'], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'delete') {
    require_permission('students.delete');
    
    // TODO: Implement student deletion
    echo json_encode(['success' => false, 'message' => 'قيد التطوير'], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
}
