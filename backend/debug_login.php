<?php
/**
 * Debug Login - Affiche exactement ce qui se passe pendant le login
 */
header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

echo '<h1>🔍 Debug Login Admin</h1>';

try {
    // Test 1: Charger config
    echo '<h2>Test 1: Chargement config.php</h2>';
    require_once __DIR__ . '/config.php';
    echo '<div style="color:green">✅ config.php chargé</div>';
    echo '<div>DB_HOST: ' . DB_HOST . '</div>';
    echo '<div>DB_NAME: ' . DB_NAME . '</div>';
    
    // Test 2: Connexion MySQL
    echo '<h2>Test 2: Connexion MySQL</h2>';
    if (function_exists('db_connection')) {
        $pdo = db_connection();
        echo '<div style="color:green">✅ Connexion MySQL réussie</div>';
        
        // Test query
        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();
        if ($result['test'] === 1) {
            echo '<div style="color:green">✅ Requête test OK</div>';
        }
    } else {
        echo '<div style="color:red">❌ Fonction db_connection() non trouvée</div>';
    }
    
    // Test 3: Vérifier table admins
    echo '<h2>Test 3: Table Admins</h2>';
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->fetch()) {
        echo '<div style="color:green">✅ Table admins existe</div>';
        
        // Vérifier contenu
        $stmt = $pdo->query("SELECT id, email, status FROM admins");
        $admins = $stmt->fetchAll();
        echo '<div>Nombre d\'admins: ' . count($admins) . '</div>';
        echo '<pre>' . print_r($admins, true) . '</pre>';
        
        // Chercher admin spécifique
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email");
        $stmt->execute(['email' => 'admin@kawkab-ouloum.ma']);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo '<div style="color:green">✅ Admin admin@kawkab-ouloum.ma trouvé!</div>';
            echo '<div>ID: ' . $admin['id'] . '</div>';
            echo '<div>Status: ' . $admin['status'] . '</div>';
            echo '<div>Full name: ' . ($admin['full_name'] ?? 'N/A') . '</div>';
            
            // Vérifier mot de passe
            echo '<h3>Test du mot de passe</h3>';
            $testCode = 'Admin@2026';
            $storedHash = $admin['code'];
            
            if (password_verify($testCode, $storedHash)) {
                echo '<div style="color:green;font-size:18px"><strong>✅ Mot de passe correct!</strong></div>';
            } else {
                echo '<div style="color:red">❌ Mot de passe incorrect</div>';
                echo '<div>Hash stocké: ' . substr($storedHash, 0, 20) . '...</div>';
            }
        } else {
            echo '<div style="color:red;font-size:18px"><strong>❌ Admin admin@kawkab-ouloum.ma NON TROUVÉ!</strong></div>';
            echo '<div>Vous devez exécuter <a href="seed.php">seed.php</a> pour créer l\'admin.</div>';
        }
    } else {
        echo '<div style="color:red">❌ Table admins n\'existe pas!</div>';
        echo '<div>Exécutez le fichier SQL pour créer les tables.</div>';
    }
    
} catch (PDOException $e) {
    echo '<div style="color:red;font-size:18px"><strong>❌ ERREUR MySQL:</strong></div>';
    echo '<div>' . $e->getMessage() . '</div>';
    echo '<br><div><strong>Solutions:</strong></div>';
    echo '<ul>';
    echo '<li>Vérifiez que MySQL est démarré dans XAMPP</li>';
    echo '<li>Vérifiez le port (3306 pour XAMPP standard)</li>';
    echo '<li>Vérifiez le fichier config.php</li>';
    echo '</ul>';
} catch (Exception $e) {
    echo '<div style="color:red"><strong>❌ ERREUR:</strong> ' . $e->getMessage() . '</div>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '<hr><h2>Actions recommandées:</h2>';
echo '<ul>';
echo '<li><a href="seed.php">🌱 Créer Admin et données de test</a></li>';
echo '<li><a href="setup_check.php">🔍 Vérifier setup complet</a></li>';
echo '<li><a href="../diagnostic.html">🧪 Page de diagnostic</a></li>';
echo '</ul>';
?>
