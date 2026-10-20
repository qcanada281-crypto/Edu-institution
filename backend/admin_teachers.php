<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_teachers_table();
ensure_teacher_requests_table();

ensure_post_request();

require_admin_access(['director']);

$action = clean_input((string) ($_POST['action'] ?? 'list'));

if ($action === 'list') {
    try {
        $teachers = db_query(
            'SELECT id, full_name, email, phone, subject_name, status, last_login, created_at
             FROM teachers
             WHERE status != "pending"
             ORDER BY id DESC'
        )->fetchAll();

        $normalized = [];
        foreach ($teachers as $teacher) {
            $normalized[] = [
                'id' => (int) $teacher['id'],
                'full_name' => (string) $teacher['full_name'],
                'email' => (string) $teacher['email'],
                'phone' => (string) ($teacher['phone'] ?? ''),
                'subject_name' => (string) ($teacher['subject_name'] ?? ''),
                'status' => (string) ($teacher['status'] ?? 'active'),
                'last_login' => $teacher['last_login'],
                'created_at' => $teacher['created_at'],
            ];
        }

        json_response(true, 'تم جلب لائحة الأساتذة.', [
            'teachers' => $normalized,
        ]);
    } catch (Throwable $exception) {
        error_log('Admin Teachers List Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب لائحة الأساتذة.', [], 500);
    }
}

if ($action === 'list_requests') {
    try {
        $requests = db_query(
            'SELECT id, full_name, email, phone, subject_name, status, created_at
             FROM teacher_requests
             WHERE status = "pending"
             ORDER BY id DESC'
        )->fetchAll();

        json_response(true, 'تم جلب طلبات الأساتذة.', [
            'requests' => $requests,
        ]);
    } catch (Throwable $exception) {
        error_log('Admin Teachers List Requests Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب الطلبات.', [], 500);
    }
}

if ($action === 'verify') {
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';

    if ($teacherId <= 0 || !in_array($newStatus, ['approved', 'rejected'], true)) {
        json_response(false, 'بيانات التحقق غير صالحة.', [], 422);
    }

    try {
        if ($newStatus === 'approved') {
            $request = db_query(
                'SELECT id, full_name, email, code, phone, subject_name
                 FROM teacher_requests
                 WHERE id = :id AND status = "pending"
                 LIMIT 1',
                ['id' => $teacherId]
            )->fetch();

            if (!$request) {
                json_response(false, 'طلب الأستاذ غير موجود أو تمت مراجعته مسبقا.', [], 404);
            }

            $existing = db_query(
                'SELECT id FROM teachers WHERE email = :email LIMIT 1',
                ['email' => (string) $request['email']]
            )->fetch();

            if ($existing) {
                db_query(
                    'UPDATE teacher_requests SET status = "approved", reviewed_at = NOW() WHERE id = :id',
                    ['id' => $teacherId]
                );
                db_query('DELETE FROM teacher_requests WHERE id = :id', ['id' => $teacherId]);
                json_response(true, 'تم قبول الطلب، لكن حساب الأستاذ موجود مسبقا.');
            }

            db_query(
                'INSERT INTO teachers (full_name, email, code, phone, subject_name, status)
                 VALUES (:full_name, :email, :code, :phone, :subject_name, :status)',
                [
                    'full_name' => (string) $request['full_name'],
                    'email' => (string) $request['email'],
                    'code' => (string) $request['code'],
                    'phone' => $request['phone'] === null ? null : (string) $request['phone'],
                    'subject_name' => $request['subject_name'] === null ? null : (string) $request['subject_name'],
                    'status' => 'active',
                ]
            );

            db_query(
                'UPDATE teacher_requests SET status = "approved", reviewed_at = NOW() WHERE id = :id',
                ['id' => $teacherId]
            );
            db_query('DELETE FROM teacher_requests WHERE id = :id', ['id' => $teacherId]);

            json_response(true, 'تم قبول الأستاذ وتفعيله بنجاح.');
        } else {
            $request = db_query(
                'SELECT id FROM teacher_requests WHERE id = :id AND status = "pending" LIMIT 1',
                ['id' => $teacherId]
            )->fetch();

            if (!$request) {
                json_response(false, 'طلب الأستاذ غير موجود أو تمت مراجعته مسبقا.', [], 404);
            }

            db_query(
                'UPDATE teacher_requests SET status = "rejected", reviewed_at = NOW() WHERE id = :id',
                ['id' => $teacherId]
            );
            db_query('DELETE FROM teacher_requests WHERE id = :id', ['id' => $teacherId]);
            json_response(true, 'تم رفض طلب الأستاذ وحذفه.');
        }
    } catch (Throwable $exception) {
        error_log('Admin Teachers Verify Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء عملية التحقق.', [], 500);
    }
}

