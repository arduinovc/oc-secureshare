<?php

$language = $_GET['lang'] ?? 'fr';

$langFile = __DIR__ . "/lang/{$language}.php";

if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/lang/fr.php";
}

$lang = require $langFile;