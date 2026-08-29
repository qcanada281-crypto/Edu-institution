<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

ensure_password_reset_tokens_table();
ensure_post_request();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim((string) ($input['email'] ?? ''));

if (empty($email)) {
    json_response(false, 'Veuillez fournir une adresse email.', [], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'Adresse email non valide.', [], 422);
}

// Check if email exists in admins or teachers table
$isAdmin = false;
$isTeacher = false;
$userId = null;
$fullName = '';

try {
    // Check admins table
    $admin = db_query(
        'SELECT id, full_name, email, status
         FROM admins
         WHERE email = :email
         LIMIT 1',
        ['email' => $email]
    )->fetch();

    if ($admin && $admin['status'] === 'active') {
        $isAdmin = true;
        $userId = (int) $admin['id'];
        $fullName = (string) $admin['full_name'];
    } else {
        // Check teachers table
        $teacher = db_query(
            'SELECT id, full_name, email, status
             FROM teachers
             WHERE email = :email
             LIMIT 1',
            ['email' => $email]
        )->fetch();

        if ($teacher && $teacher['status'] === 'active') {
            $isTeacher = true;
            $userId = (int) $teacher['id'];
            $fullName = (string) $teacher['full_name'];
        }
    }

    if (!$userId) {
        // Don''t reveal that email doesn''t exist - just say email was sent
        // This prevents email enumeration attacks
        json_response(true, 'Si ladresse email existe, un lien de réinitialisation a été envoyé.');
    }

    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
    $tokenHash = password_hash($token, PASSWORD_DEFAULT);

    // Store or update reset token
    db_query(
        'REPLACE INTO password_reset_tokens (email, user_id, user_type, token_hash, expires_at, created_at)
         VALUES (:email, :user_id, :user_type, :token_hash, :expires_at, NOW())
         ON DUPLICATE KEY UPDATE
             token_hash = :token_hash,
             expires_at = :expires_at,
             created_at = NOW(),
             is_used = 0',
        [
            'email' => $email,
            'user_id' => $userId,
            'user_type' => $isAdmin ? 'admin' : 'teacher',
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]
    );

    // Build reset URL
    $resetUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'kawkab-ouloum.ma') . '/edu-institution/reset_password_form.html?token=' . $token . '&email=' . urlencode($email);

    // Prepare email
    $subject = 'KAWKAB AL OULOUM - Réinitialisation du mot de passe';
    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1a5f7a 0%, #159895 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f4f4f4; padding: 30px; border-radius: 0 0 10px 10px; }
            .btn { display: inline-block; background: #1a5f7a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; color: #666; font-size: 0.9em; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>KAWKAB AL OULOUM</h1>
                <p>Réinitialisation du mot de passe</p>
            </div>
            <div class="content">
                <p>Bonjour ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ',</p>
                <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte KAWKAB AL OULOUM.</p>
                <p>Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe :</p>
                <p style="text-align: center;">
                    <a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '" class="btn">Réinitialiser le mot de passe</a>
                </p>
                <p>Ce lien expirera dans 1 heure.</p>
                <p>Si vous navez pas demandé cette réinitialisation, vous pouvez ignorer cet email.</p>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' KAWKAB AL OULOUM - Tous droits réservés</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ';

    // Note: In a real application, you would use PHPMailer or similar
    // For this demo, we''ll use the built-in mail() function
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: noreply@kawkab-ouloum.ma',
        'Reply-To: noreply@kawkab-ouloum.ma',
    ];

    // Send email (disabled in demo environment if mail server not configured)
    @mail($email, $subject, $message, implode("\r\n", $headers));

    // Log the request
    error_log('Password reset requested for email: ' . $email . ' (User ID: ' . $userId . ', Type: ' . ($isAdmin ? 'admin' : 'teacher') . ')');

    json_response(true, 'Si ladresse email existe, un lien de réinitialisation a été envoyé.');

} catch (Throwable $exception) {
    error_log('Forgot Password Error: ' . $exception->getMessage());
    json_response(false, 'Une erreur est survenue. Veuillez réessayer plus tard.', [], 500);
}
