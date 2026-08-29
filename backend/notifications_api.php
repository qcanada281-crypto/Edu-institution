<?php
/**
 * WhatsApp & Parent Notifications Engine API
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

ensure_parent_notifications_table();
ensure_students_table();

$pdo = db_connection();

/**
 * Format phone number to international Morocco format 212...
 */
function format_morocco_phone(?string $phone): string
{
    if (empty($phone)) return '';
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    if (strpos($cleaned, '0') === 0) {
        $cleaned = '212' . substr($cleaned, 1);
    } elseif (strpos($cleaned, '212') !== 0 && strlen($cleaned) === 9) {
        $cleaned = '212' . $cleaned;
    }
    return $cleaned;
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'get_whatsapp_link':
            $massar = trim($_POST['massar'] ?? $_GET['massar'] ?? '');
            $type = trim($_POST['type'] ?? $_GET['type'] ?? 'absence');
            $customMessage = trim($_POST['message'] ?? $_GET['message'] ?? '');
            $date = trim($_POST['date'] ?? $_GET['date'] ?? date('Y-m-d'));
            $subject = trim($_POST['subject'] ?? $_GET['subject'] ?? 'العامة');

            if (empty($massar)) {
                throw new Exception('رقم مسار التلميذ مطلوب');
            }

            // Find student by student_code or id in students or registration_requests
            $stmt = $pdo->prepare("SELECT * FROM students WHERE student_code = ? OR student_code = UPPER(?) OR id = ? LIMIT 1");
            $stmt->execute([$massar, $massar, (int) $massar]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student) {
                $stmtReg = $pdo->prepare("SELECT * FROM registration_requests WHERE student_code = ? OR student_code = UPPER(?) OR id = ? LIMIT 1");
                $stmtReg->execute([$massar, $massar, (int) $massar]);
                $student = $stmtReg->fetch(PDO::FETCH_ASSOC);
            }

            if (!$student) {
                $studentName = "التلميذ ({$massar})";
                $guardianName = "ولي الأمر المحترم";
                $phone = '';
            } else {
                $rawPhone = !empty($student['guardian_phone']) ? $student['guardian_phone'] : ($student['phone'] ?? '');
                $phone = format_morocco_phone((string) $rawPhone);
                $studentName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
                $guardianName = !empty($student['guardian_name']) ? trim($student['guardian_name']) : "ولي الأمر المحترم";
            }

            $subjectClean = trim($subject);
            $subjectStr = (!empty($subjectClean) && $subjectClean !== 'المادة المعنية')
                ? (mb_strpos($subjectClean, 'مادة') === false ? "مادة {$subjectClean}" : $subjectClean)
                : "الحصص الدراسية اليومية";

            if ($type === 'absence') {
                $title = "إشعار غياب التلميذ(ة)";
                $text = "السلام عليكم ورحمة الله، السيد(ة) {$guardianName} المحترم(ة)، تخبركم إدارة مؤسسة كوكب العلوم أن التلميذ(ة) {$studentName} (كود الطالب: {$massar}) سجل غائباً(ة) بتاريخ {$date} في {$subjectStr}. للمزيد من الاستفسار يرجى التواصل مع إدارة المؤسسة.";
            } elseif ($type === 'grades') {
                $title = "إشعار نتائج واستخراج النقط";
                $text = "السلام عليكم ورحمة الله، السيد(ة) {$guardianName} المحترم(ة)، تعلن إدارة مؤسسة كوكب العلوم عن نشر كشف نقط التلميذ(ة) {$studentName} (كود الطالب: {$massar}). يمكنكم الاطلاع على النقط والتفاصيل مباشرة عبر موقع المنصة.";
            } else {
                $title = "إشعار مدرسي إداري";
                $text = !empty($customMessage) ? $customMessage : "السلام عليكم ورحمة الله، السيد(ة) {$guardianName} المحترم(ة)، إشعار هام من إدارة مؤسسة كوكب العلوم يخص التلميذ(ة) {$studentName}.";
            }

            // Log into parent_notifications table
            $logStmt = $pdo->prepare("
                INSERT INTO parent_notifications (massar_code, student_name, phone, type, title, message, is_sent)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $logStmt->execute([$massar, $studentName, $phone, $type, $title, $text]);

            $encodedText = rawurlencode($text);
            $whatsappUrl = !empty($phone)
                ? "https://wa.me/{$phone}?text={$encodedText}"
                : "https://api.whatsapp.com/send?text={$encodedText}";

            echo json_encode([
                'success' => true,
                'whatsapp_url' => $whatsappUrl,
                'phone' => $phone,
                'message' => $text,
                'student_name' => $studentName
            ]);
            break;

        case 'list_notifications':
            $massar = trim($_POST['massar'] ?? $_GET['massar'] ?? '');
            if (empty($massar)) {
                throw new Exception('رقم مسار مطلوب');
            }

            $stmt = $pdo->prepare("
                SELECT id, massar_code, student_name, type, title, message, created_at
                FROM parent_notifications
                WHERE massar_code = ?
                ORDER BY id DESC
                LIMIT 20
            ");
            $stmt->execute([$massar]);
            $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'data' => $notifs
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'إجراء غير معروف'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
