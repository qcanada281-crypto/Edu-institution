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
    require_permission('lessons.view');
    
    try {
        ensure_lessons_table();
        $lessons = db_query(
            'SELECT id, title, lesson_type, level, subject_name, is_published, created_at 
             FROM lessons 
             ORDER BY created_at DESC'
        )->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب الدروس بنجاح',
            'data' => ['lessons' => $lessons]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'create') {
    require_permission('lessons.create');
    
    // TODO: Implement lesson creation
    echo json_encode(['success' => false, 'message' => 'قيد التطوير'], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'update') {
    require_permission('lessons.edit');
    
    // TODO: Implement lesson update
    echo json_encode(['success' => false, 'message' => 'قيد التطوير'], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'delete') {
    require_permission('lessons.delete');
    
    // TODO: Implement lesson deletion
    echo json_encode(['success' => false, 'message' => 'قيد التطوير'], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
}
