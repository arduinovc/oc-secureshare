<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require __DIR__ . '/../../app/functions.php';
$config = require __DIR__ . '/../../app/config.php';
require __DIR__ . '/../../app/helpers.php';


$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';

    if (
        password_verify(
            $password,
            $config['admin_password_hash']
        )
    ) {

        $_SESSION['authenticated'] = true;

        $target =
            $_SESSION['redirect_after_login']
            ?? 'create.php';

        unset($_SESSION['redirect_after_login']);

        header("Location: $target");
        exit;
    }

    $error = 'Mot de passe incorrect';
}

?>


<html>
<head>
<head>
    <meta charset="utf-8">
    <title>SecureShare - Authentification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" hrefpng" sizes="16xcon" href="../assets/favicon.ico">
    <link rel="stylesheet" href="../assets/style.css">
</head>
</head>
<body>

    <div class="card">

    <div class="header-bar">
        <div class="logo-container">
            <img src="../<?= e(getLogoUrl()) ?>" alt="Branding" class="logo">
            <div class="logo-separator"></div>
            <img src="../assets/logo-secureshare.png" alt="Logo" class="logo">
        </div>
    </div>
        <div class="logo-container">
        </div>

        <h1>Authentification requise</h1>

        <?php if ($error): ?>
            <div class="error-box">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">

            <label for="password">
                Mot de passe administrateur
            </label>

            <input
                type="password"
                id="password"
                name="password"
                required
                autofocus
            >

            <button type="submit" class="install-button">
                Se connecter
            </button>

        </form>

    </div>

</body>
</html>