if ($action === 'save') {
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);
    $fullName = clean_input((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(clean_input((string) ($_POST['email'] ?? '')));
    $code = (string) ($_POST['code'] ?? '');
    $phone = clean_input((string) ($_POST['phone'] ?? ''));
    $subjectName = clean_input((string) ($_POST['subject_name'] ?? ''));
    $status = clean_input((string) ($_POST['status'] ?? 'active'));

    if ($fullName === '' || $email === '') {
        json_response(false, 'الاسم والبريد الإلكتروني إلزاميان.', [], 422);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(false, 'البريد الإلكتروني غير صالح.', [], 422);
    }

    if (!str_ends_with($email, '@gmail.com')) {
        json_response(false, 'مرجو استعمال Gmail لحسابات الأساتذة.', [], 422);
    }

    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $phone)) {
        json_response(false, 'رقم الهاتف غير صالح.', [], 422);
    }

    if ($status === '') {
        $status = 'active';
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        $status = 'active';
    }

    if ($teacherId <= 0 && $code === '') {
        json_response(false, 'الكود إلزامي عند إضافة أستاذ جديد.', [], 422);
    }
    if ($code !== '' && mb_strlen($code) < 6) {
        json_response(false, 'الكود يجب أن يتكون من 6 أحرف على الأقل.', [], 422);
    }

    try {
        if ($teacherId > 0) {
            $existing = db_query(
                'SELECT id, email FROM teachers WHERE id = :id LIMIT 1',
                ['id' => $teacherId]
            )->fetch();

            if (!$existing) {
                json_response(false, 'الأستاذ غير موجود.', [], 404);
            }

            $duplicateEmail = db_query(
                'SELECT id FROM teachers WHERE email = :email AND id <> :id LIMIT 1',
                [
                    'email' => $email,
                    'id' => $teacherId,
                ]
            )->fetch();
            if ($duplicateEmail) {
                json_response(false, 'هذا البريد الإلكتروني مسجل لأستاذ آخر.', [], 409);
            }

            $params = [
                'id' => $teacherId,
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone === '' ? null : $phone,
                'subject_name' => $subjectName === '' ? null : $subjectName,
                'status' => $status,
            ];

            $codeSql = '';
            if ($code !== '') {
                $codeSql = ', code = :code';
                $params['code'] = password_hash($code, PASSWORD_DEFAULT);
            }

            db_query(
                "UPDATE teachers
                 SET full_name = :full_name,
                     email = :email,
                     phone = :phone,
                     subject_name = :subject_name,
                     status = :status
                     {$codeSql}
                 WHERE id = :id",
                $params
            );

            json_response(true, 'تم تحديث الأستاذ بنجاح.', [
                'teacher_id' => $teacherId,
            ]);
        }

        $duplicate = db_query(
            'SELECT id FROM teachers WHERE email = :email LIMIT 1',
            ['email' => $email]
        )->fetch();
        if ($duplicate) {
            json_response(false, 'هذا البريد الإلكتروني مسجل مسبقا.', [], 409);
        }

        db_query(
            'INSERT INTO teachers (full_name, email, code, phone, subject_name, status)
             VALUES (:full_name, :email, :code, :phone, :subject_name, :status)',
            [
                'full_name' => $fullName,
                'email' => $email,
                'code' => password_hash($code, PASSWORD_DEFAULT),
                'phone' => $phone === '' ? null : $phone,
                'subject_name' => $subjectName === '' ? null : $subjectName,
                'status' => $status,
            ]
        );

        json_response(true, 'تمت إضافة الأستاذ بنجاح.', [
            'teacher_id' => (int) db_connection()->lastInsertId(),
        ]);
    } catch (Throwable $exception) {
        error_log('Admin Teachers Save Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء حفظ الأستاذ.', [], 500);
    }
}

if ($action === 'delete') {
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);
    if ($teacherId <= 0) {
        json_response(false, 'معرّف الأستاذ غير صالح.', [], 422);
    }

    try {
        $statement = db_query(
            'DELETE FROM teachers WHERE id = :id',
            ['id' => $teacherId]
        );

        if ($statement->rowCount() === 0) {
            json_response(false, 'الأستاذ غير موجود.', [], 404);
        }

        json_response(true, 'تم حذف الأستاذ بنجاح.');
    } catch (Throwable $exception) {
        error_log('Admin Teachers Delete Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء حذف الأستاذ.', [], 500);
    }
}

json_response(false, 'إجراء غير صالح.', [], 400);

