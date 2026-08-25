<?php

$lockFile = __DIR__ . '/app/installed.lock';

if (!file_exists($lockFile)) {
    header('Location: public/setup.php');
    exit;
}

header('Location: public/admin/create.php');
exit;