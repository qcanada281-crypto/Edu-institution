<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_teacher_timetable_table();

ensure_post_request();

$teacherId = require_teacher_access();
$action = clean_input((string) ($_POST['action'] ?? 'list'));

function normalize_time(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    if (preg_match('/^\d{2}:\d{2}$/', $value) === 1) {
        return $value . ':00';
    }

    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
        return $value;
    }

    return null;
}

if ($action === 'list') {
    try {
        $rows = db_query(
            'SELECT id, day_of_week, start_time, end_time, class_name, subject_name, room, notes
             FROM teacher_timetable
             WHERE teacher_id = :teacher_id
             ORDER BY day_of_week ASC, start_time ASC, end_time ASC, id ASC',
            ['teacher_id' => $teacherId]
        )->fetchAll();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'day_of_week' => (int) $row['day_of_week'],
                'start_time' => substr((string) $row['start_time'], 0, 5),
                'end_time' => substr((string) $row['end_time'], 0, 5),
                'class_name' => (string) $row['class_name'],
                'subject_name' => (string) $row['subject_name'],
                'room' => (string) ($row['room'] ?? ''),
                'notes' => (string) ($row['notes'] ?? ''),
            ];
        }

        json_response(true, 'تم جلب جدول الحصص.', [
            'items' => $items,
        ]);
    } catch (Throwable $exception) {
        error_log('Teacher Timetable List Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب جدول الحصص.', [], 500);
    }
}

if ($action === 'save') {
    $missing = require_fields($_POST, ['day_of_week', 'start_time', 'end_time', 'class_name', 'subject_name']);
    if (!empty($missing)) {
        json_response(false, 'المرجو تعبئة جميع الحقول الإلزامية.', [], 422);
    }

    $dayOfWeek = (int) ($_POST['day_of_week'] ?? 0);
    $startTime = normalize_time((string) ($_POST['start_time'] ?? ''));
    $endTime = normalize_time((string) ($_POST['end_time'] ?? ''));
    $className = clean_input((string) ($_POST['class_name'] ?? ''));
    $subjectName = clean_input((string) ($_POST['subject_name'] ?? ''));
    $room = clean_input((string) ($_POST['room'] ?? ''));
    $notes = clean_input((string) ($_POST['notes'] ?? ''));

    if ($dayOfWeek < 1 || $dayOfWeek > 7) {
        json_response(false, 'اليوم غير صالح.', [], 422);
    }

    if ($startTime === null || $endTime === null) {
        json_response(false, 'الوقت غير صالح.', [], 422);
    }

    if ($startTime >= $endTime) {
        json_response(false, 'وقت البداية يجب أن يكون قبل وقت النهاية.', [], 422);
    }

    if (mb_strlen($className) > 80 || mb_strlen($subjectName) > 120) {
        json_response(false, 'القسم أو المادة غير صالحة.', [], 422);
    }

    if (mb_strlen($room) > 60) {
        json_response(false, 'اسم القاعة طويل جدا.', [], 422);
    }

    if (mb_strlen($notes) > 255) {
        json_response(false, 'الملاحظات طويلة جدا.', [], 422);
    }

    try {
        db_query(
            'INSERT INTO teacher_timetable (teacher_id, day_of_week, start_time, end_time, class_name, subject_name, room, notes)
             VALUES (:teacher_id, :day_of_week, :start_time, :end_time, :class_name, :subject_name, :room, :notes)',
            [
                'teacher_id' => $teacherId,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'class_name' => $className,
                'subject_name' => $subjectName,
                'room' => $room === '' ? null : $room,
                'notes' => $notes === '' ? null : $notes,
            ]
        );

        json_response(true, 'تمت إضافة الحصة بنجاح.', [
            'id' => (int) db_connection()->lastInsertId(),
        ]);
    } catch (Throwable $exception) {
        error_log('Teacher Timetable Save Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء حفظ الحصة.', [], 500);
    }
}

if ($action === 'delete') {
    $timetableId = (int) ($_POST['id'] ?? 0);
    if ($timetableId <= 0) {
        json_response(false, 'معرّف الحصة غير صالح.', [], 422);
    }

    try {
        $statement = db_query(
            'DELETE FROM teacher_timetable
             WHERE id = :id AND teacher_id = :teacher_id',
            [
                'id' => $timetableId,
                'teacher_id' => $teacherId,
            ]
        );

        if ($statement->rowCount() === 0) {
            json_response(false, 'لم يتم العثور على الحصة أو لا تملك صلاحية حذفها.', [], 404);
        }

        json_response(true, 'تم حذف الحصة بنجاح.');
    } catch (Throwable $exception) {
        error_log('Teacher Timetable Delete Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء حذف الحصة.', [], 500);
    }
}

json_response(false, 'إجراء غير صالح.', [], 400);

