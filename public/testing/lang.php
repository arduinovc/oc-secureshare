<?php

$language = $_GET['lang'] ?? 'fr';

$langFile = __DIR__ . "/lang/{$language}.php";

if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/lang/fr.php";
}

$lang = require $langFile;

/*
<h1>Créer un lien secret</h1>
<h1><?= t('create_secret') ?></h1>

$lang = $config['default_language'] ?? 'fr';
*/