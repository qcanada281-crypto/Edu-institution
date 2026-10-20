<?php
/**
 * Setup Check - Vérification complète du projet avec XAMPP
 */

header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

$tests = [];
$allOk = true;

// Test 1: PHP fonctionne
$tests['php'] = [
    'status' => 'ok',
    'message' => 'PHP version: ' . PHP_VERSION
];

// Test 2: Extensions requises
$required = ['pdo', 'pdo_mysql', 'json', 'session'];
$missing = [];
foreach ($required as $ext) {
    if (!extension_loaded($ext)) {
        $missing[] = $ext;
    }
}

if (empty($missing)) {
    $tests['extensions'] = [
        'status' => 'ok',
        'message' => 'Toutes les extensions sont chargées'
    ];
} else {
    $tests['extensions'] = [
        'status' => 'error',
        'message' => 'Extensions manquantes: ' . implode(', ', $missing)
    ];
    $allOk = false;
}

// Test 3: Configuration base de données
try {
    require_once __DIR__ . '/config.php';
    $tests['config'] = [
        'status' => 'ok',
        'message' => 'Fichier config.php chargé'
    ];
} catch (Exception $e) {
    $tests['config'] = [
        'status' => 'error',
        'message' => 'Erreur config: ' . $e->getMessage()
    ];
    $allOk = false;
}

// Test 4: Connexion MySQL
if (function_exists('db_connection')) {
    try {
        $pdo = db_connection();
        $tests['mysql'] = [
            'status' => 'ok',
            'message' => 'Connexion MySQL réussie'
        ];
        
        // Test 5: Vérifier les tables
        $requiredTables = [
            'admins', 'users', 'students', 'grades', 'attendance', 
            'messages', 'lessons', 'gallery_photos', 'blog_posts',
            'registration_requests'
        ];
        
        $existingTables = [];
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $existingTables[] = $row[0];
        }
        
        $missingTables = array_diff($requiredTables, $existingTables);
        
        if (empty($missingTables)) {
            $tests['tables'] = [
                'status' => 'ok',
                'message' => 'Toutes les tables (' . count($requiredTables) . ') existent'
            ];
        } else {
            $tests['tables'] = [
                'status' => 'warning',
                'message' => 'Tables manquantes: ' . implode(', ', $missingTables)
            ];
        }
        
        // Test 6: Vérifier admin par défaut
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins WHERE email = 'admin@kawkab-ouloum.ma'");
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount > 0) {
            $tests['admin'] = [
                'status' => 'ok',
                'message' => 'Admin par défaut trouvé'
            ];
        } else {
            $tests['admin'] = [
                'status' => 'warning',
                'message' => 'Admin par défaut non trouvé - doit être créé'
            ];
        }
        
    } catch (PDOException $e) {
        $tests['mysql'] = [
            'status' => 'error',
            'message' => 'Erreur MySQL: ' . $e->getMessage()
        ];
        $tests['tables'] = [
            'status' => 'error',
            'message' => 'Impossible de vérifier les tables'
        ];
        $allOk = false;
    }
} else {
    $tests['mysql'] = [
        'status' => 'error',
        'message' => 'Fonction db_connection() non trouvée'
    ];
    $allOk = false;
}

// Afficher résultats
echo '<!DOCTYPE html>';
echo '<html lang="ar" dir="rtl">';
echo '<head><meta charset="UTF-8"><title>Setup Check</title>';
echo '<style>';
echo 'body { font-family: Arial; padding: 20px; max-width: 800px; margin: 0 auto; }';
echo '.ok { background: #e8f5e9; color: #2e7d32; padding: 10px; margin: 5px 0; border-radius: 4px; }';
echo '.error { background: #ffebee; color: #c62828; padding: 10px; margin: 5px 0; border-radius: 4px; }';
echo '.warning { background: #fff3e0; color: #ef6c00; padding: 10px; margin: 5px 0; border-radius: 4px; }';
echo 'h1 { color: #333; }';
echo 'button { padding: 10px 20px; background: #6d4c41; color: white; border: none; border-radius: 4px; cursor: pointer; }';
echo '</style></head><body>';

echo '<h1>🔍 Vérification Setup</h1>';

if ($allOk) {
    echo '<div class="ok"><strong>✅ Tout est configuré correctement!</strong></div>';
} else {
    echo '<div class="error"><strong>❌ Problèmes détectés - voir détails ci-dessous</strong></div>';
}

foreach ($tests as $name => $test) {
    $class = $test['status'];
    $icon = $test['status'] === 'ok' ? '✅' : ($test['status'] === 'warning' ? '⚠️' : '❌');
    echo '<div class="' . $class . '">';
    echo '<strong>' . $icon . ' ' . ucfirst($name) . ':</strong> ' . htmlspecialchars($test['message']);
    echo '</div>';
}

// Boutons d'action
echo '<h2>Actions disponibles:</h2>';
echo '<div style="margin: 20px 0;">';
echo '<a href="seed.php"><button>🌱 Créer données de test (Admin, Users...)</button></a>';
echo '</div>';

echo '<div style="margin: 20px 0;">';
echo '<a href="../setup_test.html"><button>🧪 Page de test complète</button></a>';
echo '</div>';

echo '<h2>🔐 Identifiants par défaut après création:</h2>';
echo '<div style="background: #e8f5e9; padding: 15px; border-radius: 4px;">';
echo '<strong>Directeur:</strong> admin@kawkab-ouloum.ma / Admin@2026<br>';
echo '<strong>Secrétaire:</strong> secretary@kawkab-ouloum.ma / Secretary@2026<br>';
echo '</div>';

echo '</body></html>';
?>
