<?php

return [
    'app' => [
        'name' => 'Bisped',
        'env' => 'production',
        'debug' => false,
        'url' => 'https://www.bisped.net',
        'timezone' => 'Europe/Rome',
        'key' => 'base64:generate-a-64-character-random-key',
        'session_name' => 'bisped_session',
    ],
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'bisped_net',
        'username' => 'bisped_user',
        'password' => 'secret',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'wallet' => [
        'allowed_addresses' => [
            // '0xYourAdminWalletAddress',
        ],
        'nonce_ttl' => 300,
        'project_id' => 'your-walletconnect-project-id',
        'rpc_url' => 'https://rpc.ankr.com/eth',
    ],
    'auth_users' => [
        [
            'name' => 'Admin Bisped',
            'email' => 'admin@example.test',
            'password' => 'change-me',
            'role' => 'admin',
        ],
        [
            'name' => 'Commesso Negozio',
            'email' => 'shop@example.test',
            'password' => 'change-me',
            'role' => 'commesso',
        ],
        [
            'name' => 'Cliente Demo',
            'email' => 'cliente@example.test',
            'password' => 'change-me',
            'role' => 'cliente',
        ],
    ],
    'mail' => [
        'driver' => 'smtp',
        'host' => 'smtp.yourhost.com',
        'port' => 587,
        'username' => 'username',
        'password' => 'password',
        'encryption' => 'tls',
        'from_address' => 'noreply@bisped.net',
        'from_name' => 'Bisped',
    ],
];
