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
    require_permission('registrations.view');
    
    try {
        ensure_registration_requests_table();
        
        $requests = db_query(
            'SELECT id, student_code, first_name, last_name, class_name, level, email, phone, status, created_at 
             FROM registration_requests 
             ORDER BY created_at DESC'
        )->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم جلب طلبات التسجيل بنجاح',
            'data' => ['requests' => $requests]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في جلب البيانات'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'approve') {
    require_permission('registrations.approve');
    
    $requestId = (int) ($_POST['request_id'] ?? 0);
    
    if ($requestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الطلب غير صالح'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        ensure_registration_requests_table();
        ensure_students_table();
        
        // Get request details
        $request = db_query(
            'SELECT * FROM registration_requests WHERE id = :id LIMIT 1',
            ['id' => $requestId]
        )->fetch();
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'الطلب غير موجود'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($request['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'الطلب تمت معالجته بالفعل'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Check if student code already exists
        $existingStudent = db_query(
            'SELECT id FROM students WHERE student_code = :student_code LIMIT 1',
            ['student_code' => $request['student_code']]
        )->fetch();
        
        if ($existingStudent) {
            echo json_encode(['success' => false, 'message' => 'كود الطالب مستخدم بالفعل'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Create student record
        db_query(
            'INSERT INTO students (student_code, first_name, last_name, birth_date, gender, class_name, level, email, phone, address, guardian_name, guardian_phone, guardian_email)
             VALUES (:student_code, :first_name, :last_name, :birth_date, :gender, :class_name, :level, :email, :phone, :address, :guardian_name, :guardian_phone, :guardian_email)',
            [
                'student_code' => $request['student_code'],
                'first_name' => $request['first_name'],
                'last_name' => $request['last_name'],
                'birth_date' => $request['birth_date'],
                'gender' => $request['gender'],
                'class_name' => $request['class_name'],
                'level' => $request['level'],
                'email' => $request['email'],
                'phone' => $request['phone'],
                'address' => $request['address'],
                'guardian_name' => $request['guardian_name'],
                'guardian_phone' => $request['guardian_phone'],
                'guardian_email' => $request['guardian_email']
            ]
        );
        
        // Update request status
        db_query(
            'UPDATE registration_requests SET status = :status, reviewed_at = NOW(), reviewed_by = :reviewed_by WHERE id = :id',
            [
                'status' => 'approved',
                'reviewed_by' => (int) $_SESSION['admin_id'],
                'id' => $requestId
            ]
        );
        
        echo json_encode(['success' => true, 'message' => 'تمت الموافقة على الطلب بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في معالجة الطلب'], JSON_UNESCAPED_UNICODE);
    }
} elseif ($action === 'reject') {
    require_permission('registrations.approve');
    
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $notes = trim((string) ($_POST['notes'] ?? ''));
    
    if ($requestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الطلب غير صالح'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    try {
        ensure_registration_requests_table();
        
        // Get request details
        $request = db_query(
            'SELECT status FROM registration_requests WHERE id = :id LIMIT 1',
            ['id' => $requestId]
        )->fetch();
        
        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'الطلب غير موجود'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($request['status'] !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'الطلب تمت معالجته بالفعل'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Update request status
        db_query(
            'UPDATE registration_requests SET status = :status, notes = :notes, reviewed_at = NOW(), reviewed_by = :reviewed_by WHERE id = :id',
            [
                'status' => 'rejected',
                'notes' => $notes ?: null,
                'reviewed_by' => (int) $_SESSION['admin_id'],
                'id' => $requestId
            ]
        );
        
        echo json_encode(['success' => true, 'message' => 'تم رفض الطلب بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ في معالجة الطلب'], JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح'], JSON_UNESCAPED_UNICODE);
}
