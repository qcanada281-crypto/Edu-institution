<?php
declare(strict_types=1);

// --- Data Security & Hardened Session Config ---
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Global baseline security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
}

const DB_HOST = '127.0.0.1:3306';
const DB_NAME = 'portfolio_db';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

// Admin credentials moved to new admin system
// See admin/api/auth.php for authentication


/**
 * Database Connection with Professional Error Handling and Port Fallback
 * Connects to XAMPP default port 3306 with auto-fallback to 3308 if needed
 */
function db_connection(): PDO
{
    static $pdo = null;

    // Return existing connection (singleton pattern - best practice)
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Database configuration with port support
    $host = getenv('DB_HOST') ?: DB_HOST;
    $name = getenv('DB_NAME') ?: DB_NAME;
    $user = getenv('DB_USER') ?: DB_USER;
    $pass = getenv('DB_PASS') ?: DB_PASS;
    $charset = getenv('DB_CHARSET') ?: DB_CHARSET;

    // Helper to build DSN
    $buildDsn = function(string $h) use ($name, $charset): string {
        if (strpos($h, ':') !== false) {
            list($hostPart, $portPart) = explode(':', $h);
            return "mysql:host={$hostPart};port={$portPart};dbname={$name};charset={$charset}";
        }
        return "mysql:host={$h};dbname={$name};charset={$charset}";
    };

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci"
    ];

    $candidates = [$host];
    // Add fallback ports (3306 for XAMPP, 3308 for WAMP)
    if (strpos($host, '3308') !== false) {
        $candidates[] = str_replace('3308', '3306', $host);
    } elseif (strpos($host, '3306') !== false) {
        $candidates[] = str_replace('3306', '3308', $host);
    } else {
        $candidates[] = '127.0.0.1:3306';
    }

    $lastException = null;
    foreach (array_unique($candidates) as $candidateHost) {
        try {
            $dsn = $buildDsn($candidateHost);
            $pdo = new PDO($dsn, $user, $pass, $options);
            return $pdo;
        } catch (PDOException $exception) {
            $lastException = $exception;
            error_log("[DB CONNECT ATTEMPT FAILED: {$candidateHost}] " . $exception->getMessage());
        }
    }

    error_log('[DB ERROR] ' . ($lastException ? $lastException->getMessage() : 'Unknown error'));
    
    // Return JSON error for API calls, or throw exception for direct use
    if (function_exists('json_response')) {
        json_response(false, 'تعذر الاتصال بقاعدة البيانات. الرجاء التحقق من إعدادات XAMPP.', [], 500);
    }
    throw new Exception('Database connection failed: ' . ($lastException ? $lastException->getMessage() : 'Unknown error'));
}

/**
 * Test database connection - returns true/false for health checks
 */
function test_db_connection(): bool
{
    try {
        $pdo = db_connection();
        $stmt = $pdo->query('SELECT 1');
        return $stmt->fetchColumn() === 1;
    } catch (Exception $e) {
        error_log('[DB TEST] Connection failed: ' . $e->getMessage());
        return false;
    }
}

function clean_input(string $value): string
{
    return trim($value);
}

function json_response(bool $success, string $message, array $data = [], int $statusCode = 200): never
{
    // Clean any output before sending JSON
    if (ob_get_length()) {
        ob_end_clean();
    }
    
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ensure_post_request(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(false, 'طريقة الطلب غير مسموح بها.', [], 405);
    }
}

function require_fields(array $source, array $required): array
{
    $missing = [];

    foreach ($required as $field) {
        $value = trim((string) ($source[$field] ?? ''));
        if ($value === '') {
            $missing[] = $field;
        }
    }

    return $missing;
}

function db_query(string $sql, array $params = []): PDOStatement
{
    $statement = db_connection()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function calculate_mention(float $average): string
{
    if ($average >= 16.0) {
        return 'ممتاز';
    }
    if ($average >= 14.0) {
        return 'جيد جدا';
    }
    if ($average >= 12.0) {
        return 'جيد';
    }
    if ($average >= 10.0) {
        return 'مقبول';
    }

    return 'يحتاج دعم';
}

// Admin-specific functions removed - moved to admin/api/auth.php
// Teacher access function kept for teacher portal
function require_teacher_access(): int
{
    $userRole = strtolower(trim((string) ($_SESSION['user_role'] ?? '')));
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if (
        !isset($_SESSION['logged_in']) ||
        $_SESSION['logged_in'] !== true ||
        $userRole !== 'teacher' ||
        $userId <= 0
    ) {
        json_response(false, 'ولوج الأساتذة يتطلب تسجيل الدخول.', [], 403);
    }

    return $userId;
}

function has_table_column(string $tableName, string $columnName): bool
{
    static $cache = [];
    $cacheKey = strtolower($tableName . '.' . $columnName);
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    try {
        $result = db_query(
            'SELECT COUNT(*) AS total
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND COLUMN_NAME = :column_name',
            [
                'table_name' => $tableName,
                'column_name' => $columnName,
            ]
        )->fetch();

        $exists = (int) ($result['total'] ?? 0) > 0;
        $cache[$cacheKey] = $exists;
        return $exists;
    } catch (Throwable $exception) {
        error_log('Column lookup error: ' . $exception->getMessage());
        $cache[$cacheKey] = false;
        return false;
    }
}

function ensure_personal_info_table(): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS personal_info (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                title VARCHAR(100) DEFAULT NULL,
                bio TEXT DEFAULT NULL,
                email VARCHAR(100) DEFAULT NULL,
                phone VARCHAR(50) DEFAULT NULL,
                location VARCHAR(100) DEFAULT NULL,
                cv_url VARCHAR(255) DEFAULT NULL,
                github_url VARCHAR(255) DEFAULT NULL,
                linkedin_url VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('personal_info table error: ' . $e->getMessage());
    }
}

