<?php
/**
 * Blog Posts API - Fetch blog posts with media galleries
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

try {
    // Ensure a PDO connection is available as $pdo
    if (!isset($pdo) || !$pdo) {
        $pdo = db_connection();
    }
    // Determine action
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Fetch all published blog posts
            $stmt = $pdo->query("
                SELECT 
                    id, title, description, content, post_type, category, 
                    featured_image, media_type, video_url, gallery_json,
                    author_name, publish_date, is_published
                FROM `blog_posts`
                WHERE is_published = 1
                ORDER BY publish_date DESC
                LIMIT 12
            ");
            
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse gallery JSON for each post
            foreach ($posts as &$post) {
                if ($post['gallery_json']) {
                    $post['gallery'] = json_decode($post['gallery_json'], true);
                } else {
                    $post['gallery'] = [];
                }
                unset($post['gallery_json']);
            }
            
            echo json_encode([
                'success' => true,
                'count' => count($posts),
                'data' => $posts
            ]);
            break;
            
        case 'by_type':
            // Fetch posts by type (event, seminar, workshop, activity)
            $type = $_GET['type'] ?? 'event';
            $type = preg_replace('/[^a-z_]/', '', strtolower($type)); // Sanitize
            
            $stmt = $pdo->prepare("
                SELECT 
                    id, title, description, content, post_type, category, 
                    featured_image, media_type, video_url, gallery_json,
                    author_name, publish_date
                FROM `blog_posts`
                WHERE is_published = 1 AND post_type = ?
                ORDER BY publish_date DESC
                LIMIT 20
            ");
            
            $stmt->execute([$type]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($posts as &$post) {
                if ($post['gallery_json']) {
                    $post['gallery'] = json_decode($post['gallery_json'], true);
                } else {
                    $post['gallery'] = [];
                }
                unset($post['gallery_json']);
            }
            
            echo json_encode([
                'success' => true,
                'count' => count($posts),
                'data' => $posts
            ]);
            break;
            
        case 'by_category':
            // Fetch posts by category
            $category = $_GET['category'] ?? '';
            
            if (empty($category)) {
                throw new Exception('فئة غير محددة');
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    id, title, description, content, post_type, category, 
                    featured_image, media_type, video_url, gallery_json,
                    author_name, publish_date
                FROM `blog_posts`
                WHERE is_published = 1 AND category = ?
                ORDER BY publish_date DESC
                LIMIT 20
            ");
            
            $stmt->execute([$category]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($posts as &$post) {
                if ($post['gallery_json']) {
                    $post['gallery'] = json_decode($post['gallery_json'], true);
                } else {
                    $post['gallery'] = [];
                }
                unset($post['gallery_json']);
            }
            
            echo json_encode([
                'success' => true,
                'count' => count($posts),
                'data' => $posts
            ]);
            break;
            
        case 'single':
            // Fetch single post by ID
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id === 0) {
                throw new Exception('معرف المنشور غير صحيح');
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM `blog_posts`
                WHERE id = ? AND is_published = 1
            ");
            
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$post) {
                throw new Exception('المنشور غير موجود');
            }
            
            if ($post['gallery_json']) {
                $post['gallery'] = json_decode($post['gallery_json'], true);
            } else {
                $post['gallery'] = [];
            }
            unset($post['gallery_json']);
            
            echo json_encode([
                'success' => true,
                'data' => $post
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
