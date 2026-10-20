<?php
/**
 * Version debug de admin_auth.php - Affiche exactement l'erreur
 */
declare(strict_types=1);

// Activer tous les erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_errors.log');

// Capturer tous les outputs
$debug_output = [];
$debug_output[] = "[START] " . date('Y-m-d H:i:s');
$debug_output[] = "Request Method: " . $_SERVER['REQUEST_METHOD'];
$debug_output[] = "POST Data: " . json_encode($_POST);

try {
    $debug_output[] = "[INFO] Chargement config.php...";
    require_once __DIR__ . '/config.php';
    $debug_output[] = "[INFO] config.php chargé";
    
    // Vérifier POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $debug_output[] = "[ERROR] Method not POST";
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée', 'debug' => $debug_output]);
        exit;
    }
    
    $email = $_POST['email'] ?? '';
    $code = $_POST['code'] ?? '';
    $role = $_POST['role'] ?? '';
    
    $debug_output[] = "[INFO] Email: $email, Role: $role";
    
    if (empty($email) || empty($code)) {
        $debug_output[] = "[ERROR] Email ou code vide";
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Email et code requis', 'debug' => $debug_output]);
        exit;
    }
    
    // Vérifier connexion DB
    $debug_output[] = "[INFO] Connexion DB...";
    if (!function_exists('db_connection')) {
        $debug_output[] = "[ERROR] db_connection() non trouvée";
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Erreur configuration DB', 'debug' => $debug_output]);
        exit;
    }
    
    $pdo = db_connection();
    $debug_output[] = "[INFO] Connexion DB OK";
    
    // Vérifier table admins
    $debug_output[] = "[INFO] Vérification table admins...";
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    if (!$stmt->fetch()) {
        $debug_output[] = "[ERROR] Table admins n'existe pas";
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Table admins non trouvée', 'debug' => $debug_output]);
        exit;
    }
    $debug_output[] = "[INFO] Table admins existe";
    
    // Chercher admin
    $debug_output[] = "[INFO] Recherche admin: $email";
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email AND status = 'active'");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        $debug_output[] = "[ERROR] Admin non trouvé: $email";
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Identifiants incorrects', 'debug' => $debug_output]);
        exit;
    }
    
    $debug_output[] = "[INFO] Admin trouvé: " . $admin['full_name'];
    
    // Vérifier mot de passe
    $debug_output[] = "[INFO] Vérification mot de passe...";
    $debug_output[] = "[INFO] Hash stocké: " . substr($admin['code'], 0, 20) . "...";
    
    if (!password_verify($code, $admin['code'])) {
        $debug_output[] = "[ERROR] Mot de passe incorrect";
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Mot de passe incorrect', 'debug' => $debug_output]);
        exit;
    }
    
    $debug_output[] = "[INFO] Mot de passe OK!";
    $debug_output[] = "[SUCCESS] Login réussi!";
    
    // Succès
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'user' => [
            'id' => $admin['id'],
            'full_name' => $admin['full_name'],
            'email' => $admin['email'],
            'role' => 'director'
        ],
        'debug' => $debug_output
    ]);
    exit;
    
} catch (PDOException $e) {
    $debug_output[] = "[ERROR PDO] " . $e->getMessage();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . $e->getMessage(), 'debug' => $debug_output]);
    exit;
} catch (Exception $e) {
    $debug_output[] = "[ERROR] " . $e->getMessage();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage(), 'debug' => $debug_output]);
    exit;
}
