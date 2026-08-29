<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.html#login-popup");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard">
        <h1>مرحباً <?= htmlspecialchars($user['fullname']) ?></h1>
        <h2>آخر الرسائل</h2>
        <table>
            <?php foreach($messages as $msg): ?>
            <tr>
                <td><?= htmlspecialchars($msg['name']) ?></td>
                <td><?= htmlspecialchars($msg['email']) ?></td>
                <td><?= htmlspecialchars($msg['message']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <a href="logout.php" class="btn">تسجيل خروج</a>
    </div>
</body>
</html>