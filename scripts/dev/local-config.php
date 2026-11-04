<?php
declare(strict_types=1);

// Questo file viene caricato solo dal .env.php locale generato dal setup.
$root = dirname(__DIR__, 2);
$local = json_decode(file_get_contents($root . '/.local/environment.json'), true, 512, JSON_THROW_ON_ERROR);
$config = require (is_file($root . '/.env.local.php') ? $root . '/.env.local.php' : $root . '/.local/production.env.php');
if (is_file($root . '/.local/ai-overrides.json')) {
    $overrides = json_decode(file_get_contents($root . '/.local/ai-overrides.json'), true, 512, JSON_THROW_ON_ERROR);
    $config = array_replace_recursive($config, $overrides);
}
$staging = strtolower(explode(':', (string)($_SERVER['HTTP_HOST'] ?? ''))[0]) === $local['staging_host'];
$config['app']['env'] = $staging ? 'staging' : 'local';
$config['app']['url'] = $staging ? 'https://' . $local['staging_host'] : $local['local_url'];
$config['app']['debug'] = !$staging;
$config['app']['key'] = $staging ? $local['staging_app_key'] : $local['app_key'];
$config['app']['session_name'] = $staging ? 'bisped_staging' : 'bisped_local';
// Sostituzione integrale: nessun fallback al database di produzione.
$config['database'] = [
    'host' => '127.0.0.1', 'port' => $local['mysql_port'],
    'database' => 'bisped_local', 'username' => 'bisped_local',
    'password' => $local['database_password'], 'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
$config['auth_users'] = [[
    'name' => 'Admin Bisped locale', 'email' => 'admin@bisped.local',
    'password' => $local['admin_password'], 'role' => 'admin',
]];
$config['analytics'] = ['ga4_measurement_id' => '', 'meta_pixel_id' => ''];
$config['paypal']['environment'] = 'sandbox';
$config['paypal']['client_id'] = '';
$config['paypal']['client_secret'] = '';
$config['paypal']['payee_email'] = '';
$config['calendar']['enabled'] = false;
$config['calendar']['google_refresh_token'] = '';
$config['calendar']['auto_confirm'] = false;
$config['video_call']['enabled'] = false;
$config['google']['client_id'] = '';
$config['google']['client_secret'] = '';
$config['google']['redirect_uri'] = $config['app']['url'] . '/auth/google/callback';
$config['catalog']['runner']['work_dir'] = $root . '/storage/imports/runner';
$config['catalog']['nexths']['api_key'] = '';
$config['whatsapp']['access_token'] = '';
$config['whatsapp']['app_secret'] = '';
$config['whatsapp']['phone_number'] = '';
$config['ai_concierge']['whatsapp_number'] = '';
$config['agent']['api_key'] = $local['agent_api_key'];
$config['mail'] = [
    'driver' => 'smtp', 'host' => '127.0.0.1', 'port' => $local['smtp_port'],
    'username' => '', 'password' => '', 'encryption' => '',
    'from_address' => 'noreply@bisped.local', 'from_name' => 'Bisped locale',
];
$config['security']['turnstile'] = ['site_key' => '', 'secret_key' => ''];
// I modelli e il provider rimangono quelli di produzione. Le chiamate esterne
// si attivano esplicitamente con allow_ai=true nel file privato environment.json.
if (empty($local['allow_ai'])) {
    $config['ai_concierge']['enabled'] = false;
    foreach (['gemini', 'gemrouter', 'openai', 'deepseek'] as $provider) {
        $config[$provider]['api_key'] = '';
        $config[$provider]['enabled'] = false;
    }
}
return $config;
