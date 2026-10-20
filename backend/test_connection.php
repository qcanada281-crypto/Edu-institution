<?php
/**
 * Professional Database Connection Test
 * Tests XAMPP MySQL connection on port 3308
 */
declare(strict_types=1);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$result = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'mysql_port' => 3308,  // Port configuré
    'tests' => []
];

try {
    // Test 1: Charger config.php
    $result['tests']['config'] = ['status' => 'testing', 'message' => 'Chargement...'];
    require_once __DIR__ . '/config.php';
    $result['tests']['config'] = ['status' => 'success', 'message' => 'config.php chargé'];
    
    // Test 2: Vérifier constantes DB
    $result['tests']['constants'] = [
        'status' => 'success',
        'message' => 'DB_HOST: ' . DB_HOST . ', DB_NAME: ' . DB_NAME
    ];
    
    // Test 3: Connexion base de données (port 3308)
    $result['tests']['database'] = ['status' => 'testing', 'message' => 'Connexion...'];
    
    if (function_exists('test_db_connection')) {
        // Use the new test function
        $dbOk = test_db_connection();
        if ($dbOk) {
            $result['tests']['database'] = [
                'status' => 'success', 
                'message' => 'Connexion MySQL port 3308 OK'
            ];
        } else {
            $result['tests']['database'] = [
                'status' => 'error', 
                'message' => 'Échec connexion MySQL port 3308'
            ];
            $result['status'] = 'error';
        }
    } elseif (function_exists('db_connection')) {
        // Fallback to direct connection test
        $pdo = db_connection();
        $stmt = $pdo->query('SELECT 1 as test');
        $row = $stmt->fetch();
        
        if ($row['test'] === 1) {
            $result['tests']['database'] = [
                'status' => 'success', 
                'message' => 'Connexion PDO OK (port 3308)'
            ];
        }
    } else {
        $result['tests']['database'] = [
            'status' => 'error', 
            'message' => 'Fonctions db_connection() ou test_db_connection() non trouvées'
        ];
        $result['status'] = 'error';
    }
    
    // Test 4: Tables
    if (function_exists('db_query')) {
        $tables = ['admins', 'users', 'students', 'grades'];
        $existing = [];
        foreach ($tables as $table) {
            $check = db_query("SHOW TABLES LIKE '{$table}'")->fetch();
            if ($check) $existing[] = $table;
        }
        $result['tests']['tables'] = [
            'status' => 'success',
            'message' => count($existing) . '/' . count($tables) . ' tables trouvées: ' . implode(', ', $existing)
        ];
    }
    
    // Test 5: Admin par défaut
    if (function_exists('db_query')) {
        $admin = db_query(
            'SELECT id, email, status FROM admins WHERE email = :email',
            ['email' => 'admin@kawkab-ouloum.ma']
        )->fetch();
        
        if ($admin) {
            $result['tests']['default_admin'] = [
                'status' => 'success',
                'message' => 'Admin trouvé (id: ' . $admin['id'] . ', status: ' . $admin['status'] . ')'
            ];
        } else {
            $result['tests']['default_admin'] = [
                'status' => 'warning',
                'message' => 'Admin par défaut non trouvé - exécutez seed.php'
            ];
        }
    }
    
} catch (PDOException $e) {
    $result['tests']['database'] = [
        'status' => 'error',
        'message' => 'Erreur PDO: ' . $e->getMessage()
    ];
    $result['status'] = 'error';
    $result['hint'] = 'Vérifiez que MySQL est démarré sur le port 3308 dans XAMPP';
} catch (Exception $e) {
    $result['tests']['config'] = [
        'status' => 'error', 
        'message' => 'Erreur: ' . $e->getMessage()
    ];
    $result['status'] = 'error';
}

// Return result
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
