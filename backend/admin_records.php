<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_students_table();

ensure_post_request();

$currentRole = require_admin_access();

$action = clean_input((string) ($_POST['action'] ?? ''));
$attendanceHasSubject = false;

if (in_array($action, ['get_grades', 'get_attendance'], true) && $currentRole !== 'director') {
    json_response(false, 'عرض التقارير متاح للمدير فقط.', [], 403);
}

if (in_array($action, ['add_attendance', 'get_attendance'], true)) {
    ensure_attendance_subject_column();
    $attendanceHasSubject = has_table_column('attendance', 'absence_subject');
}

if ($action === 'add_grade') {
    $missing = require_fields($_POST, ['subject_name', 'continuous_score', 'exam_score']);
    if (!empty($missing)) {
        json_response(false, 'المرجو إدخال جميع بيانات النقطة.');
    }

    $studentId = (int) ($_POST['student_id'] ?? 0);
    $studentCode = strtoupper(clean_input((string) ($_POST['student_code'] ?? '')));
    $subjectName = clean_input((string) $_POST['subject_name']);
    $continuousScore = filter_var($_POST['continuous_score'], FILTER_VALIDATE_FLOAT);
    $examScore = filter_var($_POST['exam_score'], FILTER_VALIDATE_FLOAT);
    $coefficientRaw = clean_input((string) ($_POST['coefficient'] ?? '1'));
    $semester = clean_input((string) ($_POST['semester'] ?? 'S1'));

    if ($studentId <= 0 && $studentCode === '') {
        json_response(false, 'المرجو إدخال كود طالب صالح.');
    }

    if ($subjectName === '' || mb_strlen($subjectName) > 120) {
        json_response(false, 'اسم المادة غير صالح.');
    }

    if ($continuousScore === false || $continuousScore < 0 || $continuousScore > 20) {
        json_response(false, 'نقطة المراقبة يجب أن تكون بين 0 و 20.');
    }

    if ($examScore === false || $examScore < 0 || $examScore > 20) {
        json_response(false, 'نقطة الامتحان يجب أن تكون بين 0 و 20.');
    }

    $coefficient = 1.0;
    if ($coefficientRaw !== '') {
        $parsedCoefficient = filter_var($coefficientRaw, FILTER_VALIDATE_FLOAT);
        if ($parsedCoefficient === false || $parsedCoefficient <= 0 || $parsedCoefficient > 10) {
            json_response(false, 'المعامل يجب أن يكون رقما بين 0.01 و 10.');
        }
        $coefficient = (float) $parsedCoefficient;
    }

    if ($semester === '') {
        $semester = 'S1';
    }

    if (!preg_match('/^[A-Za-z0-9\-\s]{1,20}$/', $semester)) {
        json_response(false, 'صيغة الدورة غير صالحة.');
    }

    try {
        if ($studentId <= 0 && $studentCode !== '') {
            $studentByCode = db_query(
                'SELECT id FROM students WHERE student_code = :student_code LIMIT 1',
                ['student_code' => $studentCode]
            )->fetch();

            if ($studentByCode) {
                $studentId = (int) ($studentByCode['id'] ?? 0);
            }
        }

        if ($studentId <= 0) {
            json_response(false, 'الطالب غير موجود.');
        }

        $student = db_query(
            'SELECT id FROM students WHERE id = :id LIMIT 1',
            ['id' => $studentId]
        )->fetch();

        if (!$student) {
            json_response(false, 'الطالب غير موجود.');
        }

        $pdo = db_connection();
        $stmt = $pdo->prepare(
            'INSERT INTO grades (
                student_id,
                subject_name,
                continuous_score,
                exam_score,
                coefficient,
                semester
            ) VALUES (
                :student_id,
                :subject_name,
                :continuous_score,
                :exam_score,
                :coefficient,
                :semester
            )'
        );
        $stmt->execute([
            'student_id' => $studentId,
            'subject_name' => $subjectName,
            'continuous_score' => $continuousScore,
            'exam_score' => $examScore,
            'coefficient' => $coefficient,
            'semester' => $semester,
        ]);

        $insertedId = (int)$pdo->lastInsertId();
        log_grade_audit($insertedId, $studentId, 'INSERT', $subjectName, null, $continuousScore, null, $examScore);

        json_response(true, 'تمت إضافة النقطة بنجاح.');
    } catch (Throwable $exception) {
        error_log('Admin Grade Insert Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء إضافة النقطة.', [], 500);
    }
}

