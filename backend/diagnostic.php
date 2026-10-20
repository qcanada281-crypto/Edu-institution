<?php
// Diagnostic file for testing admin connection
header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

echo '<h1>Diagnostic Connexion Admin</h1>';
echo '<pre>';

// Test 1: Basic PHP
echo "PHP fonctionne (version: " . PHP_VERSION . ")\n\n";

// Test 2: Database connection
try {
    require_once __DIR__ . '/config.php';
    echo "Fichier config.php charge\n";
    
    // Check if db_connection exists
    if (function_exists('db_connection')) {
        echo "Fonction db_connection() existe\n";
        
        try {
            $pdo = db_connection();
            echo "Connexion MySQL reussie\n";
            
            // Test query
            $stmt = $pdo->query('SELECT 1 as test');
            $result = $stmt->fetch();
            if ($result['test'] === 1) {
                echo "Requete test OK\n";
            }
        } catch (PDOException $e) {
            echo "ERREUR MySQL: " . $e->getMessage() . "\n";
            echo "Verifiez que MySQL est demarre dans XAMPP\n";
        }
    } else {
        echo "Fonction db_connection() manquante\n";
    }
    
    // Test 3: Check admins table
    echo "\n--- Test Table Admins ---\n";
    if (function_exists('ensure_admins_table')) {
        echo "Fonction ensure_admins_table() existe\n";
        
        try {
            ensure_admins_table();
            echo "Table admins creee/verifiee\n";
            
            // Check if admin exists
            $admin = db_query(
                'SELECT id, email, status FROM admins WHERE email = :email',
                ['email' => 'admin@kawkab-ouloum.ma']
            )->fetch();
            
            if ($admin) {
                echo "Admin par defaut trouve (id: " . $admin['id'] . ", status: " . $admin['status'] . ")\n";
            } else {
                echo "Admin par defaut non trouve - sera cree lors du premier login\n";
            }
        } catch (Exception $e) {
            echo "ERREUR table admins: " . $e->getMessage() . "\n";
        }
    }
    
    // Test 4: Check get_admin_login_profile
    echo "\n--- Test Configuration ---\n";
    if (function_exists('get_admin_login_profile')) {
        echo "Fonction get_admin_login_profile() existe\n";
        
        $director = get_admin_login_profile('director');
        if ($director) {
            echo "Profil Director charge: " . $director['email'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERREUR CRITIQUE: " . $e->getMessage() . "\n";
}

echo '</pre>';
echo '<hr><h2>Instructions</h2>';
echo '<p>1. Demarrez XAMPP (Apache + MySQL)</p>';
echo '<p>2. Ouvrez admin.html et testez le login</p>';
echo '<p>3. Si erreur, verifiez la console F12</p>';
?>
