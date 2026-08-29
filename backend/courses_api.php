<?php
/**
 * School Courses & Programs Management API
 * Handles adding, editing, deleting, and listing courses/programs
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

ensure_school_courses_table();

$pdo = db_connection();

function verify_admin_access(): void
{
    if (
        !isset($_SESSION['logged_in']) ||
        $_SESSION['logged_in'] !== true ||
        !isset($_SESSION['user_role']) ||
        !isset($_SESSION['user_email'])
    ) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'يجب تسجيل الدخول أولاً'
        ]);
        exit;
    }

    $allowed_roles = ['director', 'secretary'];
    if (!in_array((string) $_SESSION['user_role'], $allowed_roles, true)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'ليس لديك صلاحيات كافية'
        ]);
        exit;
    }
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? null;

    if (in_array($action, ['admin_list', 'add', 'edit', 'delete', 'get'])) {
        verify_admin_access();
    }

    switch ($action) {
        case 'list':
            $stmt = $pdo->prepare("
                SELECT id, title, description, category, level_tag, image_path, is_published, created_at, updated_at
                FROM school_courses
                WHERE is_published = 1
                ORDER BY id ASC
            ");
            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $courses
            ]);
            break;

        case 'admin_list':
            $stmt = $pdo->prepare("
                SELECT id, title, description, category, level_tag, image_path, is_published, created_at, updated_at
                FROM school_courses
                ORDER BY id ASC
            ");
            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $courses
            ]);
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($id <= 0) {
                throw new Exception('معرف الشعبة غير صحيح');
            }

            $stmt = $pdo->prepare("SELECT * FROM school_courses WHERE id = ?");
            $stmt->execute([$id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                throw new Exception('الشعبة غير موجودة');
            }

            echo json_encode([
                'success' => true,
                'data' => $course
            ]);
            break;

        case 'add':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? 'middle');
            $level_tag = trim($_POST['level_tag'] ?? '');
            $is_published = isset($_POST['is_published']) ? (int) $_POST['is_published'] : 1;

            if (empty($title)) {
                throw new Exception('عنوان الشعبة أو البرنامج مطلوب');
            }

            $imagePath = 'images/kaoukab_alouloum1.jpg'; // default fallback image

            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed, true)) {
                    throw new Exception('نوع الصورة غير مدعوم (مسموح بـ JPG, PNG, WEBP, GIF)');
                }

                $uploadDir = __DIR__ . '/../images/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $filename = 'course_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $targetFile = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $imagePath = 'images/' . $filename;
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO school_courses (title, description, category, level_tag, image_path, is_published)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $description, $category, $level_tag, $imagePath, $is_published]);

            echo json_encode([
                'success' => true,
                'message' => 'تمت إضافة الشعبة/البرنامج بنجاح',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'edit':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? 'middle');
            $level_tag = trim($_POST['level_tag'] ?? '');
            $is_published = isset($_POST['is_published']) ? (int) $_POST['is_published'] : 1;

            if ($id <= 0) {
                throw new Exception('معرف الشعبة غير صحيح');
            }
            if (empty($title)) {
                throw new Exception('عنوان الشعبة مطلوب');
            }

            // Fetch existing
            $stmt = $pdo->prepare("SELECT * FROM school_courses WHERE id = ?");
            $stmt->execute([$id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                throw new Exception('الشعبة غير موجودة');
            }

            $imagePath = $existing['image_path'];

            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed, true)) {
                    throw new Exception('نوع الصورة غير مدعوم');
                }

                $uploadDir = __DIR__ . '/../images/';
                $filename = 'course_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $targetFile = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $imagePath = 'images/' . $filename;
                }
            }

            $stmt = $pdo->prepare("
                UPDATE school_courses
                SET title = ?, description = ?, category = ?, level_tag = ?, image_path = ?, is_published = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $category, $level_tag, $imagePath, $is_published, $id]);

            echo json_encode([
                'success' => true,
                'message' => 'تم حفظ التعديلات بنجاح'
            ]);
            break;

        case 'delete':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                throw new Exception('معرف الشعبة غير صحيح');
            }

            $stmt = $pdo->prepare("DELETE FROM school_courses WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'success' => true,
                'message' => 'تم حذف الشعبة بنجاح'
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'إجراء غير معروف'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
