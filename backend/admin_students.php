<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_students_table();
ensure_registration_table();

ensure_post_request();

$currentRole = require_admin_access();

$action = clean_input((string) ($_POST['action'] ?? 'list'));

if ($action === 'list') {
    try {
        $students = [];
        if (in_array($currentRole, ['director', 'secretary'], true)) {
            $students = db_query(
                "SELECT
                    'no' as is_request, id, student_code, first_name, last_name, birth_date, gender,
                    class_name, level, email, phone, address, guardian_name, guardian_phone, guardian_email, registration_status
                 FROM students
                 ORDER BY id DESC"
            )->fetchAll();
        }

        $stats = db_query(
            'SELECT
                (SELECT COUNT(*) FROM students) AS students_count,
                (SELECT COUNT(*) FROM grades) AS grades_count,
                (SELECT COUNT(*) FROM attendance) AS attendance_count'
        )->fetch();

        $currentUser = [
            'full_name' => (string) ($_SESSION['user_name'] ?? 'Admin'),
            'email' => (string) ($_SESSION['user_email'] ?? ''),
            'role' => $currentRole,
            'role_label' => role_label($currentRole),
        ];

        json_response(true, 'تم جلب بيانات الإدارة بنجاح.', [
            'admin' => $currentUser,
            'user' => $currentUser,
            'students' => $students,
            'stats' => [
                'students_count' => $currentRole === 'director' ? (int) ($stats['students_count'] ?? 0) : 0,
                'grades_count' => (int) ($stats['grades_count'] ?? 0),
                'attendance_count' => (int) ($stats['attendance_count'] ?? 0),
            ],
            'permissions' => [
                'can_manage_students' => $currentRole === 'director',
                'can_view_reports' => $currentRole === 'director',
            ],
        ]);
    } catch (Throwable $exception) {
        error_log('Admin Students List Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب بيانات التلاميذ.', [], 500);
    }
}

if ($action === 'list_requests' && $currentRole !== 'director') {
    json_response(false, 'عرض طلبات التسجيل متاحة للمدير فقط.', [], 403);
}

if ($action === 'list_requests') {
    try {
        $requests = db_query(
            "SELECT
                'yes' as is_request, id, student_code, first_name, last_name, birth_date, gender,
                class_name, level, email, phone, address, guardian_name, guardian_phone, guardian_email, registration_status
             FROM registration_requests
             ORDER BY id DESC"
        )->fetchAll();

        error_log('List Requests: Found ' . count($requests) . ' requests.');
        if (!empty($requests)) {
            error_log('First request: ' . print_r($requests[0], true));
        }

        json_response(true, 'تم جلب طلبات التسجيل بنجاح.', [
            'requests' => $requests,
        ]);
    } catch (Throwable $exception) {
        error_log('Admin Requests List Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب طلبات التسجيل: ' . $exception->getMessage(), [], 500);
    }
}

if ($action === 'verify' && $currentRole !== 'director') {
    json_response(false, 'الموافقة أو الرفض على المسجلين متاحة للمدير فقط.', [], 403);
}

if ($action === 'save' && !in_array($currentRole, ['director', 'secretary'], true)) {
    json_response(false, 'ليس لديك صلاحية حفظ بيانات التلاميذ.', [], 403);
}

