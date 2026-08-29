<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_teacher_absence_table();
ensure_post_request();

$adminRole = require_admin_access(['director', 'secretary']);
$action = clean_input((string) ($_POST['action'] ?? 'list'));

$statusLabels = [
    'pending' => 'قيد المراجعة',
    'approved' => 'مقبول',
    'rejected' => 'مرفوض',
];
$typeLabels = [
    'sick' => 'مرضي',
    'family' => 'عائلي',
    'admin' => 'إداري',
    'other' => 'آخر',
];

if ($action === 'list') {
    try {
        $rows = db_query(
            'SELECT a.id, a.absence_date, a.absence_type, a.reason, a.document_path,
                    a.is_urgent, a.status, a.admin_notes, a.created_at,
                    t.full_name AS teacher_name, t.phone AS teacher_phone,
                    t.email AS teacher_email, t.subject_name AS teacher_subject
             FROM teacher_absences a
             INNER JOIN teachers t ON t.id = a.teacher_id
             ORDER BY a.is_urgent DESC, a.status = "pending" DESC, a.absence_date DESC, a.created_at DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        $absences = [];
        $pendingCount = 0;
        $urgentCount = 0;
        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? 'pending');
            $isUrgent = (int) ($row['is_urgent'] ?? 0) === 1;
            
            if ($status === 'pending') {
                $pendingCount++;
            }
            if ($isUrgent && $status === 'pending') {
                $urgentCount++;
            }

            $absences[] = [
                'id' => (int) $row['id'],
                'teacher_name' => (string) ($row['teacher_name'] ?? ''),
                'teacher_phone' => (string) ($row['teacher_phone'] ?? ''),
                'teacher_email' => (string) ($row['teacher_email'] ?? ''),
                'teacher_subject' => (string) ($row['teacher_subject'] ?? ''),
                'absence_date' => (string) ($row['absence_date'] ?? ''),
                'type' => $typeLabels[(string) ($row['absence_type'] ?? '')] ?? (string) ($row['absence_type'] ?? ''),
                'reason' => (string) ($row['reason'] ?? ''),
                'document_path' => (string) ($row['document_path'] ?? ''),
                'is_urgent' => $isUrgent,
                'status' => $status,
                'status_label' => $statusLabels[$status] ?? $status,
                'admin_notes' => (string) ($row['admin_notes'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }

        json_response(true, 'تم جلب غيابات الأساتذة.', [
            'absences' => $absences,
            'pending_count' => $pendingCount,
            'urgent_count' => $urgentCount,
            'can_review' => $adminRole === 'director',
            'user_role' => $adminRole,
        ]);
    } catch (Throwable $exception) {
        error_log('Admin Teacher Absences List Error: ' . $exception->getMessage());
        json_response(false, 'تعذر جلب غيابات الأساتذة.', [], 500);
    }
}

if ($action === 'review') {
    if ($adminRole !== 'director') {
        json_response(false, 'معالجة والرد على غيابات الأساتذة من صلاحيات المدير فقط.', [], 403);
    }

    $absenceId = (int) ($_POST['absence_id'] ?? 0);
    $decision = clean_input((string) ($_POST['decision'] ?? ''));
    $notes = clean_input((string) ($_POST['admin_notes'] ?? ''));

    if ($absenceId <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
        json_response(false, 'بيانات معالجة الغياب غير صالحة.', [], 422);
    }

    try {
        $absence = db_query(
            'SELECT id FROM teacher_absences WHERE id = :id LIMIT 1',
            ['id' => $absenceId]
        )->fetch();

        if (!$absence) {
            json_response(false, 'هذا الإشعار غير موجود.', [], 404);
        }

        db_query(
            'UPDATE teacher_absences
             SET status = :status, admin_notes = :admin_notes
             WHERE id = :id',
            [
                'status' => $decision,
                'admin_notes' => $notes ?: null,
                'id' => $absenceId,
            ]
        );

        json_response(true, $decision === 'approved' ? 'تم قبول الإشعار وحفظ رد المدير.' : 'تم رفض الإشعار وحفظ رد المدير.');
    } catch (Throwable $exception) {
        error_log('Admin Teacher Absence Review Error: ' . $exception->getMessage());
        json_response(false, 'تعذر معالجة إشعار الغياب.', [], 500);
    }
}

json_response(false, 'الإجراء غير صالح.', [], 400);
