import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import http from 'node:http';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const cfg = JSON.parse(fs.readFileSync(path.join(root, '.local/environment.json'), 'utf8'));
const base = cfg.local_url;
// Le risposte vengono sempre consumate, anche con il server PHP HTTP/1.0.
function request(url, options = {}) {
  return new Promise((resolve, reject) => {
    const req = http.request(url, { method: options.method ?? 'GET', headers: { Connection: 'close', ...options.headers } }, response => {
      const chunks = [];
      response.on('data', chunk => chunks.push(chunk));
      response.on('end', () => {
        const headers = new Headers();
        for (const [key, values] of Object.entries(response.headers)) {
          for (const value of Array.isArray(values) ? values : [values]) if (value !== undefined) headers.append(key, value);
        }
        resolve({ status: response.statusCode, headers, body: Buffer.concat(chunks).toString('utf8') });
      });
      response.on('error', reject);
    });
    req.on('error', reject); req.setTimeout(15000, () => req.destroy(new Error('Timeout HTTP locale')));
    req.end(options.body?.toString());
  });
}
for (const endpoint of ['/', '/products', '/blog', '/login', '/health/db', '/assets/css/app.css']) {
  const response = await request(base + endpoint);
  assert.equal(response.status, 200, endpoint);
  console.log(`OK ${endpoint}`);
}
for (const endpoint of ['/.env.php', '/.local/production.env.php', '/.cloudflared/cert-yed1z.ai.pem', '/install.php', '/_setup-check.php?key=bisped-diag-2026']) {
  assert.equal((await request(base + endpoint)).status, 404, endpoint);
}
console.log('OK file privati e diagnostica non accessibili dal web');
const proxy = `http://127.0.0.1:${cfg.proxy_port}`;
assert.equal((await request(proxy)).status, 401, 'Protezione staging');
const auth = `Basic ${Buffer.from(`${cfg.staging_username}:${cfg.staging_password}`).toString('base64')}`;
const stage = await request(proxy, { headers: { Authorization: auth } });
assert.equal(stage.status, 200);
assert.match(stage.headers.get('x-robots-tag'), /noindex/);
assert.equal((await request(proxy + '/.env.php', { headers: { Authorization: auth } })).status, 404);
console.log('OK staging con password e noindex');

const cookies = new Map();
const remember = response => { for (const line of response.headers.getSetCookie()) { const [pair] = line.split(';'); const split = pair.indexOf('='); cookies.set(pair.slice(0, split), pair.slice(split + 1)); } };
const cookie = () => [...cookies].map(([k, v]) => `${k}=${v}`).join('; ');
const login = await request(base + '/login'); remember(login);
const html = login.body;
const csrf = html.match(/name="csrf_token"\s+value="([^"]+)"/)?.[1];
assert.ok(csrf, 'Token CSRF del login');
const submitted = await request(base + '/login', {
  method: 'POST', redirect: 'manual', headers: { Cookie: cookie(), 'Content-Type': 'application/x-www-form-urlencoded', Origin: base },
  body: new URLSearchParams({ csrf_token: csrf, email: 'admin@bisped.local', password: cfg.admin_password }),
});
remember(submitted);
assert.ok([302, 303].includes(submitted.status), 'Redirect dopo login');
assert.equal(submitted.headers.get('location'), '/admin/dashboard');
const dashboard = await request(base + '/admin/dashboard', { headers: { Cookie: cookie() }, redirect: 'manual' });
assert.equal(dashboard.status, 200, 'Dashboard autenticata');
console.log('OK login e dashboard amministratore locale');
const pricingPage = await request(base + '/admin/settings', { headers: { Cookie: cookie() } });
assert.equal(pricingPage.status, 200);
for (const field of ['markup_low', 'markup_high', 'low_cost_limit', 'high_cost_limit', 'minimum_sale_markup']) assert.ok(pricingPage.body.includes(`name="${field}"`), 'Campo ricarico ' + field);
console.log('OK impostazioni delle fasce di ricarico');

