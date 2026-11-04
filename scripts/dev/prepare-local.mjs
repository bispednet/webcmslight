import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawn } from 'node:child_process';
import { pipeline } from 'node:stream/promises';
import { fileURLToPath } from 'node:url';
import net from 'node:net';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const local = path.join(root, '.local');
const php = path.join(root, 'runtime/php/php.exe');
const mysql = path.join(root, 'runtime/mysql-8.0.45-winx64');
for (const dir of ['.local/logs', '.local/mail', '.local/sessions', 'runtime/mysql-data', 'storage/logs', 'storage/cache', 'storage/tmp', 'storage/imports']) fs.mkdirSync(path.join(root, dir), { recursive: true });
const secretsFile = path.join(local, 'environment.json');
const random = () => crypto.randomBytes(24).toString('base64url');
if (!fs.existsSync(secretsFile)) fs.writeFileSync(secretsFile, JSON.stringify({
  local_url: 'http://127.0.0.1:8080', staging_host: 'staging.bisped.net',
  mysql_port: 3307, smtp_port: 1025, proxy_port: 8081, control_port: 8082,
  database_password: random(), mysql_root_password: random(), admin_password: random(),
  staging_username: 'bispednet', staging_password: random(), control_token: random(),
  app_key: 'base64:' + crypto.randomBytes(32).toString('base64'),
  staging_app_key: 'base64:' + crypto.randomBytes(32).toString('base64'),
  agent_api_key: crypto.randomBytes(32).toString('hex'), allow_ai: false,
}, null, 2), { mode: 0o600 });
const config = JSON.parse(fs.readFileSync(secretsFile, 'utf8'));
const normalized = value => value.replaceAll('\\', '/');
fs.writeFileSync(path.join(root, 'runtime/mysql.ini'), `[mysqld]\nbasedir="${normalized(mysql)}"\ndatadir="${normalized(path.join(root, 'runtime/mysql-data'))}"\nport=${config.mysql_port}\nbind-address=127.0.0.1\nmysqlx=0\nskip-name-resolve=1\ncharacter-set-server=utf8mb4\ncollation-server=utf8mb4_unicode_ci\nmax_allowed_packet=64M\nlocal-infile=0\nlog-error="${normalized(path.join(local, 'logs/mysql.log'))}"\n`);
fs.writeFileSync(path.join(root, 'runtime/php/php.ini'), `[PHP]\nextension_dir="${normalized(path.join(root, 'runtime/php/ext'))}"\nextension=curl\nextension=ftp\nextension=fileinfo\nextension=gd\nextension=intl\nextension=mbstring\nextension=mysqli\nextension=openssl\nextension=pdo_mysql\nextension=zip\ndate.timezone=Europe/Rome\nmemory_limit=256M\nmax_execution_time=120\npost_max_size=32M\nupload_max_filesize=24M\ndisplay_errors=Off\nlog_errors=On\nerror_log="${normalized(path.join(local, 'logs/php.log'))}"\nsession.save_path="${normalized(path.join(local, 'sessions'))}"\nsession.use_strict_mode=1\nSMTP=127.0.0.1\nsmtp_port=${config.smtp_port}\nsendmail_from=noreply@bisped.local\nmail.log="${normalized(path.join(local, 'logs/mail.log'))}"\ncurl.cainfo="${normalized(path.join(root, 'runtime/php/cacert.pem'))}"\nopenssl.cafile="${normalized(path.join(root, 'runtime/php/cacert.pem'))}"\n`);
if (fs.existsSync(path.join(root, '.env.php')) && !fs.readFileSync(path.join(root, '.env.php'), 'utf8').includes('scripts/dev/local-config.php')) throw new Error('.env.php preesistente: conservarlo e verificare prima di sostituirlo');
fs.writeFileSync(path.join(root, '.env.php'), "<?php\nreturn require __DIR__ . '/scripts/dev/local-config.php';\n", { mode: 0o600 });
fs.writeFileSync(path.join(local, 'mysql-client.ini'), `[client]\nhost=127.0.0.1\nport=${config.mysql_port}\nuser=bisped_local\npassword=${config.database_password}\ndefault-character-set=utf8mb4\n`, { mode: 0o600 });
fs.writeFileSync(path.join(local, 'mysql-root.ini'), `[client]\nhost=127.0.0.1\nport=${config.mysql_port}\nuser=root\npassword=${config.mysql_root_password}\n`, { mode: 0o600 });
fs.writeFileSync(path.join(local, 'ACCESSI-LOCALI.txt'), `Bisped - credenziali del solo ambiente di sviluppo\n\nSito locale: ${config.local_url}\nLogin CMS: ${config.local_url}/login\nEmail: admin@bisped.local\nPassword CMS: ${config.admin_password}\n\nStaging: https://${config.staging_host}\nUtente protezione staging: ${config.staging_username}\nPassword protezione staging: ${config.staging_password}\n\nDatabase: 127.0.0.1:${config.mysql_port}\nNome e utente: bisped_local\nPassword database: ${config.database_password}\n\nEmail di prova salvate in .local/mail\nAI: per abilitarla impostare allow_ai=true in .local/environment.json\n`, { mode: 0o600 });

