<?php
require_once 'config.php';
session_start();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = $_POST['course_id'] ?? '';
    $user_id = $_SESSION['user_id'] ?? null;
    
    if(!$user_id) {
        header("Location: ../index.html#login-popup");
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, enrollment_date) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $course_id]);
        header("Location: ../courses.html?enrolled=success");
    } catch(PDOException $e) {
        echo "حدث خطأ: " . $e->getMessage();
    }
}
?>