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
        'admin_evm_addresses' => [
            // '0xYourAdminWalletAddress',
        ],
        'admin_solana_addresses' => [
            // 'YourSolanaAdminAddress',
        ],
        'nonce_ttl' => 300,
        'project_id' => 'your-walletconnect-project-id',
        'rpc_url' => 'https://rpc.ankr.com/eth',
    ],
    'google' => [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => 'https://www.bisped.net/auth/google/callback',
        'admin_emails' => [
            'bisped@gmail.com',
        ],
    ],
    'gemini' => [
        'api_key' => '',
        'model' => 'gemma-4-31b-it',
        'requests_per_minute' => 10,
        'requests_per_day' => 5000,
        'cooldown_seconds' => 7,
    ],
    'calendar' => [
        'enabled' => true,
        'calendar_id' => 'primary',
        'timezone' => 'Europe/Rome',
        'default_duration_minutes' => 30,
        'google_refresh_token' => '',
        'auto_confirm' => false,
        'meet_enabled' => true,
    ],
    'company' => [
        'legal_name' => 'bisp&d s.r.l.',
        'address' => 'Piazza della Costituzione, 68 - 57025 Piombino LI Italia',
        'vat_id' => 'IT0156025048',
        'rea' => 'LI-138175',
        'sdi' => 'M5UXCR1',
        'pec' => 'bisped@pec.it',
        'share_capital' => '100.000 euro interamente versato',
    ],
    'auth_users' => [
        [
            'name' => 'Admin Bisped',
            'email' => 'admin@example.test',
            'password' => 'replace-with-a-long-random-password',
            'role' => 'admin',
        ],
        [
            'name' => 'Commesso Negozio',
            'email' => 'shop@example.test',
            'password' => 'replace-with-a-long-random-password',
            'role' => 'commesso',
        ],
        [
            'name' => 'Cliente Demo',
            'email' => 'cliente@example.test',
            'password' => 'replace-with-a-long-random-password',
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