function ensure_projects_table(): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS projects (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(150) NOT NULL,
                description TEXT DEFAULT NULL,
                image_url VARCHAR(255) DEFAULT NULL,
                project_url VARCHAR(255) DEFAULT NULL,
                github_url VARCHAR(255) DEFAULT NULL,
                category VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('projects table error: ' . $e->getMessage());
    }
}

function ensure_skills_table(): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS skills (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                category VARCHAR(50) DEFAULT NULL,
                percentage INT UNSIGNED DEFAULT 80,
                icon_url VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('skills table error: ' . $e->getMessage());
    }
}

function ensure_grades_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            'CREATE TABLE IF NOT EXISTS grades (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id INT UNSIGNED NOT NULL,
                subject_name VARCHAR(120) NOT NULL,
                continuous_score DECIMAL(5,2) NOT NULL DEFAULT 0,
                exam_score DECIMAL(5,2) NOT NULL DEFAULT 0,
                coefficient DECIMAL(4,2) NOT NULL DEFAULT 1,
                semester VARCHAR(20) NOT NULL DEFAULT "S1",
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_grades_student (student_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        if (!has_table_column('grades', 'coefficient')) {
            db_query('ALTER TABLE grades ADD COLUMN coefficient DECIMAL(4,2) NOT NULL DEFAULT 1 AFTER exam_score');
        }

        if (!has_table_column('grades', 'semester')) {
            db_query('ALTER TABLE grades ADD COLUMN semester VARCHAR(20) NOT NULL DEFAULT "S1" AFTER coefficient');
        }

        if (!has_table_column('grades', 'created_at')) {
            db_query('ALTER TABLE grades ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }
    } catch (Throwable $exception) {
        error_log('Grades table migration warning: ' . $exception->getMessage());
    }
}

function ensure_attendance_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            'CREATE TABLE IF NOT EXISTS attendance (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id INT UNSIGNED NOT NULL,
                absence_date DATE NOT NULL,
                session_label VARCHAR(50) NOT NULL,
                absence_subject VARCHAR(120) NULL,
                justified TINYINT(1) NOT NULL DEFAULT 0,
                notes VARCHAR(255) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_attendance_student_date (student_id, absence_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        if (!has_table_column('attendance', 'absence_subject')) {
            db_query('ALTER TABLE attendance ADD COLUMN absence_subject VARCHAR(120) NULL AFTER session_label');
        }

        if (!has_table_column('attendance', 'justified')) {
            db_query('ALTER TABLE attendance ADD COLUMN justified TINYINT(1) NOT NULL DEFAULT 0 AFTER absence_subject');
        }

        if (!has_table_column('attendance', 'notes')) {
            db_query('ALTER TABLE attendance ADD COLUMN notes VARCHAR(255) NULL AFTER justified');
        }

        if (!has_table_column('attendance', 'created_at')) {
            db_query('ALTER TABLE attendance ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }
    } catch (Throwable $exception) {
        error_log('Attendance table migration warning: ' . $exception->getMessage());
    }
}

function ensure_attendance_subject_column(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    if (has_table_column('attendance', 'absence_subject')) {
        return;
    }

    try {
        db_query(
            'ALTER TABLE attendance
             ADD COLUMN absence_subject VARCHAR(120) NULL AFTER session_label'
        );
    } catch (Throwable $exception) {
        // Ignore duplicate/parallel alter attempts and keep runtime compatible.
        error_log('Attendance subject migration warning: ' . $exception->getMessage());
    }
}

function ensure_lessons_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            'CREATE TABLE IF NOT EXISTS lessons (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                description TEXT NULL,
                lesson_type ENUM("pdf", "video", "image") NOT NULL,
                level VARCHAR(80) NULL,
                subject_name VARCHAR(120) NULL,
                content_url VARCHAR(500) NOT NULL,
                thumbnail_url VARCHAR(500) NULL,
                is_published TINYINT(1) NOT NULL DEFAULT 1,
                teacher_id INT UNSIGNED NULL,
                created_by_role VARCHAR(30) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_lesson_type (lesson_type),
                INDEX idx_lesson_level (level),
                INDEX idx_lesson_published (is_published),
                INDEX idx_lesson_teacher (teacher_id),
                CONSTRAINT fk_lessons_teacher
                    FOREIGN KEY (teacher_id) REFERENCES teachers (id)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (Throwable $exception) {
        error_log('Lessons table migration warning: ' . $exception->getMessage());
    }
}

function ensure_registration_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS registration_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_code VARCHAR(50),
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                birth_date DATE,
                gender ENUM('male', 'female'),
                class_name VARCHAR(50),
                level VARCHAR(50),
                email VARCHAR(100),
                phone VARCHAR(20),
                address TEXT,
                guardian_name VARCHAR(100),
                guardian_phone VARCHAR(20),
                guardian_email VARCHAR(100),
                registration_status VARCHAR(20) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
	    } catch (Throwable $exception) {
	        error_log('Registration table migration warning: ' . $exception->getMessage());
	    }
	
	    // Ensure registration_status column exists (legacy tables may use `status`)
	    if (!has_table_column('registration_requests', 'registration_status')) {
	        try {
	            if (has_table_column('registration_requests', 'status')) {
	                db_query(
	                    "ALTER TABLE registration_requests
	                     CHANGE COLUMN status registration_status VARCHAR(20) DEFAULT 'pending'"
	                );
	            } else {
	                db_query(
	                    "ALTER TABLE registration_requests
	                     ADD COLUMN registration_status VARCHAR(20) DEFAULT 'pending'
	                     AFTER guardian_email"
	                );
	            }
	        } catch (Throwable $exception) {
	            error_log('Registration table registration_status migration warning: ' . $exception->getMessage());
	        }
	    }
	
	    // Ensure updated_at column exists
	    if (!has_table_column('registration_requests', 'updated_at')) {
	        try {
	            db_query(
                "ALTER TABLE registration_requests
                 ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                 AFTER created_at"
            );
        } catch (Throwable $exception) {
            error_log('Registration table add updated_at warning: ' . $exception->getMessage());
        }
    }

    // Ensure created_at column exists
    if (!has_table_column('registration_requests', 'created_at')) {
        try {
            db_query(
                "ALTER TABLE registration_requests
                 ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                 AFTER registration_status"
            );
        } catch (Throwable $exception) {
            error_log('Registration table add created_at warning: ' . $exception->getMessage());
        }
    }
}

