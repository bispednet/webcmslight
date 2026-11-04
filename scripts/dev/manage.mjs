import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import net from 'node:net';
import crypto from 'node:crypto';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const filename = fileURLToPath(import.meta.url);
const root = path.resolve(path.dirname(filename), '../..');
const local = path.join(root, '.local');
const cfg = JSON.parse(fs.readFileSync(path.join(local, 'environment.json'), 'utf8'));
const controlUrl = `http://127.0.0.1:${cfg.control_port}`;
const action = process.argv[2] ?? 'status';
async function control(action = 'status') {
  return fetch(`${controlUrl}/${action}`, { method: action === 'stop' ? 'POST' : 'GET', headers: { Authorization: `Bearer ${cfg.control_token}` }, signal: AbortSignal.timeout(2500) });
}
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
const portOpen = port => new Promise(resolve => { const s = net.connect(port, '127.0.0.1'); s.once('connect', () => { s.destroy(); resolve(true); }); s.once('error', () => resolve(false)); });

if (action === 'status') {
  try { const response = await control(); if (!response.ok) throw new Error('Porta controllo occupata'); console.log(await response.text()); } catch { console.log('Ambiente locale fermo'); process.exitCode = 1; }
} else if (action === 'stop') {
  try {
    const response = await control('stop'); if (!response.ok) throw new Error('Arresto rifiutato');
    await response.text();
    for (let i = 0; i < 120; i++) {
      if (!fs.existsSync(path.join(local, 'manager-state.json')) && !(await portOpen(cfg.mysql_port))) { console.log('Ambiente locale arrestato'); process.exit(0); }
      await pause(250);
    }
    throw new Error('Arresto ancora in corso: consultare .local/logs');
  } catch (error) { if (fs.existsSync(path.join(local, 'manager-state.json'))) throw error; console.log('Ambiente locale gia fermo'); }
} else if (action === 'start') {
  try { const current = await control(); if (!current.ok) throw new Error('Porta occupata'); console.log('Ambiente locale gia avviato: ' + cfg.local_url); process.exit(0); } catch { /* Avvio iniziale. */ }
  const out = fs.openSync(path.join(local, 'logs/manager.log'), 'a');
  const err = fs.openSync(path.join(local, 'logs/manager-error.log'), 'a');
  const child = spawn(process.execPath, [filename, 'serve'], { cwd: root, detached: true, windowsHide: true, stdio: ['ignore', out, err] });
  child.unref(); fs.closeSync(out); fs.closeSync(err);
  let ready = false;
  for (let i = 0; i < 100; i++) { await pause(300); try { const response = await control(); if (response.ok) { ready = true; break; } } catch { /* Avvio in corso. */ } }
  if (!ready) throw new Error('Avvio non riuscito: vedere .local/logs/manager-error.log');
  console.log(`Sito locale pronto: ${cfg.local_url}\nLogin CMS: ${cfg.local_url}/login\nCredenziali: .local/ACCESSI-LOCALI.txt`);
} else if (action === 'serve') {
  const ports = [cfg.mysql_port, cfg.smtp_port, cfg.proxy_port, cfg.control_port, 8080];
  for (const port of ports) if (await portOpen(port)) throw new Error(`Porta locale ${port} gia occupata: nessun processo avviato`);
  const children = [];
  const servers = [];
  let controlServer;
  let stopping = false;
  function launch(executable, args, name, required = true) {
    const log = fs.openSync(path.join(local, `logs/${name}.log`), 'a');
    const child = spawn(executable, args, { cwd: root, windowsHide: true, stdio: ['ignore', log, log] });
    fs.closeSync(log); children.push(child);
    child.once('error', error => { console.error(`${name}: ${error.message}`); if (required) void stop(); });
    child.once('exit', code => { if (!stopping) { console.error(`${name} terminato (${code})`); if (required) void stop(); } });
    return child;
  }
  async function stop() {
    if (stopping) return; stopping = true;
    for (const server of servers) if (server !== controlServer) server.close();
    const shutdown = spawn(path.join(root, 'runtime/mysql-8.0.45-winx64/bin/mysqladmin.exe'), [`--defaults-extra-file=${path.join(local, 'mysql-root.ini')}`, 'shutdown'], { windowsHide: true, stdio: 'ignore' });
    await Promise.race([new Promise(resolve => shutdown.once('exit', resolve)), pause(10000)]);
    // La porta TCP si chiude prima che InnoDB abbia rilasciato i file su Windows.
    // Attendere l'uscita del monitor MySQL evita un riavvio durante lo shutdown.
    if (mysql.exitCode === null) await Promise.race([new Promise(resolve => mysql.once('exit', resolve)), pause(15000)]);
    for (const child of children) if (child.exitCode === null) child.kill();
    controlServer?.close();
    fs.rmSync(path.join(local, 'manager-state.json'), { force: true });
    setTimeout(() => process.exit(0), 300);
  }
  process.once('SIGINT', () => void stop()); process.once('SIGTERM', () => void stop());
  const mysql = launch(path.join(root, 'runtime/mysql-8.0.45-winx64/bin/mysqld.exe'), [`--defaults-file=${path.join(root, 'runtime/mysql.ini')}`, '--console'], 'mysql-server');
  for (let i = 0; i < 120 && !(await portOpen(cfg.mysql_port)); i++) { if (mysql.exitCode !== null) throw new Error('MySQL non avviato'); await pause(250); }
  if (!(await portOpen(cfg.mysql_port))) { await stop(); throw new Error('MySQL non risponde'); }
  launch(path.join(root, 'runtime/php/php.exe'), ['-c', path.join(root, 'runtime/php/php.ini'), '-S', '127.0.0.1:8080', '-t', path.join(root, 'public'), path.join(root, 'scripts/dev/router.php')], 'php-server');

  // SMTP locale: riceve e salva le email, senza alcuna consegna esterna.
  const smtp = net.createServer(socket => {
    socket.setTimeout(60000, () => socket.destroy());
    socket.on('error', () => {});
    socket.write('220 bisped.local ESMTP local-capture\r\n');
    let buffer = ''; let inData = false; let lines = []; let size = 0;
    socket.on('data', chunk => {
      size += chunk.length; if (size > 5 * 1024 * 1024) { socket.end('552 Message too large\r\n'); return; }
      buffer += chunk.toString('utf8');
      let index;
      while ((index = buffer.indexOf('\n')) !== -1) {
        const line = buffer.slice(0, index).replace(/\r$/, ''); buffer = buffer.slice(index + 1);
        if (inData) {
          if (line === '.') {
            fs.writeFileSync(path.join(local, 'mail', `${new Date().toISOString().replaceAll(':', '-')}-${crypto.randomBytes(3).toString('hex')}.eml`), lines.join('\r\n'));
            inData = false; lines = []; size = 0; socket.write('250 Captured locally\r\n');
          } else lines.push(line.startsWith('..') ? line.slice(1) : line);
        } else if (/^EHLO/i.test(line)) socket.write('250-bisped.local\r\n250 SIZE 5242880\r\n');
        else if (/^DATA$/i.test(line)) { inData = true; lines = []; socket.write('354 End with dot\r\n'); }
        else if (/^QUIT$/i.test(line)) socket.end('221 Bye\r\n');
        else socket.write('250 OK\r\n');
      }
    });
  });
  smtp.listen(cfg.smtp_port, '127.0.0.1'); servers.push(smtp);

  const expectedAuth = Buffer.from(`Basic ${Buffer.from(`${cfg.staging_username}:${cfg.staging_password}`).toString('base64')}`);
  const proxy = http.createServer((request, response) => {
    response.setHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    response.setHeader('Cache-Control', 'private, no-store');
    const actual = Buffer.from(request.headers.authorization ?? '');
    if (actual.length !== expectedAuth.length || !crypto.timingSafeEqual(actual, expectedAuth)) {
      response.writeHead(401, { 'WWW-Authenticate': 'Basic realm="Bisped staging", charset="UTF-8"' }); response.end('Anteprima Bisped protetta'); return;
    }
    if (!request.url.startsWith('/') || request.url.startsWith('//')) { response.writeHead(400); response.end(); return; }
    const headers = { ...request.headers, host: cfg.staging_host, 'x-forwarded-proto': 'https', 'x-forwarded-host': cfg.staging_host };
    delete headers.authorization; delete headers['proxy-authorization']; delete headers['proxy-connection'];
    const upstream = http.request({ hostname: '127.0.0.1', port: 8080, method: request.method, path: request.url, headers }, incoming => {
      response.writeHead(incoming.statusCode, { ...incoming.headers, 'x-robots-tag': 'noindex, nofollow, noarchive', 'cache-control': 'private, no-store' }); incoming.pipe(response);
    });
    upstream.on('error', () => { if (!response.headersSent) response.writeHead(502); response.end('Anteprima non disponibile'); });
    request.on('aborted', () => upstream.destroy()); request.pipe(upstream);
  });
  proxy.listen(cfg.proxy_port, '127.0.0.1'); servers.push(proxy);
  controlServer = http.createServer((request, response) => {
    if (request.headers.authorization !== `Bearer ${cfg.control_token}`) { response.writeHead(403); response.end(); return; }
    if (request.method === 'POST' && request.url === '/stop') { response.end('Arresto ambiente locale in corso'); void stop(); return; }
    response.setHeader('Content-Type', 'application/json');
    response.end(JSON.stringify({ running: !stopping, stopping, url: cfg.local_url, mysqlPort: cfg.mysql_port, emailCapturePort: cfg.smtp_port, stagingProxyPort: cfg.proxy_port, pid: process.pid }));
  });
  for (let i = 0; i < 100 && !(await portOpen(8080)); i++) await pause(100);
  controlServer.listen(cfg.control_port, '127.0.0.1'); servers.push(controlServer);
  fs.writeFileSync(path.join(local, 'manager-state.json'), JSON.stringify({ pid: process.pid, startedAt: new Date().toISOString() }));
  const tunnelConfig = path.join(root, '.cloudflared/staging.yml');
  if (fs.existsSync(tunnelConfig)) launch('cloudflared', ['tunnel', '--no-autoupdate', '--config', tunnelConfig, 'run'], 'cloudflared', false);
  if (cfg.auto_catalog_sync !== false) {
    let catalogJob;
    const synchronize = () => {
      if (stopping || (catalogJob && catalogJob.exitCode === null)) return;
      catalogJob = launch(path.join(root, 'runtime/php/php.exe'), [path.join(root, 'scripts/auto-update/import-products.php'), '--supplier=runner', '--mode=commercial'], 'catalog-sync', false);
    };
    setTimeout(synchronize, 1000).unref();
    setInterval(synchronize, Math.max(300, Number(cfg.catalog_sync_interval_seconds ?? 3600)) * 1000).unref();
  }
  console.log('Servizi Bisped locali avviati');
} else throw new Error('Usare start, stop oppure status');
