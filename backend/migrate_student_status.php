<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = db_connection();
    
    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM students LIKE 'registration_status'");
    if ($check->rowCount() === 0) {
        $pdo->exec("ALTER TABLE students ADD COLUMN registration_status VARCHAR(20) DEFAULT 'approved' AFTER guardian_email");
        echo "✅ MIGRATION SUCCESS: 'registration_status' column added to 'students' table.\n";
    } else {
        echo "ℹ️ MIGRATION SKIPPED: 'registration_status' column already exists.\n";
    }

    // Ensure existing students are 'approved' (they should be by default, but let's be sure)
    $pdo->exec("UPDATE students SET registration_status = 'approved' WHERE registration_status IS NULL OR registration_status = ''");
    echo "✅ DATA UPDATE SUCCESS: Set default 'approved' status for existing records.\n";

} catch (Exception $e) {
    http_response_code(500);
    echo "❌ MIGRATION ERROR: " . $e->getMessage() . "\n";
}
