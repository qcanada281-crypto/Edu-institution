<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_messages_table();
ensure_post_request();

$currentRole = require_admin_access();

// يسمح للمدير والسكرتارية برؤية الرسائل
if (!in_array($currentRole, ['director', 'secretary'], true)) {
    json_response(false, 'عرض الرسائل متاح للإدارة فقط.', [], 403);
}

$action = clean_input((string) ($_POST['action'] ?? 'list'));

if ($action === 'list') {
    try {
        $messages = db_query(
            'SELECT id, name, email, phone, subject, message, status, created_at
             FROM messages
             ORDER BY created_at DESC'
        )->fetchAll();

        json_response(true, 'تم جلب الرسائل بنجاح.', [
            'messages' => $messages,
        ]);
    } catch (Throwable $exception) {
        error_log('Admin Messages List Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب الرسائل.', [], 500);
    }
}

if ($action === 'mark_read') {
    $messageId = (int) ($_POST['message_id'] ?? 0);
    
    if ($messageId <= 0) {
        json_response(false, 'معرف الرسالة غير صالح.');
    }

    try {
        db_query(
            'UPDATE messages SET status = :status WHERE id = :id',
            [
                'status' => 'in_progress',
                'id' => $messageId,
            ]
        );

        json_response(true, 'تم تحديث حالة الرسالة.');
    } catch (Throwable $exception) {
        error_log('Mark Message Read Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء تحديث الرسالة.', [], 500);
    }
}

if ($action === 'mark_closed') {
    $messageId = (int) ($_POST['message_id'] ?? 0);
    
    if ($messageId <= 0) {
        json_response(false, 'معرف الرسالة غير صالح.');
    }

    try {
        db_query(
            'UPDATE messages SET status = :status WHERE id = :id',
            [
                'status' => 'closed',
                'id' => $messageId,
            ]
        );

        json_response(true, 'تم إغلاق الرسالة.');
    } catch (Throwable $exception) {
        error_log('Close Message Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء إغلاق الرسالة.', [], 500);
    }
}

if ($action === 'delete') {
    $messageId = (int) ($_POST['message_id'] ?? 0);
    
    if ($messageId <= 0) {
        json_response(false, 'معرف الرسالة غير صالح.');
    }

    try {
        db_query(
            'DELETE FROM messages WHERE id = :id',
            ['id' => $messageId]
        );

        json_response(true, 'تم حذف الرسالة.');
    } catch (Throwable $exception) {
        error_log('Delete Message Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء حذف الرسالة.', [], 500);
    }
}

json_response(false, 'عملية غير معروفة.');
