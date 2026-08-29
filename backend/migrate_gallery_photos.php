<?php
/**
 * Migration: Add gallery_photos table for managing school photos
 * Allows directors and secretaries to add, edit, and delete photos
 */

require_once __DIR__ . '/config.php';

try {
    ensure_gallery_photos_table();
    echo "✓ جدول الصور في المعرض تم إنشاؤه/التحديث بنجاح<br>";
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../images/gallery/';
    if (!is_dir($upload_dir)) {
        if (mkdir($upload_dir, 0755, true)) {
            echo "✓ مجلد الصور (images/gallery/) تم إنشاؤه بنجاح<br>";
        } else {
            echo "⚠ تحذير: لم يتمكن من إنشاء مجلد الصور<br>";
        }
    } else {
        echo "✓ مجلد الصور موجود بالفعل<br>";
    }
    
    echo "<br><strong style='color: green;'>✓ تم إنشاء قاعدة البيانات بنجاح!</strong><br>";
    echo "<p>يمكن الآن للمدير والسكرتير إضافة وتعديل وحذف الصور من لوحة التحكم</p>";
    
} catch (PDOException $e) {
    echo "✗ خطأ في الهجرة: " . htmlspecialchars($e->getMessage()) . "<br>";
    exit(1);
}
?>
