<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const DB_HOST = '127.0.0.1:3308';
const DB_NAME = 'edu_institution';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

// Default portal credentials (change them if needed).
// ملاحظة: تم تحويل النطاق الافتراضي إلى .ma ليتماشى مع المعروض في واجهة الدخول.
const DIRECTOR_LOGIN_EMAIL = 'admin@kawkab-ouloum.ma';
const DIRECTOR_LOGIN_CODE = 'Admin@2026';
const SECRETARY_LOGIN_EMAIL = 'secretary@kawkab-ouloum.ma';
const SECRETARY_LOGIN_CODE = 'Secretary@2026';

// Backward compatibility aliases.
const ADMIN_LOGIN_EMAIL = DIRECTOR_LOGIN_EMAIL;
const ADMIN_LOGIN_CODE = DIRECTOR_LOGIN_CODE;

/**
 * Database Connection with Professional Error Handling
 * Uses PDO for security (prepared statements) and supports port 3308
 */
function db_connection(): PDO
{
    static $pdo = null;

    // Return existing connection (singleton pattern - best practice)
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Database configuration with port support
    $host = getenv('DB_HOST') ?: DB_HOST;  // Format: 127.0.0.1:3308
    $name = getenv('DB_NAME') ?: DB_NAME;
    $user = getenv('DB_USER') ?: DB_USER;
    $pass = getenv('DB_PASS') ?: DB_PASS;
    $charset = getenv('DB_CHARSET') ?: DB_CHARSET;

    // Parse host and port
    if (strpos($host, ':') !== false) {
        list($hostPart, $portPart) = explode(':', $host);
        $dsn = "mysql:host={$hostPart};port={$portPart};dbname={$name};charset={$charset}";
    } else {
        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Fetch associative arrays
            PDO::ATTR_EMULATE_PREPARES => false,                // Real prepared statements (secure)
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci"
        ]);
    } catch (PDOException $exception) {
        error_log('[DB ERROR] ' . $exception->getMessage());
        
        // Return JSON error for API calls, or throw exception for direct use
        if (function_exists('json_response')) {
            json_response(false, 'تعذر الاتصال بقاعدة البيانات. الرجاء التحقق من إعدادات XAMPP.', [], 500);
        }
        throw new Exception('Database connection failed: ' . $exception->getMessage());
    }

    return $pdo;
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

function normalize_admin_role(string $role): string
{
    $normalizedRole = strtolower(trim($role));
    if ($normalizedRole === 'admin') {
        return 'director';
    }

    return $normalizedRole;
}

function role_label(string $role): string
{
    if ($role === 'director') {
        return 'المدير';
    }
    if ($role === 'secretary') {
        return 'السكرتارية';
    }
    if ($role === 'teacher') {
        return 'الأستاذ';
    }

    return 'الإدارة';
}

function get_admin_login_profile(string $role): ?array
{
    $normalizedRole = normalize_admin_role($role);

    if ($normalizedRole === 'director') {
        return [
            'role' => 'director',
            'label' => role_label('director'),
            'full_name' => 'مدير المؤسسة',
            'email' => strtolower((string) (
                getenv('DIRECTOR_LOGIN_EMAIL') ?:
                (getenv('ADMIN_LOGIN_EMAIL') ?: DIRECTOR_LOGIN_EMAIL)
            )),
            'code' => (string) (
                getenv('DIRECTOR_LOGIN_CODE') ?:
                (getenv('ADMIN_LOGIN_CODE') ?: DIRECTOR_LOGIN_CODE)
            ),
        ];
    }

    if ($normalizedRole === 'secretary') {
        return [
            'role' => 'secretary',
            'label' => role_label('secretary'),
            'full_name' => 'سكرتارية المؤسسة',
            'email' => strtolower((string) (getenv('SECRETARY_LOGIN_EMAIL') ?: SECRETARY_LOGIN_EMAIL)),
            'code' => (string) (getenv('SECRETARY_LOGIN_CODE') ?: SECRETARY_LOGIN_CODE),
        ];
    }

    return null;
}

function require_admin_access(array $allowedRoles = ['director', 'secretary']): string
{
    $normalizedAllowedRoles = [];
    foreach ($allowedRoles as $allowedRole) {
        $normalizedAllowedRoles[] = normalize_admin_role((string) $allowedRole);
    }

    $userRole = normalize_admin_role((string) ($_SESSION['user_role'] ?? ''));

    if (
        !isset($_SESSION['logged_in']) ||
        $_SESSION['logged_in'] !== true ||
        !in_array($userRole, $normalizedAllowedRoles, true)
    ) {
        error_log("Access Denied: LoggedIn=" . (isset($_SESSION['logged_in']) ? 'Yes' : 'No') . ", Role=" . $userRole);
        json_response(false, 'ولوج الإدارة مخصص للمدير أو السكرتارية.', [], 403);
    }

    return $userRole;
}

function require_teacher_access(): int
{
    $userRole = normalize_admin_role((string) ($_SESSION['user_role'] ?? ''));
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
                created_by_role VARCHAR(30) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_lesson_type (lesson_type),
                INDEX idx_lesson_level (level),
                INDEX idx_lesson_published (is_published)
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
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                code VARCHAR(255) NOT NULL,
                status VARCHAR(20) DEFAULT 'active',
                last_login DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        
        // Seed default admin accounts if not exist
        $adminProfiles = [
            [
                'email' => 'admin@kawkab-ouloum.ma',
                'code' => 'Admin@2026',
                'full_name' => 'المدير',
                'role' => 'director'
            ],
            [
                'email' => 'secretary@kawkab-ouloum.ma',
                'code' => 'Secretary@2026',
                'full_name' => 'السكرتارية',
                'role' => 'secretary'
            ]
        ];
        
        foreach ($adminProfiles as $profile) {
            $existing = db_query(
                'SELECT id FROM admins WHERE email = :email LIMIT 1',
                ['email' => $profile['email']]
            )->fetch();
            
            if (!$existing) {
                db_query(
                    'INSERT INTO admins (full_name, email, code, status) VALUES (:full_name, :email, :code, :status)',
                    [
                        'full_name' => $profile['full_name'],
                        'email' => $profile['email'],
                        'code' => password_hash($profile['code'], PASSWORD_DEFAULT),
                        'status' => 'active'
                    ]
                );
                error_log('Created default admin: ' . $profile['email']);
            }
        }
    } catch (Throwable $exception) {
        error_log('Admins table migration warning: ' . $exception->getMessage());
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