function ensure_registration_requests_table(): void
{
    ensure_registration_table();
}

function ensure_students_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS students (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_code VARCHAR(50) NOT NULL UNIQUE,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                birth_date DATE NOT NULL,
                gender ENUM('male', 'female') NOT NULL,
                class_name VARCHAR(50) NOT NULL,
                level VARCHAR(50) NOT NULL,
                email VARCHAR(100),
                phone VARCHAR(20),
                address TEXT,
                guardian_name VARCHAR(100) NOT NULL,
                guardian_phone VARCHAR(20) NOT NULL,
                guardian_email VARCHAR(100),
                registration_status VARCHAR(20) DEFAULT 'registered',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_student_code (student_code),
                INDEX idx_level (level),
                INDEX idx_class (class_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $exception) {
        error_log('Students table migration warning: ' . $exception->getMessage());
    }

    // Upgrade legacy tables that may miss newer columns.
    if (!has_table_column('students', 'registration_status')) {
        try {
            db_query(
                "ALTER TABLE students
                 ADD COLUMN registration_status VARCHAR(20) DEFAULT 'registered'
                 AFTER guardian_email"
            );
        } catch (Throwable $exception) {
            error_log('Students table add registration_status warning: ' . $exception->getMessage());
        }
    }

    if (!has_table_column('students', 'updated_at')) {
        try {
            db_query(
                "ALTER TABLE students
                 ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                 ON UPDATE CURRENT_TIMESTAMP
                 AFTER created_at"
            );
        } catch (Throwable $exception) {
            error_log('Students table add updated_at warning: ' . $exception->getMessage());
        }
    }
}

// Backwards-compatible: ensure_admins_table ensures the admins table exists.
function ensure_admins_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS admins (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                code VARCHAR(255) NOT NULL,
                status VARCHAR(20) DEFAULT 'active',
                last_login DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_admin_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $exception) {
        error_log('Admins table migration warning: ' . $exception->getMessage());
    }
}

/**
 * Normalize admin role values and provide a canonical role name
 */
function normalize_admin_role(string $role): string
{
    $r = strtolower(trim($role));
    if ($r === 'admin') {
        return 'director';
    }
    if ($r === 'director' || $r === 'secretary') {
        return $r;
    }
    return '';
}