if ($action === 'update_grade') {
    $gradeId = (int)($_POST['grade_id'] ?? 0);
    if ($gradeId <= 0) {
        json_response(false, 'معرف النقطة غير صالح.');
    }

    $existing = db_query('SELECT * FROM grades WHERE id = :id LIMIT 1', ['id' => $gradeId])->fetch();
    if (!$existing) {
        json_response(false, 'النقطة المطلوبة غير موجودة.');
    }

    $continuousRaw = trim((string)($_POST['continuous_score'] ?? ''));
    $examRaw = trim((string)($_POST['exam_score'] ?? ''));
    $newContinuous = $continuousRaw !== '' ? (float)$continuousRaw : (float)$existing['continuous_score'];
    $newExam = $examRaw !== '' ? (float)$examRaw : (float)$existing['exam_score'];

    if ($newContinuous < 0 || $newContinuous > 20 || $newExam < 0 || $newExam > 20) {
        json_response(false, 'النقط يجب أن تكون بين 0 و 20.');
    }

    try {
        db_query(
            'UPDATE grades SET continuous_score = :continuous, exam_score = :exam WHERE id = :id',
            [
                'continuous' => $newContinuous,
                'exam'       => $newExam,
                'id'         => $gradeId,
            ]
        );

        log_grade_audit(
            $gradeId,
            (int)$existing['student_id'],
            'UPDATE',
            (string)$existing['subject_name'],
            (float)$existing['continuous_score'],
            $newContinuous,
            (float)$existing['exam_score'],
            $newExam
        );

        json_response(true, 'تم تحديث النقطة بنجاح وتسجيل العملية في سجل التدقيق.');
    } catch (Throwable $e) {
        error_log('Admin Grade Update Error: ' . $e->getMessage());
        json_response(false, 'وقع خطأ أثناء تحديث النقطة.', [], 500);
    }
}

if ($action === 'delete_grade') {
    $gradeId = (int)($_POST['grade_id'] ?? 0);
    if ($gradeId <= 0) {
        json_response(false, 'معرف النقطة غير صالح.');
    }

    $existing = db_query('SELECT * FROM grades WHERE id = :id LIMIT 1', ['id' => $gradeId])->fetch();
    if (!$existing) {
        json_response(false, 'النقطة المطلوبة غير موجودة.');
    }

    try {
        db_query('DELETE FROM grades WHERE id = :id', ['id' => $gradeId]);

        log_grade_audit(
            $gradeId,
            (int)$existing['student_id'],
            'DELETE',
            (string)$existing['subject_name'],
            (float)$existing['continuous_score'],
            null,
            (float)$existing['exam_score'],
            null
        );

        json_response(true, 'تم حذف النقطة بنجاح وتسجيل العملية في سجل التدقيق.');
    } catch (Throwable $e) {
        error_log('Admin Grade Delete Error: ' . $e->getMessage());
        json_response(false, 'وقع خطأ أثناء حذف النقطة.', [], 500);
    }
}


if ($action === 'add_attendance') {
    if (!$attendanceHasSubject) {
        json_response(
            false,
            'يلزم تحديث بنية جدول الغياب لإضافة حقل المادة. المرجو التواصل مع الإدارة التقنية.',
            [],
            500
        );
    }

    $missing = require_fields($_POST, ['absence_date', 'session_label', 'absence_subject']);
    if (!empty($missing)) {
        json_response(false, 'المرجو إدخال جميع بيانات الغياب.');
    }

    $studentId = (int) ($_POST['student_id'] ?? 0);
    $studentCode = strtoupper(clean_input((string) ($_POST['student_code'] ?? '')));
    $absenceDate = clean_input((string) $_POST['absence_date']);
    $sessionLabel = clean_input((string) $_POST['session_label']);
    $absenceSubject = clean_input((string) $_POST['absence_subject']);
    $justified = clean_input((string) ($_POST['justified'] ?? '0')) === '1' ? 1 : 0;
    $notes = clean_input((string) ($_POST['notes'] ?? ''));

    if ($studentId <= 0 && $studentCode === '') {
        json_response(false, 'المرجو إدخال كود طالب صالح.');
    }

    if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $absenceDate)) {
        json_response(false, 'تاريخ الغياب غير صالح.');
    }

    $parsedDate = DateTime::createFromFormat('Y-m-d', $absenceDate);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $absenceDate) {
        json_response(false, 'تاريخ الغياب غير صحيح.');
    }

    if ($sessionLabel === '' || mb_strlen($sessionLabel) > 50) {
        json_response(false, 'الحصة غير صالحة.');
    }

    if ($absenceSubject === '' || mb_strlen($absenceSubject) > 120) {
        json_response(false, 'المادة غير صالحة.');
    }

    if (mb_strlen($notes) > 255) {
        json_response(false, 'الملاحظة طويلة جدا.');
    }

    try {
        if ($studentId <= 0 && $studentCode !== '') {
            $studentByCode = db_query(
                'SELECT id FROM students WHERE student_code = :student_code LIMIT 1',
                ['student_code' => $studentCode]
            )->fetch();

            if ($studentByCode) {
                $studentId = (int) ($studentByCode['id'] ?? 0);
            }
        }

        if ($studentId <= 0) {
            json_response(false, 'الطالب غير موجود.');
        }

        $student = db_query(
            'SELECT id FROM students WHERE id = :id LIMIT 1',
            ['id' => $studentId]
        )->fetch();

        if (!$student) {
            json_response(false, 'الطالب غير موجود.');
        }

        db_query(
            'INSERT INTO attendance (
                student_id,
                absence_date,
                session_label,
                absence_subject,
                justified,
                notes
            ) VALUES (
                :student_id,
                :absence_date,
                :session_label,
                :absence_subject,
                :justified,
                :notes
            )',
            [
                'student_id' => $studentId,
                'absence_date' => $absenceDate,
                'session_label' => $sessionLabel,
                'absence_subject' => $absenceSubject,
                'justified' => $justified,
                'notes' => $notes === '' ? null : $notes,
            ]
        );

        json_response(true, 'تم تسجيل الغياب بنجاح.');
    } catch (Throwable $exception) {
        error_log('Admin Attendance Insert Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء تسجيل الغياب.', [], 500);
    }
}

