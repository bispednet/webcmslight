import fs from 'node:fs';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { pipeline } from 'node:stream/promises';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const privateDir = path.join(root, '.local');
fs.mkdirSync(path.join(privateDir, 'backups'), { recursive: true });
const remote = '/home/uu4c5pdm/domains/bisped.net/public_html';
const sshArgs = ['-F', 'NUL', '-i', path.join(process.env.USERPROFILE, '.ssh/bispednet_hostit_ed25519'), '-o', 'IdentitiesOnly=yes', '-o', 'BatchMode=yes', '-o', 'ConnectTimeout=15', '-o', 'StrictHostKeyChecking=yes', '-o', `UserKnownHostsFile=${path.join(process.env.USERPROFILE, '.ssh/known_hosts_bispednet')}`, 'uu4c5pdm@bisped.net'];
async function receive(command, filename, input) {
  const child = spawn('ssh', [...sshArgs, command], { windowsHide: true, stdio: ['pipe', 'pipe', 'pipe'] });
  let error = '';
  child.stderr.on('data', data => { error += data.toString(); });
  const complete = new Promise((resolve, reject) => {
    child.once('error', reject);
    child.once('close', code => code === 0 ? resolve() : reject(new Error(`Export SSH non riuscito (${code}): ${error.slice(0, 400)}`)));
  });
  child.stdin.end(input);
  await Promise.all([pipeline(child.stdout, fs.createWriteStream(filename + '.part', { mode: 0o600 })), complete]);
  fs.renameSync(filename + '.part', filename);
  console.log(`Copia privata completata: ${path.relative(root, filename)} (${fs.statSync(filename).size} byte)`);
}

const mode = process.argv[2] ?? 'config-db';
if (mode === 'config-db') {
  await receive(`cat '${remote}/.env.php'`, path.join(privateDir, 'production.env.php'));
  const stamp = new Date().toISOString().replace(/[:.]/g, '-');
  const dump = path.join(privateDir, 'backups', `production-${stamp}.sql`);
  const php = String.raw`<?php
$config=require '${remote}/.env.php';
$d=$config['database'];
$f=tempnam(sys_get_temp_dir(),'bisped_dump_');
chmod($f,0600);
function iniValue($v){return '"'.strtr((string)$v,["\\"=>"\\\\",'"'=>'\\"',"\n"=>"\\n","\r"=>"\\r"]).'"';}
try {
 $ini="[client]\nuser=".iniValue($d['username'])."\npassword=".iniValue($d['password'])."\nhost=".iniValue($d['host']??'localhost')."\nport=".(int)($d['port']??3306)."\n";
 if(!empty($d['socket'])) $ini.='socket='.iniValue($d['socket'])."\n";
 file_put_contents($f,$ini);
 $p=proc_open(['mysqldump','--defaults-extra-file='.$f,'--single-transaction','--quick','--skip-lock-tables','--skip-add-locks','--no-tablespaces','--set-gtid-purged=OFF','--column-statistics=0','--hex-blob','--default-character-set=utf8mb4',$d['database']],[0=>['file','/dev/null','r'],1=>STDOUT,2=>STDERR],$pipes);
 $code=is_resource($p)?proc_close($p):1;
} finally {unlink($f);}
exit($code);
`;
  await receive('php', dump, php);
  fs.writeFileSync(path.join(privateDir, 'latest-dump.json'), JSON.stringify({ path: path.relative(root, dump), createdAt: new Date().toISOString() }, null, 2));
} else if (mode === 'media') {
  await receive(`tar -C '${remote}/public' -czf - media`, path.join(privateDir, 'backups/media.tar.gz'));
} else {
  throw new Error('Usare config-db oppure media');
}
