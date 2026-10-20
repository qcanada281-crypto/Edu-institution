<?php
/**
 * Comprehensive Database Connection Test
 * Tests MySQL connection on port 3308 with detailed error reporting
 */

declare(strict_types=1);

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/db_errors.log');

// Set headers for JSON response
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$testResults = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'mysql_port' => 3308,
    'tests' => [],
    'overall_status' => 'unknown'
];

try {
    // Test 1: Load configuration
    $testResults['tests']['config'] = [
        'status' => 'testing',
        'message' => 'Loading config.php...'
    ];
    
    require_once __DIR__ . '/config.php';
    
    $testResults['tests']['config'] = [
        'status' => 'success',
        'message' => 'config.php loaded successfully',
        'db_host' => DB_HOST,
        'db_name' => DB_NAME,
        'db_user' => DB_USER,
        'db_pass' => DB_PASS ? '[SET]' : '[EMPTY]'
    ];
    
    // Test 2: Parse host and port
    $testResults['tests']['port_parsing'] = [
        'status' => 'testing',
        'message' => 'Parsing host and port...'
    ];
    
    $hostParts = explode(':', DB_HOST);
    $actualHost = $hostParts[0] ?? 'localhost';
    $actualPort = $hostParts[1] ?? 3306;
    
    $testResults['tests']['port_parsing'] = [
        'status' => 'success',
        'message' => 'Host and port parsed',
        'host' => $actualHost,
        'port' => $actualPort,
        'expected_port' => 3308,
        'port_correct' => ($actualPort == 3308)
    ];
    
    // Test 3: Direct PDO connection test
    $testResults['tests']['pdo_connection'] = [
        'status' => 'testing',
        'message' => 'Testing PDO connection...'
    ];
    
    $dsn = "mysql:host={$actualHost};port={$actualPort};dbname=" . DB_NAME . ";charset=utf8mb4";
    
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        $testResults['tests']['pdo_connection'] = [
            'status' => 'success',
            'message' => 'PDO connection successful',
            'dsn' => $dsn
        ];
        
        // Test 4: Database query test
        $testResults['tests']['query_test'] = [
            'status' => 'testing',
            'message' => 'Testing basic query...'
        ];
        
        $stmt = $pdo->query('SELECT VERSION() as version, CONNECTION_ID() as connection_id');
        $result = $stmt->fetch();
        
        $testResults['tests']['query_test'] = [
            'status' => 'success',
            'message' => 'Query executed successfully',
            'mysql_version' => $result['version'],
            'connection_id' => $result['connection_id']
        ];
        
        // Test 5: Tables check
        $testResults['tests']['tables_check'] = [
            'status' => 'testing',
            'message' => 'Checking required tables...'
        ];
        
        $requiredTables = ['admins', 'users', 'students', 'grades', 'attendance'];
        $existingTables = [];
        
        $stmt = $pdo->query('SHOW TABLES');
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $existingTables[] = $row[0];
        }
        
        $missingTables = array_diff($requiredTables, $existingTables);
        
        if (empty($missingTables)) {
            $testResults['tests']['tables_check'] = [
                'status' => 'success',
                'message' => 'All required tables exist',
                'total_tables' => count($existingTables),
                'required_found' => count($requiredTables)
            ];
        } else {
            $testResults['tests']['tables_check'] = [
                'status' => 'warning',
                'message' => 'Some tables are missing',
                'missing_tables' => array_values($missingTables),
                'existing_tables' => $existingTables
            ];
        }
        
        // Test 6: Admin account check
        $testResults['tests']['admin_check'] = [
            'status' => 'testing',
            'message' => 'Checking admin account...'
        ];
        
        if (in_array('admins', $existingTables)) {
            $stmt = $pdo->prepare('SELECT id, email, status, full_name FROM admins WHERE email = :email');
            $stmt->execute(['email' => 'admin@kawkab-ouloum.ma']);
            $admin = $stmt->fetch();
            
            if ($admin) {
                $testResults['tests']['admin_check'] = [
                    'status' => 'success',
                    'message' => 'Default admin account found',
                    'admin_id' => $admin['id'],
                    'admin_email' => $admin['email'],
                    'admin_name' => $admin['full_name'],
                    'admin_status' => $admin['status']
                ];
            } else {
                $testResults['tests']['admin_check'] = [
                    'status' => 'warning',
                    'message' => 'Default admin account not found',
                    'solution' => 'Run seed.php to create admin account'
                ];
            }
        } else {
            $testResults['tests']['admin_check'] = [
                'status' => 'error',
                'message' => 'Admins table does not exist',
                'solution' => 'Import database.sql first'
            ];
        }
        
        // Set overall status
        $allSuccess = true;
        foreach ($testResults['tests'] as $test) {
            if ($test['status'] === 'error') {
                $allSuccess = false;
                break;
            }
        }
        
        $testResults['overall_status'] = $allSuccess ? 'success' : 'warning';
        
    } catch (PDOException $e) {
        $testResults['tests']['pdo_connection'] = [
            'status' => 'error',
            'message' => 'PDO connection failed',
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage(),
            'dsn' => $dsn
        ];
        
        $testResults['overall_status'] = 'error';
        
        // Provide specific troubleshooting
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            $testResults['troubleshooting'] = [
                'issue' => 'Database credentials incorrect',
                'solution' => 'Check DB_USER and DB_PASS in config.php'
            ];
        } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
            $testResults['troubleshooting'] = [
                'issue' => 'Database does not exist',
                'solution' => 'Create database in phpMyAdmin: edu_institution'
            ];
        } elseif (strpos($e->getMessage(), 'Connection refused') !== false || strpos($e->getMessage(), 'No connection') !== false) {
            $testResults['troubleshooting'] = [
                'issue' => 'MySQL not running on port 3308',
                'solution' => 'Start MySQL in XAMPP Control Panel and verify port 3308'
            ];
        }
    }
    
} catch (Exception $e) {
    $testResults['tests']['config'] = [
        'status' => 'error',
        'message' => 'Failed to load config.php',
        'error' => $e->getMessage()
    ];
    $testResults['overall_status'] = 'error';
}

// Return JSON response
echo json_encode($testResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
