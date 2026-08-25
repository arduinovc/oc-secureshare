<?php

return [
    // Paramètre de la base de données
    'db_host' => 'localhost',
    'db_name' => 'secrets_db',
    'db_user' => 'secrets_user',
    'db_pass' => 'secrets_password',

    // URL publique de ton application
    'base_url' => 'https://secrets.yourdomain.com',

    // À générer une seule fois.
    // php -r "echo base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)), PHP_EOL;"
    'app_key' => 'secrets_app_key',

    // Sel interne pour anonymiser les IP dans les logs
    // php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
    'log_salt' => 'secrets_log_salt',

    // TTL par défaut en minutes
    'default_ttl_minutes' => 1440,

    // Défini la langue par défaut (français)
    'default_language' => 'fr',

    // Défini le mot de passe administrateur
    // php -r "echo password_hash();"
    'admin_password_hash' => '$adminPasswordHash',

    // Autorise le bypass de l'authentification via LAN
    'allow_trusted_lan' => true,

    // Défini l'accès LAN qui ne nécessite pas d'authentification
    'trusted_lan' => '192.168.1.0/24',
];