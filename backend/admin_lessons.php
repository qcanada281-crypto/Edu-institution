<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_post_request();
$currentRole = require_admin_access(['director', 'secretary']);
ensure_lessons_table();

$action = clean_input((string) ($_POST['action'] ?? ''));

function url_host(string $url): string
{
    $host = (string) (parse_url($url, PHP_URL_HOST) ?? '');
    return strtolower($host);
}

function url_path(string $url): string
{
    return strtolower((string) (parse_url($url, PHP_URL_PATH) ?? ''));
}

function text_contains(string $haystack, string $needle): bool
{
    if ($needle === '') {
        return true;
    }
    return strpos($haystack, $needle) !== false;
}

function text_ends_with(string $value, string $suffix): bool
{
    if ($suffix === '') {
        return true;
    }
    $valueLength = strlen($value);
    $suffixLength = strlen($suffix);
    if ($suffixLength > $valueLength) {
        return false;
    }
    return substr($value, -$suffixLength) === $suffix;
}

function is_valid_content_url_by_type(string $lessonType, string $url): bool
{
    $host = url_host($url);
    $path = url_path($url);

    if ($lessonType === 'video') {
        $isYoutube = text_contains($host, 'youtube.com') || text_contains($host, 'youtu.be');
        $isVimeo = text_contains($host, 'vimeo.com');
        $isDirectVideo = (bool) preg_match('/\.(mp4|webm|ogg|m3u8)$/i', $path);
        return $isYoutube || $isVimeo || $isDirectVideo;
    }

    if ($lessonType === 'pdf') {
        if (text_ends_with($path, '.pdf')) {
            return true;
        }
        return text_contains(strtolower($url), '.pdf');
    }

    if ($lessonType === 'image') {
        return (bool) preg_match('/\.(jpg|jpeg|png|webp|gif|svg)$/i', $path);
    }

    return false;
}

function is_valid_image_url(string $url): bool
{
    $path = url_path($url);
    return (bool) preg_match('/\.(jpg|jpeg|png|webp|gif|svg)$/i', $path);
}

if ($action === 'list') {
    try {
        $rows = db_query(
            'SELECT id, title, description, lesson_type, level, subject_name, content_url, thumbnail_url, is_published, created_by_role, created_at
             FROM lessons
             ORDER BY created_at DESC, id DESC'
        )->fetchAll();
        json_response(true, 'تم جلب الدروس بنجاح.', ['lessons' => $rows]);
    } catch (Throwable $exception) {
        error_log('Lessons list error: ' . $exception->getMessage());
        json_response(false, 'تعذر جلب الدروس.', [], 500);
    }
}

