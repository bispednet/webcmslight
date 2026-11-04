import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import {fileURLToPath, pathToFileURL} from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const modulePath = path.join(root, '.local/browser-test/node_modules/playwright/index.mjs');
const {chromium} = await import(pathToFileURL(modulePath).href);
const php = String.raw`<?php require 'app/bootstrap.php';if(config('app.env')!=='local')throw new RuntimeException('Local only');$d=\App\Core\Database::connection();$out=[];foreach($d->query("SELECT p.id,p.name,p.slug,p.price FROM pc_builds b JOIN products p ON p.id=b.product_id WHERE p.sku LIKE 'BISBUILD-%'")->fetchAll(PDO::FETCH_ASSOC)as$p){$s=(new \App\Services\Catalog\PcCompatibilityService($d))->configuratorForProduct((int)$p['id']);$out[]=$p+['summary'=>$s];}echo json_encode($out);`;
const result = spawnSync(path.join(root, 'runtime/php/php.exe'), [], {cwd: root, input: php, encoding: 'utf8', windowsHide: true, maxBuffer: 10*1024*1024});
assert.equal(result.status, 0);
const builds = JSON.parse(result.stdout);
assert.ok(builds.length > 0);
for (const build of builds) {
    assert.ok(build.summary.valid && build.summary.stock.available, build.name);
    assert.equal(Number(build.price), build.summary.total);
    assert.ok(build.name.endsWith(build.summary.memory.total_gb+'GB'));
}
const target = builds.find(b => b.summary.memory.slots === 2 && b.summary.memory.quantity === 1 && b.summary.memory.choices.some(c=>c.quantity===2));
assert.ok(target, 'Build di prova con due moduli disponibili');
const browser = await chromium.launch({executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true});
try {
    const page = await browser.newPage({viewport: {width: 1365, height: 1000}});
    const errors = []; page.on('pageerror', error=>errors.push(error.message));
    const base = 'http://127.0.0.1:8080';
    const response = await page.goto(base+'/products/'+target.slug);
    assert.equal(response.status(),200);
    const quantity = page.locator('[data-ram-quantity]');
    await quantity.waitFor({state:'visible'});
    await page.waitForFunction(()=>document.querySelector('[data-ram-summary]')?.textContent.includes('slot'));
    assert.ok(!(await page.locator('[data-slot-meta="ram"]').textContent()).includes('no iGPU'));
    const choose = async q => {
        const response = page.waitForResponse(r=>r.url().includes('/configurator-options?') && new URL(r.url()).searchParams.get('ram_quantity')===String(q));
        await quantity.selectOption(String(q));
        const data = await (await response).json();
        await page.waitForFunction(q=>document.querySelector('[data-ram-quantity]')?.value===String(q)&&!document.querySelector('.pc-configurator__status')?.textContent.includes('Aggiorno'),q);
        return data;
    };
    const doubled = await choose(2);
    assert.ok(doubled.valid); assert.equal(doubled.memory.total_gb,target.summary.memory.total_gb*2);
    const unit = target.summary.options.ram.find(r=>r.id===target.summary.selected.ram).price;
    assert.equal(Math.round((doubled.total-target.summary.total)*100),Math.round(unit*100));
    assert.match(await page.locator('[data-ram-summary]').textContent(),new RegExp(doubled.memory.total_gb+' GB totali'));
    console.log('OK browser: RAM x2 aggiorna capacita, totale e stock');

    const board = doubled.options.motherboard.find(o=>o.specs.memory_slots===4);
    assert.ok(board,'Alternative con quattro slot presenti');
    await page.locator('[data-trigger-slot="motherboard"]').click();
    const changed = page.waitForResponse(r=>r.url().includes('/configurator-options?')&&new URL(r.url()).searchParams.get('motherboard')===String(board.id));
    await page.locator('[data-options-slot="motherboard"] [data-option-value="'+board.id+'"]').click();
    await (await changed).json();
    await page.waitForFunction(()=>Array.from(document.querySelector('[data-ram-quantity]')?.options||[]).some(o=>o.value==='4'));
    const four = await choose(4);assert.ok(four.valid);assert.equal(four.memory.modules,4);
    console.log('OK browser: scheda a quattro slot abilita RAM x4');

    const forged = new URL(base+'/products/'+target.slug+'/configurator-options');
    for(const [key,value] of Object.entries(target.summary.selected))forged.searchParams.set(key,String(value));
    forged.searchParams.set('ram_quantity','4');
    const blocked = await (await page.request.get(forged.href)).json();assert.equal(blocked.valid,false);assert.equal(blocked.total,0);
    forged.searchParams.set('ram_quantity','-2');assert.equal((await page.request.get(forged.href)).status(),422);
    console.log('OK HTTP: impossibile forzare quattro banchi su due slot');

    await page.route('**/configurator-options?*', async route=>{
        if(new URL(route.request().url()).searchParams.get('ram_quantity')==='1')await new Promise(resolve=>setTimeout(resolve,350));
        await route.continue();
    });
    await quantity.selectOption('1'); await quantity.selectOption('2');
    await page.waitForTimeout(1000);
    assert.equal(await quantity.inputValue(),'2');
    assert.ok((await page.locator('[data-ram-summary]').textContent()).includes('2 banchi'));
    assert.deepEqual(errors,[]);
    await page.locator('[data-config-row="ram"]').screenshot({path:path.join(root,'.local/pc-ram-browser.png')});
    console.log('OK browser: modifiche rapide conservano la selezione piu recente; nessun errore JavaScript');
} finally { await browser.close(); }
