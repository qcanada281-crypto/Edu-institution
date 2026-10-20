<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_post_request();
ensure_lessons_table();

$type = strtolower(clean_input((string) ($_POST['type'] ?? '')));
$search = clean_input((string) ($_POST['search'] ?? ''));

$allowedTypes = ['pdf', 'video', 'image'];
if ($type !== '' && !in_array($type, $allowedTypes, true)) {
    json_response(false, 'نوع المحتوى غير صالح.', [], 400);
}

try {
    $query = 'SELECT id, title, description, lesson_type, level, subject_name, content_url, thumbnail_url, created_at
              FROM lessons
              WHERE is_published = 1';
    $params = [];

    if ($type !== '') {
        $query .= ' AND lesson_type = :lesson_type';
        $params['lesson_type'] = $type;
    }

    if ($search !== '') {
        $query .= ' AND (
            title LIKE :search
            OR description LIKE :search
            OR level LIKE :search
            OR subject_name LIKE :search
        )';
        $params['search'] = '%' . $search . '%';
    }

    $query .= ' ORDER BY created_at DESC, id DESC';

    $rows = db_query($query, $params)->fetchAll();
    json_response(true, 'تم جلب الدروس بنجاح.', ['lessons' => $rows]);
} catch (Throwable $exception) {
    error_log('Public lessons list error: ' . $exception->getMessage());
    json_response(false, 'تعذر جلب الدروس حاليا.', [], 500);
}
