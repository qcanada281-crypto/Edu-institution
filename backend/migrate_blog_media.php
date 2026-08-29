<?php
/**
 * Migration: Add blog_posts table for storing blog articles with media
 * This migration creates a table to store blog posts with images, videos, and descriptions
 */

require_once __DIR__ . '/config.php';

try {
    if (!isset($pdo) || !$pdo) {
        $pdo = db_connection();
    }
    // Create blog_posts table
    $sql = "CREATE TABLE IF NOT EXISTS `blog_posts` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NULL,
        `content` LONGTEXT NULL,
        `post_type` ENUM('article', 'event', 'seminar', 'workshop', 'activity') NOT NULL DEFAULT 'article',
        `category` VARCHAR(100) NULL,
        `featured_image` VARCHAR(500) NULL,
        `media_type` ENUM('image', 'video', 'gallery') NOT NULL DEFAULT 'image',
        `video_url` VARCHAR(500) NULL,
        `gallery_json` JSON NULL COMMENT 'JSON array of image URLs for gallery',
        `author_name` VARCHAR(120) NULL,
        `publish_date` DATE NOT NULL DEFAULT CURDATE(),
        `is_published` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_post_type` (`post_type`),
        INDEX `idx_category` (`category`),
        INDEX `idx_publish_date` (`publish_date`),
        INDEX `idx_is_published` (`is_published`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    if ($pdo->exec($sql)) {
        // Insert sample blog posts with media
        $samplePosts = [
            [
                'title' => 'ندوة توعية حول الصحة النفسية',
                'description' => 'تنظيم ندوة توعية شاملة للتلاميذ حول أهمية الصحة النفسية والتعامل مع الضغوط الدراسية',
                'post_type' => 'seminar',
                'category' => 'صحة و وعي',
                'author_name' => 'المؤسسة التعليمية',
                'publish_date' => date('Y-m-d', strtotime('-5 days')),
                'media_type' => 'image',
                'featured_image' => 'images/events/seminar-1.jpg'
            ],
            [
                'title' => 'مسابقة الخطابة والإلقاء',
                'description' => 'تنظيم مسابقة أولى للخطابة والإلقاء بين تلاميذ المؤسسة، اختبار المهارات الشفوية والثقة بالنفس',
                'post_type' => 'competition',
                'category' => 'أنشطة',
                'author_name' => 'قسم اللغات',
                'publish_date' => date('Y-m-d', strtotime('-3 days')),
                'media_type' => 'gallery'
            ],
            [
                'title' => 'ورشة عمل: مهارات البحث العلمي',
                'description' => 'ورشة تدريبية متقدمة حول طرق البحث العلمي والكتابة الأكاديمية للطلاب المتفوقين',
                'post_type' => 'workshop',
                'category' => 'تعليم',
                'author_name' => 'قسم العلوم',
                'publish_date' => date('Y-m-d'),
                'media_type' => 'video',
                'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ'
            ],
            [
                'title' => 'رحلة ميدانية إلى المتحف الوطني',
                'description' => 'تنظيم رحلة تعليمية لتلاميذ المستوى الثاني حول الحضارة المغربية والتراث الثقافي',
                'post_type' => 'activity',
                'category' => 'رحلات',
                'author_name' => 'قسم الدراسات الاجتماعية',
                'publish_date' => date('Y-m-d', strtotime('-1 day')),
                'media_type' => 'gallery'
            ],
            [
                'title' => 'تكريم الطلاب المتفوقين',
                'description' => 'حفل تكريم الطلاب المتفوقين في المستويات المختلفة وتوزيع الجوائز والشهادات',
                'post_type' => 'event',
                'category' => 'فعاليات',
                'author_name' => 'الإدارة',
                'publish_date' => date('Y-m-d', strtotime('-7 days')),
                'media_type' => 'image',
                'featured_image' => 'images/events/awards.jpg'
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO `blog_posts` 
            (`title`, `description`, `post_type`, `category`, `author_name`, `publish_date`, `media_type`, `featured_image`, `video_url`, `is_published`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

        foreach ($samplePosts as $post) {
            $stmt->execute([
                $post['title'],
                $post['description'],
                $post['post_type'],
                $post['category'],
                $post['author_name'],
                $post['publish_date'],
                $post['media_type'],
                $post['featured_image'] ?? null,
                $post['video_url'] ?? null
            ]);
        }

        echo "✓ جدول المدونة والأحداث تم إنشاؤه بنجاح مع بيانات تجريبية<br>";
    }
} catch (PDOException $e) {
    echo "✗ خطأ في الهجرة: " . htmlspecialchars($e->getMessage()) . "<br>";
    exit(1);
}
?>
