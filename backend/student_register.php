<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_students_table();
ensure_registration_table();

ensure_post_request();

$requiredFields = [
    'student_code',
    'first_name',
    'last_name',
    'birth_date',
    'gender',
    'class_name',
    'level',
    'guardian_name',
    'guardian_phone',
];

$missing = require_fields($_POST, $requiredFields);
if (!empty($missing)) {
    json_response(false, 'المرجو تعبئة جميع الحقول الإلزامية.');
}

$studentCode = strtoupper(clean_input((string) $_POST['student_code']));
$firstName = clean_input((string) $_POST['first_name']);
$lastName = clean_input((string) $_POST['last_name']);
$birthDate = clean_input((string) $_POST['birth_date']);
$gender = clean_input((string) $_POST['gender']);
$className = clean_input((string) $_POST['class_name']);
$level = clean_input((string) $_POST['level']);
$email = clean_input((string) ($_POST['email'] ?? ''));
$phone = clean_input((string) ($_POST['phone'] ?? ''));
$address = clean_input((string) ($_POST['address'] ?? ''));
$guardianName = clean_input((string) $_POST['guardian_name']);
$guardianPhone = clean_input((string) $_POST['guardian_phone']);
$guardianEmail = clean_input((string) ($_POST['guardian_email'] ?? ''));

if (!preg_match('/^[A-Z0-9\-]{5,20}$/', $studentCode)) {
    json_response(false, 'صيغة كود الطالب غير صحيحة.');
}

if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $birthDate)) {
    json_response(false, 'تاريخ الازدياد غير صالح.');
}

if (!in_array($gender, ['male', 'female'], true)) {
    json_response(false, 'قيمة الجنس غير صالحة.');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'بريد الطالب غير صالح.');
}

if ($guardianEmail !== '' && !filter_var($guardianEmail, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'بريد ولي الأمر غير صالح.');
}

if (!preg_match('/^[0-9+\-\s]{8,20}$/', $guardianPhone)) {
    json_response(false, 'هاتف ولي الأمر غير صالح.');
}

try {
    $pdo = db_connection();

    // Check if already registered in students table
    $existingByCode = db_query(
        'SELECT id FROM students WHERE student_code = :student_code LIMIT 1',
        ['student_code' => $studentCode]
    )->fetch();

    if ($existingByCode) {
        json_response(false, 'كود الطالب مسجل مسبقا ومقبول بالفعل.');
    }

    // Check if already has a pending request
    $existingRequest = db_query(
        'SELECT id, registration_status FROM registration_requests WHERE student_code = :student_code LIMIT 1',
        ['student_code' => $studentCode]
    )->fetch();

    if ($existingRequest) {
        $status = $existingRequest['registration_status'];
        if ($status === 'pending') {
            json_response(false, 'لديك طلب تسجيل قيد المراجعة من طرف الإدارة. يرجى الانتظار.');
        } elseif ($status === 'approved') {
            json_response(false, 'تم قبول طلبك مسبقا. يمكنك الولوج الآن.');
        } elseif ($status === 'rejected') {
            json_response(false, 'تم رفض طلبك مسبقا. يرجى التواصل مع الإدارة.');
        }
    }

    if ($email !== '') {
        $existingByEmail = db_query(
            'SELECT id FROM students WHERE email = :email LIMIT 1',
            ['email' => $email]
        )->fetch();

        if ($existingByEmail) {
            json_response(false, 'بريد الطالب موجود مسبقا.');
        }
    }

	    // Insert into registration_requests (pending approval)
	    $insert = $pdo->prepare(
	        'INSERT INTO registration_requests (
	            student_code,
	            first_name,
	            last_name,
	            birth_date,
	            gender,
	            class_name,
	            level,
	            email,
	            phone,
	            address,
	            guardian_name,
	            guardian_phone,
	            guardian_email,
	            registration_status
	        ) VALUES (
	            :student_code,
	            :first_name,
	            :last_name,
	            :birth_date,
	            :gender,
	            :class_name,
	            :level,
	            :email,
	            :phone,
	            :address,
	            :guardian_name,
	            :guardian_phone,
	            :guardian_email,
	            :registration_status
	        )'
	    );

    $insert->execute([
        'student_code' => $studentCode,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'birth_date' => $birthDate,
        'gender' => $gender,
        'class_name' => $className,
        'level' => $level,
        'email' => $email === '' ? null : $email,
        'phone' => $phone === '' ? null : $phone,
        'address' => $address === '' ? null : $address,
        'guardian_name' => $guardianName,
        'guardian_phone' => $guardianPhone,
        'guardian_email' => $guardianEmail === '' ? null : $guardianEmail,
	        'registration_status' => 'pending', // Pending admin approval
	    ]);

    json_response(true, 'تم إرسال طلب التسجيل بنجاح! سيتم مراجعته من طرف الإدارة. ستتلقى إشعارا عند الموافقة.', [
        'request_id' => (int) $pdo->lastInsertId(),
        'student_code' => $studentCode,
    ]);
} catch (Throwable $exception) {
    error_log('Student Register Error: ' . $exception->getMessage());
    json_response(false, 'وقع خطأ أثناء إرسال طلب التسجيل.', [], 500);
}