if ($action === 'save') {
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

    $studentId = (int) ($_POST['student_id'] ?? 0);
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

    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
        json_response(false, 'هاتف الطالب غير صالح.');
    }

    if (!preg_match('/^[0-9+\-\s]{8,20}$/', $guardianPhone)) {
        json_response(false, 'هاتف ولي الأمر غير صالح.');
    }

    if (mb_strlen($firstName) > 90 || mb_strlen($lastName) > 90) {
        json_response(false, 'اسم الطالب طويل جدا.');
    }

    if (mb_strlen($className) > 60 || mb_strlen($level) > 60) {
        json_response(false, 'القسم أو المستوى غير صالح.');
    }

    if ($studentId > 0 && $currentRole === 'secretary') {
        json_response(false, 'تعديل بيانات التلاميذ متاح للمدير فقط، يمكنك فقط إضافة تلاميذ جدد.', [], 403);
    }

    try {
        if ($studentId > 0) {
            $existing = db_query(
                'SELECT id FROM students WHERE id = :id LIMIT 1',
                ['id' => $studentId]
            )->fetch();

            if (!$existing) {
                json_response(false, 'الطالب غير موجود.');
            }
        }

        $duplicateByCode = db_query(
            'SELECT id FROM students
             WHERE student_code = :student_code
               AND (:student_id1 = 0 OR id <> :student_id2)
             LIMIT 1',
            [
                'student_code' => $studentCode,
                'student_id1' => $studentId,
                'student_id2' => $studentId,
            ]
        )->fetch();

        if ($duplicateByCode) {
            json_response(false, 'كود الطالب مسجل مسبقا.');
        }

        if ($email !== '') {
            $duplicateByEmail = db_query(
                'SELECT id FROM students
                 WHERE email = :email
                   AND (:student_id1 = 0 OR id <> :student_id2)
                 LIMIT 1',
                [
                    'email' => $email,
                    'student_id1' => $studentId,
                    'student_id2' => $studentId,
                ]
            )->fetch();

            if ($duplicateByEmail) {
                json_response(false, 'بريد الطالب موجود مسبقا.');
            }
        }

        if ($studentId > 0 && $currentRole === 'secretary') {
             // In the new system, secretaries can't edit existing students anyway.
             // But if they were editing a request, that's allowed?
             // Actually, let's just stick to the rule: Secretaries only add NEW students to requests.
        }

        $isRequest = ($_POST['is_request'] ?? '') === 'yes';

        if ($studentId > 0) {
            $tableName = $isRequest ? 'registration_requests' : 'students';

            db_query(
                "UPDATE $tableName SET
                    student_code = :student_code,
                    first_name = :first_name,
                    last_name = :last_name,
                    birth_date = :birth_date,
                    gender = :gender,
                    class_name = :class_name,
                    level = :level,
                    email = :email,
                    phone = :phone,
                    address = :address,
                    guardian_name = :guardian_name,
                    guardian_phone = :guardian_phone,
                    guardian_email = :guardian_email,
                    registration_status = :registration_status,
                    updated_at = NOW()
                 WHERE id = :id",
                [
                    'id' => $studentId,
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
                    'registration_status' => $status,
                ]
            );

            json_response(true, 'تم تحديث البيانات بنجاح.', [
                'student_id' => $studentId,
                'student_code' => $studentCode,
            ]);
        }
        
        $targetTable = ($currentRole === 'secretary') ? 'registration_requests' : 'students';
        $status = ($currentRole === 'secretary') ? 'pending' : 'approved';

        $pdo = db_connection();
        $insert = $pdo->prepare(
            "INSERT INTO $targetTable (
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
            )"
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
            'registration_status' => $status,
        ]);

        $message = ($status === 'pending') 
            ? 'تم استلام طلب التسجيل بنجاح، وهو الآن قيد المراجعة من طرف الإدارة.' 
            : 'تم استلام طلب التسجيل بنجاح، وهو الآن قيد المراجعة من طرف الإدارة..';

        json_response(true, $message, [
            'student_id' => (int) $pdo->lastInsertId(),
            'student_code' => $studentCode,
        ]);
    } catch (Throwable $exception) {
        error_log('Admin Students Save Error: ' . $exception->getMessage());
        $sysMsg = strtolower($exception->getMessage());
        $userMsg = 'وقع خطأ أثناء حفظ بيانات الطالب.';
        
        if (strpos($sysMsg, 'duplicate') !== false || strpos($sysMsg, '1062') !== false || $exception->getCode() == 23000) {
            if (strpos($sysMsg, 'email') !== false) {
                $userMsg = 'خطأ: البريد الإلكتروني مسجل مسبقاً في النظام.';
            } elseif (strpos($sysMsg, 'phone') !== false) {
                $userMsg = 'خطأ: رقم الهاتف مسجل مسبقاً لتلميذ آخر.';
            } elseif (strpos($sysMsg, 'student_code') !== false) {
                $userMsg = 'خطأ: كود الطالب (Code) موجود مسبقاً.';
            } else {
                $userMsg = 'خطأ: إحدى البيانات (إيميل، هاتف، كود مسار) تتكرر مع تلميذ آخر.';
            }
        }
        
        json_response(false, $userMsg, ['debug' => $exception->getMessage()], 400);
    }
}

	if ($action === 'verify') {
	    $studentId = (int)($_POST['student_id'] ?? 0);
	    $newStatus = $_POST['status'] ?? '';
	
	    if ($studentId <= 0 || !in_array($newStatus, ['approved', 'rejected'], true)) {
	        json_response(false, 'بيانات التحقق غير صالحة.');
	    }
	
	    $isRequest = ($_POST['is_request'] ?? '') === 'yes';
	
	    $pdo = db_connection();
	
	    try {
	        if ($isRequest) {
	            $request = db_query(
	                'SELECT
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
	                    guardian_email
	                 FROM registration_requests
	                 WHERE id = :id
	                 LIMIT 1',
	                ['id' => $studentId]
	            )->fetch(PDO::FETCH_ASSOC);
	
	            if (!$request) {
	                json_response(false, 'لم يتم العثور على طلب التسجيل.');
	            }
	
	            if ($newStatus === 'approved') {
	                $pdo->beginTransaction();
	
	                $existingStudent = db_query(
	                    'SELECT id FROM students WHERE student_code = :student_code LIMIT 1',
	                    ['student_code' => (string) $request['student_code']]
	                )->fetch();
	
	                if ($existingStudent) {
	                    db_query('DELETE FROM registration_requests WHERE id = :id', ['id' => $studentId]);
	                    $pdo->commit();
	                    json_response(true, 'تم قبول التسجيل مسبقاً. تم حذف الطلب من قائمة المراجعة.');
	                }
	
	                $studentData = $request;
	                $studentData['registration_status'] = 'approved';
	
	                $insert = $pdo->prepare(
	                    'INSERT INTO students (
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
	                $insert->execute($studentData);
	
	                db_query('DELETE FROM registration_requests WHERE id = :id', ['id' => $studentId]);
	                $pdo->commit();
	                json_response(true, 'تم قبول التسجيل ونقل التلميذ للقائمة الرسمية.');
	            }
	
	            db_query(
	                'UPDATE registration_requests
	                 SET registration_status = "rejected", updated_at = NOW()
	                 WHERE id = :id',
	                ['id' => $studentId]
	            );
	            json_response(true, 'تم رفض طلب التسجيل.');
	        }
	
	        $update = $pdo->prepare('UPDATE students SET registration_status = :status WHERE id = :id');
	        $update->execute(['status' => $newStatus, 'id' => $studentId]);
	        $label = ($newStatus === 'approved') ? 'تمت الموافقة' : 'تم الرفض';
	        json_response(true, "{$label} على التسجيل بنجاح.");
	    } catch (Throwable $exception) {
	        if ($pdo->inTransaction()) {
	            $pdo->rollBack();
	        }
	
	        error_log('Admin Students Verify Error: ' . $exception->getMessage());
	        $sysMsg = strtolower($exception->getMessage());
	        $userMsg = 'وقع خطأ أثناء عملية التحقق.';
	
	        if (strpos($sysMsg, 'duplicate') !== false || strpos($sysMsg, '1062') !== false || $exception->getCode() == 23000) {
	            if (strpos($sysMsg, 'email') !== false) {
	                $userMsg = 'خطأ: البريد الإلكتروني مسجل مسبقاً في النظام.';
	            } elseif (strpos($sysMsg, 'phone') !== false) {
	                $userMsg = 'خطأ: رقم الهاتف مسجل مسبقاً لتلميذ آخر.';
	            } elseif (strpos($sysMsg, 'student_code') !== false) {
	                $userMsg = 'خطأ: كود الطالب (Code) موجود مسبقاً.';
	            } else {
	                $userMsg = 'خطأ: إحدى البيانات تتكرر مع تلميذ آخر.';
	            }
	
	            json_response(false, $userMsg, ['debug' => $exception->getMessage()], 400);
	        }
	
	        json_response(false, $userMsg, ['debug' => $exception->getMessage()], 500);
	    }
	}

if ($action === 'delete') {
    $studentId = (int)($_POST['student_id'] ?? 0);
    if ($studentId <= 0) {
        json_response(false, 'معرف التلميذ غير صالح.');
    }

    if ($currentRole !== 'director') {
        json_response(false, 'عذراً، المدير فقط من يملك صلاحية الحذف.');
    }

    $isRequest = ($_POST['is_request'] ?? '') === 'yes';
    $pdo = db_connection();
    
    if ($isRequest) {
        db_query('DELETE FROM registration_requests WHERE id = :id', ['id' => $studentId]);
        json_response(true, 'تم حذف طلب التسجيل بنجاح.');
    } else {
        db_query('DELETE FROM students WHERE id = :id', ['id' => $studentId]);
        json_response(true, 'تم حذف بيانات الطالب بنجاح.');
    }
}

json_response(false, 'الإجراء المطلوب غير مدعوم.', [], 400);
