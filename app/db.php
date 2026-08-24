<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/config.php';

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $config['db_host'],
    $config['db_name']
);

$pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Purge les enregistrements expirés depuis plus de 24 heures
$pdo->exec("DELETE FROM secret_links WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");