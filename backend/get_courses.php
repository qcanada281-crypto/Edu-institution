<?php
require_once 'config.php';
header('Content-Type: application/json');

// Ensure PDO connection exists
if (!isset($pdo) || !$pdo) {
    $pdo = db_connection();
}

try {
    $stmt = $pdo->query("SELECT * FROM courses ORDER BY created_at DESC");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $courses]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>