if ($action === 'get_grades') {
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $semester = clean_input((string) ($_POST['semester'] ?? ''));

    if ($studentId <= 0) {
        json_response(false, 'معرف الطالب غير صالح.');
    }

    try {
        $query = 'SELECT id, subject_name, continuous_score, exam_score, coefficient, semester FROM grades WHERE student_id = :student_id';
        $params = ['student_id' => $studentId];

        if ($semester !== '') {
            $query .= ' AND semester = :semester';
            $params['semester'] = $semester;
        }

        $query .= ' ORDER BY subject_name ASC';

        $grades = db_query($query, $params)->fetchAll();
        json_response(true, 'تم جلب النقط بنجاح.', $grades);
    } catch (Throwable $exception) {
        error_log('Admin Get Grades Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب النقط.', [], 500);
    }
}

if ($action === 'get_attendance') {
    $studentId = (int) ($_POST['student_id'] ?? 0);

    if ($studentId <= 0) {
        json_response(false, 'معرف الطالب غير صالح.');
    }

    try {
        if ($attendanceHasSubject) {
            $attendance = db_query(
                'SELECT id, absence_date, session_label, absence_subject, justified, notes
                 FROM attendance
                 WHERE student_id = :student_id
                 ORDER BY absence_date DESC',
                ['student_id' => $studentId]
            )->fetchAll();
        } else {
            $attendance = db_query(
                'SELECT id, absence_date, session_label, "" AS absence_subject, justified, notes
                 FROM attendance
                 WHERE student_id = :student_id
                 ORDER BY absence_date DESC',
                ['student_id' => $studentId]
            )->fetchAll();
        }

        json_response(true, 'تم جلب الغياب بنجاح.', $attendance);
    } catch (Throwable $exception) {
        error_log('Admin Get Attendance Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب الغياب.', [], 500);
    }
}

// ==========================================
// TRANSCRIPT PERMISSION REQUESTS MANAGEMENT
// ==========================================
if ($action === 'list_transcript_requests') {
    ensure_transcript_requests_table();
    try {
        $requests = db_query(
            'SELECT r.*, s.first_name, s.last_name, s.class_name, s.level, s.guardian_name, s.guardian_phone
             FROM transcript_requests r
             LEFT JOIN students s ON r.student_id = s.id
             ORDER BY CASE WHEN r.status = "pending" THEN 0 ELSE 1 END, r.id DESC
             LIMIT 200'
        )->fetchAll();

        $pendingCount = 0;
        foreach ($requests as $r) {
            if (($r['status'] ?? '') === 'pending') {
                $pendingCount++;
            }
        }

        json_response(true, 'تم جلب طلبات سحب النقط بنجاح.', [
            'requests' => $requests,
            'pending_count' => $pendingCount,
            'can_manage' => in_array($currentRole, ['director', 'secretary'], true)
        ]);
    } catch (Throwable $exception) {
        error_log('Admin List Transcript Requests Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب طلبات سحب النقط.', [], 500);
    }
}

