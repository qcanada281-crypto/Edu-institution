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
    require_permission('teachers.view');
    
    try {
        ensure_teachers_table();
        $teachers = db_query(
            'SELECT id, full_name, email, phone, subject_name, status, created_at 
             FROM teachers 
             ORDER BY created_at DESC'
        )->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب الأساتذة بنجاح',
            'data' => ['teachers' => $teachers]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'create') {
    require_permission('teachers.create');
    
    // TODO: Implement teacher creation
    echo json_encode(['success' => false, 'message' => 'قيد التطوير'], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'update') {
    require_permission('teachers.edit');
    
    // TODO: Implement teacher update
    echo json_encode(['success' => false, 'message' => 'قيد التطوير'], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'delete') {
    require_permission('teachers.delete');
    
    // TODO: Implement teacher deletion
    echo json_encode(['success' => false, 'message' => 'قيد التطوير'], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
}
