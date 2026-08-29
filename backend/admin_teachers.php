<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Ensure required tables exist and request is POST
ensure_teacher_requests_table();
ensure_teachers_table();
ensure_post_request();

// Only director may approve/reject teachers
$currentRole = require_admin_access(['director']);

$action = clean_input((string) ($_POST['action'] ?? 'list'));

if ($action === 'list') {
    try {
        // Pending requests
        $pending = db_query('SELECT id, full_name, email, phone, subject_name, status, created_at FROM teacher_requests ORDER BY created_at DESC')->fetchAll();
        // Existing teachers
        $teachers = db_query('SELECT id, full_name, email, phone, subject_name, status, created_at FROM teachers ORDER BY created_at DESC')->fetchAll();

        json_response(true, 'تم جلب بيانات الأساتذة وطلبات التسجيل.', ['pending' => $pending, 'teachers' => $teachers]);
    } catch (Throwable $e) {
        error_log('Admin Teachers List Error: ' . $e->getMessage());
        json_response(false, 'خطأ أثناء جلب بيانات الأساتذة.', [], 500);
    }
}

if ($action === 'approve') {
    $reqId = (int)($_POST['request_id'] ?? 0);
    if ($reqId <= 0) {
        json_response(false, 'معرف الطلب غير صالح.', [], 400);
    }

    try {
        $req = db_query('SELECT * FROM teacher_requests WHERE id = :id LIMIT 1', ['id' => $reqId])->fetch();
        if (!$req) {
            json_response(false, 'طلب التسجيل غير موجود.', [], 404);
        }

        // Insert into teachers table using stored hashed code
        db_query(
            'INSERT INTO teachers (full_name, email, code, phone, subject_name, status, created_at) VALUES (:full_name, :email, :code, :phone, :subject_name, :status, :created_at)',
            [
                'full_name' => $req['full_name'],
                'email' => $req['email'],
                'code' => $req['code'],
                'phone' => $req['phone'],
                'subject_name' => $req['subject_name'],
                'status' => 'active',
                'created_at' => $req['created_at'],
            ]
        );

        // Mark request as approved and set reviewed_at
        db_query('UPDATE teacher_requests SET status = "approved", reviewed_at = NOW() WHERE id = :id', ['id' => $reqId]);

        json_response(true, 'تمت الموافقة على طلب الأستاذ ونُقل إلى لائحة الأساتذة.');
    } catch (Throwable $e) {
        error_log('Admin Teachers Approve Error: ' . $e->getMessage());
        json_response(false, 'خطأ أثناء الموافقة على طلب الأستاذ.', ['debug' => $e->getMessage()], 500);
    }
}

if ($action === 'reject') {
    $reqId = (int)($_POST['request_id'] ?? 0);
    $reason = clean_input((string) ($_POST['reason'] ?? ''));
    if ($reqId <= 0) {
        json_response(false, 'معرف الطلب غير صالح.', [], 400);
    }

    try {
        $req = db_query('SELECT id FROM teacher_requests WHERE id = :id LIMIT 1', ['id' => $reqId])->fetch();
        if (!$req) {
            json_response(false, 'طلب التسجيل غير موجود.', [], 404);
        }

        db_query('UPDATE teacher_requests SET status = :status, reviewed_at = NOW() WHERE id = :id', ['status' => 'rejected', 'id' => $reqId]);

        // Optionally log reason to teacher_requests_review table (not implemented currently)

        json_response(true, 'تم رفض طلب التسجيل بنجاح.');
    } catch (Throwable $e) {
        error_log('Admin Teachers Reject Error: ' . $e->getMessage());
        json_response(false, 'خطأ أثناء رفض طلب الأستاذ.', [], 500);
    }
}

json_response(false, 'الإجراء المطلوب غير مدعوم.', [], 400);

?>
