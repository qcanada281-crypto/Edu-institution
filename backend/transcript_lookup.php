<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_post_request();

// Rate limiting: Maximum 15 attempts per minute per IP to prevent brute force / scraping
if (!check_rate_limit('transcript_lookup', 15, 60)) {
    json_response(false, 'تم تجاوز الحد المسموح به من المحاولات. يرجى الانتظار دقيقة واحدة والمحاولة مجدداً.', [], 429);
}

ensure_transcript_requests_table();

$action = clean_input((string) ($_POST['action'] ?? 'lookup'));

$missing = require_fields($_POST, ['student_code']);
if (!empty($missing)) {
    json_response(false, 'المرجو إدخال كود الطالب (رمز مسار).');
}

$studentCode = strtoupper(clean_input((string) $_POST['student_code']));
if (!preg_match('/^[A-Z0-9\-\_]{3,30}$/', $studentCode)) {
    json_response(false, 'صيغة كود الطالب غير صالحة.');
}

$birthDate = clean_input((string) ($_POST['birth_date'] ?? ''));

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

    // Fallback for slight variations in trailing zeros (e.g. G145000000 vs G1450000000)
    if (!$student) {
        $trimmedCode = rtrim($studentCode, '0');
        if (strlen($trimmedCode) >= 3) {
            $matched = db_query(
                'SELECT id, student_code, first_name, last_name, class_name, level, birth_date
                 FROM students
                 WHERE student_code LIKE :pattern
                 LIMIT 1',
                ['pattern' => $trimmedCode . '%']
            )->fetch();
            if ($matched) {
                $student = $matched;
                $studentCode = (string) $student['student_code'];
            }
        }
    }

    if (!$student) {
        json_response(false, 'لم يتم العثور على أي سجل بهذا الكود.');
    }

    // Strict privacy verification: birth_date is required to verify the student's or guardian's identity
    if ($birthDate === '') {
        json_response(false, 'يرجى إدخال تاريخ الازدياد للتحقق من هوية التلميذ وحماية خصوصية النقط.');
    }

    if ($student['birth_date'] !== $birthDate) {
        json_response(false, 'تاريخ الازدياد المدخل غير مطابق لبيانات التلميذ المسجلة.');
    }

    // ==========================================
    // ACTION: SUBMIT PERMISSION REQUEST TO ADMIN
    // ==========================================
    if ($action === 'request_permission') {
        $existing = db_query(
            'SELECT id, status, expires_at, created_at, admin_notes
             FROM transcript_requests
             WHERE student_code = :student_code
             ORDER BY id DESC
             LIMIT 1',
            ['student_code' => $studentCode]
        )->fetch();

        // Check if there is already an active pending request
        if ($existing && $existing['status'] === 'pending') {
            json_response(true, 'يوجد بالفعل طلب ترخيص قيد المراجعة لدى الإدارة. يرجى الانتظار لحين معالجته.', [
                'request_status' => 'pending',
                'created_at' => $existing['created_at'],
                'student' => [
                    'full_name' => $student['first_name'] . ' ' . $student['last_name'],
                    'student_code' => $student['student_code'],
                    'class_name' => $student['class_name']
                ]
            ]);
        }

        // Check if there is an active valid approved request already
        if ($existing && $existing['status'] === 'approved' && (!$existing['expires_at'] || strtotime($existing['expires_at']) > time())) {
            json_response(true, 'تمت الموافقة مسبقاً على سحب بيان النقط وهو متاح الآن للاطلاع فوراً.', [
                'request_status' => 'approved',
                'expires_at' => $existing['expires_at']
            ]);
        }

        $notes = clean_input((string) ($_POST['notes'] ?? 'طلب سحب بيان النقط عبر البوابة'));
        db_query(
            'INSERT INTO transcript_requests (student_id, student_code, status, requested_by, request_notes)
             VALUES (:student_id, :student_code, "pending", :requested_by, :request_notes)',
            [
                'student_id' => (int) $student['id'],
                'student_code' => $studentCode,
                'requested_by' => $student['first_name'] . ' ' . $student['last_name'],
                'request_notes' => $notes,
            ]
        );

        // Record a parent notification for transparency
        ensure_parent_notifications_table();
        db_query(
            "INSERT INTO parent_notifications (massar_code, student_name, type, title, message)
             VALUES (:massar, :name, 'general', 'طلب سحب بيان النقط', 'تم إرسال طلب ترخيص سحب بيان النقط إلى إدارة المؤسسة بنجاح وهو قيد المعالجة.')",
            [
                'massar' => $studentCode,
                'name' => $student['first_name'] . ' ' . $student['last_name']
            ]
        );

        json_response(true, 'تم إرسال طلب إذن سحب النقط إلى الإدارة بنجاح. ستتم مراجعته والموافقة عليه من طرف الإدارة لتمكينكم من تحميل البيان.', [
            'request_status' => 'pending',
            'created_at' => date('Y-m-d H:i'),
            'student' => [
                'full_name' => $student['first_name'] . ' ' . $student['last_name'],
                'student_code' => $student['student_code'],
                'class_name' => $student['class_name']
            ]
        ]);
    }

    // ==========================================
    // ACTION: LOOKUP / CHECK APPROVAL PERMISSION
    // ==========================================
    $latestRequest = db_query(
        'SELECT id, status, expires_at, created_at, admin_notes, approved_by
         FROM transcript_requests
         WHERE student_code = :student_code
         ORDER BY id DESC
         LIMIT 1',
        ['student_code' => $studentCode]
    )->fetch();

    $studentInfo = [
        'full_name' => $student['first_name'] . ' ' . $student['last_name'],
        'student_code' => $student['student_code'],
        'class_name' => $student['class_name'],
        'level' => $student['level'],
    ];

    // If no request exists at all
    if (!$latestRequest) {
        json_response(false, 'يتطلب سحب بيان النقط إذنًا مسبقًا وموافقة رسمية من إدارة المؤسسة لحماية خصوصية التلميذ.', [
            'need_permission' => true,
            'request_status' => 'none',
            'student' => $studentInfo,
        ]);
    }

    // If request is still pending
    if ($latestRequest['status'] === 'pending') {
        json_response(false, 'طلب سحب بيان النقط قيد المراجعة حالياً من طرف إدارة المؤسسة. يرجى الانتظار لحين تأكيد الموافقة.', [
            'need_permission' => true,
            'request_status' => 'pending',
            'created_at' => $latestRequest['created_at'],
            'student' => $studentInfo,
        ]);
    }

    // If request was rejected
    if ($latestRequest['status'] === 'rejected') {
        $rejectReason = !empty($latestRequest['admin_notes']) ? $latestRequest['admin_notes'] : 'يرجى مراجعة إدارة المؤسسة.';
        json_response(false, 'تم رفض طلب سحب بيان النقط من طرف الإدارة: ' . $rejectReason, [
            'need_permission' => true,
            'request_status' => 'rejected',
            'admin_notes' => $rejectReason,
            'student' => $studentInfo,
        ]);
    }

    // If approved, check if expired
    if ($latestRequest['status'] === 'approved') {
        if (!empty($latestRequest['expires_at']) && strtotime((string) $latestRequest['expires_at']) < time()) {
            json_response(false, 'انتهت مدة صلاحية ترخيص سحب بيان النقط الممنوح من الإدارة. يرجى تقديم طلب جديد.', [
                'need_permission' => true,
                'request_status' => 'expired',
                'expired_at' => $latestRequest['expires_at'],
                'student' => $studentInfo,
            ]);
        }
    }

    // ==========================================
    // PERMISSION IS ACTIVE & APPROVED: FETCH GRADES
    // ==========================================
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
        json_response(false, 'تمت الموافقة من طرف الإدارة، لكن لا توجد نقط مسجلة لهذا الطالب حالياً.');
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

    json_response(true, 'تم التحقق من ترخيص الإدارة واستخراج بيان النقط بنجاح.', [
        'permission' => [
            'approved' => true,
            'approved_by' => $latestRequest['approved_by'] ?: 'إدارة المؤسسة',
            'expires_at' => $latestRequest['expires_at'],
        ],
        'student' => $studentInfo,
        'grades' => $normalizedGrades,
        'summary' => [
            'general_average' => number_format($generalAverage, 2, '.', ''),
            'mention' => calculate_mention($generalAverage),
        ],
        'notifications' => $notifications,
    ]);
} catch (Throwable $exception) {
    error_log('Transcript Lookup Error: ' . $exception->getMessage());
    json_response(false, 'وقع خطأ أثناء استخراج بيان النقط: ' . $exception->getMessage(), [], 500);
}