assert.equal((await request(base + '/_dev/ai')).status, 302, 'Configurazione AI riservata');
const aiPage = await request(base + '/_dev/ai', { headers: { Cookie: cookie() } });
assert.equal(aiPage.status, 200);
assert.match(aiPage.body, /pc_configurator_model/);
const aiCsrf = aiPage.body.match(/name="csrf_token"\s+value="([^"]+)"/)?.[1];
assert.ok(aiCsrf);
assert.equal((await request(base + '/_dev/ai', { method: 'POST', headers: { Cookie: cookie(), 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'save' }) })).status, 419);
const profilesCode = String.raw`require 'app/bootstrap.php';$out=[];foreach(['concierge','editorial','pc_configurator']as$p){$s=config('gemini.'.$p,[]);$out[$p]=['provider'=>$s['provider']??config('ai.profiles.'.$p.'.provider',config('ai.provider','gemini')),'model'=>$s['model']??'','temperature'=>$s['temperature']??0.35];}echo json_encode($out);`;
const profileResult = spawnSync(path.join(root, 'runtime/php/php.exe'), ['-r', profilesCode], { cwd: root, encoding: 'utf8', windowsHide: true });
assert.equal(profileResult.status, 0);
const profiles = JSON.parse(profileResult.stdout);
const overridesPath = path.join(root, '.local/ai-overrides.json');
const originalOverrides = fs.existsSync(overridesPath) ? fs.readFileSync(overridesPath) : null;
const originalEnvironment = fs.readFileSync(path.join(root, '.local/environment.json'));
const fields = new URLSearchParams({ action: 'save', csrf_token: aiCsrf, allow_ai: 'on' });
for (const [profile, values] of Object.entries(profiles)) for (const [key, value] of Object.entries(values)) fields.set(profile + '_' + key, String(value));
fields.set('pc_configurator_model', 'bisped-staging-selftest');
try {
  const saved = await request(base + '/_dev/ai', { method: 'POST', headers: { Cookie: cookie(), 'Content-Type': 'application/x-www-form-urlencoded' }, body: fields });
  assert.equal(saved.status, 303);
  const changed = JSON.parse(fs.readFileSync(overridesPath, 'utf8'));
  assert.equal(changed.gemini.pc_configurator.model, 'bisped-staging-selftest');
  assert.equal(changed.gemini.concierge.model, profiles.concierge.model);
  const check = spawnSync(path.join(root, 'runtime/php/php.exe'), ['-r', profilesCode], { cwd: root, encoding: 'utf8', windowsHide: true });
  assert.equal(JSON.parse(check.stdout).pc_configurator.model, 'bisped-staging-selftest');
  console.log('OK cambio modello PC indipendente dal chatbot, applicato alle richieste');
} finally {
  if (originalOverrides === null) fs.rmSync(overridesPath, { force: true }); else fs.writeFileSync(overridesPath, originalOverrides);
  fs.writeFileSync(path.join(root, '.local/environment.json'), originalEnvironment);
}
if (process.argv.includes('--test-ai')) {
  const refreshedPage = await request(base + '/_dev/ai', { headers: { Cookie: cookie() } });
  const refreshedCsrf = refreshedPage.body.match(/name="csrf_token"\s+value="([^"]+)"/)?.[1];
  const tested = await request(base + '/_dev/ai', { method: 'POST', headers: { Cookie: cookie(), 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'test', csrf_token: refreshedCsrf, profile: 'concierge', prompt: 'Rispondi solo OK.' }) });
  const output = tested.body.match(/<pre>([\s\S]*?)<\/pre>/)?.[1];
  assert.ok(output?.trim(), 'Risposta reale del provider AI');
  console.log('OK risposta reale del modello chatbot');
}

const home = (await request(base)).body;
const images = [...home.matchAll(/<img\b[^>]*\bsrc="(\/media\/[^"?]+)[^"]*"/g)].map(match => match[1]);
for (const image of [...new Set(images)].slice(0, 10)) assert.equal((await request(base + image)).status, 200, image);
console.log(`OK immagini della home (${Math.min(new Set(images).size, 10)} verificate)`);
