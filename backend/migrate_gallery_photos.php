<?php
/**
 * Migration: Add gallery_photos table for managing school photos
 * Allows directors and secretaries to add, edit, and delete photos
 */

require_once __DIR__ . '/config.php';

try {
    // Get PDO connection
    $pdo = db_connection();
    
    // Create gallery_photos table
    $sql = "CREATE TABLE IF NOT EXISTS `gallery_photos` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NULL,
        `category` ENUM('trips', 'events', 'ceremonies', 'activities') NOT NULL,
        `image_path` VARCHAR(500) NOT NULL UNIQUE,
        `image_filename` VARCHAR(255) NOT NULL,
        `author_name` VARCHAR(120) NULL,
        `photo_date` DATE NULL,
        `is_published` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_category` (`category`),
        INDEX `idx_published` (`is_published`),
        INDEX `idx_date` (`photo_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
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
