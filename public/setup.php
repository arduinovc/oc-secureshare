<?php

$lockFile = __DIR__ . '/../app/installed.lock';
$configFile = __DIR__ . '/../app/config.php';


if (file_exists($lockFile)) {
    //die('Application déjà installée.');
    header('Location: create.php');
    exit;
}


$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dbHost = trim($_POST['db_host']);
    $dbName = trim($_POST['db_name']);
    $dbUser = trim($_POST['db_user']);
    $dbPass = $_POST['db_pass'];

    try {

        $pdo = new PDO(
            "mysql:host=$dbHost;charset=utf8mb4",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );

        $pdo->exec("
            CREATE DATABASE IF NOT EXISTS `$dbName`
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci
        ");

        $pdo->exec("USE `$dbName`");

        $pdo->exec("
            CREATE TABLE secret_links (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                token_hash CHAR(64) NOT NULL UNIQUE,
                ciphertext TEXT NOT NULL,
                nonce VARBINARY(24) NOT NULL,

                label VARCHAR(255) NULL,
                ticket VARCHAR(100) NULL,

                max_views INT UNSIGNED NOT NULL DEFAULT 1,
                views INT UNSIGNED NOT NULL DEFAULT 0,

                expires_at DATETIME NOT NULL,
                revoked_at DATETIME NULL,

                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_token_hash (token_hash),
                INDEX idx_expires_at (expires_at),
                INDEX idx_revoked_at (revoked_at)
            )
        ");

        $pdo->exec("
            CREATE TABLE secret_opens (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                link_id BIGINT UNSIGNED NOT NULL,
                opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip_hash CHAR(64) NULL,
                user_agent VARCHAR(500) NULL,

                FOREIGN KEY (link_id) REFERENCES secret_links(id) ON DELETE CASCADE,
                INDEX idx_link_id (link_id)
            )
        ");

        $encryptionKey = base64_encode(
            random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)
        );

        $logSalt = bin2hex(random_bytes(32));

        $site_url =
            (isset($_SERVER['HTTPS']) ? 'https' : 'http') .
            '://' .
            $_SERVER['HTTP_HOST'];

        $config = <<<PHP
<?php

return [

    //Database connexion
    'db_host' => '$dbHost',
    'db_name' => '$dbName',
    'db_user' => '$dbUser',
    'db_pass' => '$dbPass',

    // Webapp URL (location to find view.php)
    'base_url' => '{$site_url}',

    //Key to encrypt and decrypt passwords
    'app_key' => '$encryptionKey',

    //Random key for IP Anonymisation
    'log_salt' => '$logSalt',

    //Default TTL (1 day)
    'default_ttl_minutes' => 1440,

];
PHP;

        file_put_contents($configFile, $config);


        $logoPath = __DIR__ . '/assets/logo.png';

        if (
            isset($_FILES['logo']) &&
            $_FILES['logo']['error'] === UPLOAD_ERR_OK
        ) {
            move_uploaded_file(
                $_FILES['logo']['tmp_name'],
                $logoPath
            );
        }
        
        file_put_contents(
            $lockFile,
            date('Y-m-d H:i:s')
        );

        $success = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>SecretShare - Installation et initialisation de la base de données</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"  class="container py-5">
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

        .secret {
            font-family: monospace;
            font-size: 1.2rem;
            background: #111827;
            color: #fff;
            padding: 16px;
            border-radius: 8px;
            word-break: break-all;
        }

        .error {
            color: #b91c1c;
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

        .logo {
            max-width: 250px;
            max-height: 80px;
            width: auto;
            height: auto;
        }
    </style>
</head>
<body>
<div class="card">
<div class="logo-container">
    <img src="assets/logo-default.png" alt="SecureShare" />
</div>

<h1>Installation OC-SecureShare</h1>

<?php if($success): ?>

<div class="alert alert-success">
    Installation terminée.
</div>

<div class="alert alert-warning">
    Supprimez maintenant setup.php du serveur.
</div>

<?php else: ?>

<?php if($error): ?>
<div class="alert alert-danger">
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>


<form method="post" autocomplete="off">
<div class="instructions">
    <h3>Informations</h3>

    <ul>
        <li>Vous devez disposer de PHP8.1+.</li>
        <li>Vous devez disposer des identifiants de votre base de données MySQL ou MariaDB.</li>
        <li>N'exposez jamais le fichier app/config.php qui contient la clé de chiffrement.</li>
    </ul>
</div>



<form method="post" enctype="multipart/form-data">

    <div class="mb-3">
        <label>Serveur MySQL</label>
        <input type="text" name="db_host" class="form-control" value="localhost">
    </div>

    <div class="mb-3">
        <label>Base</label>
        <input type="text" name="db_name" class="form-control">
    </div>

    <div class="mb-3">
        <label>Utilisateur</label>
        <input type="text" name="db_user" class="form-control">
    </div>

    <div class="mb-3">
        <label>Mot de passe</label>
        <input type="password" name="db_pass" class="form-control">
    </div>

    <div class="mb-3">
        <label for="logo">Logo (PNG ou JPG, facultatif)</label>
        <input type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg">
    </div>

    <button class="btn btn-primary">
        Installer
    </button>

</form>

<?php endif; ?>
</div>

</body>
</html>