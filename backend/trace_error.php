<?php
/**
 * Trace Error - Affiche exactement la réponse brute du serveur
 */
header('Content-Type: text/html; charset=UTF-8');

// Activer affichage erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h1>🔍 Trace Erreur - Réponse Serveur</h1>';

// Test 1: Vérifier PHP
echo '<h2>1. Test PHP de base</h2>';
echo '<div style="color:green">✅ PHP fonctionne (version: ' . PHP_VERSION . ')</div>';

// Test 2: Vérifier si config charge
echo '<h2>2. Chargement config.php</h2>';
try {
    ob_start();
    require_once __DIR__ . '/config.php';
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo '<div style="color:red"><strong>⚠️ Output avant config:</strong></div>';
        echo '<pre style="background:#ffebee;padding:10px;">' . htmlspecialchars($output) . '</pre>';
    } else {
        echo '<div style="color:green">✅ Config chargé sans output</div>';
    }
} catch (Exception $e) {
    echo '<div style="color:red">❌ Erreur config: ' . $e->getMessage() . '</div>';
}

// Test 3: Simuler requête admin_auth
echo '<h2>3. Test admin_auth.php (simulation)</h2>';
echo '<form method="POST" action="admin_auth.php" target="_blank">';
echo '<input type="hidden" name="email" value="admin@kawkab-ouloum.ma">';
echo '<input type="hidden" name="code" value="Admin@2026">';
echo '<input type="hidden" name="role" value="director">';
echo '<button type="submit" style="padding:10px 20px;background:#6d4c41;color:white;border:none;border-radius:4px;cursor:pointer;">Tester admin_auth.php (voir réponse brute)</button>';
echo '</form>';

// Test 4: Tester avec file_get_contents
echo '<h2>4. Test avec file_get_contents</h2>';
$url = 'http://' . $_SERVER['HTTP_HOST'] . '/edu-institution/backend/admin_auth.php';
$postData = http_build_query([
    'email' => 'admin@kawkab-ouloum.ma',
    'code' => 'Admin@2026',
    'role' => 'director'
]);

$opts = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-type: application/x-www-form-urlencoded',
        'content' => $postData
    ]
];

$context = stream_context_create($opts);
$result = @file_get_contents($url, false, $context);

if ($result === false) {
    echo '<div style="color:red">❌ Erreur file_get_contents: ' . error_get_last()['message'] . '</div>';
} else {
    echo '<div style="color:green">✅ Réponse reçue (' . strlen($result) . ' caractères)</div>';
    echo '<h3>Réponse brute:</h3>';
    echo '<pre style="background:#f5f5f5;padding:15px;border:1px solid #ddd;overflow:auto;max-height:400px;">';
    echo htmlspecialchars(substr($result, 0, 2000));
    if (strlen($result) > 2000) {
        echo '\n... (truncated)';
    }
    echo '</pre>';
    
    // Vérifier si c'est du JSON
    $json = json_decode($result);
    if ($json !== null) {
        echo '<div style="color:green;font-size:18px"><strong>✅ JSON VALIDE!</strong></div>';
    } else {
        echo '<div style="color:red;font-size:18px"><strong>❌ JSON INVALIDE!</strong></div>';
        echo '<div>Erreur JSON: ' . json_last_error_msg() . '</div>';
        
        // Chercher l'erreur
        if (strpos($result, '<br />') !== false || strpos($result, '<b>') !== false) {
            echo '<div style="color:orange;margin-top:10px;">⚠️ HTML/PHP Error détecté dans la réponse!</div>';
        }
    }
}

echo '<hr><h2>Liens rapides:</h2>';
echo '<ul>';
echo '<li><a href="debug_login.php" target="_blank">🔍 Debug Login (détaillé)</a></li>';
echo '<li><a href="seed.php" target="_blank">🌱 Créer données test</a></li>';
echo '<li><a href="../diagnostic.html" target="_blank">🧪 Diagnostic complet</a></li>';
echo '</ul>';
?>
