<?php

$lockFile = dirname(__DIR__) . '/app/installed.lock';

if (!file_exists($lockFile)) {
    header('Location: setup.php');
    exit;
}

header('Location: create.php');
exit;