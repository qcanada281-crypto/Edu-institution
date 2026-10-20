<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_password_reset_tokens_table();
ensure_post_request();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$token = trim((string) ($input['token'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$newPassword = trim((string) ($input['new_password'] ?? ''));

if (empty($token) || empty($email) || empty($newPassword)) {
    json_response(false, 'Tous les champs sont requis.', [], 422);
}

if (strlen($newPassword) < 8) {
    json_response(false, 'Le mot de passe doit contenir au moins 8 caractères.', [], 422);
}

try {
    $tokenRecord = db_query(
        'SELECT id, user_id, user_type, token_hash, expires_at, is_used
         FROM password_reset_tokens
         WHERE email = :email
         ORDER BY created_at DESC
         LIMIT 1',
        ['email' => $email]
    )->fetch();

    if (!$tokenRecord) {
        json_response(false, 'Aucune requête de réinitialisation trouvée pour cet email.', [], 404);
    }

    if ((int) ($tokenRecord['is_used'] ?? 0) === 1) {
        json_response(false, 'Ce lien a déjà été utilisé.', [], 400);
    }

    $expiresAt = strtotime((string) $tokenRecord['expires_at']);
    if (time() > $expiresAt) {
        json_response(false, 'Ce lien a expiré. Veuillez demander une nouvelle réinitialisation.', [], 400);
    }

    $tokenHash = (string) $tokenRecord['token_hash'];
    if (!password_verify($token, $tokenHash)) {
        json_response(false, 'Lien invalide ou expiré.', [], 400);
    }

    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    if ($newPasswordHash === false) {
        json_response(false, 'Erreur lors du hachage du mot de passe.', [], 500);
    }

    $userId = (int) $tokenRecord['user_id'];
    $userType = (string) $tokenRecord['user_type'];

    if ($userType === 'admin') {
        db_query(
            'UPDATE admins SET code = :code, updated_at = NOW() WHERE id = :id',
            ['code' => $newPasswordHash, 'id' => $userId]
        );
    } elseif ($userType === 'teacher') {
        db_query(
            'UPDATE teachers SET code = :code, updated_at = NOW() WHERE id = :id',
            ['code' => $newPasswordHash, 'id' => $userId]
        );
    } else {
        json_response(false, 'Type d\'utilisateur non supporté.', [], 400);
    }

    db_query(
        'UPDATE password_reset_tokens SET is_used = 1, updated_at = NOW() WHERE id = :id',
        ['id' => (int) $tokenRecord['id']]
    );

    db_query(
        'UPDATE password_reset_tokens SET is_used = 1 WHERE email = :email AND id <> :current_id',
        ['email' => $email, 'current_id' => (int) $tokenRecord['id']]
    );

    error_log("Password reset successful for $userType ID $userId (email: $email)");

    json_response(true, 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.');

} catch (Throwable $exception) {
    error_log('Reset Password Error: ' . $exception->getMessage());
    json_response(false, 'Une erreur est survenue. Veuillez réessayer plus tard.', [], 500);
}
