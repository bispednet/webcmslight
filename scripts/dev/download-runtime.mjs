import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { Readable } from 'node:stream';
import { pipeline } from 'node:stream/promises';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const cache = path.join(root, 'runtime/downloads');
fs.mkdirSync(cache, { recursive: true });
async function download(url, filename, expected, algorithm = 'sha256') {
  const destination = path.join(cache, filename);
  if (!fs.existsSync(destination)) {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`Download ${filename}: HTTP ${response.status}`);
    await pipeline(Readable.fromWeb(response.body), fs.createWriteStream(destination + '.part'));
    fs.renameSync(destination + '.part', destination);
  }
  if (expected) {
    const hash = crypto.createHash(algorithm);
    for await (const chunk of fs.createReadStream(destination)) hash.update(chunk);
    if (hash.digest('hex').toLowerCase() !== expected.toLowerCase()) throw new Error(`Checksum errato: ${filename}`);
  }
  console.log(`Download verificato: ${filename}`);
  return destination;
}

const releases = await (await fetch('https://windows.php.net/downloads/releases/releases.json')).json();
const php = releases['8.3']['nts-vs16-x64'].zip;
const mysqlName = 'mysql-8.0.45-winx64.zip';
const mysqlUrl = `https://cdn.mysql.com/Downloads/MySQL-8.0/${mysqlName}`;
const checksumResponse = await fetch(mysqlUrl + '.md5');
if (!checksumResponse.ok) throw new Error('Checksum MySQL ufficiale non disponibile');
const mysqlMd5 = (await checksumResponse.text()).match(/\b[a-fA-F0-9]{32}\b/)?.[0];
if (!mysqlMd5) throw new Error('Checksum MySQL non valido');
const [phpZip, mysqlZip] = await Promise.all([
  download(`https://windows.php.net/downloads/releases/${php.path}`, php.path, php.sha256),
  download(mysqlUrl, mysqlName, mysqlMd5, 'md5'),
]);
for (const [archive, directory, executable] of [
  [phpZip, 'php', 'php.exe'],
  [mysqlZip, 'mysql-8.0.45-winx64', 'bin/mysqld.exe'],
]) {
  const target = path.join(root, 'runtime', directory);
  if (fs.existsSync(path.join(target, executable))) continue;
  fs.mkdirSync(directory === 'php' ? target : path.dirname(target), { recursive: true });
  const result = spawnSync('tar.exe', ['-xf', archive, '-C', directory === 'php' ? target : path.dirname(target)], { encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error(`Estrazione non riuscita: ${directory}`);
  console.log(`Runtime pronto: ${directory}`);
}
const cacert = await fetch('https://curl.se/ca/cacert.pem');
if (!cacert.ok) throw new Error('Download CA TLS non riuscito');
fs.writeFileSync(path.join(root, 'runtime/php/cacert.pem'), await cacert.text());
fs.writeFileSync(path.join(root, 'runtime/runtime-versions.json'), JSON.stringify({ php: releases['8.3'].version, mysql: '8.0.45', phpSha256: php.sha256, mysqlMd5 }, null, 2));
