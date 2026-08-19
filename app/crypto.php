<?php

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string|false
{
    $padding = 4 - (strlen($data) % 4);
    if ($padding < 4) {
        $data .= str_repeat('=', $padding);
    }

    return base64_decode(strtr($data, '-_', '+/'), true);
}

function get_app_key(array $config): string
{
    $key = base64_decode($config['app_key'], true);

    if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new RuntimeException('Clé applicative invalide.');
    }

    return $key;
}

function encrypt_secret(string $plaintext, array $config): array
{
    $key = get_app_key($config);
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

    $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key);

    return [
        'ciphertext' => base64_encode($ciphertext),
        'nonce' => $nonce,
    ];
}

function decrypt_secret(string $ciphertextBase64, string $nonce, array $config): string
{
    $key = get_app_key($config);
    $ciphertext = base64_decode($ciphertextBase64, true);

    if ($ciphertext === false) {
        throw new RuntimeException('Secret invalide.');
    }

    $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

    if ($plaintext === false) {
        throw new RuntimeException('Déchiffrement impossible.');
    }

    return $plaintext;
}