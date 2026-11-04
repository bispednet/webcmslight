<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$local = json_decode(file_get_contents($root . '/.local/environment.json'), true, 512, JSON_THROW_ON_ERROR);
$password = $local['mysql_root_password'];
$pdo = new PDO('mysql:host=127.0.0.1;port=' . $local['mysql_port'] . ';charset=utf8mb4', 'root', $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS bisped_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec("CREATE USER IF NOT EXISTS 'bisped_local'@'127.0.0.1' IDENTIFIED BY " . $pdo->quote($local['database_password']));
$pdo->exec("ALTER USER 'bisped_local'@'127.0.0.1' IDENTIFIED BY " . $pdo->quote($local['database_password']));
$pdo->exec("GRANT ALL PRIVILEGES ON bisped_local.* TO 'bisped_local'@'127.0.0.1'");
$pdo->exec("ALTER USER 'root'@'localhost' IDENTIFIED BY " . $pdo->quote($local['mysql_root_password']));
echo "Utenti e database locali pronti\n";
