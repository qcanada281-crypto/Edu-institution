<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_post_request();

// Rate limiting: Maximum 10 lookup attempts per minute per IP to prevent scraping
if (!check_rate_limit('transcript_lookup', 10, 60)) {
    json_response(false, 'تم تجاوز الحد المسموح به من المحاولات. يرجى الانتظار دقيقة واحدة والمحاولة مجدداً.', [], 429);
}

$missing = require_fields($_POST, ['student_code']);
if (!empty($missing)) {
    json_response(false, 'المرجو إدخال كود الطالب (رمز مسار).');
}

$studentCode = strtoupper(clean_input((string) $_POST['student_code']));
if (!preg_match('/^[A-Z0-9\-\_]{3,30}$/', $studentCode)) {
    json_response(false, 'صيغة كود الطالب غير صالحة.');
}

$birthDate = clean_input((string)($_POST['birth_date'] ?? ''));

try {
    $student = db_query(
        'SELECT id, student_code, first_name, last_name, class_name, level, birth_date
         FROM students
         WHERE student_code = :student_code
         LIMIT 1',
        [
            'student_code' => $studentCode,
        ]
    )->fetch();

    if (!$student) {
        json_response(false, 'لم يتم العثور على أي سجل بهذا الكود.');
    }

    // Optional privacy verification: If birth_date was provided, check it matches
    if ($birthDate !== '' && $student['birth_date'] !== $birthDate) {
        json_response(false, 'تاريخ الميلاد المدخل غير مطابق لبيانات التلميذ.');
    }

    $grades = db_query(
        'SELECT
            subject_name,
            continuous_score,
            exam_score,
            coefficient
         FROM grades
         WHERE student_id = :student_id
         ORDER BY subject_name ASC',
        ['student_id' => (int) $student['id']]
    )->fetchAll();

    if (!$grades) {
        json_response(false, 'لا توجد نقط مسجلة لهذا الطالب حاليا.');
    }

    $sumWeighted = 0.0;
    $sumCoefficients = 0.0;
    $normalizedGrades = [];

    foreach ($grades as $grade) {
        $continuous = (float) $grade['continuous_score'];
        $exam = (float) $grade['exam_score'];
        $coefficient = (float) $grade['coefficient'];
        $average = (($continuous * 0.4) + ($exam * 0.6));

        $sumWeighted += ($average * $coefficient);
        $sumCoefficients += $coefficient;

        $normalizedGrades[] = [
            'subject_name' => $grade['subject_name'],
            'continuous_score' => number_format($continuous, 2, '.', ''),
            'exam_score' => number_format($exam, 2, '.', ''),
            'coefficient' => number_format($coefficient, 2, '.', ''),
            'average' => number_format($average, 2, '.', ''),
        ];
    }

    $generalAverage = $sumCoefficients > 0 ? ($sumWeighted / $sumCoefficients) : 0.0;

    ensure_parent_notifications_table();
    $notifStmt = db_query(
        "SELECT id, type, title, message, created_at FROM parent_notifications WHERE massar_code = :massar ORDER BY id DESC LIMIT 10",
        ['massar' => $studentCode]
    );
    $notifications = $notifStmt ? $notifStmt->fetchAll() : [];

    json_response(true, 'تم استخراج بيان النقط بنجاح.', [
        'student' => [
            'full_name' => $student['first_name'] . ' ' . $student['last_name'],
            'student_code' => $student['student_code'],
            'class_name' => $student['class_name'],
            'level' => $student['level'],
        ],
        'grades' => $normalizedGrades,
        'summary' => [
            'general_average' => number_format($generalAverage, 2, '.', ''),
            'mention' => calculate_mention($generalAverage),
        ],
        'notifications' => $notifications,
    ]);
} catch (Throwable $exception) {
    error_log('Transcript Lookup Error: ' . $exception->getMessage());
    json_response(false, 'وقع خطأ أثناء استخراج بيان النقط.', [], 500);
}
