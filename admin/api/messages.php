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
    require_permission('messages.view');
    
    try {
        $messages = db_query(
            'SELECT id, name, email, subject, status, created_at 
             FROM messages 
             ORDER BY created_at DESC'
        )->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب الرسائل بنجاح',
            'data' => ['messages' => $messages]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'mark_read') {
    require_permission('messages.manage');
    
    // TODO: Implement message status update
    echo json_encode(['success' => false, 'message' => 'قيد التطوير'], JSON_UNESCAPED_UNICODE);
} elseif ($action === 'delete') {
    require_permission('messages.manage');
    
    // TODO: Implement message deletion
    echo json_encode(['success' => false, 'message' => 'قيد التطوير'], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
}
