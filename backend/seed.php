<?php
require 'config.php';

// Ensure all tables exist
ensure_admins_table();
ensure_users_table();
ensure_students_table();
ensure_teachers_table();

// Create default admin accounts
$admins = [
    ['email' => 'admin@kawkab-ouloum.ma', 'code' => 'Admin@2026', 'full_name' => 'المدير', 'role' => 'director'],
    ['email' => 'secretary@kawkab-ouloum.ma', 'code' => 'Secretary@2026', 'full_name' => 'السكرتارية', 'role' => 'secretary'],
];

echo "<h2>Création des comptes administrateurs...</h2>";

foreach ($admins as $admin) {
    $stmt = db_query("SELECT id FROM admins WHERE email = :email", ['email' => $admin['email']]);
    if (!$stmt->fetch()) {
        db_query(
            "INSERT INTO admins (full_name, email, code, status) VALUES (:full_name, :email, :code, 'active')",
            [
                'full_name' => $admin['full_name'],
                'email' => $admin['email'],
                'code' => password_hash($admin['code'], PASSWORD_DEFAULT)
            ]
        );
        echo "<p style='color:green'>✅ Admin créé: {$admin['email']} / {$admin['code']}</p>";
    } else {
        echo "<p>ℹ️ Admin existe déjà: {$admin['email']}</p>";
    }
}

// Create test teacher
$teacherEmail = 'teacher@kawkab-ouloum.ma';
$stmt = db_query("SELECT id FROM teachers WHERE email = :email", ['email' => $teacherEmail]);
if (!$stmt->fetch()) {
    db_query(
        "INSERT INTO teachers (full_name, email, code, phone, subject_name, status) 
         VALUES (:full_name, :email, :code, :phone, :subject_name, 'active')",
        [
            'full_name' => 'أستاذ تجريبي',
            'email' => $teacherEmail,
            'code' => password_hash('Teacher@2026', PASSWORD_DEFAULT),
            'phone' => '0600000000',
            'subject_name' => 'الرياضيات'
        ]
    );
    echo "<p style='color:green'>✅ Enseignant créé: {$teacherEmail} / Teacher@2026</p>";
} else {
    echo "<p>ℹ️ Enseignant existe déjà: {$teacherEmail}</p>";
}

echo "<h2>Création des données étudiant...</h2>";

$stmt = db_query("SELECT id FROM students WHERE student_code = 'STU2026001'");
if (!$stmt->fetch()) {
    db_query("INSERT INTO students (student_code, first_name, last_name, birth_date, gender, class_name, level, guardian_name, guardian_phone, registration_status) VALUES ('STU2026001', 'ياسين', 'الناجي', '2008-04-16', 'male', '3ème Année Collège - A', 'Collège', 'عبد الرحمن الناجي', '0600000000', 'registered')");
    
    // Add some random grades to ensure the dashboard works fully.
    $studentId = db_connection()->lastInsertId();
    db_query("CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_name VARCHAR(100),
        continuous_score DECIMAL(5,2),
        exam_score DECIMAL(5,2),
        coefficient DECIMAL(4,2),
        semester VARCHAR(10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    db_query("INSERT INTO grades (student_id, subject_name, continuous_score, exam_score, coefficient, semester) VALUES 
        ($studentId, 'الرياضيات', 15.5, 16.0, 5, 'S1'),
        ($studentId, 'اللغة العربية', 14.0, 15.0, 4, 'S1'),
        ($studentId, 'الفيزياء', 16.0, 14.5, 4, 'S1'),
        ($studentId, 'علوم الحياة والأرض', 14.5, 13.0, 4, 'S1');
    ");
    
    db_query("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        absence_date DATE,
        session_label VARCHAR(50),
        justified TINYINT(1),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    db_query("INSERT INTO attendance (student_id, absence_date, session_label, justified, notes) VALUES 
        ($studentId, '2026-03-10', 'الصباح', 1, 'شهادة طبية'),
        ($studentId, '2026-04-05', 'المساء', 0, 'تأخر')
    ");

    echo "تم إنشاء الطالب التجريبي ونقاطه بنجاح!";
} else {
    echo "الطالب التجريبي موجود مسبقاً.";
}
