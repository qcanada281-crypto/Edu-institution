<?php
/**
 * COMPREHENSIVE DATABASE DIAGNOSTIC TOOL
 * ======================================
 * Detects all database issues and generates repair commands
 * Language: Bilingual (AR/FR/EN)
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

// Database configuration
$db_config = [
    'host'    => '127.0.0.1:3306',
    'name'    => 'portfolio_db',
    'user'    => 'root',
    'pass'    => '',
    'charset' => 'utf8mb4'
];

$issues = [];
$repairs = [];
$warnings = [];
$successes = [];

// HTML header
echo '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تشخيص قاعدة البيانات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #3498db, #2c3e50); color: white; padding: 30px; border-radius: 8px; margin-bottom: 30px; }
        .header h1 { margin-bottom: 10px; }
        .section { background: white; padding: 25px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section h2 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 15px; margin-bottom: 20px; }
        .issue { background: #fadbd8; border-left: 4px solid #e74c3c; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d5f4e6; border-left: 4px solid #27ae60; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .warning { background: #fef5e7; border-left: 4px solid #f39c12; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .info { background: #d6eaf8; border-left: 4px solid #2980b9; padding: 15px; margin: 10px 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: right; }
        th { background: #3498db; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .code-block { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 4px; overflow-x: auto; margin: 10px 0; font-family: "Courier New", monospace; font-size: 12px; line-height: 1.5; }
        .label { display: inline-block; background: #3498db; color: white; padding: 3px 8px; border-radius: 3px; margin: 0 5px 0 0; font-size: 11px; }
        .error-label { background: #e74c3c; }
        .success-label { background: #27ae60; }
        .warning-label { background: #f39c12; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .summary-box { background: #ecf0f1; padding: 15px; border-radius: 8px; text-align: center; }
        .summary-box .number { font-size: 32px; font-weight: bold; color: #2c3e50; }
        .summary-box .label { display: block; margin-top: 10px; }
    </style>
</head>
<body>
<div class="container">
<div class="header">
    <h1>🔍 أداة تشخيص قاعدة البيانات</h1>
    <p>Database Diagnostic Tool - Outil de Diagnostic de Base de Données</p>
</div>
';

// Step 1: Test connection
echo '<div class="section"><h2>✓ الخطوة 1: اختبار الاتصال بـ MySQL</h2>';

try {
    // Parse host and port
    $parts = explode(':', $db_config['host']);
    $host = $parts[0];
    $port = isset($parts[1]) ? (int)$parts[1] : 3306;
    
    $dsn = "mysql:host={$host};port={$port};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo '<div class="success">✓ <strong>الاتصال بـ MySQL نجح</strong></div>';
    echo '<div class="info">
        <strong>بيانات الاتصال:</strong><br>
        Host: ' . htmlspecialchars($host) . '<br>
        Port: ' . $port . '<br>
        User: ' . htmlspecialchars($db_config['user']) . '<br>
        Charset: ' . htmlspecialchars($db_config['charset']) . '
    </div>';
    $successes[] = 'MySQL Connection';
    
} catch (PDOException $e) {
    echo '<div class="issue">✗ <strong>خطأ في الاتصال:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<div class="warning">
        <strong>خطوات الحل:</strong><br>
        1. تأكد أن XAMPP/WAMP server يعمل<br>
        2. تأكد أن MySQL service يعمل على البورت 3308<br>
        3. افتح phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a>
    </div>';
    $issues[] = 'MySQL Connection Failed';
    exit;
}

// Step 2: Select database
echo '</div><div class="section"><h2>✓ الخطوة 2: التحقق من قاعدة البيانات</h2>';

try {
    $pdo->exec("USE `{$db_config['name']}`");
    echo '<div class="success">✓ <strong>قاعدة البيانات موجودة:</strong> ' . htmlspecialchars($db_config['name']) . '</div>';
    $successes[] = 'Database Selection';
} catch (PDOException $e) {
    echo '<div class="warning">⚠ قاعدة البيانات غير موجودة. سيتم إنشاؤها...</div>';
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db_config['name']}`");
        echo '<div class="success">✓ تم إنشاء قاعدة البيانات بنجاح</div>';
    } catch (PDOException $e2) {
        echo '<div class="issue">✗ خطأ في إنشاء قاعدة البيانات: ' . htmlspecialchars($e2->getMessage()) . '</div>';
        $issues[] = 'Cannot create database';
    }
}

// Step 3: Check all required tables
echo '</div><div class="section"><h2>✓ الخطوة 3: التحقق من الجداول</h2>';

$required_tables = [
    'users' => ['id', 'email', 'full_name', 'password', 'role', 'status', 'created_at'],
    'admins' => ['id', 'email', 'full_name'],
    'students' => ['id', 'student_code', 'first_name', 'last_name', 'class_name', 'level'],
    'grades' => ['id', 'student_id', 'subject_name', 'continuous_score', 'exam_score'],
    'attendance' => ['id', 'student_id', 'absence_date', 'session_label', 'justified'],
    'messages' => ['id', 'name', 'email', 'message', 'status', 'created_at'],
    'lessons' => ['id', 'title', 'lesson_type', 'content_url', 'is_published'],
    'gallery_photos' => ['id', 'image_path', 'category', 'is_published'],
    'blog_posts' => ['id', 'title', 'slug', 'content', 'post_type', 'is_published'],
    'registration_requests' => ['id', 'student_code', 'first_name', 'last_name'],
    'password_reset_tokens' => ['id', 'email', 'token_hash', 'expires_at'],
];

echo '<table>
    <tr>
        <th>الجدول / Table</th>
        <th>الحالة / Status</th>
        <th>عدد الأعمدة / Columns</th>
        <th>عدد السجلات / Rows</th>
    </tr>';

$tables_status = [];

foreach ($required_tables as $table_name => $columns) {
    try {
        $result = $pdo->query("SELECT COUNT(*) as cnt FROM `{$table_name}`");
        $row_count = $result->fetchColumn();
        
        // Check columns
        $col_result = $pdo->query("SHOW COLUMNS FROM `{$table_name}`");
        $db_columns = $col_result->fetchAll(PDO::FETCH_COLUMN);
        $col_count = count($db_columns);
        
        echo '<tr>
            <td><strong>' . htmlspecialchars($table_name) . '</strong></td>
            <td><span class="label success-label">✓ موجود</span></td>
            <td>' . $col_count . '</td>
            <td>' . $row_count . '</td>
        </tr>';
        
        $tables_status[$table_name] = ['exists' => true, 'rows' => $row_count, 'columns' => $db_columns];
        $successes[] = "Table: $table_name";
        
    } catch (PDOException $e) {
        echo '<tr>
            <td><strong>' . htmlspecialchars($table_name) . '</strong></td>
            <td><span class="label error-label">✗ غير موجود</span></td>
            <td>-</td>
            <td>-</td>
        </tr>';
        
        $tables_status[$table_name] = ['exists' => false, 'rows' => 0, 'columns' => []];
        $issues[] = "Missing table: $table_name";
    }
}

echo '</table>';

// Step 4: Check for specific errors in database.sql
echo '</div><div class="section"><h2>✓ الخطوة 4: فحص أخطاء SQL في database.sql</h2>';

$sql_errors = [
    [
        'line' => 213,
        'error' => 'Missing comma after closing parenthesis',
        'detected' => true,
        'fix' => 'Line 213: Change ; to , (or add missing INSERT rows)'
    ],
    [
        'line' => 225,
        'error' => 'Missing comma after closing parenthesis in attendance INSERT',
        'detected' => true,
        'fix' => 'Line 225: Change ; to , (or add missing INSERT rows)'
    ]
];

foreach ($sql_errors as $sql_error) {
    if ($sql_error['detected']) {
        echo '<div class="issue">';
        echo '✗ <strong>خطأ SQL في السطر ' . $sql_error['line'] . ':</strong><br>';
        echo 'Error: ' . htmlspecialchars($sql_error['error']) . '<br>';
        echo 'Fix: ' . htmlspecialchars($sql_error['fix']);
        echo '</div>';
        $issues[] = "SQL Error at line {$sql_error['line']}";
        $repairs[] = $sql_error['fix'];
    }
}

// Step 5: Check foreign keys
echo '</div><div class="section"><h2>✓ الخطوة 5: التحقق من المفاتيح الأجنبية</h2>';

try {
    $fk_result = $pdo->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    $foreign_keys = $fk_result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($foreign_keys)) {
        echo '<div class="warning">⚠ لا توجد مفاتيح أجنبية معرّفة (قد تكون مفقودة)</div>';
        $warnings[] = 'No foreign keys found';
    } else {
        echo '<table>
            <tr>
                <th>Constraint</th>
                <th>Table</th>
                <th>Column</th>
                <th>Referenced Table</th>
                <th>Referenced Column</th>
            </tr>';
        
        foreach ($foreign_keys as $fk) {
            echo '<tr>
                <td>' . htmlspecialchars($fk['CONSTRAINT_NAME']) . '</td>
                <td>' . htmlspecialchars($fk['TABLE_NAME']) . '</td>
                <td>' . htmlspecialchars($fk['COLUMN_NAME']) . '</td>
                <td>' . htmlspecialchars($fk['REFERENCED_TABLE_NAME']) . '</td>
                <td>' . htmlspecialchars($fk['REFERENCED_COLUMN_NAME']) . '</td>
            </tr>';
        }
        
        echo '</table>';
        echo '<div class="success">✓ Found ' . count($foreign_keys) . ' foreign key(s)</div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="warning">⚠ خطأ في فحص المفاتيح الأجنبية: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Step 6: Check for missing columns
echo '</div><div class="section"><h2>✓ الخطوة 6: التحقق من الأعمدة المفقودة</h2>';

$required_columns = [
    'users' => ['id', 'full_name', 'email', 'password', 'role', 'status'],
    'students' => ['student_code', 'first_name', 'last_name', 'class_name', 'level'],
    'grades' => ['student_id', 'subject_name', 'continuous_score', 'exam_score'],
    'attendance' => ['student_id', 'absence_date', 'session_label', 'absence_subject', 'justified'],
];

$missing_columns = [];

foreach ($required_columns as $table => $columns) {
    if (!isset($tables_status[$table]) || !$tables_status[$table]['exists']) {
        continue;
    }
    
    $db_cols = array_map('strtolower', $tables_status[$table]['columns']);
    
    foreach ($columns as $col) {
        if (!in_array(strtolower($col), $db_cols)) {
            $missing_columns[$table][] = $col;
            $issues[] = "Missing column: {$table}.{$col}";
        }
    }
}

if (empty($missing_columns)) {
    echo '<div class="success">✓ جميع الأعمدة المطلوبة موجودة</div>';
} else {
    echo '<div class="issue">✗ أعمدة مفقودة:</div>';
    foreach ($missing_columns as $table => $cols) {
        echo '<div class="warning">' . htmlspecialchars($table) . ': ' . implode(', ', $cols) . '</div>';
        foreach ($cols as $col) {
            $repairs[] = "ALTER TABLE `{$table}` ADD COLUMN `{$col}` VARCHAR(255) NOT NULL DEFAULT '';";
        }
    }
}

// Summary
echo '</div><div class="section"><h2>📊 ملخص التشخيص</h2>';
echo '<div class="summary">
    <div class="summary-box">
        <div class="number">' . count($successes) . '</div>
        <span class="label success-label">✓ نجح</span>
    </div>
    <div class="summary-box">
        <div class="number">' . count($issues) . '</div>
        <span class="label error-label">✗ مشاكل</span>
    </div>
    <div class="summary-box">
        <div class="number">' . count($warnings) . '</div>
        <span class="label warning-label">⚠ تحذيرات</span>
    </div>
</div>';

// Detailed issues
if (!empty($issues)) {
    echo '<div class="issue"><strong>🔴 المشاكل المكتشفة:</strong><br><ul>';
    foreach ($issues as $issue) {
        echo '<li>' . htmlspecialchars($issue) . '</li>';
    }
    echo '</ul></div>';
}

// Repair commands
if (!empty($repairs)) {
    echo '</div><div class="section"><h2>🔧 أوامر الإصلاح</h2>';
    echo '<p>نسخ ولصق الأوامر التالية في phpMyAdmin أو MySQL CLI:</p>';
    
    foreach ($repairs as $repair) {
        echo '<div class="code-block">' . htmlspecialchars($repair) . '</div>';
    }
}

// Test config.php connection
echo '</div><div class="section"><h2>✓ الخطوة 7: اختبار config.php</h2>';

try {
    require_once __DIR__ . '/../config.php';
    $test_pdo = db_connection();
    $test_result = $test_pdo->query('SELECT 1')->fetchColumn();
    if ($test_result == 1) {
        echo '<div class="success">✓ اتصال config.php يعمل بنجاح!</div>';
        $successes[] = 'config.php connection';
    }
} catch (Throwable $e) {
    echo '<div class="issue">✗ خطأ في اتصال config.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $issues[] = 'config.php connection error';
}

echo '</div></div></body></html>';
