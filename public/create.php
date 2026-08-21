<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    $maxViews = (int)($_POST['max_views'] ?? 1);

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
    <title>SecretShare - Création d'un lien sécurisé pour partager vos mots de passe</title>
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

    </style>
</head>
<body>
<div class="card">
    <h1>Créer un lien secret</h1>

    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">

    <div class="instructions">
        <h3>Informations</h3>

        <ul>
            <li>Le secret est chiffré avant stockage.</li>
            <li>Le lien expire automatiquement après la durée configurée (par défaut : 24 heures).</li>
            <li>Le nombre maximal d'ouvertures peut être limité (par défaut : 3).</li>
            <li>Une fois expiré ou consommé, le secret est définitivement supprimé.</li>
            <li>Ne transmettez le lien qu'au destinataire prévu.</li>
        </ul>
    </div>

    <label for="secret">Mot de passe à transmettre</label>
        <textarea id="secret" name="secret" rows="4" required></textarea>

        <label for="label">Client, facultatif</label>
        <input id="label" name="label" type="text" maxlength="255">

        <label for="ticket">Ticket, facultatif</label>
        <input id="ticket" name="ticket" type="text" maxlength="100">

        <label for="ttl_minutes">Expiration, en minutes</label>
        <input id="ttl_minutes" name="ttl_minutes" type="number" min="1" max="10080" value="<?= e((string)$config['default_ttl_minutes']) ?>">

        <label for="max_views">Nombre maximum d’ouvertures</label>
        <input id="max_views" name="max_views" type="number" min="1" max="20" value="1">

        <button type="submit">Générer le lien</button>
    </form>

    <?php if ($generatedUrl): ?>
        <div class="success">
            <strong>Lien généré :</strong><br>
            <input type="text" value="<?= e($generatedUrl) ?>" readonly onclick="this.select()">
        </div>
    <?php endif; ?>
</div>
</body>
</html>