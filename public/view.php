<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/crypto.php';
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/functions.php';

$token = $_GET['t'] ?? '';
$secret = null;
$error = null;

if ($token === '' || strlen($token) < 30) {
    $error = 'Lien invalide.';
} else {
    $tokenHash = hash('sha256', $token);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT *
            FROM secret_links
            WHERE token_hash = :token_hash
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute(['token_hash' => $tokenHash]);
        $link = $stmt->fetch();

        if (!$link) {
            $error = 'Lien introuvable ou déjà expiré.';
            $pdo->rollBack();
        } elseif ($link['revoked_at'] !== null) {
            $error = 'Ce lien a été révoqué.';
            $pdo->rollBack();
        } elseif (new DateTimeImmutable($link['expires_at']) < new DateTimeImmutable()) {
            $error = 'Ce lien a expiré.';
            $pdo->rollBack();
        } elseif ((int)$link['views'] >= (int)$link['max_views']) {
            $error = 'Ce lien a déjà été utilisé.';
            $pdo->rollBack();
        } else {
            $secret = decrypt_secret($link['ciphertext'], $link['nonce'], $config);
            //$secret = decrypt_secret($link['ciphertext'], base64_decode($link['nonce']), $config);
            
            $newViews = (int)$link['views'] + 1;
            $shouldRevoke = $newViews >= (int)$link['max_views'];

            $update = $pdo->prepare("
                UPDATE secret_links
                SET views = :views,
                    revoked_at = CASE WHEN :should_revoke = 1 THEN NOW() ELSE revoked_at END
                WHERE id = :id
            ");
            $update->execute([
                'views' => $newViews,
                'should_revoke' => $shouldRevoke ? 1 : 0,
                'id' => $link['id'],
            ]);

            $log = $pdo->prepare("
                INSERT INTO secret_opens (
                    link_id,
                    ip_hash,
                    user_agent
                ) VALUES (
                    :link_id,
                    :ip_hash,
                    :user_agent
                )
            ");
            $log->execute([
                'link_id' => $link['id'],
                'ip_hash' => client_ip_hash($config),
                'user_agent' => user_agent_short(),
            ]);

            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = 'Erreur lors de l’ouverture du lien.';
    }
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>SecureShare - Récupération de votre mot de passe sécurisé</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" hrefpng" sizes="16xcon" href="assets/favicon.ico">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="card">
    <div class="header-bar">
        <div class="logo-container">
            <img src="<?= e(getLogoUrl()) ?>" alt="Branding" class="logo">
            <div class="logo-separator"></div>
            <img src="assets/logo-secureshare.png" alt="Logo" class="logo">
        </div>
    </div>

    <h1>Votre mot de passe</h1>

    <div class="instructions">
        <h3>Informations</h3>

        <ul>
            <li>Ce service est destiné au partage sécurisé de mots de passe et informations sensibles.</li>
            <li>Le mot de passe sera définitivement supprimée de la base de données.</li>
            <li>Le lien expire automatiquement après trois ouvertures ou 24 heures.</li>
        </ul>
    </div>

    <?php if ($error): ?>
        <p class="error"><?= e($error) ?></p>
        
    <?php elseif ($secret !== null): ?>
        <div class="secret"><?= e($secret) ?></div>
    <?php endif; ?>
</div>
</body>
</html>