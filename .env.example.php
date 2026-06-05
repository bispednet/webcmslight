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
        'editorial' => [
            'model' => 'gemma-4-31b-it',
            'requests_per_minute' => 10,
            'tokens_per_minute' => 5000,
            'requests_per_day' => 1000,
            'cooldown_seconds' => 7,
            'timeout_seconds' => 150,
            'temperature' => 0.35,
            'include_thoughts' => false,
            'strip_reasoning' => true,
            'thinking_level' => 'minimal',
            'thinking_budget' => 0,
        ],
        'concierge' => [
            'model' => 'gemini-3.1-flash-lite',
            'requests_per_minute' => 15,
            'tokens_per_minute' => 250000,
            'requests_per_day' => 500,
            'cooldown_seconds' => 2,
            'timeout_seconds' => 20,
            'temperature' => 0.35,
            'include_thoughts' => false,
            'strip_reasoning' => true,
            'thinking_level' => 'minimal',
            'thinking_budget' => 0,
        ],
    ],
    'ai_concierge' => [
        'enabled' => true,
        'mode' => 'hybrid',
        'display_name' => 'SarAI',
        'whatsapp_number' => '393346582116',
        'max_messages_per_conversation' => 40,
        'rate_limit_per_minute' => 12,
        'retention_days' => 180,
    ],
    // ── Agent API ──────────────────────────────────────────────────────────
    // Chiave per il Custom GPT / agent AI. Genera con: bin2hex(random_bytes(32))
    // NON condividere e NON committare il valore reale.
    'agent' => [
        'api_key' => '',
    ],
    // ── Catalogo: import prodotti da fornitori B2B ─────────────────────────
    // Pricing: (costo × (1 + markup%) + markup_fisso) × (1 + IVA), arrotondato ,90
    'catalog' => [
        'enabled'         => false,   // metti true quando il fornitore è configurato
        'vat'             => 0.22,    // IVA applicata al prezzo di vendita
        'markup_default'  => 0.10,    // +10% sul costo d'acquisto
        'markup_fixed'    => 5.00,    // +5€ fissi (scoraggia la minuteria a basso margine)
        'max_discount'    => 0.05,    // sconto massimo applicabile in trattativa (5%)
        'require_image'   => true,    // scarta i prodotti senza foto valida
        'import_unmapped' => false,   // se true importa anche famiglie non mappate (in "accessori")
        'family_exclude'  => [],      // famiglie Runner extra da escludere (oltre ai default)
        'markup' => [                 // markup % per categoria (override del default)
            'smartphone'    => 0.08,
            'notebook'      => 0.08,
            'componenti-pc' => 0.10,
            'gaming'        => 0.10,
            'connettivita'  => 0.15,
            'accessori'     => 0.25,
        ],
        'fixed' => [                  // €fissi per categoria (override di markup_fixed)
            'smartphone' => 8.00,
            'notebook'   => 10.00,
            'accessori'  => 5.00,
        ],
        // Fornitore Runner S.p.A. — tracciati txt pipe-delimited via FTPS
        'runner' => [
            'ftp_host'      => 'techstore.runner.it', // host FTP Runner (NON ftp.runner.it: è Cloudflare)
            'ftp_user'      => '',         // codice cliente, es. C111445
            'ftp_pass'      => '',
            'ftp_port'      => 21,
            'ftp_ssl'       => true,       // Runner richiede FTPS (TLS esplicito)
            'customer_code' => '',         // cartella prezzi personalizzati (= codice cliente)
            'work_dir'      => '',         // es. '/home/uu4c5pdm/.../storage/imports/runner'
            'skip_download' => false,      // true per usare solo i file già in work_dir (test)
        ],
        'nexths' => [
            'mode'          => 'csv',  // 'csv' oppure 'api'
            'csv_path'      => '',
            'csv_delimiter' => ';',
            'api_url'       => '',
            'api_key'       => '',
        ],
    ],
    'whatsapp' => [
        'mode' => 'click_to_chat',
        'phone_number' => '393346582116',
        'phone_number_id' => '',
        'business_account_id' => '',
        'access_token' => '',
        'webhook_verify_token' => '',
        'app_secret' => '',
    ],
    'video_call' => [
        'enabled' => true,
        'provider' => 'google_meet',
        'default_duration_minutes' => 30,
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