if (!function_exists('role_label')) {
    function role_label(string $role): string
    {
        $r = strtolower(trim($role));
        if ($r === 'director') {
            return 'المدير';
        }
        if ($r === 'secretary') {
            return 'السكرتارية';
        }
        return 'الإدارة';
    }
}

/**
 * Return default admin login profiles for known roles.
 * This keeps legacy behavior when credentials are embedded for recovery.
 */
function get_admin_login_profile(string $role): ?array
{
    $role = normalize_admin_role($role);
    $profiles = [
        'director' => [
            'email' => 'admin@kawkab-ouloum.ma',
            'code' => 'Admin@2026',
            'label' => 'المدير',
            'full_name' => 'Director',
            'role' => 'director',
        ],
        'secretary' => [
            'email' => 'secretary@kawkab-ouloum.ma',
            'code' => 'Secretary@2026',
            'label' => 'السكرتارية',
            'full_name' => 'Secretary',
            'role' => 'secretary',
        ],
    ];

    return $profiles[$role] ?? null;
}

if (!function_exists('require_admin_access')) {
    function require_admin_access(array $allowedRoles = null): string
    {
        $loggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
        $role = $_SESSION['admin_role'] ?? $_SESSION['user_role'] ?? '';
        $userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;

        if (!$loggedIn || $userId <= 0 || $role === '') {
            json_response(false, 'يجب تسجيل الدخول للوصول إلى هذه الصفحة.', [], 403);
        }

        $normalized = strtolower(trim((string) $role));
        if ($allowedRoles === null) {
            $allowedRoles = ['director', 'secretary'];
        }
        $allowed = array_map('strtolower', $allowedRoles);
        if (!in_array($normalized, $allowed, true)) {
            json_response(false, 'ليس لديك صلاحية للوصول إلى هذه المورد.', [], 403);
        }

        return $normalized;
    }
}

function ensure_messages_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(30) NULL,
                subject VARCHAR(200) NULL,
                message TEXT NOT NULL,
                status ENUM('new', 'in_progress', 'closed') NOT NULL DEFAULT 'new',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $exception) {
        error_log('Messages table migration warning: ' . $exception->getMessage());
    }
}

function ensure_users_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(30) NOT NULL,
                status VARCHAR(20) DEFAULT 'active',
                last_login DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $exception) {
        error_log('Users table migration warning: ' . $exception->getMessage());
    }
}

function ensure_teachers_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS teachers (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(120) NOT NULL,
                email VARCHAR(180) NOT NULL UNIQUE,
                code VARCHAR(255) NOT NULL,
                phone VARCHAR(30) NULL,
                subject_name VARCHAR(120) NULL,
                bio TEXT NULL,
                specialty VARCHAR(100) NULL,
                avatar VARCHAR(255) NULL,
                rating DECIMAL(2,1) DEFAULT 5.0,
                status VARCHAR(20) DEFAULT 'active',
                last_login DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_teacher_email (email),
                INDEX idx_teacher_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        
        // Add missing columns if table already exists (migration)
        try {
            $columns = db_query("SHOW COLUMNS FROM teachers")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('specialty', $columns)) {
                db_query("ALTER TABLE teachers ADD COLUMN specialty VARCHAR(100) NULL AFTER bio");
            }
            if (!in_array('avatar', $columns)) {
                db_query("ALTER TABLE teachers ADD COLUMN avatar VARCHAR(255) NULL AFTER specialty");
            }
            if (!in_array('rating', $columns)) {
                db_query("ALTER TABLE teachers ADD COLUMN rating DECIMAL(2,1) DEFAULT 5.0 AFTER avatar");
            }
        } catch (Throwable $e) {
            // Ignore errors for column existence checks
        }
    } catch (Throwable $exception) {
        error_log('Teachers table migration warning: ' . $exception->getMessage());
    }
}

 function ensure_teacher_requests_table(): void
 {
     static $checked = false;
     if ($checked) {
         return;
     }
     $checked = true;

     try {
         db_query(
             "CREATE TABLE IF NOT EXISTS teacher_requests (
                 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                 full_name VARCHAR(120) NOT NULL,
                 email VARCHAR(180) NOT NULL UNIQUE,
                 code VARCHAR(255) NOT NULL,
                 phone VARCHAR(30) NULL,
                 subject_name VARCHAR(120) NULL,
                 status VARCHAR(20) DEFAULT 'pending',
                 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                 reviewed_at DATETIME NULL,
                 INDEX idx_teacher_req_email (email),
                 INDEX idx_teacher_req_status (status)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
         );
     } catch (Throwable $exception) {
         error_log('Teacher requests table migration warning: ' . $exception->getMessage());
     }
 }

