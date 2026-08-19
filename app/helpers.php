<?php

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function client_ip_hash(array $config): ?string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    if (!$ip) {
        return null;
    }

    return hash('sha256', $config['log_salt'] . '|' . $ip);
}

function user_agent_short(): ?string
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    if (!$ua) {
        return null;
    }

    return mb_substr($ua, 0, 500);
}