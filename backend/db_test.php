<?php
declare(strict_types=1);

// Simple test script to check database connectivity and table existence
require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=UTF-8');

echo '<!DOCTYPE html>';
echo '<html lang="ar" dir="rtl">';
echo '<head>';
echo '<meta charset="UTF-8">';
echo '<title>Database Connection Test</title>';
echo '<style>';
echo 'body { font-family: Arial, sans-serif; padding: 20px; direction: rtl; }';
echo '.success { color: green; }';
echo '.error { color: red; }';
echo '.info { color: blue; }';
echo 'table { border-collapse: collapse; width: 100%; margin-top: 20px; }';
echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }';
echo 'th { background-color: #4CAF50; color: white; }';
echo '</style>';
echo '</head>';
echo '<body>';
echo '<h1>Database Connection Test - مؤسسة Kawkab Al Ouloum</h1>';

try {
    $pdo = db_connection();
    echo '<p class="success">✓ Database connection successful!</p>';
    
    // Check if tables exist
    $tables = ['students', 'registration_requests', 'admins', 'attendance', 'grades', 'messages', 'lessons', 'users'];
    
    echo '<table>';
    echo '<tr><th>Table Name</th><th>Status</th></tr>';
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
            $count = $stmt->fetchColumn();
            echo '<tr><td>' . htmlspecialchars($table) . '</td><td class="success">✓ Exists (' . $count . ' rows)</td></tr>';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'doesn\'t exist') !== false) {
                echo '<tr><td>' . htmlspecialchars($table) . '</td><td class="error">✗ Table does not exist</td></tr>';
            } else {
                echo '<tr><td>' . htmlspecialchars($table) . '</td><td class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
            }
        }
    }
    
    echo '</table>';
    
    // Show connection info
    echo '<h2>Connection Details</h2>';
    echo '<ul>';
    echo '<li><strong>Host:</strong> ' . DB_HOST . '</li>';
    echo '<li><strong>Database:</strong> ' . DB_NAME . '</li>';
    echo '<li><strong>User:</strong> ' . DB_USER . '</li>';
    echo '</ul>';
    
    // Check registration_requests table structure
    echo '<h2>Registration Requests Table Structure</h2>';
    try {
        $stmt = $pdo->query("DESCRIBE registration_requests");
        $columns = $stmt->fetchAll();
        echo '<table>';
        echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
        foreach ($columns as $col) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } catch (PDOException $e) {
        echo '<p class="error">Could not describe registration_requests: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    
} catch (Throwable $e) {
    echo '<p class="error">✗ Database connection failed!</p>';
    echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<h2>Troubleshooting Steps:</h2>';
    echo '<ol>';
    echo '<li>Make sure WAMP server is running (green icon)</li>';
    echo '<li>Open phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></li>';
    echo '<li>Import the database file: <code>backend/bg/database.sql</code></li>';
    echo '<li>Refresh this page</li>';
    echo '</ol>';
}

echo '</body>';
echo '</html>';
