<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
ensure_announcements_table();

// Check if student is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_role'] ?? '') !== 'student') {
    json_response(false, 'غير مصرح لك.', [], 403);
}

try {
    $studentId = (int) $_SESSION['user_id'];
    
    // Get student's class
    $student = db_query(
        "SELECT class_name FROM students WHERE id = :id LIMIT 1",
        ['id' => $studentId]
    )->fetch();
    
    if (!$student) {
        json_response(false, 'لم يتم العثور على التلميذ.');
    }
    
    $className = $student['class_name'];
    
    // Fetch announcements
    $announcements = db_query(
        "SELECT a.id, a.title, a.content, a.type, a.created_at,
            a.file_path, a.file_path AS attachment_url,
            t.full_name as teacher_name
         FROM announcements a
         JOIN teachers t ON a.teacher_id = t.id
         WHERE a.class_name = :class_name OR a.class_name IS NULL OR a.class_name = 'all'
         ORDER BY a.created_at DESC",
        ['class_name' => $className]
    )->fetchAll(PDO::FETCH_ASSOC);
    
    json_response(true, 'تم جلب الإعلانات بنجاح.', ['announcements' => $announcements]);
} catch (Throwable $e) {
    error_log('Portal Announcements List Error: ' . $e->getMessage());
    json_response(false, 'حدث خطأ أثناء جلب الإعلانات.', [], 500);
}
