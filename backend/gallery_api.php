<?php
/**
 * Gallery Management API
 * Handles adding, editing, deleting, and listing gallery photos
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

// Get PDO connection
$pdo = db_connection();

// Verify admin access for admin operations
function verify_admin_access() {
    if (!isset($_SESSION['user_role']) || !isset($_SESSION['user_email'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'يجب تسجيل الدخول أولاً'
        ]);
        exit;
    }
    
    $allowed_roles = ['director', 'secretary'];
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
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
    
    // Only check admin access for admin operations
    if (in_array($action, ['admin_list', 'upload', 'edit', 'delete'])) {
        verify_admin_access();
    }
    
    switch ($action) {
        case 'list':
            // Fetch all gallery photos
            $category = $_GET['category'] ?? null;
            
            if ($category && in_array($category, ['trips', 'events', 'ceremonies', 'activities'])) {
                $stmt = $pdo->prepare("
                    SELECT * FROM `gallery_photos`
                    WHERE category = ? AND is_published = 1
                    ORDER BY photo_date DESC, created_at DESC
                ");
                $stmt->execute([$category]);
            } else {
                $stmt = $pdo->query("
                    SELECT * FROM `gallery_photos`
                    WHERE is_published = 1
                    ORDER BY photo_date DESC, created_at DESC
                ");
            }
            
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'count' => count($photos),
                'data' => $photos
            ]);
            break;
            
        case 'admin_list':
            // Fetch all photos for admin (including unpublished)
            $stmt = $pdo->query("
                SELECT * FROM `gallery_photos`
                ORDER BY photo_date DESC, created_at DESC
            ");
            
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'count' => count($photos),
                'data' => $photos
            ]);
            break;
            
        case 'upload':
            // Handle file upload
            if (!isset($_FILES['image'])) {
                throw new Exception('لم يتم اختيار صورة');
            }
            
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $photo_date = trim($_POST['photo_date'] ?? date('Y-m-d'));
            $author_name = trim($_POST['author_name'] ?? $_SESSION['admin_name'] ?? 'المؤسسة');
            
            if (empty($title)) {
                throw new Exception('اسم الصورة مطلوب');
            }
            
            if (!in_array($category, ['trips', 'events', 'ceremonies', 'activities'])) {
                throw new Exception('فئة الصورة غير صحيحة');
            }
            
            // Validate file
            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('صيغة الصورة غير مدعومة. استخدم JPG أو PNG أو WebP');
            }
            
            if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
                throw new Exception('حجم الصورة أكبر من 5MB');
            }
            
            // Create unique filename
            $upload_dir = __DIR__ . '/../images/gallery/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'photo_' . time() . '_' . uniqid() . '.' . $ext;
            $filepath = $upload_dir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('فشل تحميل الصورة');
            }
            
            // Save to database
            $stmt = $pdo->prepare("
                INSERT INTO `gallery_photos` 
                (title, description, category, image_path, image_filename, author_name, photo_date, is_published)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $image_path = 'images/gallery/' . $filename;
            $stmt->execute([$title, $description, $category, $image_path, $filename, $author_name, $photo_date]);
            
            $photo_id = $pdo->lastInsertId();
            
            // Get the inserted photo
            $stmt = $pdo->prepare("SELECT * FROM `gallery_photos` WHERE id = ?");
            $stmt->execute([$photo_id]);
            $photo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'تم رفع الصورة بنجاح',
                'data' => $photo
            ]);
            break;
            
        case 'edit':
            // Edit photo details
            $id = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $photo_date = trim($_POST['photo_date'] ?? date('Y-m-d'));
            $author_name = trim($_POST['author_name'] ?? '');
            $is_published = (int)($_POST['is_published'] ?? 1);
            
            if ($id === 0) {
                throw new Exception('معرف الصورة غير صحيح');
            }
            
            if (empty($title)) {
                throw new Exception('اسم الصورة مطلوب');
            }
            
            if (!in_array($category, ['trips', 'events', 'ceremonies', 'activities'])) {
                throw new Exception('فئة الصورة غير صحيحة');
            }
            
            // Verify photo exists
            $stmt = $pdo->prepare("SELECT * FROM `gallery_photos` WHERE id = ?");
            $stmt->execute([$id]);
            $photo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$photo) {
                throw new Exception('الصورة غير موجودة');
            }
            
            // Update photo
            $stmt = $pdo->prepare("
                UPDATE `gallery_photos`
                SET title = ?, description = ?, category = ?, photo_date = ?, author_name = ?, is_published = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$title, $description, $category, $photo_date, $author_name, $is_published, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'تم تحديث الصورة بنجاح'
            ]);
            break;
            
        case 'delete':
            // Delete photo
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id === 0) {
                throw new Exception('معرف الصورة غير صحيح');
            }
            
            // Get photo to delete file
            $stmt = $pdo->prepare("SELECT * FROM `gallery_photos` WHERE id = ?");
            $stmt->execute([$id]);
            $photo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$photo) {
                throw new Exception('الصورة غير موجودة');
            }
            
            // Delete file
            $file_path = __DIR__ . '/../' . $photo['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM `gallery_photos` WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'تم حذف الصورة بنجاح'
            ]);
            break;
            
        default:
            throw new Exception('إجراء غير معروف');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
?>
