<?php
declare(strict_types=1);

// ============================================================
// Script di diagnostica setup — Bisped
// Apri con: https://bisped.net/_setup-check.php?key=bisped-diag-2026
// ⚠️ RIMUOVERE dal server dopo l'uso (cancella il file via FTP).
// ============================================================

$EXPECTED_KEY = 'bisped-diag-2026';
if (($_GET['key'] ?? '') !== $EXPECTED_KEY) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: text/plain; charset=utf-8');

function line(string $label, string $status, string $detail = ''): void
{
    echo str_pad($label, 28) . ' ' . $status . ($detail ? '  → ' . $detail : '') . "\n";
}

echo "=== BISPED SETUP CHECK ===\n\n";

// 1. PHP version
$ver = PHP_VERSION;
$okVer = version_compare($ver, '8.2.0', '>=');
line('PHP version', $okVer ? '[OK]' : '[ERRORE]', $ver . ($okVer ? '' : ' — serve >= 8.2'));

// 2. Estensioni
foreach (['pdo_mysql', 'mbstring', 'curl', 'json', 'fileinfo', 'openssl'] as $ext) {
    line("ext: {$ext}", extension_loaded($ext) ? '[OK]' : '[MANCANTE]');
}

echo "\n";

// 3. .env.php
$envPath = dirname(__DIR__) . '/.env.php';
if (!is_file($envPath)) {
    line('.env.php', '[MANCANTE]', $envPath);
    echo "\n→ Carica .env.php via FTP nella root del sito.\n";
    exit;
}
line('.env.php', '[OK]', 'trovato');

$config = require $envPath;
$db = $config['database'] ?? [];
line('app.env', '[INFO]', (string)($config['app']['env'] ?? '?'));
line('app.debug', '[INFO]', var_export($config['app']['debug'] ?? null, true));
line('app.url', '[INFO]', (string)($config['app']['url'] ?? '?'));

echo "\n";

// 4. Connessione DB
$host = (string)($db['host'] ?? '');
$port = (int)($db['port'] ?? 3306);
$name = (string)($db['database'] ?? '');
$user = (string)($db['username'] ?? '');
$pass = (string)($db['password'] ?? '');

line('DB host', '[INFO]', "{$host}:{$port}");
line('DB name', '[INFO]', $name);
line('DB user', '[INFO]', $user);

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    line('DB connessione', '[OK]', 'connesso');

    // 5. Tabelle
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $count = count($tables);
    line('DB tabelle', $count > 0 ? '[OK]' : '[VUOTO]', "{$count} tabelle");

    if ($count === 0) {
        echo "\n→ Il database è vuoto. Importa database/production_import.sql via phpMyAdmin.\n";
    } else {
        // Verifica tabelle chiave
        foreach (['settings', 'products', 'blog_posts', 'admins'] as $t) {
            $exists = in_array($t, $tables, true);
            if ($exists) {
                $n = (int)$pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
                line("tabella {$t}", '[OK]', "{$n} righe");
            } else {
                line("tabella {$t}", '[MANCANTE]', 'importa il dump SQL');
            }
        }
    }
} catch (Throwable $e) {
    line('DB connessione', '[ERRORE]', $e->getMessage());
    echo "\n→ Verifica host, nome DB, utente e password nel .env.php.\n";
    echo "  Su HOST.it l'host è spesso 'localhost'. Se non funziona, controlla\n";
    echo "  nel pannello l'hostname MySQL esatto.\n";
}

echo "\n";

// 6. Permessi storage
$storage = dirname(__DIR__) . '/storage';
if (is_dir($storage)) {
    line('storage/ scrivibile', is_writable($storage) ? '[OK]' : '[NON SCRIVIBILE]', $storage);
} else {
    line('storage/', '[MANCANTE]', 'crea la cartella storage/ con permessi 755');
}

echo "\n=== FINE — ricordati di cancellare questo file ===\n";
