<?php
require_once __DIR__ . '/config.php';

try {
    db_query('ALTER TABLE lessons ADD COLUMN teacher_id INT UNSIGNED NULL');
    echo "Column added successfully.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
