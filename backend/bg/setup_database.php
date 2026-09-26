<?php
/**
 * Database Setup Script for edu_institution
 * This script will:
 * 1. Create the database if it doesn't exist
 * 2. Import all tables from database.sql
 * 3. Import the missing registration_requests table
 * 4. Verify the installation
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

// Database connection details
$db_host = '127.0.0.1:3306';
$db_user = 'root';
$db_pass = '';
$db_name = 'portfolio_db';

echo '<!DOCTYPE html>';
echo '<html lang="ar" dir="rtl">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>إعداد قاعدة البيانات - مؤسسة Kawkab Al Ouloum</title>';
echo '<style>';
echo 'body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; direction: rtl; }';
echo '.container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }';
echo 'h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }';
echo 'h2 { color: #2980b9; margin-top: 30px; }';
echo '.success { color: #27ae60; background: #d5f4e6; padding: 15px; border-radius: 5px; border-right: 4px solid #27ae60; margin: 10px 0; }';
echo '.error { color: #e74c3c; background: #fadbd8; padding: 15px; border-radius: 5px; border-right: 4px solid #e74c3c; margin: 10px 0; }';
echo '.info { color: #2980b9; background: #d6eaf8; padding: 15px; border-radius: 5px; border-right: 4px solid #2980b9; margin: 10px 0; }';
echo '.warning { color: #f39c12; background: #fef5e7; padding: 15px; border-radius: 5px; border-right: 4px solid #f39c12; margin: 10px 0; }';
echo 'table { border-collapse: collapse; width: 100%; margin-top: 20px; }';
echo 'th, td { border: 1px solid #ddd; padding: 12px; text-align: right; }';
echo 'th { background-color: #3498db; color: white; }';
echo 'tr:nth-child(even) { background-color: #f2f2f2; }';
echo '.step { background: #ecf0f1; padding: 15px; margin: 15px 0; border-radius: 5px; }';
echo '.step-number { display: inline-block; width: 30px; height: 30px; background: #3498db; color: white; border-radius: 50%; text-align: center; line-height: 30px; margin-left: 10px; font-weight: bold; }';
echo 'a { color: #3498db; text-decoration: none; }';
echo 'a:hover { text-decoration: underline; }';
echo '.btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; border-radius: 5px; text-decoration: none; margin: 5px; }';
echo '.btn:hover { background: #2980b9; text-decoration: none; }';
echo '</style>';
echo '</head>';
echo '<body>';
echo '<div class="container">';
echo '<h1>🎓 إعداد قاعدة البيانات - مؤسسة Kawkab Al Ouloum التعليمية</h1>';

$steps = [];
$errors = [];

try {
    // Step 1: Connect to MySQL (without selecting database)
    echo '<div class="step"><span class="step-number">1</span> <strong>الاتصال بـ MySQL</strong></div>';
    $pdo_no_db = new PDO("mysql:host={$db_host};charset=utf8mb4", $db_user, $db_pass);
    $pdo_no_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo '<div class="success">✓ تم الاتصال بـ MySQL بنجاح</div>';
    
    // Step 2: Create database if not exists
    echo '<div class="step"><span class="step-number">2</span> <strong>إنشاء قاعدة البيانات</strong></div>';
    $pdo_no_db->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo '<div class="success">✓ تم إنشاء قاعدة البيانات أو التأكد من وجودها</div>';
    
    // Step 3: Select the database
    $pdo_no_db->exec("USE `{$db_name}`");
    
    // Step 4: Import database.sql
    echo '<div class="step"><span class="step-number">3</span> <strong>استيراد database.sql</strong></div>';
    $sql_file = __DIR__ . '/database.sql';
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL by statements (handle multiple queries)
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        
        $imported_tables = 0;
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            try {
                $pdo_no_db->exec($statement);
                $imported_tables++;
            } catch (PDOException $e) {
                // Ignore errors for CREATE TABLE IF NOT EXISTS or INSERT duplicates
                if (strpos($e->getMessage(), 'already exists') === false) {
                    $errors[] = "SQL Import Warning: " . $e->getMessage();
                }
            }
        }
        echo '<div class="success">✓ تم استيراد database.sql (' . $imported_tables . ' statements executed)</div>';
    } else {
        echo '<div class="error">✗ ملف database.sql غير موجود في: ' . htmlspecialchars($sql_file) . '</div>';
    }
    
    // Step 5: Create registration_requests table
    echo '<div class="step"><span class="step-number">4</span> <strong>إنشاء جدول registration_requests</strong></div>';
    $reg_sql_file = __DIR__ . '/registration_requests_table.sql';
    if (file_exists($reg_sql_file)) {
        $reg_sql_content = file_get_contents($reg_sql_file);
        $pdo_no_db->exec($reg_sql_content);
        echo '<div class="success">✓ تم إنشاء جدول registration_requests بنجاح</div>';
    } else {
        echo '<div class="warning">⚠ ملف registration_requests_table.sql غير موجود</div>';
    }
    
    // Step 6: Verify all tables
    echo '<div class="step"><span class="step-number">5</span> <strong>التحقق من الجداول</strong></div>';
    
    $required_tables = [
        'messages' => 'الرسائل (Messages)',
        'personal_info' => 'المعلومات الشخصية (Personal Info)',
        'projects' => 'المشاريع (Projects)',
        'skills' => 'المهارات (Skills)',
    ];
    
    echo '<table>';
    echo '<tr><th>الجدول</th><th>الوصف</th><th>الحالة</th><th>عدد السجلات</th></tr>';
    
    foreach ($required_tables as $table_name => $table_desc) {
        try {
            $stmt = $pdo_no_db->query("SELECT COUNT(*) as count FROM `{$table_name}`");
            $count = $stmt->fetchColumn();
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($table_name) . '</strong></td>';
            echo '<td>' . htmlspecialchars($table_desc) . '</td>';
            echo '<td style="color: #27ae60;">✓ موجود</td>';
            echo '<td>' . $count . ' سجل</td>';
            echo '</tr>';
        } catch (PDOException $e) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($table_name) . '</strong></td>';
            echo '<td>' . htmlspecialchars($table_desc) . '</td>';
            echo '<td style="color: #e74c3c;">✗ غير موجود</td>';
            echo '<td>-</td>';
            echo '</tr>';
            $errors[] = "Table {$table_name} is missing";
        }
    }
    echo '</table>';
    
    // Step 7: Test connection with config.php
    echo '<div class="step"><span class="step-number">6</span> <strong>اختبار الاتصال عبر config.php</strong></div>';
    require_once __DIR__ . '/../config.php';
    
    try {
        $test_pdo = db_connection();
        $test_result = $test_pdo->query('SELECT 1')->fetchColumn();
        if ($test_result == 1) {
            echo '<div class="success">✓ الاتصال عبر config.php يعمل بنجاح!</div>';
        }
    } catch (Throwable $e) {
        echo '<div class="error">✗ خطأ في الاتصال: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $errors[] = $e->getMessage();
    }
    
    // Final Summary
    echo '<h2>📊 ملخص الإعداد</h2>';
    
    if (empty($errors)) {
        echo '<div class="success">';
        echo '<h3>🎉 تم الإعداد بنجاح!</h3>';
        echo '<p>قاعدة البيانات جاهزة للاستخدام. يمكنك الآن:</p>';
        echo '<ul>';
        echo '<li>تسجيل طالب جديد من صفحة: <a href="../inscription.html">inscription.html</a></li>';
        echo '<li>تسجيل الدخول للإدارة من: <a href="../login.html">login.html</a></li>';
        echo '<li>الصفحة الرئيسية: <a href="../index.html">index.html</a></li>';
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<div class="warning">';
        echo '<h3>⚠️ توجد بعض التحذيرات:</h3>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    echo '<h2>🔗 روابط مفيدة</h2>';
    echo '<a href="../index.html" class="btn">الصفحة الرئيسية</a>';
    echo '<a href="../login.html" class="btn">تسجيل الدخول</a>';
    echo '<a href="../inscription.html" class="btn">تسجيل طالب</a>';
    echo '<a href="db_test.php" class="btn">اختبار قاعدة البيانات</a>';
    
} catch (PDOException $e) {
    echo '<div class="error">';
    echo '<h3>✗ خطأ في الاتصال بـ MySQL</h3>';
    echo '<p><strong>الخطأ:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<h3>خطوات حل المشكلة:</h3>';
    echo '<ol>';
    echo '<li>تأكد أن WAMP server يعمل (الأيقونة خضراء)</li>';
    echo '<li>تأكد أن MySQL service يعمل</li>';
    echo '<li>تحقق من بيانات الاتصال:</li>';
    echo '<ul>';
    echo '<li>Host: ' . htmlspecialchars($db_host) . '</li>';
    echo '<li>User: ' . htmlspecialchars($db_user) . '</li>';
    echo '<li>Password: ' . (empty($db_pass) ? '(empty)' : '***') . '</li>';
    echo '</ul>';
    echo '<li>افتح phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></li>';
    echo '</ol>';
    echo '</div>';
}

echo '</div>';
echo '</body>';
echo '</html>';