function ensure_teacher_timetable_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    ensure_teachers_table();

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS teacher_timetable (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT UNSIGNED NOT NULL,
                day_of_week TINYINT UNSIGNED NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                class_name VARCHAR(80) NOT NULL,
                subject_name VARCHAR(120) NOT NULL,
                room VARCHAR(60) NULL,
                notes VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_teacher_day (teacher_id, day_of_week),
                INDEX idx_teacher_time (teacher_id, start_time),
                CONSTRAINT fk_teacher_timetable_teacher
                    FOREIGN KEY (teacher_id) REFERENCES teachers (id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $exception) {
        error_log('Teacher timetable table migration warning: ' . $exception->getMessage());
    }
}

function ensure_teacher_absence_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    ensure_teachers_table();

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS teacher_absences (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT UNSIGNED NOT NULL,
                absence_date DATE NOT NULL,
                absence_type VARCHAR(20) NOT NULL,
                reason TEXT NOT NULL,
                document_path VARCHAR(255) NULL,
                status VARCHAR(20) DEFAULT 'pending',
                admin_notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_teacher_date (teacher_id, absence_date),
                INDEX idx_status (status),
                CONSTRAINT fk_teacher_absence_teacher
                    FOREIGN KEY (teacher_id) REFERENCES teachers (id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        if (!has_table_column('teacher_absences', 'is_urgent')) {
            db_query('ALTER TABLE teacher_absences ADD COLUMN is_urgent TINYINT(1) NOT NULL DEFAULT 0 AFTER reason');
        }
    } catch (Throwable $exception) {
        error_log('Teacher absence table migration warning: ' . $exception->getMessage());
    }
}

function ensure_teacher_homework_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    ensure_teachers_table();

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS teacher_homework (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT UNSIGNED NOT NULL,
                title VARCHAR(200) NOT NULL,
                class_name VARCHAR(100) NOT NULL,
                subject VARCHAR(100) NOT NULL,
                due_date DATE NOT NULL,
                description TEXT NOT NULL,
                file_path VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_teacher (teacher_id),
                INDEX idx_class (class_name),
                INDEX idx_due_date (due_date),
                CONSTRAINT fk_teacher_homework_teacher
                    FOREIGN KEY (teacher_id) REFERENCES teachers (id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $exception) {
        error_log('Teacher homework table migration warning: ' . $exception->getMessage());
    }
}

function ensure_password_reset_tokens_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(180) NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                user_type ENUM('admin', 'teacher') NOT NULL,
                token_hash VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                is_used TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_token_used (is_used),
                INDEX idx_expires (expires_at),
                UNIQUE KEY uniq_email_user (email, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $exception) {
        error_log('Password reset tokens table migration warning: ' . $exception->getMessage());
    }
}

// ==========================================
// === PROMOTION SYSTEM TABLES (NEW) ===
// ==========================================

function ensure_promotions_table(): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS promotions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                annee_scolaire_from VARCHAR(20) NOT NULL,
                annee_scolaire_to VARCHAR(20) NOT NULL,
                date_promotion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                total_eleves INT UNSIGNED NOT NULL DEFAULT 0,
                total_admis INT UNSIGNED NOT NULL DEFAULT 0,
                total_redoublants INT UNSIGNED NOT NULL DEFAULT 0,
                total_classes_creees INT UNSIGNED NOT NULL DEFAULT 0,
                promoted_by INT UNSIGNED NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_annee_from (annee_scolaire_from),
                INDEX idx_annee_to (annee_scolaire_to)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $exception) {
        error_log('Promotions table migration warning: ' . $exception->getMessage());
    }
}

function ensure_historique_scolaire_table(): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS historique_scolaire (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id INT UNSIGNED NOT NULL,
                annee_scolaire VARCHAR(20) NOT NULL,
                level VARCHAR(50) NOT NULL,
                class_name VARCHAR(80) NULL,
                moyenne_s1 DECIMAL(5,2) NULL,
                moyenne_s2 DECIMAL(5,2) NULL,
                moyenne_generale DECIMAL(5,2) NULL,
                resultat ENUM('admis','redoublant','en_cours') NOT NULL DEFAULT 'en_cours',
                mention VARCHAR(50) NULL,
                promotion_id INT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_student (student_id),
                INDEX idx_annee (annee_scolaire),
                INDEX idx_resultat (resultat),
                UNIQUE KEY uniq_student_annee (student_id, annee_scolaire)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $exception) {
        error_log('Historique scolaire table migration warning: ' . $exception->getMessage());
    }
}

function ensure_classes_table(): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS classes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nom_classe VARCHAR(80) NOT NULL,
                level VARCHAR(50) NOT NULL,
                annee_scolaire VARCHAR(20) NOT NULL,
                capacite_max INT UNSIGNED NOT NULL DEFAULT 30,
                effectif_actuel INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_level (level),
                INDEX idx_annee (annee_scolaire),
                UNIQUE KEY uniq_classe_annee (nom_classe, annee_scolaire)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $exception) {
        error_log('Classes table migration warning: ' . $exception->getMessage());
    }
}

