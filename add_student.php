<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1:3307';
const DB_NAME = 'edu_institution';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $studentCode = strtoupper(trim($_POST['student_code'] ?? ''));
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $className = trim($_POST['class_name'] ?? '');
        $level = trim($_POST['level'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $guardianName = trim($_POST['guardian_name'] ?? '');
        $guardianPhone = trim($_POST['guardian_phone'] ?? '');
        $guardianEmail = trim($_POST['guardian_email'] ?? '');

        if (empty($studentCode) || empty($firstName) || empty($lastName) || empty($birthDate) || empty($gender) || empty($className) || empty($level) || empty($guardianName) || empty($guardianPhone)) {
            $message = 'المرجو تعبئة جميع الحقول الإلزامية';
        } else {
            $stmt = $pdo->prepare("INSERT INTO students (student_code, first_name, last_name, birth_date, gender, class_name, level, email, phone, address, guardian_name, guardian_phone, guardian_email, registration_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'registered')");
            $stmt->execute([$studentCode, $firstName, $lastName, $birthDate, $gender, $className, $level, $email, $phone, $address, $guardianName, $guardianPhone, $guardianEmail]);
            $message = 'تم تسجيل التلميذ بنجاح!';
            $success = true;
        }
    } catch (PDOException $e) {
        $message = 'خطأ: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة تلميذ | مؤسسة Kawkab Al Ouloum</title>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Almarai', sans-serif; background: #f0f4f8; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; }
        h1 { color: #1a5f2a; text-align: center; margin-bottom: 20px; }
        .form-box { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: bold; }
        label .required { color: #e74c3c; }
        input, select { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px; font-family: inherit; }
        input:focus, select:focus { outline: none; border-color: #1a5f2a; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .btn { width: 100%; padding: 15px; background: #1a5f2a; color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; font-family: inherit; }
        .btn:hover { background: #144a1f; }
        .message { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .links { display: flex; gap: 10px; justify-content: center; margin-top: 20px; }
        .links a { padding: 10px 20px; background: #1a5f2a; color: white; text-decoration: none; border-radius: 8px; }
        @media (max-width: 600px) { .row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <h1>إضافة تلميذ جديد</h1>
        
        <div class="form-box">
            <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <form method="post">
                <div class="row">
                    <div class="form-group">
                        <label>رقم التلميذ <span class="required">*</span></label>
                        <input type="text" name="student_code" placeholder="STU2026004" required>
                    </div>
                    <div class="form-group">
                        <label>تاريخ الميلاد <span class="required">*</span></label>
                        <input type="date" name="birth_date" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label>الاسم <span class="required">*</span></label>
                        <input type="text" name="first_name" placeholder="الاسم" required>
                    </div>
                    <div class="form-group">
                        <label>النسب <span class="required">*</span></label>
                        <input type="text" name="last_name" placeholder="النسب" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label>الجنس <span class="required">*</span></label>
                        <select name="gender" required>
                            <option value="">-- اختر --</option>
                            <option value="male">ذكر</option>
                            <option value="female">أنثى</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>القسم <span class="required">*</span></label>
                        <input type="text" name="class_name" placeholder="3AC-1" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label>المستوى <span class="required">*</span></label>
                        <input type="text" name="level" placeholder="Baccalauréat" required>
                    </div>
                    <div class="form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" placeholder="student@kawkab.ma">
                    </div>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label>الهاتف</label>
                        <input type="tel" name="phone" placeholder="0660000000">
                    </div>
                    <div class="form-group">
                        <label>العنوان</label>
                        <input type="text" name="address" placeholder="المدينة">
                    </div>
                </div>

                <hr style="margin: 25px 0; border: none; border-top: 2px dashed #ddd;">

                <h3 style="color: #1a5f2a; margin-bottom: 15px;">معلومات ولي الأمر</h3>

                <div class="row">
                    <div class="form-group">
                        <label>اسم ولي الأمر <span class="required">*</span></label>
                        <input type="text" name="guardian_name" required>
                    </div>
                    <div class="form-group">
                        <label>هاتف ولي الأمر <span class="required">*</span></label>
                        <input type="tel" name="guardian_phone" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>بريد ولي الأمر</label>
                    <input type="email" name="guardian_email" placeholder="parent@email.com">
                </div>

                <button type="submit" class="btn">تسجيل التلميذ</button>
            </form>

            <div class="links">
                <a href="talamid.php">قائمة التلاميذ</a>
                <a href="index.html">الرئيسية</a>
            </div>
        </div>
    </div>
</body>
</html>