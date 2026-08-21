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

    <style>
        body {
            font-family: system-ui, Arial, sans-serif;
            max-width: 720px;
            margin: 40px auto;
            padding: 0 16px;
            background: #f7f7f7;
        }

        .card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 24px;
        }

        label {
            display: block;
            margin-top: 14px;
            font-weight: 600;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #bbb;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            margin-top: 18px;
            padding: 10px 16px;
            border: 0;
            border-radius: 8px;
            background: #111827;
            color: #fff;
            cursor: pointer;
        }

        .error {
            color: #b91c1c;
            margin-bottom: 12px;
        }

        .success {
            background: #ecfdf5;
            border: 1px solid #10b981;
            padding: 14px;
            border-radius: 8px;
            margin-top: 20px;
            word-break: break-all;
        }


        .instructions {
            background: #eff6ff;
            border: 1px solid #93c5fd;
            border-left: 5px solid #2563eb;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #1e3a8a;
        }

        .instructions h3 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .instructions ul {
            margin: 0;
            padding-left: 18px;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-container img {
            max-width: 250px;
            max-height: 80px;
            width: auto;
            height: auto;
        }

        .install-button {
            display: block;
            margin: 30px auto 0;
            padding: 14px 40px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;

            box-shadow:
                0 4px 8px rgba(0,0,0,0.15),
                0 2px 0 rgba(0,0,0,0.20);

            transition: all 0.15s ease;
        }

        .install-button:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .install-button:active {
            transform: translateY(2px);
            box-shadow:
                0 2px 4px rgba(0,0,0,0.15);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .admin-btn {
            background: #374151;
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .admin-btn:hover {
            background: #1f2937;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 600;
        }

    </style>
</head>
<body>
<div class="card">
    <div class="header-bar">
        <div class="logo-container">
            <img src="<?= e(getLogoUrl()) ?>" alt="Logo" class="logo">
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

    btn.addEventListener('click', async () => {

        const input = document.getElementById('secretLink');

        await navigator.clipboard.writeText(input.value);

        btn.innerText = '✅ Copié';
    });

}
</script>


</body>
</html>