function ensure_affectations_classes_table(): void
{
    static $checked = false;
    if ($checked) return;
    $checked = true;

    ensure_classes_table();

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS affectations_classes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id INT UNSIGNED NOT NULL,
                classe_id INT UNSIGNED NOT NULL,
                annee_scolaire VARCHAR(20) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_student (student_id),
                INDEX idx_classe (classe_id),
                INDEX idx_annee (annee_scolaire),
                UNIQUE KEY uniq_student_classe_annee (student_id, annee_scolaire)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $exception) {
        error_log('Affectations classes table migration warning: ' . $exception->getMessage());
    }
}

function ensure_gallery_photos_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS gallery_photos (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                category ENUM('trips', 'events', 'ceremonies', 'activities', 'other') NOT NULL DEFAULT 'other',
                image_path VARCHAR(500) NOT NULL,
                image_filename VARCHAR(255) NULL,
                thumbnail_path VARCHAR(500) NULL,
                location_label VARCHAR(100) NULL,
                author_name VARCHAR(120) NULL,
                photo_date DATE NULL,
                is_published TINYINT(1) NOT NULL DEFAULT 1,
                uploaded_by VARCHAR(50) NULL,
                upload_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_gallery_category (category),
                INDEX idx_gallery_published (is_published),
                INDEX idx_gallery_date (photo_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $columns = db_query('SHOW COLUMNS FROM gallery_photos')->fetchAll(PDO::FETCH_COLUMN);

        $addColumn = static function (string $sql) use ($columns): void {
            db_query($sql);
        };

        if (!in_array('image_filename', $columns, true)) {
            $addColumn('ALTER TABLE gallery_photos ADD COLUMN image_filename VARCHAR(255) NULL AFTER image_path');
            $columns[] = 'image_filename';
        }
        if (!in_array('author_name', $columns, true)) {
            $addColumn('ALTER TABLE gallery_photos ADD COLUMN author_name VARCHAR(120) NULL');
            $columns[] = 'author_name';
        }
        if (!in_array('photo_date', $columns, true)) {
            $addColumn('ALTER TABLE gallery_photos ADD COLUMN photo_date DATE NULL');
            $columns[] = 'photo_date';
        }
        if (!in_array('location_label', $columns, true)) {
            $addColumn('ALTER TABLE gallery_photos ADD COLUMN location_label VARCHAR(100) NULL');
            $columns[] = 'location_label';
        }
        if (!in_array('created_at', $columns, true)) {
            $addColumn('ALTER TABLE gallery_photos ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
            $columns[] = 'created_at';
        }
        if (!in_array('updated_at', $columns, true)) {
            $addColumn('ALTER TABLE gallery_photos ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
            $columns[] = 'updated_at';
        }

        if (in_array('image_filename', $columns, true)) {
            db_query(
                "UPDATE gallery_photos
                 SET image_filename = SUBSTRING_INDEX(image_path, '/', -1)
                 WHERE (image_filename IS NULL OR image_filename = '') AND image_path IS NOT NULL AND image_path != ''"
            );
        }
        if (in_array('photo_date', $columns, true) && in_array('upload_date', $columns, true)) {
            db_query(
                'UPDATE gallery_photos SET photo_date = DATE(upload_date) WHERE photo_date IS NULL AND upload_date IS NOT NULL'
            );
        }
        if (in_array('author_name', $columns, true) && in_array('uploaded_by', $columns, true)) {
            db_query(
                'UPDATE gallery_photos SET author_name = uploaded_by WHERE (author_name IS NULL OR author_name = "") AND uploaded_by IS NOT NULL'
            );
        }
    } catch (Throwable $exception) {
        error_log('Gallery photos table migration warning: ' . $exception->getMessage());
    }
}

function ensure_announcements_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS announcements (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                content TEXT NOT NULL,
                type ENUM('normal', 'urgent') NOT NULL DEFAULT 'normal',
                class_name VARCHAR(50) NULL,
                teacher_id INT UNSIGNED NOT NULL,
                file_path VARCHAR(500) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_class_name (class_name),
                CONSTRAINT fk_announcements_teacher
                    FOREIGN KEY (teacher_id) REFERENCES teachers (id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Add file_path column to existing tables that may not have it
        if (!has_table_column('announcements', 'file_path')) {
            db_query("ALTER TABLE announcements ADD COLUMN file_path VARCHAR(500) NULL AFTER teacher_id");
        }
    } catch (Throwable $exception) {
        error_log('Announcements table migration warning: ' . $exception->getMessage());
    }
}

function ensure_school_courses_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS school_courses (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                category VARCHAR(50) NOT NULL DEFAULT 'middle',
                level_tag VARCHAR(100) NULL,
                image_path VARCHAR(500) NOT NULL DEFAULT 'images/kaoukab_alouloum1.jpg',
                is_published TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_course_cat (category),
                INDEX idx_course_pub (is_published)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $count = (int) db_query("SELECT COUNT(*) FROM school_courses")->fetchColumn();
        if ($count === 0) {
            $defaultCourses = [
                [
                    'title' => 'سلك الثانوي الإعدادي (السنة 1، 2، 3)',
                    'description' => 'ترسيخ المكتسبات العلمية واللغوية وبناء الشخصية المستقلة والتفكير النقدي للمتعلم.',
                    'category' => 'middle',
                    'level_tag' => '3 سنوات دراسية',
                    'image_path' => 'images/kaoukab_alouloum1.jpg',
                    'is_published' => 1
                ],
                [
                    'title' => 'مسلك العلوم التجريبية (علوم فيزياء / علوم حياة وأرض)',
                    'description' => 'برنامج أكاديمي مكثف يركز على التجارب العلمية والتحليل الفيزيائي والبيولوجي للتحضير للبكالوريا.',
                    'category' => 'science',
                    'level_tag' => 'مسلك علمي',
                    'image_path' => 'images/kaoukab_alouloum2.jpg',
                    'is_published' => 1
                ],
                [
                    'title' => 'مسلك العلوم الرياضية (أ / ب)',
                    'description' => 'دراسة متعمقة للرياضيات والفيزياء والتفكير المنطقي للتأهيل للمدارس والمعاهد العليا للهندسة.',
                    'category' => 'science',
                    'level_tag' => 'مسلك رياضي',
                    'image_path' => 'images/kaoukab_alouloum3.jpg',
                    'is_published' => 1
                ],
                [
                    'title' => 'مسلك الآداب والعلوم الإنسانية',
                    'description' => 'تعميق الفكر الأدبي والفلسفي واللغوي والدراسات التاريخية والجغرافية لبناء شخصية مفكرة ومتواصلة.',
                    'category' => 'arts',
                    'level_tag' => 'مسلك أدبي',
                    'image_path' => 'images/kaoukab_alouloum4.jpg',
                    'is_published' => 1
                ],
                [
                    'title' => 'برامج دعم اللغات (الفرنسية والإنجليزية)',
                    'description' => 'حصص دعم مخصصة لتطوير مهارات التعبير الشفهي والكتابي والاستعداد لاختبارات اللغة الدولية.',
                    'category' => 'support',
                    'level_tag' => 'لغات حية',
                    'image_path' => 'images/kaoukab_alouloum5.jpg',
                    'is_published' => 1
                ],
                [
                    'title' => 'ورشات الإعلاميات والبرمجة المدرسية',
                    'description' => 'تدريب التلاميذ على المهارات الرقمية والبرمجية واستخدام التقنيات الحديثة في البحث والتعلم.',
                    'category' => 'support',
                    'level_tag' => 'ورشات عمل',
                    'image_path' => 'images/kaoukab_alouloum6.jpg',
                    'is_published' => 1
                ]
            ];

            foreach ($defaultCourses as $course) {
                db_query(
                    "INSERT INTO school_courses (title, description, category, level_tag, image_path, is_published) VALUES (?, ?, ?, ?, ?, ?)",
                    [$course['title'], $course['description'], $course['category'], $course['level_tag'], $course['image_path'], $course['is_published']]
                );
            }
        }
    } catch (Throwable $e) {
        error_log('ensure_school_courses_table error: ' . $e->getMessage());
    }
}

function ensure_parent_notifications_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS parent_notifications (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                massar_code VARCHAR(50) NOT NULL,
                student_name VARCHAR(150) NULL,
                phone VARCHAR(30) NULL,
                type ENUM('absence', 'grades', 'general') NOT NULL DEFAULT 'absence',
                title VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                is_sent TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_notif_massar (massar_code),
                INDEX idx_notif_type (type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('ensure_parent_notifications_table error: ' . $e->getMessage());
    }
}

/**
 * Regenerate session securely upon login/privilege change to prevent Session Fixation
 */
function secure_session_regenerate(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Ensure Grade Audit Logs Table exists for tracking any grade changes/tampering
 */
function ensure_grade_audit_table(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        db_query(
            "CREATE TABLE IF NOT EXISTS grade_audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                grade_id INT UNSIGNED NOT NULL,
                student_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL DEFAULT 0,
                user_role VARCHAR(50) NOT NULL DEFAULT 'unknown',
                action VARCHAR(20) NOT NULL, -- 'INSERT', 'UPDATE', 'DELETE'
                subject_name VARCHAR(120) NOT NULL,
                old_continuous DECIMAL(5,2) NULL,
                new_continuous DECIMAL(5,2) NULL,
                old_exam DECIMAL(5,2) NULL,
                new_exam DECIMAL(5,2) NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_audit_student (student_id),
                INDEX idx_audit_grade (grade_id),
                INDEX idx_audit_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        error_log('ensure_grade_audit_table error: ' . $e->getMessage());
    }
}

/**
 * Record a tamper-evident audit record whenever a grade is modified
 */
function log_grade_audit(
    int $gradeId,
    int $studentId,
    string $action,
    string $subjectName,
    ?float $oldContinuous,
    ?float $newContinuous,
    ?float $oldExam,
    ?float $newExam
): void {
    try {
        ensure_grade_audit_table();

        $userId = (int)($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0);
        $userRole = (string)($_SESSION['admin_role'] ?? $_SESSION['user_role'] ?? 'system');
        $ip = clean_input((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
        $ua = substr(clean_input((string)($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255);

        db_query(
            "INSERT INTO grade_audit_logs (
                grade_id, student_id, user_id, user_role, action, subject_name,
                old_continuous, new_continuous, old_exam, new_exam, ip_address, user_agent
            ) VALUES (
                :grade_id, :student_id, :user_id, :user_role, :action, :subject_name,
                :old_continuous, :new_continuous, :old_exam, :new_exam, :ip, :ua
            )",
            [
                'grade_id'       => $gradeId,
                'student_id'     => $studentId,
                'user_id'        => $userId,
                'user_role'      => $userRole,
                'action'         => strtoupper($action),
                'subject_name'   => $subjectName,
                'old_continuous' => $oldContinuous,
                'new_continuous' => $newContinuous,
                'old_exam'       => $oldExam,
                'new_exam'       => $newExam,
                'ip'             => $ip,
                'ua'             => $ua,
            ]
        );
    } catch (Throwable $e) {
        error_log('[AUDIT LOG ERROR] Failed to record grade audit: ' . $e->getMessage());
    }
}

/**
 * Rate Limiter to protect public endpoints (e.g. transcript scraping)
 * Returns true if allowed, false if limit exceeded.
 */
function check_rate_limit(string $actionKey, int $maxAttempts = 10, int $windowSeconds = 60): bool
{
    $ip = clean_input((string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

    try {
        static $rateTableChecked = false;
        if (!$rateTableChecked) {
            db_query(
                "CREATE TABLE IF NOT EXISTS rate_limits (
                    ip_address VARCHAR(45) NOT NULL,
                    action_key VARCHAR(50) NOT NULL,
                    attempts INT UNSIGNED NOT NULL DEFAULT 1,
                    window_start INT UNSIGNED NOT NULL,
                    PRIMARY KEY (ip_address, action_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            $rateTableChecked = true;
        }

        $now = time();
        $record = db_query(
            "SELECT attempts, window_start FROM rate_limits WHERE ip_address = :ip AND action_key = :action LIMIT 1",
            ['ip' => $ip, 'action' => $actionKey]
        )->fetch();

        if (!$record) {
            db_query(
                "INSERT INTO rate_limits (ip_address, action_key, attempts, window_start) VALUES (:ip, :action, 1, :now)",
                ['ip' => $ip, 'action' => $actionKey, 'now' => $now]
            );
            return true;
        }

        $windowStart = (int)$record['window_start'];
        $attempts = (int)$record['attempts'];

        if (($now - $windowStart) > $windowSeconds) {
            // Reset window
            db_query(
                "UPDATE rate_limits SET attempts = 1, window_start = :now WHERE ip_address = :ip AND action_key = :action",
                ['now' => $now, 'ip' => $ip, 'action' => $actionKey]
            );
            return true;
        }

        if ($attempts >= $maxAttempts) {
            return false;
        }

        db_query(
            "UPDATE rate_limits SET attempts = attempts + 1 WHERE ip_address = :ip AND action_key = :action",
            ['ip' => $ip, 'action' => $actionKey]
        );
        return true;
    } catch (Throwable $e) {
        error_log('[RATE LIMIT] ' . $e->getMessage());
        return true; // fail open safely so application doesn't crash on rate limit DB error
    }
}

/**
 * Generate cryptographically secure CSRF Token
 */
function generate_csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token safely with hash_equals
 */
function verify_csrf_token(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $stored = $_SESSION['csrf_token'] ?? '';
    if ($stored === '' || empty($token)) {
        return false;
    }
    return hash_equals($stored, $token);
}

/**
 * Require valid CSRF token on state-changing POST/PUT requests
 */
function require_csrf_token(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token((string)$token)) {
        json_response(false, 'رمز التوثيق (CSRF Token) غير صالح أو منتهي الصلاحية. يرجى إعادة تحديث الصفحة.', [], 403);
    }
}

/**
 * Strict File Upload MIME Validation using finfo_file
 */
function validate_uploaded_file_mime(string $tmpFilePath, array $allowedMimeTypes): bool
{
    if (!file_exists($tmpFilePath) || !is_readable($tmpFilePath)) {
        return false;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) {
        return false;
    }
    $detectedMime = finfo_file($finfo, $tmpFilePath);
    finfo_close($finfo);

    if (!$detectedMime) {
        return false;
    }

    return in_array(strtolower($detectedMime), array_map('strtolower', $allowedMimeTypes), true);
}


