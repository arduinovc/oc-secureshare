<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE secret_links
            SET revoked_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
    }

    header('Location: admin.php');
    exit;
}

$stmt = $pdo->query("
    SELECT
        l.id,
        l.label,
        l.ticket,
        l.max_views,
        l.views,
        l.expires_at,
        l.revoked_at,
        l.created_at,
        (
            SELECT MAX(o.opened_at)
            FROM secret_opens o
            WHERE o.link_id = l.id
        ) AS last_opened_at
    FROM secret_links l
    ORDER BY l.created_at DESC
    LIMIT 100
");

$links = $stmt->fetchAll();

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>SecureShare - Affichage de l'état des tokens.</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" hrefpng" sizes="16xcon" href="assets/favicon.ico">

    <style>
        body {
            font-family: system-ui, Arial, sans-serif;
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 16px;
            background: #f7f7f7;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f1f5f9;
        }

        button {
            padding: 6px 10px;
            border: 0;
            border-radius: 6px;
            background: #b91c1c;
            color: white;
            cursor: pointer;
        }

        .muted {
            color: #64748b;
        }
        .logo-container {
            text-align: left;
            margin-bottom: 20px;
        }

        .logo-container img {
            max-width: 250px;
            max-height: 80px;
            width: auto;
            height: auto;
        }
    </style>
</head>
<body>
<div class="logo-container">
    <img src="<?= e(getLogoUrl()) ?>" alt="Logo" class="logo">
</div>
<h1>Etat des liens générés</h1>

<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Client</th>
        <th>Ticket</th>
        <th>Vues</th>
        <th>Expiration</th>
        <th>Créé le</th>
        <th>Dernière ouverture</th>
        <th>Statut</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($links as $link): ?>
        <?php
        $expired = new DateTimeImmutable($link['expires_at']) < new DateTimeImmutable();
        $revoked = $link['revoked_at'] !== null;
        ?>
        <tr>
            <td><?= e((string)$link['id']) ?></td>
            <td><?= e($link['label']) ?></td>
            <td><?= e($link['ticket']) ?></td>
            <td><?= e($link['views'] . ' / ' . $link['max_views']) ?></td>
            <td><?= e($link['expires_at']) ?></td>
            <td><?= e($link['created_at']) ?></td>
            <td><?= e($link['last_opened_at'] ?? '-') ?></td>
            <td>
                <?php if ($revoked): ?>
                    Révoqué
                <?php elseif ($expired): ?>
                    Expiré
                <?php else: ?>
                    Actif
                <?php endif; ?>
            </td>
            <td>
                <?php if (!$revoked): ?>
                    <form method="post">
                        <input type="hidden" name="id" value="<?= e((string)$link['id']) ?>">
                        <button type="submit">Expirer</button>
                    </form>
                <?php else: ?>
                    <span class="muted">-</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>