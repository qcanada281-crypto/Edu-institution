<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Public API - no authentication required
// Returns only active teachers with limited info

header('Content-Type: application/json; charset=UTF-8');

$action = $_GET['action'] ?? 'list';

// List all active teachers
if ($action === 'list') {
    try {
        ensure_teachers_table();
        
        $teachers = db_query(
            'SELECT id, full_name, email, phone, subject_name, bio, specialty, 
                    avatar, rating, created_at, status
             FROM teachers 
             WHERE status = "active"
             ORDER BY full_name ASC'
        )->fetchAll();
        
        $sanitizedTeachers = [];
        foreach ($teachers as $teacher) {
            $sanitizedTeachers[] = [
                'id' => (int) $teacher['id'],
                'full_name' => (string) $teacher['full_name'],
                'subject_name' => (string) ($teacher['subject_name'] ?? ''),
                'bio' => (string) ($teacher['bio'] ?? ''),
                'specialty' => (string) ($teacher['specialty'] ?? 'مؤسسة Kawkab Al Ouloum'),
                'avatar' => (string) ($teacher['avatar'] ?? ''),
                'rating' => (string) ($teacher['rating'] ?? '5.0'),
                'created_at' => (string) ($teacher['created_at'] ?? ''),
                'email' => (string) ($teacher['email'] ?? ''),
                'phone' => (string) ($teacher['phone'] ?? ''),
            ];
        }
        
        json_response(true, 'تم جلب قائمة الأساتذة بنجاح.', [
            'teachers' => $sanitizedTeachers,
            'count' => count($sanitizedTeachers),
        ]);
    } catch (Throwable $exception) {
        error_log('Public Teachers List Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب البيانات.', [], 500);
    }
}

// Get teacher timetable
if ($action === 'timetable') {
    $teacherId = (int) ($_GET['teacher_id'] ?? 0);
    
    if ($teacherId <= 0) {
        json_response(false, 'معرّف الأستاذ غير صالح.', [], 422);
    }
    
    try {
        ensure_teacher_timetable_table();
        
        // Verify teacher is active
        $teacher = db_query(
            'SELECT id FROM teachers WHERE id = :id AND status = "active" LIMIT 1',
            ['id' => $teacherId]
        )->fetch();
        
        if (!$teacher) {
            json_response(false, 'الأستاذ غير موجود أو غير مفعل.', [], 404);
        }
        
        $rows = db_query(
            'SELECT id, day_of_week, start_time, end_time, class_name, subject_name, room, notes
             FROM teacher_timetable
             WHERE teacher_id = :teacher_id
             ORDER BY day_of_week ASC, start_time ASC',
            ['teacher_id' => $teacherId]
        )->fetchAll();
        
        $timetable = [];
        foreach ($rows as $row) {
            $timetable[] = [
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
        
        json_response(true, 'تم جلب جدول الحصص بنجاح.', [
            'timetable' => $timetable,
        ]);
    } catch (Throwable $exception) {
        error_log('Public Teacher Timetable Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب الجدول.', [], 500);
    }
}

// Get single teacher details
if ($action === 'detail') {
    $teacherId = (int) ($_GET['teacher_id'] ?? 0);
    
    if ($teacherId <= 0) {
        json_response(false, 'معرّف الأستاذ غير صالح.', [], 422);
    }
    
    try {
        $teacher = db_query(
            'SELECT id, full_name, email, phone, subject_name, bio, specialty, 
                    avatar, rating, created_at
             FROM teachers 
             WHERE id = :id AND status = "active"
             LIMIT 1',
            ['id' => $teacherId]
        )->fetch();
        
        if (!$teacher) {
            json_response(false, 'الأستاذ غير موجود.', [], 404);
        }
        
        json_response(true, 'تم جلب بيانات الأستاذ بنجاح.', [
            'teacher' => [
                'id' => (int) $teacher['id'],
                'full_name' => (string) $teacher['full_name'],
                'subject_name' => (string) ($teacher['subject_name'] ?? ''),
                'bio' => (string) ($teacher['bio'] ?? ''),
                'specialty' => (string) ($teacher['specialty'] ?? ''),
                'avatar' => (string) ($teacher['avatar'] ?? ''),
                'rating' => (string) ($teacher['rating'] ?? '5.0'),
                'email' => (string) ($teacher['email'] ?? ''),
                'phone' => (string) ($teacher['phone'] ?? ''),
                'created_at' => (string) ($teacher['created_at'] ?? ''),
            ],
        ]);
    } catch (Throwable $exception) {
        error_log('Public Teacher Detail Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب البيانات.', [], 500);
    }
}

// Get teacher homework
if ($action === 'homework') {
    $teacherId = (int) ($_GET['teacher_id'] ?? 0);
    
    if ($teacherId <= 0) {
        json_response(false, 'معرّف الأستاذ غير صالح.', [], 422);
    }
    
    try {
        ensure_teacher_homework_table();
        
        // Verify teacher is active
        $teacher = db_query(
            'SELECT id FROM teachers WHERE id = :id AND status = "active" LIMIT 1',
            ['id' => $teacherId]
        )->fetch();
        
        if (!$teacher) {
            json_response(false, 'الأستاذ غير موجود أو غير مفعل.', [], 404);
        }
        
        $rows = db_query(
            'SELECT id, title, class_name, subject, due_date, description, file_path
             FROM teacher_homework
             WHERE teacher_id = :teacher_id
             ORDER BY due_date ASC, created_at DESC',
            ['teacher_id' => $teacherId]
        )->fetchAll();
        
        $homework = [];
        foreach ($rows as $row) {
            $homework[] = [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'class_name' => (string) $row['class_name'],
                'subject' => (string) $row['subject'],
                'due_date' => (string) $row['due_date'],
                'description' => (string) $row['description'],
                'has_file' => !empty($row['file_path']),
                'file_path' => (string) ($row['file_path'] ?? ''),
            ];
        }
        
        json_response(true, 'تم جلب الواجبات بنجاح.', [
            'homework' => $homework,
        ]);
    } catch (Throwable $exception) {
        error_log('Public Teacher Homework Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب الواجبات.', [], 500);
    }
}

// Get teacher announcements
if ($action === 'announcements') {
    $teacherId = (int) ($_GET['teacher_id'] ?? 0);
    
    if ($teacherId <= 0) {
        json_response(false, 'معرّف الأستاذ غير صالح.', [], 422);
    }
    
    try {
        ensure_announcements_table();
        
        // Verify teacher is active
        $teacher = db_query(
            'SELECT id FROM teachers WHERE id = :id AND status = "active" LIMIT 1',
            ['id' => $teacherId]
        )->fetch();
        
        if (!$teacher) {
            json_response(false, 'الأستاذ غير موجود أو غير مفعل.', [], 404);
        }
        
        $announcements = db_query(
            "SELECT id, title, content, type, class_name, file_path, created_at 
             FROM announcements 
             WHERE teacher_id = :teacher_id 
             ORDER BY created_at DESC",
            ['teacher_id' => $teacherId]
        )->fetchAll(PDO::FETCH_ASSOC);
        
        json_response(true, 'تم جلب الإعلانات بنجاح.', [
            'announcements' => $announcements,
        ]);
    } catch (Throwable $exception) {
        error_log('Public Teacher Announcements Error: ' . $exception->getMessage());
        json_response(false, 'وقع خطأ أثناء جلب الإعلانات.', [], 500);
    }
}

json_response(false, 'إجراء غير صالح.', [], 400);

