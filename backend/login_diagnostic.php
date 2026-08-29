<?php
/**
 * LOGIN DIAGNOSTIC TOOL
 * Diagnostique les problèmes de login
 */

header('Content-Type: text/html; charset=UTF-8');

echo '<!DOCTYPE html>';
echo '<html lang="ar" dir="rtl">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<title>تشخيص الدخول</title>';
echo '<style>';
echo 'body { font-family: Arial; padding: 20px; background: #f5f5f5; }';
echo '.container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }';
echo 'h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 15px; }';
echo '.test { padding: 15px; margin: 15px 0; border-radius: 5px; }';
echo '.success { background: #d5f4e6; border-left: 4px solid #27ae60; color: #27ae60; }';
echo '.error { background: #fadbd8; border-left: 4px solid #e74c3c; color: #e74c3c; }';
echo '.info { background: #d6eaf8; border-left: 4px solid #2980b9; color: #2980b9; }';
echo 'code { background: #ecf0f1; padding: 5px 10px; border-radius: 3px; font-family: monospace; }';
echo '</style>';
echo '</head>';
echo '<body>';
echo '<div class="container">';
echo '<h1>🔍 تشخيص مشاكل الدخول</h1>';

// Test 1: Check if config.php can be loaded
echo '<div class="test info">';
echo '<h2>✓ اختبار 1: تحميل ملف الإعدادات</h2>';

$config_path = __DIR__ . '/config.php';
if (file_exists($config_path)) {
    echo '<div class="success">✓ ملف config.php موجود</div>';
    
    try {
        require_once $config_path;
        echo '<div class="success">✓ تم تحميل config.php بنجاح</div>';
        
        // Test 2: Try database connection
        echo '</div><div class="test info"><h2>✓ اختبار 2: الاتصال بقاعدة البيانات</h2>';
        
        try {
            $pdo = db_connection();
            echo '<div class="success">✓ الاتصال بقاعدة البيانات نجح!</div>';
            
            // Test 3: Check users table
            echo '</div><div class="test info"><h2>✓ اختبار 3: جدول المستخدمين</h2>';
            
            try {
                $result = $pdo->query('SELECT COUNT(*) as count FROM users');
                $count = $result->fetchColumn();
                echo '<div class="success">✓ جدول users يحتوي على ' . $count . ' مستخدم</div>';
                
                // Test 4: Check students table
                echo '</div><div class="test info"><h2>✓ اختبار 4: جدول الطلاب</h2>';
                
                try {
                    $result = $pdo->query('SELECT COUNT(*) as count FROM students');
                    $count = $result->fetchColumn();
                    echo '<div class="success">✓ جدول students يحتوي على ' . $count . ' طالب</div>';
                    
                    // Test 5: Check test student
                    echo '</div><div class="test info"><h2>✓ اختبار 5: البيانات الاختبارية</h2>';
                    
                    $result = $pdo->query("SELECT * FROM students WHERE student_code = 'G142026001'");
                    $student = $result->fetch();
                    
                    if ($student) {
                        echo '<div class="success">✓ الطالب الاختباري موجود: ' . $student['first_name'] . ' ' . $student['last_name'] . '</div>';
                        echo '<div class="info">';
                        echo '<strong>البيانات:</strong><br>';
                        echo 'كود: ' . $student['student_code'] . '<br>';
                        echo 'تاريخ الميلاد: ' . $student['birth_date'] . '<br>';
                        echo 'الفصل: ' . $student['class_name'] . '<br>';
                        echo 'المستوى: ' . $student['level'];
                        echo '</div>';
                    } else {
                        echo '<div class="error">✗ الطالب الاختباري غير موجود</div>';
                        echo '<div class="info">للدخول، استخدم البيانات التالية:<br>';
                        echo '<code>Code: G142026001<br>Date: 2008-04-16</code>';
                        echo '</div>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="error">✗ خطأ في جدول students: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">✗ خطأ في جدول users: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">✗ خطأ في الاتصال بقاعدة البيانات: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="info">';
            echo '<strong>تأكد من:</strong><br>';
            echo '1. MySQL يعمل على البورت 3308<br>';
            echo '2. قاعدة البيانات edu_institution موجودة<br>';
            echo '3. المستخدم root لديه إذن الوصول<br>';
            echo '4. لا يوجد كلمة مرور للمستخدم root<br>';
            echo '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">✗ خطأ في تحميل config.php: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
    
} else {
    echo '<div class="error">✗ ملف config.php غير موجود في: ' . htmlspecialchars($config_path) . '</div>';
}

echo '</div>';
echo '<div class="test info" style="margin-top: 30px;">';
echo '<h2>📝 الخطوات الموصى بها:</h2>';
echo '<ol>';
echo '<li>تأكد من أن MySQL قيد التشغيل (أنظر إلى XAMPP Control Panel)</li>';
echo '<li>افتح phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></li>';
echo '<li>تحقق من وجود قاعدة البيانات: edu_institution</li>';
echo '<li>عد إلى صفحة الدخول: <a href="../login.html">login.html</a></li>';
echo '<li>جرب البيانات التالية:<br><code>Code: G142026001 | Date: 2008-04-16</code></li>';
echo '</ol>';
echo '</div>';
echo '</div>';
echo '</body>';
echo '</html>';