if ($action === 'save') {
    // Only 'title' and 'lesson_type' are strictly required now.
    $missing = require_fields($_POST, ['title', 'lesson_type']);
    if (!empty($missing)) {
        json_response(false, 'العنوان والنوع حقول إجبارية.');
    }

    $id = (int) ($_POST['lesson_id'] ?? 0);
    $title = clean_input((string) $_POST['title']);
    $description = clean_input((string) ($_POST['description'] ?? ''));
    $lessonType = strtolower(clean_input((string) $_POST['lesson_type']));
    $level = clean_input((string) ($_POST['level'] ?? ''));
    $subjectName = clean_input((string) ($_POST['subject_name'] ?? ''));
    $contentUrl = clean_input((string) ($_POST['content_url'] ?? ''));
    $thumbnailUrl = clean_input((string) ($_POST['thumbnail_url'] ?? ''));
    $isPublished = clean_input((string) ($_POST['is_published'] ?? '1')) === '1' ? 1 : 0;

    // Handle file upload
    if (isset($_FILES['lesson_file']) && $_FILES['lesson_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/lessons/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileTmpPath = $_FILES['lesson_file']['tmp_name'];
        $fileName = $_FILES['lesson_file']['name'];
        $fileSize = $_FILES['lesson_file']['size'];
        
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = [];
        if ($lessonType === 'pdf') {
            $allowedExts = ['pdf'];
        } elseif ($lessonType === 'video') {
            $allowedExts = ['mp4', 'webm', 'ogg'];
        } elseif ($lessonType === 'image') {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        }

        if (!in_array($fileExt, $allowedExts, true)) {
            json_response(false, "امتداد الملف غير مسموح به لنوع الدرس المختار. المسموح: " . implode(", ", $allowedExts));
        }

        if ($fileSize > 250 * 1024 * 1024) { // 250MB limit
            json_response(false, 'حجم الملف كبير جداً. الحد الأقصى 250MB.');
        }

        $newFileName = uniqid('lesson_', true) . '.' . $fileExt;
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // Update content URL to point to the relative path
            $contentUrl = 'uploads/lessons/' . $newFileName;
        } else {
            json_response(false, 'حدث خطأ أثناء رفع الملف.');
        }
    }

    if ($contentUrl === '') {
        json_response(false, 'يجب إدخال رابط المحتوى أو رفع ملف.');
    }

    if (mb_strlen($title) < 3 || mb_strlen($title) > 180) {
        json_response(false, 'عنوان الدرس يجب أن يكون بين 3 و180 حرفا.');
    }
    if (!in_array($lessonType, ['pdf', 'video', 'image'], true)) {
        json_response(false, 'نوع الدرس غير صالح.');
    }
    if ($description !== '' && mb_strlen($description) > 2000) {
        json_response(false, 'وصف الدرس طويل جدا.');
    }
    if ($level !== '' && mb_strlen($level) > 80) {
        json_response(false, 'المستوى طويل جدا.');
    }
    if ($subjectName !== '' && mb_strlen($subjectName) > 120) {
        json_response(false, 'المادة طويلة جدا.');
    }
    // Only check validity if it's an HTTP URL (not uploaded file path)
    if (strpos($contentUrl, 'uploads/lessons/') !== 0) {
        if (!filter_var($contentUrl, FILTER_VALIDATE_URL) || mb_strlen($contentUrl) > 500) {
            json_response(false, 'رابط المحتوى غير صالح.');
        }
        if (!is_valid_content_url_by_type($lessonType, $contentUrl)) {
            if ($lessonType === 'video') {
                json_response(false, 'رابط الفيديو يجب أن يكون YouTube/Vimeo أو ملف فيديو مباشر (mp4/webm).');
            }
            if ($lessonType === 'pdf') {
                json_response(false, 'رابط PDF غير صالح. استعمل رابطا ينتهي بـ .pdf');
            }
            json_response(false, 'رابط الصورة غير صالح. استعمل رابط صورة حقيقي (jpg/png/webp/gif).');
        }
    }
    
    if ($thumbnailUrl !== '' && (!filter_var($thumbnailUrl, FILTER_VALIDATE_URL) || mb_strlen($thumbnailUrl) > 500)) {
        json_response(false, 'رابط الصورة المصغرة غير صالح.');
    }
    if ($thumbnailUrl !== '' && !is_valid_image_url($thumbnailUrl)) {
        json_response(false, 'رابط الصورة المصغرة يجب أن يكون رابط صورة مباشر.');
    }

    if ($lessonType === 'image' && $thumbnailUrl === '') {
        $thumbnailUrl = $contentUrl;
    }

    try {
        if ($id > 0) {
            $existing = db_query('SELECT id FROM lessons WHERE id = :id LIMIT 1', ['id' => $id])->fetch();
            if (!$existing) {
                json_response(false, 'الدرس غير موجود.');
            }

            db_query(
                'UPDATE lessons
                 SET title = :title,
                     description = :description,
                     lesson_type = :lesson_type,
                     level = :level,
                     subject_name = :subject_name,
                     content_url = :content_url,
                     thumbnail_url = :thumbnail_url,
                     is_published = :is_published
                 WHERE id = :id',
                [
                    'id' => $id,
                    'title' => $title,
                    'description' => $description === '' ? null : $description,
                    'lesson_type' => $lessonType,
                    'level' => $level === '' ? null : $level,
                    'subject_name' => $subjectName === '' ? null : $subjectName,
                    'content_url' => $contentUrl,
                    'thumbnail_url' => $thumbnailUrl === '' ? null : $thumbnailUrl,
                    'is_published' => $isPublished,
                ]
            );

            json_response(true, 'تم تحديث الدرس بنجاح.');
        }

        db_query(
            'INSERT INTO lessons (
                title,
                description,
                lesson_type,
                level,
                subject_name,
                content_url,
                thumbnail_url,
                is_published,
                created_by_role
            ) VALUES (
                :title,
                :description,
                :lesson_type,
                :level,
                :subject_name,
                :content_url,
                :thumbnail_url,
                :is_published,
                :created_by_role
            )',
            [
                'title' => $title,
                'description' => $description === '' ? null : $description,
                'lesson_type' => $lessonType,
                'level' => $level === '' ? null : $level,
                'subject_name' => $subjectName === '' ? null : $subjectName,
                'content_url' => $contentUrl,
                'thumbnail_url' => $thumbnailUrl === '' ? null : $thumbnailUrl,
                'is_published' => $isPublished,
                'created_by_role' => $currentRole,
            ]
        );

        json_response(true, 'تمت إضافة الدرس بنجاح.');
    } catch (Throwable $exception) {
        error_log('Lessons save error: ' . $exception->getMessage());
        json_response(false, 'تعذر حفظ الدرس.', [], 500);
    }
}

if ($action === 'delete') {
    $lessonId = (int) ($_POST['lesson_id'] ?? 0);
    if ($lessonId <= 0) {
        json_response(false, 'معرف الدرس غير صالح.');
    }

    try {
        $existing = db_query('SELECT id FROM lessons WHERE id = :id LIMIT 1', ['id' => $lessonId])->fetch();
        if (!$existing) {
            json_response(false, 'الدرس غير موجود.');
        }

        db_query('DELETE FROM lessons WHERE id = :id', ['id' => $lessonId]);
        json_response(true, 'تم حذف الدرس بنجاح.');
    } catch (Throwable $exception) {
        error_log('Lessons delete error: ' . $exception->getMessage());
        json_response(false, 'تعذر حذف الدرس.', [], 500);
    }
}

json_response(false, 'الإجراء المطلوب غير مدعوم.', [], 400);
