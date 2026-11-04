import fs from 'node:fs';
import path from 'node:path';
import https from 'node:https';
import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const cfg = JSON.parse(fs.readFileSync(path.join(root, '.local/environment.json'), 'utf8'));
// DNS via HTTPS per verificare un record appena creato anche quando il resolver
// locale conserva NXDOMAIN. La verifica TLS del nome host rimane attiva.
const dns = await (await fetch('https://cloudflare-dns.com/dns-query?name=' + encodeURIComponent(cfg.staging_host) + '&type=A', { headers: { accept: 'application/dns-json' } })).json();
const addresses = (dns.Answer ?? []).filter(answer => answer.type === 1).map(answer => answer.data);
assert.ok(addresses.length, 'Record DNS pubblico');
const lookup = (_host, options, callback) => options.all ? callback(null, addresses.map(address => ({ address, family: 4 }))) : callback(null, addresses[0], 4);
const authorization = 'Basic ' + Buffer.from(cfg.staging_username + ':' + cfg.staging_password).toString('base64');
const cookies = new Map();
function request(endpoint, authenticated = true, body) {
  return new Promise((resolve, reject) => {
    const headers = { Connection: 'close', Cookie: [...cookies].map(([key, value]) => `${key}=${value}`).join('; '), ...(authenticated ? { Authorization: authorization } : {}), ...(body ? { 'Content-Type': 'application/x-www-form-urlencoded' } : {}) };
    const req = https.request('https://' + cfg.staging_host + endpoint, { method: body ? 'POST' : 'GET', family: 4, lookup, headers }, response => {
      for (const line of response.headers['set-cookie'] ?? []) { const pair = line.split(';')[0]; const split = pair.indexOf('='); cookies.set(pair.slice(0, split), pair.slice(split + 1)); }
      const chunks = [];
      response.on('data', chunk => chunks.push(chunk));
      response.on('end', () => resolve({ status: response.statusCode, headers: response.headers, body: Buffer.concat(chunks).toString('utf8') }));
    });
    req.on('error', reject); req.setTimeout(30000, () => req.destroy(new Error('Timeout staging')));
    req.end(body?.toString());
  });
}
assert.equal((await request('/', false)).status, 401);
console.log('OK anteprima esterna protetta da password');
const home = await request('/');
assert.equal(home.status, 200); assert.match(home.headers['x-robots-tag'], /noindex/);
assert.equal((await request('/products')).status, 200);
assert.equal(JSON.parse((await request('/health/db')).body).ok, true);
for (const endpoint of ['/.env.php', '/.env.local.php', '/.local/production.env.php', '/.cloudflared/staging-credentials.json']) assert.equal((await request(endpoint)).status, 404);
console.log('OK sito, catalogo, database e protezione file tramite HTTPS');
assert.equal((await request('/_dev/ai')).status, 302);
const login = await request('/login');
const csrf = login.body.match(/name="csrf_token"\s+value="([^"]+)"/)?.[1];
assert.ok(csrf);
const submitted = await request('/login', true, new URLSearchParams({ csrf_token: csrf, email: 'admin@bisped.local', password: cfg.admin_password }));
assert.ok([302, 303].includes(submitted.status)); assert.equal(submitted.headers.location, '/admin/dashboard');
assert.equal((await request('/admin/dashboard')).status, 200);
const settings = await request('/_dev/ai');
assert.equal(settings.status, 200); assert.match(settings.body, /pc_configurator_model/);
assert.match(settings.body, /editorial_model/); assert.match(settings.body, /concierge_model/);
console.log('OK login CMS e tre profili AI tramite tunnel pubblico');
