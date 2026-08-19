<?php

return [
    'db_host' => 'localhost',
    'db_name' => 'secrets',
    'db_user' => 'secrets_user',
    'db_pass' => 'secrets_password',

    // URL publique de ton application
    'base_url' => 'https://secrets.yourdomain.com',

    // À générer une seule fois.
    // php -r "echo base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)), PHP_EOL;"
    'app_key' => 'secrets_app_key',

    // Sel interne pour anonymiser les IP dans les logs
    'log_salt' => 'secrets_log_salt',

    // TTL par défaut en minutes
    'default_ttl_minutes' => 1440,
];