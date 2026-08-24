<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../app/functions.php';
$config = require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/crypto.php';
require __DIR__ . '/../app/helpers.php';


$generatedUrl = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secret = trim($_POST['secret'] ?? '');
    $label = trim($_POST['label'] ?? '');
    $ticket = trim($_POST['ticket'] ?? '');

    $ttlMinutes = (int)($_POST['ttl_minutes'] ?? $config['default_ttl_minutes']);
    $maxViews = (int)($_POST['max_views'] ?? 3);

    if ($secret === '') {
        $error = 'Le mot de passe est obligatoire.';
    } elseif ($ttlMinutes < 1 || $ttlMinutes > 10080) {
        $error = 'La durée doit être comprise entre 1 minute et 7 jours.';
    } elseif ($maxViews < 1 || $maxViews > 20) {
        $error = 'Le nombre d’ouvertures doit être compris entre 1 et 20.';
    } else {
        $token = base64url_encode(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $encrypted = encrypt_secret($secret, $config);

        $expiresAt = (new DateTimeImmutable())
            ->modify('+' . $ttlMinutes . ' minutes')
            ->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            INSERT INTO secret_links (
                token_hash,
                ciphertext,
                nonce,
                label,
                ticket,
                max_views,
                expires_at
            ) VALUES (
                :token_hash,
                :ciphertext,
                :nonce,
                :label,
                :ticket,
                :max_views,
                :expires_at
            )
        ");

        $stmt->execute([
            'token_hash' => $tokenHash,
            'ciphertext' => $encrypted['ciphertext'],
            'nonce' => $encrypted['nonce'],
            'label' => $label !== '' ? $label : null,
            'ticket' => $ticket !== '' ? $ticket : null,
            'max_views' => $maxViews,
            'expires_at' => $expiresAt,
        ]);

        $generatedUrl = rtrim($config['base_url'], '/') . '/view.php?t=' . urlencode($token);

        // On efface la variable côté PHP dès que possible
        $secret = '';
    }
}

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>SecureShare - Création d'un lien sécurisé pour partager vos mots de passe</title>
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
        <div class="header-actions">
            <a href="admin.php" class="admin-btn" target="_blank">Administration</a>
        </div>
    </div>
    
    <h1>Chiffrer un mot de passe</h1>

    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>


    <form method="post" autocomplete="off">

    <!-- <div class="instructions">
            <h3>Informations</h3>
            <ul>
                <li>Ne transmettez le lien qu'au destinataire prévu.</li>
            </ul>
        </div> -->

        <label for="secret">Mot de passe</label>
            <textarea id="secret" name="secret" rows="4" required autofocus></textarea>

        <div class="form-grid">

            <div class="form-group">
                <label for="label">Client (facultatif)</label>
                <input id="label" name="label" type="text" maxlength="255">
            </div>

            <div class="form-group">
                <label for="ticket">Commentaire (facultatif)</label>
                <input id="ticket" name="ticket" type="text" maxlength="100">
            </div>

            <div class="form-group">
                <label for="ttl_minutes">Expiration (minutes)</label>
                <input
                    id="ttl_minutes"
                    name="ttl_minutes"
                    type="number"
                    min="1"
                    max="10080"
                    value="<?= e((string)$config['default_ttl_minutes']) ?>">
            </div>

            <div class="form-group">
                <label for="max_views">Nb. ouvertures</label>
                <input
                    id="max_views"
                    name="max_views"
                    type="number"
                    min="1"
                    max="20"
                    value="3">
            </div>

        </div>

        <button type="submit" class="install-button">Générer le lien</button>
    </form>

    <?php if ($generatedUrl): ?>
        <div class="success">

            <strong>✅ Lien généré</strong>

            <input
                type="text"
                id="secretLink"
                value="<?= e($generatedUrl) ?>"
                readonly
            >

            <button type="button" class="install-button" id="copyBtn">
                📋 Copier le lien
            </button>

        </div>

    <?php endif; ?>
</div>

<script>
const btn = document.getElementById('copyBtn');

if (btn) {

    btn.addEventListener('click', function () {

        const input = document.getElementById('secretLink');

        input.select();
        input.setSelectionRange(0, 99999);

        const success = document.execCommand('copy');

        if (success) {
            btn.innerText = '✅ Copié';
        } else {
            btn.innerText = '❌ Échec';
        }

    });

}
</script>


</body>
</html>