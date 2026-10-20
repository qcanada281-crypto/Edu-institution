<?php
require_once __DIR__ . '/config.php';

echo "<h2>Debug: Database State</h2>";

// Check students table
echo "<h3>Students Table (approved students):</h3>";
try {
    $students = db_query("SELECT id, student_code, first_name, last_name, registration_status FROM students ORDER BY id DESC LIMIT 5")->fetchAll();
    if (empty($students)) {
        echo "<p>No students found.</p>";
    } else {
        echo "<pre>" . print_r($students, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check registration_requests table
echo "<h3>Registration Requests Table (pending requests):</h3>";
try {
    $requests = db_query("SELECT id, student_code, first_name, last_name, registration_status FROM registration_requests ORDER BY id DESC LIMIT 5")->fetchAll();
    if (empty($requests)) {
        echo "<p>No pending requests found.</p>";
    } else {
        echo "<pre>" . print_r($requests, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check table structure
echo "<h3>Registration Requests Table Structure:</h3>";
try {
    $columns = db_query("SHOW COLUMNS FROM registration_requests")->fetchAll();
    echo "<pre>" . print_r($columns, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Table might not exist: " . $e->getMessage() . "</p>";
}
?>
