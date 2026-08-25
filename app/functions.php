<?php

function getLogoUrl(): string
{
    $custom = __DIR__ . '/../public/assets/logo.png';

    if (file_exists($custom)) {
        return 'assets/logo.png';
    }

    return 'assets/logo-default.png';
}


function t(string $key): string
{
    global $lang;

    return $lang[$key] ?? $key;
}

function ipInCidr(string $ip, string $cidr): bool
{
    [$subnet, $mask] = explode('/', $cidr);

    return
        (ip2long($ip) & ~((1 << (32 - $mask)) - 1))
        ===
        (ip2long($subnet) & ~((1 << (32 - $mask)) - 1));
}

function requireAuth(array $config): void
{
    session_start();

    // Déjà authentifié
    if (!empty($_SESSION['authenticated'])) {
        return;
    }

    // Réseau autorisé
    if (
        !empty($config['allow_trusted_lan'])
        && !empty($config['trusted_lan'])
    ) {

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

        if (
            ipInCidr(
                $clientIp,
                $config['trusted_lan']
            )
        ) {
            return;
        }
    }

    $_SESSION['redirect_after_login'] =
        $_SERVER['REQUEST_URI'];

    header('Location: login.php');
    exit;
}