async function run(command, args, options = {}) {
  const child = spawn(command, args, { cwd: root, windowsHide: true, stdio: ['ignore', 'pipe', 'pipe'], ...options });
  let error = '';
  child.stderr?.on('data', data => { error += data.toString(); });
  child.stdout?.resume();
  await new Promise((resolve, reject) => { child.once('error', reject); child.once('close', code => code === 0 ? resolve() : reject(new Error(`Comando locale non riuscito (${code}): ${error.slice(-1200)}`))); });
}
const first = !fs.existsSync(path.join(root, 'runtime/mysql-data/mysql'));
if (first) {
  console.log('Inizializzazione del database locale');
  await run(path.join(mysql, 'bin/mysqld.exe'), [`--defaults-file=${path.join(root, 'runtime/mysql.ini')}`, '--initialize-insecure']);
}
const serverLog = fs.openSync(path.join(local, 'logs/mysql-setup.log'), 'a');
const bootstrap = path.join(local, 'mysql-bootstrap.sql');
const usersReady = path.join(local, 'mysql-users-ready.json');
const serverArgs = [`--defaults-file=${path.join(root, 'runtime/mysql.ini')}`, '--console'];
if (!fs.existsSync(usersReady)) {
  fs.writeFileSync(bootstrap, `CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '${config.mysql_root_password}';\nGRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;\nALTER USER 'root'@'localhost' IDENTIFIED BY '${config.mysql_root_password}';\n`, { mode: 0o600 });
  serverArgs.push(`--init-file=${bootstrap}`);
}
const server = spawn(path.join(mysql, 'bin/mysqld.exe'), serverArgs, { cwd: root, windowsHide: true, stdio: ['ignore', serverLog, serverLog] });
server.once('error', error => console.error(error.message));
const available = () => new Promise(resolve => { const s = net.connect(config.mysql_port, '127.0.0.1'); s.once('connect', () => { s.destroy(); resolve(true); }); s.once('error', () => resolve(false)); });
try {
  let ready = false;
  for (let i = 0; i < 120; i++) { if (await available()) { ready = true; break; } if (server.exitCode !== null) throw new Error('MySQL locale si e arrestato: vedere .local/logs/mysql.log'); await new Promise(resolve => setTimeout(resolve, 250)); }
  if (!ready) throw new Error('MySQL locale non risponde');
  await run(php, [path.join(root, 'scripts/dev/setup-database.php')]);
  fs.writeFileSync(usersReady, JSON.stringify({ createdAt: new Date().toISOString() }));
  fs.rmSync(bootstrap, { force: true });
  const marker = path.join(local, 'database-clone.json');
  if (!fs.existsSync(marker)) {
    const latest = JSON.parse(fs.readFileSync(path.join(local, 'latest-dump.json'), 'utf8'));
    const child = spawn(path.join(mysql, 'bin/mysql.exe'), [`--defaults-extra-file=${path.join(local, 'mysql-client.ini')}`, '--binary-mode', 'bisped_local'], { windowsHide: true, stdio: ['pipe', 'ignore', 'pipe'] });
    let error = ''; child.stderr.on('data', data => { error += data.toString(); });
    const done = new Promise((resolve, reject) => { child.once('error', reject); child.once('close', code => code === 0 ? resolve() : reject(new Error(`Import locale non riuscito: ${error.slice(0, 600)}`))); });
    await Promise.all([pipeline(fs.createReadStream(path.join(root, latest.path)), child.stdin), done]);
    fs.writeFileSync(marker, JSON.stringify({ source: latest.path, importedAt: new Date().toISOString() }, null, 2));
    console.log('Database di produzione importato nella copia locale');
  } else console.log('Database locale gia presente: dati conservati');
} finally {
  try { await run(path.join(mysql, 'bin/mysqladmin.exe'), [`--defaults-extra-file=${path.join(local, 'mysql-root.ini')}`, 'shutdown']); } catch { server.kill(); }
  await new Promise(resolve => { if (server.exitCode !== null) resolve(); else server.once('exit', resolve); });
  fs.closeSync(serverLog);
}
if (!fs.existsSync(path.join(local, 'media-imported.json'))) {
  const temporaryMedia = path.resolve(local, 'media-import');
  fs.mkdirSync(temporaryMedia, { recursive: true });
  await run('tar.exe', ['-xzf', path.join(local, 'backups/media.tar.gz'), '-C', temporaryMedia]);
  fs.cpSync(path.join(temporaryMedia, 'media'), path.join(root, 'public/media'), { recursive: true, force: false, errorOnExist: false });
  if (temporaryMedia !== path.resolve(root, '.local/media-import')) throw new Error('Percorso temporaneo media inatteso');
  fs.rmSync(temporaryMedia, { recursive: true });
  fs.writeFileSync(path.join(local, 'media-imported.json'), JSON.stringify({ importedAt: new Date().toISOString() }));
  console.log('Immagini di produzione disponibili in public/media');
}
console.log('Configurazione locale pronta. Avvia con Avvia-Locale.ps1');