if ($action === 'approve_transcript_request') {
    ensure_transcript_requests_table();
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $durationHours = (int) ($_POST['duration_hours'] ?? 24);
    if ($durationHours < 0) {
        $durationHours = 24;
    }

    if ($requestId <= 0) {
        json_response(false, 'معرف الطلب غير صالح.');
    }

    try {
        $req = db_query('SELECT * FROM transcript_requests WHERE id = :id', ['id' => $requestId])->fetch();
        if (!$req) {
            json_response(false, 'الطلب غير موجود.');
        }

        $expiresAt = $durationHours > 0 ? date('Y-m-d H:i:s', strtotime("+{$durationHours} hours")) : null;
        $adminName = (string) ($_SESSION['user_name'] ?? 'الإدارة');

        db_query(
            'UPDATE transcript_requests
             SET status = "approved", expires_at = :expires_at, approved_by = :approved_by, duration_hours = :duration_hours
             WHERE id = :id',
            [
                'expires_at' => $expiresAt,
                'approved_by' => $adminName,
                'duration_hours' => $durationHours,
                'id' => $requestId
            ]
        );

        // Notify parent/student
        ensure_parent_notifications_table();
        $durationLabel = $durationHours > 0 ? "لمدة {$durationHours} ساعة" : "بشكل دائم";
        db_query(
            "INSERT INTO parent_notifications (massar_code, student_name, type, title, message)
             VALUES (:massar, :name, 'grades', 'الموافقة على سحب بيان النقط', :msg)",
            [
                'massar' => $req['student_code'],
                'name' => $req['requested_by'] ?: $req['student_code'],
                'msg' => "وافقت إدارة المؤسسة على طلب سحب بيان النقط ({$durationLabel}). يمكنكم الآن الاطلاع على النقط وطباعتها بأمان."
            ]
        );

        json_response(true, "تمت الموافقة على الطلب بنجاح وتفعيل إمكانية السحب {$durationLabel}.");
    } catch (Throwable $exception) {
        error_log('Admin Approve Transcript Request Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء الموافقة على الطلب.', [], 500);
    }
}

if ($action === 'reject_transcript_request') {
    ensure_transcript_requests_table();
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $reason = clean_input((string) ($_POST['reason'] ?? 'تم رفض الطلب من طرف الإدارة. يرجى مراجعة إدارة المؤسسة.'));
    if ($reason === '') {
        $reason = 'تم رفض الطلب من طرف الإدارة. يرجى مراجعة إدارة المؤسسة.';
    }

    if ($requestId <= 0) {
        json_response(false, 'معرف الطلب غير صالح.');
    }

    try {
        $req = db_query('SELECT * FROM transcript_requests WHERE id = :id', ['id' => $requestId])->fetch();
        if (!$req) {
            json_response(false, 'الطلب غير موجود.');
        }

        $adminName = (string) ($_SESSION['user_name'] ?? 'الإدارة');

        db_query(
            'UPDATE transcript_requests
             SET status = "rejected", admin_notes = :notes, approved_by = :approved_by
             WHERE id = :id',
            [
                'notes' => $reason,
                'approved_by' => $adminName,
                'id' => $requestId
            ]
        );

        // Notify parent
        ensure_parent_notifications_table();
        db_query(
            "INSERT INTO parent_notifications (massar_code, student_name, type, title, message)
             VALUES (:massar, :name, 'general', 'تحديث بخصوص طلب بيان النقط', :msg)",
            [
                'massar' => $req['student_code'],
                'name' => $req['requested_by'] ?: $req['student_code'],
                'msg' => "تم رفض طلب سحب بيان النقط من طرف الإدارة: {$reason}"
            ]
        );

        json_response(true, 'تم رفض الطلب وتسجيل الملاحظة بنجاح.');
    } catch (Throwable $exception) {
        error_log('Admin Reject Transcript Request Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء رفض الطلب.', [], 500);
    }
}

if ($action === 'delete_transcript_request') {
    ensure_transcript_requests_table();
    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId <= 0) {
        json_response(false, 'معرف الطلب غير صالح.');
    }

    try {
        db_query('DELETE FROM transcript_requests WHERE id = :id', ['id' => $requestId]);
        json_response(true, 'تم حذف سجل الطلب بنجاح.');
    } catch (Throwable $exception) {
        error_log('Admin Delete Transcript Request Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء حذف الطلب.', [], 500);
    }
}

json_response(false, 'الإجراء المطلوب غير مدعوم.', [], 400);
