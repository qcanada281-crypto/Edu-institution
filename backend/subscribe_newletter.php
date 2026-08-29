<?php
require_once 'config.php';

// Ensure PDO available
if (!isset($pdo) || !$pdo) {
    $pdo = db_connection();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    
    if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO newsletter (email, subscribed_at) VALUES (?, NOW())");
            $stmt->execute([$email]);
            echo json_encode(['success' => true, 'message' => 'تم الاشتراك بنجاح']);
        } catch(PDOException $e) {
            if($e->errorInfo[1] == 1062) {
                echo json_encode(['success' => false, 'message' => 'البريد مسجل بالفعل']);
            } else {
                echo json_encode(['success' => false, 'message' => 'حدث خطأ']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'بريد إلكتروني غير صالح']);
    }
}
?>