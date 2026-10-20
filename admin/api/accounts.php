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

if ($action === 'list') {
    require_permission('accounts.manage');
    
    try {
        ensure_new_admins_table();
        
        $admins = db_query(
            'SELECT id, full_name, email, role, status, last_login_at, created_at 
             FROM admins 
             ORDER BY created_at DESC'
        )->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب الحسابات بنجاح',
            'data' => ['admins' => $admins]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'create') {
    require_permission('accounts.create');
    
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = trim((string) ($_POST['role'] ?? ''));
    
    if ($fullName === '' || $email === '' || $password === '' || $role === '') {
        echo json_encode(['success' => false, 'message' => 'جميع الحقول إلزامية'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!in_array($role, ['director', 'secretary'], true)) {
        echo json_encode(['success' => false, 'message' => 'الدور غير صالح'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني غير صالح'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        ensure_new_admins_table();
        
        // Check if email already exists
        $existing = db_query(
            'SELECT id FROM admins WHERE email = :email LIMIT 1',
            ['email' => strtolower($email)]
        )->fetch();
        
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني مستخدم بالفعل'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        db_query(
            'INSERT INTO admins (full_name, email, password_hash, role, status) 
             VALUES (:full_name, :email, :password_hash, :role, :status)',
            [
                'full_name' => $fullName,
                'email' => strtolower($email),
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'status' => 'active'
            ]
        );
        
        echo json_encode(['success' => true, 'message' => 'تم إنشاء الحساب بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في إنشاء الحساب'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'delete') {
    require_permission('accounts.manage');
    
    $adminId = (int) ($_POST['admin_id'] ?? 0);
    
    if ($adminId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الحساب غير صالح'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Prevent deleting own account
    if ($adminId === (int) ($_SESSION['admin_id'] ?? 0)) {
        echo json_encode(['success' => false, 'message' => 'لا يمكن حذف حسابك الخاص'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        ensure_new_admins_table();
        
        db_query('DELETE FROM admins WHERE id = :id', ['id' => $adminId]);
        
        echo json_encode(['success' => true, 'message' => 'تم حذف الحساب بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في حذف الحساب'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'change_status') {
    require_permission('accounts.manage');
    
    $adminId = (int) ($_POST['admin_id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));
    
    if ($adminId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الحساب غير صالح'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (!in_array($status, ['active', 'inactive', 'suspended'], true)) {
        echo json_encode(['success' => false, 'message' => 'الحالة غير صالحة'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Prevent deactivating own account
    if ($adminId === (int) ($_SESSION['admin_id'] ?? 0) && $status !== 'active') {
        echo json_encode(['success' => false, 'message' => 'لا يمكن تعطيل حسابك الخاص'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        ensure_new_admins_table();
        
        db_query(
            'UPDATE admins SET status = :status WHERE id = :id',
            ['status' => $status, 'id' => $adminId]
        );
        
        echo json_encode(['success' => true, 'message' => 'تم تحديث حالة الحساب بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في تحديث الحالة'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
}
