<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_students_table();

ensure_post_request();

$missing = require_fields($_POST, ['student_code']);
if (!empty($missing)) {
    json_response(false, 'المرجو إدخال كود الطالب.');
}

$studentCode = strtoupper(clean_input((string) $_POST['student_code']));

try {
    ensure_attendance_subject_column();
    $attendanceHasSubject = has_table_column('attendance', 'absence_subject');

    $student = db_query(
        'SELECT id, student_code, first_name, last_name, class_name, guardian_name
         FROM students
         WHERE student_code = :student_code
         LIMIT 1',
        ['student_code' => $studentCode]
    )->fetch();

    if (!$student) {
        json_response(false, 'الطالب غير موجود.');
    }

    if ($attendanceHasSubject) {
        $records = db_query(
            'SELECT absence_date, session_label, absence_subject, justified, notes
             FROM attendance
             WHERE student_id = :student_id
             ORDER BY absence_date DESC, id DESC',
            ['student_id' => (int) $student['id']]
        )->fetchAll();
    } else {
        $records = db_query(
            'SELECT absence_date, session_label, "" AS absence_subject, justified, notes
             FROM attendance
             WHERE student_id = :student_id
             ORDER BY absence_date DESC, id DESC',
            ['student_id' => (int) $student['id']]
        )->fetchAll();
    }

    $totalAbsences = count($records);
    $justifiedAbsences = 0;
    $normalized = [];

    foreach ($records as $record) {
        $isJustified = (int) $record['justified'] === 1;
        if ($isJustified) {
            $justifiedAbsences++;
        }

        $normalized[] = [
            'absence_date' => $record['absence_date'],
            'session_label' => $record['session_label'],
            'absence_subject' => $record['absence_subject'] ?? '',
            'justified' => $isJustified,
            'notes' => $record['notes'],
        ];
    }

    $unjustifiedAbsences = $totalAbsences - $justifiedAbsences;

    ensure_parent_notifications_table();
    $notifStmt = db_query(
        "SELECT id, type, title, message, created_at FROM parent_notifications WHERE massar_code = :massar ORDER BY id DESC LIMIT 10",
        ['massar' => $studentCode]
    );
    $notifications = $notifStmt ? $notifStmt->fetchAll() : [];

    json_response(true, 'تم جلب سجل الغياب والإشعارات.', [
        'student' => [
            'full_name' => $student['first_name'] . ' ' . $student['last_name'],
            'student_code' => $student['student_code'],
            'class_name' => $student['class_name'],
            'guardian_name' => $student['guardian_name'],
        ],
        'stats' => [
            'total_absences' => $totalAbsences,
            'justified_absences' => $justifiedAbsences,
            'unjustified_absences' => $unjustifiedAbsences,
        ],
        'records' => $normalized,
        'notifications' => $notifications,
    ]);
} catch (Throwable $exception) {
    error_log('Parent Attendance Error: ' . $exception->getMessage());
    json_response(false, 'وقع خطأ أثناء جلب سجل الغياب.', [], 500);
}
