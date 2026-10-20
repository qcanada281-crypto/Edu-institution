<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_post_request();

// Only director can run full promotion
require_admin_access(['director']);

$pdo = db_connection();

try {
    $pdo->beginTransaction();

    // Ensure academic tables exist
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS academic_years (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            year_label VARCHAR(32) NOT NULL UNIQUE,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS enrollments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            academic_year_id INT NOT NULL,
            level VARCHAR(60) NOT NULL,
            class_name VARCHAR(80) NULL,
            status ENUM("active","repeated","graduated") NOT NULL DEFAULT "active",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_enrollments_student (student_id),
            INDEX idx_enrollments_year (academic_year_id),
            CONSTRAINT fk_enroll_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            CONSTRAINT fk_enroll_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS promotion_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            promoted_by INT NULL,
            academic_year_from INT NOT NULL,
            academic_year_to INT NOT NULL,
            promoted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            affected_students INT NOT NULL DEFAULT 0,
            notes TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    // Find current active academic year
    $stmt = $pdo->prepare('SELECT id, year_label FROM academic_years WHERE is_active = 1 LIMIT 1');
    $stmt->execute();
    $current = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$current) {
        // pick latest year if any
        $stmt = $pdo->query('SELECT id, year_label FROM academic_years ORDER BY id DESC LIMIT 1');
        $current = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    if (!$current) {
        // create a sensible default: previous/current year based on today (e.g., 2025-2026)
        $y = (int) date('Y');
        $label = ($y - 1) . '-' . $y;
        $stmt = $pdo->prepare('INSERT INTO academic_years (year_label, is_active) VALUES (?, 1)');
        $stmt->execute([$label]);
        $currentId = (int) $pdo->lastInsertId();
        $currentLabel = $label;
    } else {
        $currentId = (int) $current['id'];
        $currentLabel = (string) $current['year_label'];
    }

    // Determine next academic year label
    $parts = preg_split('/[^0-9]+/', $currentLabel);
    $parts = array_filter($parts, 'strlen');
    if (count($parts) >= 2) {
        $start = (int) $parts[0];
        $end = (int) $parts[1];
        $nextLabel = ($end) . '-' . ($end + 1);
    } else {
        // fallback: increment last year
        $y = (int) date('Y');
        $nextLabel = $y . '-' . ($y + 1);
    }

    // Create or select next year
    $stmt = $pdo->prepare('SELECT id FROM academic_years WHERE year_label = ? LIMIT 1');
    $stmt->execute([$nextLabel]);
    $next = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($next) {
        $nextId = (int) $next['id'];
    } else {
        $stmt = $pdo->prepare('INSERT INTO academic_years (year_label, is_active) VALUES (?, 0)');
        $stmt->execute([$nextLabel]);
        $nextId = (int) $pdo->lastInsertId();
    }

    // Promotion parameters
    $finalLevel = isset($_POST['final_level']) ? (int) $_POST['final_level'] : 12;

    // Fetch active enrollments for current year
    $stmt = $pdo->prepare('SELECT * FROM enrollments WHERE academic_year_id = ? AND status = "active"');
    $stmt->execute([$currentId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $affected = 0;
    $updateStmt = $pdo->prepare('UPDATE enrollments SET status = ? WHERE id = ?');
    $insertStmt = $pdo->prepare('INSERT INTO enrollments (student_id, academic_year_id, level, class_name, status) VALUES (?, ?, ?, ?, ?)');

    foreach ($rows as $row) {
        $studentId = (int) $row['student_id'];
        $levelRaw = $row['level'];
        $className = $row['class_name'];
        $enrollmentId = (int) $row['id'];

        // determine numeric level if possible
        $newLevel = $levelRaw;
        if (is_numeric($levelRaw)) {
            $lvl = (int) $levelRaw;
            if ($lvl >= $finalLevel) {
                // graduate
                $updateStmt->execute(['graduated', $enrollmentId]);
                continue;
            }
            $newLevel = (string) ($lvl + 1);
        }

        // insert new enrollment for next year
        $insertStmt->execute([$studentId, $nextId, $newLevel, $className, 'active']);
        $affected++;
    }

    // Log promotion
    $promotedBy = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $stmt = $pdo->prepare('INSERT INTO promotion_logs (promoted_by, academic_year_from, academic_year_to, affected_students, notes) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$promotedBy, $currentId, $nextId, $affected, null]);

    $pdo->commit();

    json_response(true, 'تمت عملية الترحيل', ['affected' => $affected]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Promotion error: ' . $e->getMessage());
    json_response(false, 'خطأ أثناء الترحيل: ' . $e->getMessage(), [], 500);
}

