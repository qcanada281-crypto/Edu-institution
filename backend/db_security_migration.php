<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Data Security & Performance Database Migration ===\n\n";

try {
    $pdo = db_connection();

    // 1. Create Grade Audit Logs Table
    echo "[1/4] Ensuring grade_audit_logs table exists...\n";
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS grade_audit_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            grade_id INT UNSIGNED NOT NULL,
            student_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL DEFAULT 0,
            user_role VARCHAR(50) NOT NULL DEFAULT 'unknown',
            action VARCHAR(20) NOT NULL,
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
    echo "  -> grade_audit_logs table verified.\n";

    // 2. Helper function to check if an index exists
    $indexExists = function(PDO $pdo, string $table, string $indexName): bool {
        $stmt = $pdo->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = :index_name");
        $stmt->execute(['index_name' => $indexName]);
        return $stmt->rowCount() > 0;
    };

    // 3. Performance Indexes on grades
    echo "\n[2/4] Optimizing 'grades' table indexes...\n";
    if (!$indexExists($pdo, 'grades', 'idx_grades_student_subject')) {
        $pdo->exec("CREATE INDEX idx_grades_student_subject ON grades (student_id, subject_name)");
        echo "  -> Created index 'idx_grades_student_subject'.\n";
    } else {
        echo "  -> Index 'idx_grades_student_subject' already exists.\n";
    }

    if (!$indexExists($pdo, 'grades', 'idx_grades_student_semester')) {
        $pdo->exec("CREATE INDEX idx_grades_student_semester ON grades (student_id, semester)");
        echo "  -> Created index 'idx_grades_student_semester'.\n";
    } else {
        echo "  -> Index 'idx_grades_student_semester' already exists.\n";
    }

    // 4. Performance Indexes on attendance
    echo "\n[3/4] Optimizing 'attendance' table indexes...\n";
    if (!$indexExists($pdo, 'attendance', 'idx_attendance_lookup')) {
        $pdo->exec("CREATE INDEX idx_attendance_lookup ON attendance (student_id, absence_date)");
        echo "  -> Created index 'idx_attendance_lookup'.\n";
    } else {
        echo "  -> Index 'idx_attendance_lookup' already exists.\n";
    }

    // 5. Performance Indexes on students
    echo "\n[4/4] Optimizing 'students' table indexes...\n";
    if (!$indexExists($pdo, 'students', 'idx_students_filter')) {
        $pdo->exec("CREATE INDEX idx_students_filter ON students (level, class_name, registration_status)");
        echo "  -> Created composite index 'idx_students_filter'.\n";
    } else {
        echo "  -> Index 'idx_students_filter' already exists.\n";
    }

    // 6. Rate limits table
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS rate_limits (
            ip_address VARCHAR(45) NOT NULL,
            action_key VARCHAR(50) NOT NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 1,
            window_start INT UNSIGNED NOT NULL,
            PRIMARY KEY (ip_address, action_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    echo "  -> Verified 'rate_limits' table.\n";

    echo "\n============================================\n";
    echo " SUCCESS: All Database Security & Performance\n";
    echo " Migrations applied successfully!\n";
    echo "============================================\n";

} catch (Throwable $e) {
    http_response_code(500);
    echo "\nERROR DURING MIGRATION: " . $e->getMessage() . "\n";
}
