<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1:3307';
const DB_NAME = 'edu_institution';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$message = '';
if (isset($_POST['action']) && $_POST['action'] === 'approve' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $req = $pdo->query("SELECT * FROM registration_requests WHERE id = $id")->fetch();
    if ($req) {
        $stmt = $pdo->prepare("INSERT INTO students (student_code, first_name, last_name, birth_date, gender, class_name, level, email, phone, address, guardian_name, guardian_phone, guardian_email, registration_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'registered')");
        $stmt->execute([$req['student_code'], $req['first_name'], $req['last_name'], $req['birth_date'], $req['gender'], $req['class_name'], $req['level'], $req['email'], $req['phone'], $req['address'], $req['guardian_name'], $req['guardian_phone'], $req['guardian_email']]);
        $pdo->exec("UPDATE registration_requests SET status='approved', reviewed_at=NOW() WHERE id = $id");
        $message = 'تم قبول التلميذ بنجاح!';
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $pdo->exec("DELETE FROM registration_requests WHERE id = " . (int)$_POST['id']);
    $message = 'تم حذف الطلب.';
}

$requests = $pdo->query("SELECT * FROM registration_requests ORDER BY id DESC")->fetchAll();
$students = $pdo->query("SELECT * FROM students ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التلاميذ | مؤسسة Kawkab Al Ouloum</title>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Almarai', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1, h2 { color: #1a5f2a; }
        h1 { text-align: center; margin-bottom: 30px; }
        h2 { margin: 30px 0 15px; border-bottom: 2px solid #1a5f2a; padding-bottom: 10px; }
        .message { padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab { padding: 10px 20px; background: #ddd; border-radius: 8px; cursor: pointer; }
        .tab.active { background: #1a5f2a; color: white; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; margin-bottom: 20px; }
        th { background: #1a5f2a; color: white; padding: 12px; text-align: right; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f9f9f9; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; }
        .btn-approve { background: #28a745; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 11px; }
        .pending { background: #ffc107; color: #000; }
        .approved { background: #28a745; color: white; }
        .rejected { background: #dc3545; color: white; }
        .registered { background: #17a2b8; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>لوحة إدارة التلاميذ</h1>
        
        <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2>طلبات التسجيل الجديدة (<?php echo count($requests); ?>)</h2>
        <?php if (empty($requests)): ?>
        <p style="text-align: center; color: #666; padding: 20px;">لا توجد طلبات تسجيل جديدة</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>كود الطالب</th>
                    <th>الاسم</th>
                    <th>القسم</th>
                    <th>المستوى</th>
                    <th>ولي الأمر</th>
                    <th>الهاتف</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?php echo $r['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($r['student_code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['class_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['level']); ?></td>
                    <td><?php echo htmlspecialchars($r['guardian_name']); ?></td>
                    <td dir="ltr"><?php echo htmlspecialchars($r['guardian_phone']); ?></td>
                    <td><span class="badge <?php echo $r['status']; ?>"><?php echo $r['status']; ?></span></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                            <button type="submit" class="btn btn-approve">قبول</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                            <button type="submit" class="btn btn-delete" onclick="return confirm('هل أنت متأكد؟')">حذف</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <h2>التلاميذ المسجلين (<?php echo count($students); ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>كود الطالب</th>
                    <th>الاسم</th>
                    <th>القسم</th>
                    <th>المستوى</th>
                    <th>ولي الأمر</th>
                    <th>الهاتف</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><?php echo $s['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($s['student_code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($s['class_name']); ?></td>
                    <td><?php echo htmlspecialchars($s['level']); ?></td>
                    <td><?php echo htmlspecialchars($s['guardian_name']); ?></td>
                    <td dir="ltr"><?php echo htmlspecialchars($s['guardian_phone']); ?></td>
                    <td><span class="badge registered">مسجل</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>