import fs from 'node:fs';
import path from 'node:path';
import http from 'node:http';
import crypto from 'node:crypto';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const cfg = JSON.parse(fs.readFileSync(path.join(root, '.local/environment.json'), 'utf8'));
const php = code => {
  const guard = String.raw`<?php require 'app/bootstrap.php';if(config('app.env')!=='local'||config('database.host')!=='127.0.0.1'||(int)config('database.port')!==3307)throw new RuntimeException('Database non locale');$db=\App\Core\Database::connection();`;
  const result = spawnSync(path.join(root, 'runtime/php/php.exe'), [], { input: guard + code, cwd: root, encoding: 'utf8', windowsHide: true });
  assert.equal(result.status, 0, 'Query di test sul solo database locale');
  return result.stdout ? JSON.parse(result.stdout) : null;
};
let cookie = '';
const request = (endpoint, body) => new Promise((resolve, reject) => {
  const req = http.request(cfg.local_url + endpoint, { method: body ? 'POST' : 'GET', headers: { Connection: 'close', Cookie: cookie, ...(body ? { 'Content-Type': 'application/json' } : {}) } }, response => {
    if (response.headers['set-cookie']) cookie = response.headers['set-cookie'].map(line => line.split(';')[0]).join('; ');
    const chunks = [];
    response.on('data', chunk => chunks.push(chunk));
    response.on('end', () => resolve({ status: response.statusCode, body: Buffer.concat(chunks).toString('utf8') }));
  });
  req.on('error', reject); req.setTimeout(30000, () => req.destroy(new Error('Timeout checkout di prova')));
  req.end(body ? JSON.stringify(body) : undefined);
});
const token = async () => {
  const response = await request('/login');
  const value = response.body.match(/name="csrf_token"\s+value="([^"]+)"/)?.[1];
  assert.ok(value); return value;
};
const products = php(String.raw`echo json_encode($db->query("SELECT id,slug,is_payment_product,payment_method FROM products WHERE sku='FA808UP-S9023W' OR (supplier_status='active' AND stock_qty>0) ORDER BY (sku='FA808UP-S9023W') DESC LIMIT 2")->fetchAll(PDO::FETCH_ASSOC));`);
assert.equal(products.length, 2);
const retired = products[0]; const available = products[1];
const publicId = crypto.randomBytes(16).toString('hex');
const paypalId = 'BISPED-LOCAL-TEST-' + crypto.randomBytes(8).toString('hex');
const countBefore = php("echo json_encode((int)$db->query('SELECT COUNT(*) FROM product_payment_orders')->fetchColumn());");
try {
  php(`$db->exec("UPDATE products SET is_payment_product=1,payment_method='paypal' WHERE id IN (${products.map(p => Number(p.id)).join(',')})");`);
  let response = await request('/payments/paypal/orders/' + encodeURIComponent(retired.slug), { csrf_token: await token(), expected_amount: '1.00' });
  assert.equal(response.status, 409, 'Notebook ritirato respinto prima di creare un ordine PayPal');
  assert.equal(JSON.parse(response.body).error, 'catalog_changed');
  assert.equal(php("echo json_encode((int)$db->query('SELECT COUNT(*) FROM product_payment_orders')->fetchColumn());"), countBefore);
  console.log('OK notebook rimosso: nessun ordine o chiamata di pagamento');
  response = await request('/payments/paypal/orders/' + encodeURIComponent(available.slug), { csrf_token: await token(), expected_amount: '0.01' });
  assert.equal(response.status, 409, 'Vecchio prezzo o importo alterato respinto');
  console.log('OK prezzo alterato respinto dal controller');
  php(`$db->prepare("INSERT INTO product_payment_orders (public_id,product_id,product_name,product_slug,payment_method,delivery_method,status,currency,amount,paypal_order_id) VALUES (:public,:product,'Test locale',:slug,'paypal','pickup','created','EUR',1,:paypal)")->execute(['public'=>'${publicId}','product'=>${Number(retired.id)},'slug'=>${JSON.stringify(retired.slug)},'paypal'=>'${paypalId}']);`);
  response = await request('/payments/paypal/orders/' + paypalId + '/capture', { csrf_token: await token() });
  assert.equal(response.status, 409, 'Incasso di ordine gia approvato bloccato per articolo ritirato');
  console.log('OK controllo pre-incasso: nessuna cattura PayPal');
  php(`$db->exec("UPDATE product_payment_orders SET status='completed' WHERE public_id='${publicId}'");`);
  response = await request('/payments/paypal/orders/' + paypalId + '/capture', { csrf_token: await token() });
  assert.equal(response.status, 200);
  assert.equal(JSON.parse(response.body).status, 'COMPLETED');
  console.log('OK ordine completato idempotente: nessuna seconda cattura');
} finally {
  php(`$db->exec("DELETE FROM product_payment_orders WHERE public_id='${publicId}'");`);
  for (const product of products) php(`$db->prepare('UPDATE products SET is_payment_product=:enabled,payment_method=:method WHERE id=:id')->execute(['enabled'=>${Number(product.is_payment_product)},'method'=>${JSON.stringify(product.payment_method)},'id'=>${Number(product.id)}]);`);
}
