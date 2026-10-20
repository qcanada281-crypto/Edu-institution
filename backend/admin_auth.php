<?php
declare(strict_types=1);

// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/admin_auth_errors.log');

// Start output buffering to prevent any unwanted output
ob_start();

// Log request details
$requestLog = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
    'post_data' => array_intersect_key($_POST, array_flip(['email', 'role'])), // Log only safe fields
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
];
error_log('[ADMIN_AUTH] Request: ' . json_encode($requestLog));

// CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';

ensure_post_request();

$missing = require_fields($_POST, ['email', 'code']);
if (!empty($missing)) {
    json_response(false, 'البريد الإلكتروني والكود إلزاميان.');
}

$email = clean_input((string) $_POST['email']);
$code = (string) $_POST['code'];
$requestedRole = normalize_admin_role(clean_input((string) ($_POST['role'] ?? 'director')));
if ($requestedRole === '') {
    $requestedRole = 'director';
}

// Handle teacher login via teachers table
if ($requestedRole === 'teacher') {
    ensure_teachers_table();
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, 'البريد الإلكتروني غير صالح.', [], 422);
    }
    
    if (!str_ends_with($email, '@gmail.com')) {
        json_response(false, 'مرجو استعمال حساب Gmail لتسجيل دخول الأساتذة.', [], 422);
    }
    
    try {
        $teacher = db_query(
            'SELECT id, full_name, email, code, status, phone, subject_name
             FROM teachers
             WHERE email = :email
             LIMIT 1',
            ['email' => strtolower($email)]
        )->fetch();
        
        if (!$teacher || !password_verify($code, (string) ($teacher['code'] ?? ''))) {
            json_response(false, 'معلومات الدخول غير صحيحة.', [], 401);
        }
        
        if (($teacher['status'] ?? 'active') !== 'active') {
            json_response(false, 'حساب الأستاذ غير مفعل حالياً.', [], 403);
        }
        
        db_query(
            'UPDATE teachers SET last_login = NOW() WHERE id = :id',
            ['id' => (int) $teacher['id']]
        );
        
        $_SESSION['user_id'] = (int) $teacher['id'];
        $_SESSION['user_name'] = (string) $teacher['full_name'];
        $_SESSION['user_email'] = (string) $teacher['email'];
        $_SESSION['user_role'] = 'teacher';
        $_SESSION['logged_in'] = true;
        
        json_response(true, 'تم تسجيل الدخول بنجاح.', [
            'user' => [
                'id' => (int) $teacher['id'],
                'full_name' => (string) $teacher['full_name'],
                'email' => (string) $teacher['email'],
                'role' => 'teacher',
                'role_label' => role_label('teacher'),
                'phone' => (string) ($teacher['phone'] ?? ''),
                'subject_name' => (string) ($teacher['subject_name'] ?? ''),
            ],
        ]);
    } catch (Throwable $exception) {
        error_log('Teacher Auth via Admin Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء تسجيل دخول الأستاذ.', [], 500);
    }
    exit;
}

ensure_admins_table();

$loginProfile = get_admin_login_profile($requestedRole);
if (!$loginProfile) {
    json_response(false, 'نوع الإدارة غير صالح.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'البريد الإلكتروني غير صالح.');
}

try {
    $emailLower = strtolower($email);
    $roleEmail = strtolower((string) ($loginProfile['email'] ?? ''));
    $roleCode = (string) ($loginProfile['code'] ?? '');
    $roleLabel = (string) ($loginProfile['label'] ?? 'الإدارة');
    $roleName = (string) ($loginProfile['full_name'] ?? 'Admin');
    $roleValue = normalize_admin_role((string) ($loginProfile['role'] ?? 'director'));

    if ($emailLower !== $roleEmail) {
        json_response(false, 'البريد الإلكتروني لا يطابق حساب ' . $roleLabel . '.', [], 401);
    }

    // Keep known credentials for each role available for recovery.
    $defaultRoleHash = password_hash($roleCode, PASSWORD_DEFAULT);
    $existingRoleAccount = db_query(
        'SELECT id FROM admins WHERE email = :email LIMIT 1',
        ['email' => $roleEmail]
    )->fetch();

    if ($existingRoleAccount) {
        db_query(
            'UPDATE admins
             SET full_name = :full_name, code = :code, status = :status
             WHERE id = :id',
            [
                'id' => (int) $existingRoleAccount['id'],
                'full_name' => $roleName,
                'code' => $defaultRoleHash,
                'status' => 'active',
            ]
        );
    } else {
        db_query(
            'INSERT INTO admins (full_name, email, code, status)
             VALUES (:full_name, :email, :code, :status)',
            [
                'full_name' => $roleName,
                'email' => $roleEmail,
                'code' => $defaultRoleHash,
                'status' => 'active',
            ]
        );
    }

    $admin = db_query(
        'SELECT id, full_name, email, code, status
         FROM admins
         WHERE email = :email
         LIMIT 1',
        ['email' => $roleEmail]
    )->fetch();

    if (!$admin || !password_verify($code, (string) ($admin['code'] ?? ''))) {
        json_response(false, 'معلومات الدخول غير صحيحة.', [], 401);
    }

    if ($admin['status'] !== 'active') {
        json_response(false, 'الحساب الإداري غير مفعل حالياً.', [], 403);
    }

    db_query(
        'UPDATE admins SET last_login = NOW() WHERE id = :id',
        ['id' => (int) $admin['id']]
    );

    $_SESSION['user_id'] = (int) $admin['id'];
    $_SESSION['user_name'] = $admin['full_name'];
    $_SESSION['user_email'] = $admin['email'];
    $_SESSION['user_role'] = $roleValue;
    $_SESSION['logged_in'] = true;

    json_response(true, 'تم تسجيل دخول ' . $roleLabel . ' بنجاح.', [
        'user' => [
            'id' => (int) $admin['id'],
            'full_name' => $admin['full_name'],
            'email' => $admin['email'],
            'role' => $roleValue,
        ],
    ]);
} catch (PDOException $exception) {
    error_log('[DB ERROR] Port 3308 - ' . $exception->getMessage());
    json_response(false, 'تعذر الاتصال بقاعدة البيانات (port 3308). تأكد من تشغيل MySQL في XAMPP.', [], 500);
} catch (Throwable $exception) {
    error_log('[Admin Auth Error] ' . $exception->getMessage());
    json_response(false, 'وقع خطأ أثناء تسجيل الدخول.', [], 500);
}