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
    $defaultLanguage = $_POST['default_language'] ?? 'fr';

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

    //Default Language (french)
    'default_language' => '$defaultLanguage',
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
    <title>SecureShare - Installation et initialisation de la base de données</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"  class="container py-5">
    <link rel="icon" type="image/png" sizes="32x32" hrefpng" sizes="16xcon" href="assets/favicon.ico">
    <link rel="stylesheet" href="assets/style.css">
    <style>       
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .logo-container img {
            max-width: 720px;
            max-height: 300px;
        }
    </style>
</head>
<body>
<div class="card">
<div class="logo-container">
    <img src="assets/logo-defaultfull.png" alt="SecureShare" />
</div>

<h1>Installation de SecureShare</h1>

<?php if($success): ?>

<div class="success-box">
    ✅ Installation terminée avec succès.
    Supprimez maintenant setup.php du serveur.
</div>

<?php else: ?>

<?php if ($error): ?>
<div class="error-box">
    <div class="title">❌ Erreur d'installation</div>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>


<form method="post" enctype="multipart/form-data" autocomplete="off">
<div class="instructions">
    <h3>Informations</h3>

    <ul>
        <li>Vous devez disposer de PHP8.1+.</li>
        <li>Vous devez disposer des identifiants de votre base de données MySQL ou MariaDB.</li>
        <li>N'exposez jamais le fichier app/config.php qui contient la clé de chiffrement.</li>
        <li>Le logo doit être au format .png.</li>
    </ul>
</div>
    <div class="form-row">
        <label>Langue par défaut</label>

        <select name="default_language">
            <option value="fr" selected>🇫🇷 Français</option>
            <option value="en">🇬🇧 English</option>
        </select>
    </div>
    
    <div class="form-row">
        <label>Serveur MySQL</label>
        <input type="text" name="db_host" value="localhost">
    </div>

    <div class="form-row">
        <label>Base</label>
        <input type="text" name="db_name">
    </div>

    <div class="form-row">
        <label>Utilisateur</label>
        <input type="text" name="db_user">
    </div>

    <div class="form-row">
        <label>Mot de passe</label>
        <input type="password" name="db_pass">
    </div>

    <div class="form-row">
        <label>Logo</label>
        <input type="file" id="logo" name="logo" accept=".png">
    </div>

    <button type="submit" class="install-button">
        Installer
    </button>

</form>

<?php endif; ?>
</div>

</body>
</html>