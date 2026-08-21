<?php

function getLogoUrl(): string
{
    $custom = __DIR__ . '/../public/assets/logo.png';

    if (file_exists($custom)) {
        return 'assets/logo.png';
    }

    return 'assets/logo-default.png';
}

/*
function t(string $key): string
{
    global $lang;

/   return $lang[$key] ?? $key;
}

*/