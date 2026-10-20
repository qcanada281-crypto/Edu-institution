<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../backend/config.php';
require_once __DIR__ . '/../../backend/rbac_schema.php';
require_once __DIR__ . '/../../backend/rbac_middleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure admins table exists with new schema
function ensure_new_admins_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        // Check if table exists and has the new schema
        $columns = db_query("SHOW COLUMNS FROM admins")->fetchAll(PDO::FETCH_COLUMN);
        
        // If table doesn't exist, create it with new schema
        if (empty($columns)) {
            db_query(
                "CREATE TABLE admins (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    full_name VARCHAR(120) NOT NULL,
                    email VARCHAR(160) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    role ENUM('director', 'secretary') NOT NULL,
                    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
                    last_login_at DATETIME NULL,
                    failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
                    locked_until DATETIME NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_status (status),
                    INDEX idx_role (role)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            
            // Seed default admin accounts
            seed_default_admins();
            return;
        }
        
        // Migrate old schema to new schema if needed
        if (!in_array('password_hash', $columns)) {
            db_query(
                "ALTER TABLE admins 
                 ADD COLUMN password_hash VARCHAR(255) NOT NULL AFTER email,
                 ADD COLUMN role ENUM('director', 'secretary') NOT NULL DEFAULT 'director' AFTER password_hash,
                 ADD COLUMN failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0 AFTER last_login,
                 ADD COLUMN locked_until DATETIME NULL AFTER failed_login_attempts"
            );
            
            // Migrate existing data
            $admins = db_query('SELECT id, email, code FROM admins')->fetchAll();
            foreach ($admins as $admin) {
                // Use default password for migrated accounts
                $defaultPassword = 'Admin@2026';
                $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
                
                // Determine role based on email
                $role = strpos($admin['email'], 'director') !== false || strpos($admin['email'], 'admin') !== false ? 'director' : 'secretary';
                
                db_query(
                    "UPDATE admins SET password_hash = :password_hash, role = :role WHERE id = :id",
                    [
                        'password_hash' => $passwordHash,
                        'role' => $role,
                        'id' => $admin['id']
                    ]
                );
            }
        }
        
        // Seed default admins if they don't exist
        seed_default_admins();
        
    } catch (Throwable $exception) {
        error_log('Admins table migration error: ' . $exception->getMessage());
    }
}

function seed_default_admins(): void
{
    $defaultAdmins = [
        [
            'email' => 'admin@kawkab-ouloum.ma',
            'password' => 'Admin@2026',
            'full_name' => 'المدير',
            'role' => 'director'
        ],
        [
            'email' => 'secretary@kawkab-ouloum.ma',
            'password' => 'Secretary@2026',
            'full_name' => 'السكرتارية',
            'role' => 'secretary'
        ]
    ];
    
    foreach ($defaultAdmins as $adminData) {
        $existing = db_query(
            'SELECT id FROM admins WHERE email = :email LIMIT 1',
            ['email' => $adminData['email']]
        )->fetch();
        
        if (!$existing) {
            db_query(
                'INSERT INTO admins (full_name, email, password_hash, role, status) 
                 VALUES (:full_name, :email, :password_hash, :role, :status)',
                [
                    'full_name' => $adminData['full_name'],
                    'email' => $adminData['email'],
                    'password_hash' => password_hash($adminData['password'], PASSWORD_DEFAULT),
                    'role' => $adminData['role'],
                    'status' => 'active'
                ]
            );
        }
    }
}

function json_response(bool $success, string $message, array $data = [], int $statusCode = 200): never
{
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

ensure_new_admins_table();

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $email = trim((string) ($input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    
    if ($email === '' || $password === '') {
        json_response(false, 'البريد الإلكتروني وكلمة المرور إلزاميان.', [], 422);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, 'البريد الإلكتروني غير صالح.', [], 422);
    }
    
    try {
        $admin = db_query(
            'SELECT id, full_name, email, password_hash, role, status, failed_login_attempts, locked_until 
             FROM admins 
             WHERE email = :email 
             LIMIT 1',
            ['email' => strtolower($email)]
        )->fetch();
        
        if (!$admin) {
            json_response(false, 'معلومات الدخول غير صحيحة.', [], 401);
        }
        
        // Check if account is locked
        if ($admin['locked_until'] && new DateTime($admin['locked_until']) > new DateTime()) {
            $unlockTime = new DateTime($admin['locked_until']);
            $remaining = $unlockTime->diff(new DateTime())->i;
            json_response(false, "الحساب مغلق مؤقتاً. يرجى المحاولة بعد $remaining دقيقة.", [], 423);
        }
        
        // Check account status
        if ($admin['status'] !== 'active') {
            json_response(false, 'الحساب الإداري غير مفعل حالياً.', [], 403);
        }
        
        // Verify password
        if (!password_verify($password, $admin['password_hash'])) {
            // Increment failed attempts
            $failedAttempts = (int) $admin['failed_login_attempts'] + 1;
            $lockedUntil = null;
            
            if ($failedAttempts >= 5) {
                // Lock account for 15 minutes
                $lockedUntil = (new DateTime())->add(new DateInterval('PT15M'))->format('Y-m-d H:i:s');
            }
            
            db_query(
                'UPDATE admins SET failed_login_attempts = :failed_attempts, locked_until = :locked_until WHERE id = :id',
                [
                    'failed_attempts' => $failedAttempts,
                    'locked_until' => $lockedUntil,
                    'id' => $admin['id']
                ]
            );
            
            json_response(false, 'معلومات الدخول غير صحيحة.', [], 401);
        }
        
        // Reset failed attempts on successful login
        db_query(
            'UPDATE admins SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id',
            ['id' => $admin['id']]
        );
        
        // Set session
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Get user permissions
        $permissions = get_user_permissions();
        
        json_response(true, 'تم تسجيل الدخول بنجاح.', [
            'admin' => [
                'id' => (int) $admin['id'],
                'full_name' => $admin['full_name'],
                'email' => $admin['email'],
                'role' => $admin['role'],
                'permissions' => $permissions
            ]
        ]);
        
    } catch (Throwable $exception) {
        error_log('Admin Auth Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء تسجيل الدخول.', [], 500);
    }
}

// Handle logout request
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || (isset($_GET['action']) && $_GET['action'] === 'logout')) {
    session_destroy();
    json_response(true, 'تم تسجيل الخروج بنجاح.');
}

// Handle session check
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['action']) && $_GET['action'] === 'check')) {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        json_response(true, 'الجلسة نشطة.', [
            'admin' => [
                'id' => (int) ($_SESSION['admin_id'] ?? 0),
                'full_name' => $_SESSION['admin_name'] ?? '',
                'email' => $_SESSION['admin_email'] ?? '',
                'role' => $_SESSION['admin_role'] ?? ''
            ]
        ]);
    } else {
        json_response(false, 'الجلسة منتهية.', [], 401);
    }
}

json_response(false, 'الطلب غير مدعوم.', [], 405);
