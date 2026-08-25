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

/*
function ipInCidr(string $ip, string $cidr): bool
{
    [$subnet, $mask] = explode('/', $cidr);

    return
        (ip2long($ip) & ~((1 << (32 - $mask)) - 1))
        ===
        (ip2long($subnet) & ~((1 << (32 - $mask)) - 1));
}
*/

function ipInCidr(string $ip, string $cidr): bool
{
    if (empty($cidr) || strpos($cidr, '/') === false) {
        return false;
    }

    [$subnet, $mask] = explode('/', $cidr, 2);

    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);

    if ($ipLong === false || $subnetLong === false) {
        return false;
    }

    $maskLong = -1 << (32 - (int)$mask);

    return (($ipLong & $maskLong) === ($subnetLong & $maskLong));
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
        $config['allow_trusted'] === true
        && (
            !empty($config['trusted_lan'])
            || !empty($config['trusted_ip'])
        )
    ) {

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

        if (
            ipInCidr($clientIp, $config['trusted_lan']) ||
            (!empty($config['trusted_ip']) && $clientIp === $config['trusted_ip'])
        ) {
            return;
        }
    }

    $_SESSION['redirect_after_login'] =
        $_SERVER['REQUEST_URI'];

    header('Location: login.php');
    exit;
}
