<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_students_table();

ensure_post_request();

$missing = require_fields($_POST, ['student_code', 'birth_date']);
if (!empty($missing)) {
    json_response(false, 'المرجو إدخال كود الطالب وتاريخ الازدياد.');
}

$studentCode = strtoupper(clean_input((string) ($_POST['student_code'] ?? '')));
$birthDate = clean_input((string) ($_POST['birth_date'] ?? ''));

if (!preg_match('/^[A-Z0-9\-]{5,20}$/', $studentCode)) {
    json_response(false, 'صيغة كود الطالب غير صحيحة.');
}

if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $birthDate)) {
    json_response(false, 'تاريخ الازدياد غير صالح.');
}

try {
    $student = db_query(
        'SELECT
            id,
            student_code,
            first_name,
            last_name,
            class_name,
            level,
            birth_date
         FROM students
         WHERE student_code = :student_code
           AND birth_date = :birth_date
         LIMIT 1',
        [
            'student_code' => $studentCode,
            'birth_date' => $birthDate,
        ]
    )->fetch();

    if (!$student) {
        json_response(false, 'المعطيات غير صحيحة. تأكد من كود الطالب وتاريخ الازدياد.', [], 401);
    }

    $gradeRows = db_query(
        'SELECT
            subject_name,
            continuous_score,
            exam_score,
            coefficient,
            semester
         FROM grades
         WHERE student_id = :student_id
         ORDER BY semester ASC, subject_name ASC',
        ['student_id' => (int) $student['id']]
    )->fetchAll();

    $gradesBySemester = [];
    $globalWeightedSum = 0.0;
    $globalCoefficientSum = 0.0;

    foreach ($gradeRows as $row) {
        $semester = trim((string) ($row['semester'] ?? 'S1'));
        if ($semester === '') {
            $semester = 'S1';
        }

        $continuous = (float) $row['continuous_score'];
        $exam = (float) $row['exam_score'];
        $coefficient = (float) $row['coefficient'];
        if ($coefficient <= 0) {
            $coefficient = 1.0;
        }

        $average = ($continuous * 0.4) + ($exam * 0.6);
        $globalWeightedSum += ($average * $coefficient);
        $globalCoefficientSum += $coefficient;

        if (!isset($gradesBySemester[$semester])) {
            $gradesBySemester[$semester] = [];
        }

        $gradesBySemester[$semester][] = [
            'subject_name' => (string) $row['subject_name'],
            'continuous_score' => number_format($continuous, 2, '.', ''),
            'exam_score' => number_format($exam, 2, '.', ''),
            'coefficient' => number_format($coefficient, 2, '.', ''),
            'average' => number_format($average, 2, '.', ''),
        ];
    }

    $attendanceRows = db_query(
        'SELECT
            absence_date,
            session_label,
            justified,
            notes
         FROM attendance
         WHERE student_id = :student_id
         ORDER BY absence_date DESC, id DESC',
        ['student_id' => (int) $student['id']]
    )->fetchAll();

    $attendanceRecords = [];
    $justifiedAbsences = 0;

    foreach ($attendanceRows as $row) {
        $isJustified = (int) $row['justified'] === 1;
        if ($isJustified) {
            $justifiedAbsences++;
        }

        $attendanceRecords[] = [
            'absence_date' => (string) $row['absence_date'],
            'session_label' => (string) $row['session_label'],
            'justified' => $isJustified,
            'notes' => $row['notes'] !== null ? (string) $row['notes'] : '',
        ];
    }

    $totalAbsences = count($attendanceRecords);
    $unjustifiedAbsences = $totalAbsences - $justifiedAbsences;
    $attendanceRate = $totalAbsences > 0
        ? max(0, 100 - (int) round(($unjustifiedAbsences / max(1, $totalAbsences)) * 100))
        : 100;

    $globalAverage = $globalCoefficientSum > 0 ? ($globalWeightedSum / $globalCoefficientSum) : 0.0;

    $semesters = array_keys($gradesBySemester);
    sort($semesters);

    json_response(true, 'تم تسجيل الدخول بنجاح.', [
        'student' => [
            'student_code' => (string) $student['student_code'],
            'full_name' => (string) $student['first_name'] . ' ' . (string) $student['last_name'],
            'class_name' => (string) $student['class_name'],
            'level' => (string) $student['level'],
        ],
        'semesters' => $semesters,
        'grades_by_semester' => $gradesBySemester,
        'attendance' => [
            'records' => $attendanceRecords,
            'stats' => [
                'total_absences' => $totalAbsences,
                'justified_absences' => $justifiedAbsences,
                'unjustified_absences' => $unjustifiedAbsences,
                'attendance_rate' => $attendanceRate,
            ],
        ],
        'summary' => [
            'general_average' => number_format($globalAverage, 2, '.', ''),
            'mention' => calculate_mention($globalAverage),
        ],
    ]);
} catch (Throwable $exception) {
    error_log('Portal Auth Error: ' . $exception->getMessage());
    json_response(false, 'وقع خطأ أثناء تسجيل الدخول للبوابة.', [], 500);
}

