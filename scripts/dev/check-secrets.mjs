import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const mode = process.argv[2] ?? '--working';
function git(args) {
  const result = spawnSync('git', args, { cwd: root, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error('Verifica Git non riuscita: aggiornare i riferimenti prima del push');
  return result.stdout;
}
const blocked = /^(?:\.cloudflared\/|\.local\/|runtime\/|github\/|\.deploy-ftp\.local$)|(?:^|\/)\.env(?:\..+)?\.php$|\.(?:pem|key|p12|pfx|sql\.gz|dump|backup)$/i;
const forbiddenPath = file => file !== '.env.example.php' && (/(?:^|\/)env\.php$/i.test(file) || (file.startsWith('.github/') && file !== '.github/workflows/deploy-hostit.yml') || blocked.test(file) || (file.endsWith('.sql') && file !== 'database/schema.sql'));
const secretsCode = String.raw`$all=[];foreach(['.local/production.env.php','.env.local.php','.local/ai-overrides.json','.local/environment.json']as$f){if(is_file($f))$all[$f]=str_ends_with($f,'.php')?require $f:json_decode(file_get_contents($f),true);}$out=[];$walk=function($a,$prefix='')use(&$walk,&$out){foreach($a as$k=>$v){$n=$prefix.'.'.$k;if(is_array($v))$walk($v,$n);elseif(is_string($v)&&strlen($v)>=10&&preg_match('/password|secret|token|api_key|ftp_pass|app\.key/i',$n))$out[]=$v;}};$walk($all);echo json_encode($out);`;
let secrets = [];
if (fs.existsSync(path.join(root, 'runtime/php/php.exe'))) {
  const result = spawnSync(path.join(root, 'runtime/php/php.exe'), ['-r', secretsCode], { cwd: root, encoding: 'utf8', windowsHide: true });
  if (result.status !== 0) throw new Error('Impossibile completare il controllo privato dei segreti');
  secrets = JSON.parse(result.stdout);
}
const problems = [];
function inspect(file, content) {
  if (forbiddenPath(file)) { problems.push(`File riservato: ${file}`); return; }
  if (/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----[\r\n]/.test(content) || /-----BEGIN ARGO TUNNEL TOKEN-----[\r\n]/.test(content)) problems.push(`Credenziale PEM: ${file}`);
  if (/\b(?:gh[pousr]_[A-Za-z0-9]{20,}|github_pat_[A-Za-z0-9_]{30,}|sk-[A-Za-z0-9_-]{20,}|AIza[0-9A-Za-z_-]{30,})\b/.test(content)) problems.push(`Formato di chiave API in: ${file}`);
  if (secrets.some(secret => content.includes(secret))) problems.push(`Valore segreto in: ${file}`);
}
let count = 0;
if (mode === '--push') {
  const input = fs.readFileSync(0, 'utf8');
  for (const line of input.trim().split(/\r?\n/).filter(Boolean)) {
    const [, localSha, , remoteSha] = line.split(/\s+/);
    if (!/^[0-9a-f]{40,64}$/.test(localSha ?? '') || /^0+$/.test(localSha)) continue;
    const range = /^0+$/.test(remoteSha ?? '') ? [localSha, '--not', '--remotes=origin'] : [`${remoteSha}..${localSha}`];
    const commits = git(['rev-list', ...range]).trim().split(/\r?\n/).filter(Boolean);
    for (const commit of commits) {
      const identity = git(['show', '-s', '--format=%an%n%ae%n%cn%n%ce', commit]).trim().split(/\r?\n/);
      if (identity[0] !== 'bispednet' || identity[2] !== 'bispednet' || identity[1] !== '111435701+bispednet@users.noreply.github.com' || identity[3] !== '111435701+bispednet@users.noreply.github.com') problems.push('Identita del commit non conforme al progetto bispednet');
      const message = git(['show', '-s', '--format=%B', commit]);
      if (secrets.some(secret => message.includes(secret))) problems.push('Segreto nel messaggio di commit');
      const files = [...new Set(git(['diff-tree', '-m', '--root', '--no-commit-id', '-r', '--name-only', '--diff-filter=ACMR', commit]).trim().split(/\r?\n/).filter(Boolean))];
      for (const file of files) { inspect(file, git(['show', `${commit}:${file}`])); count++; }
    }
  }
} else if (mode === '--staged') {
  for (const file of git(['diff', '--cached', '--name-only', '--diff-filter=ACMR', '-z']).split('\0').filter(Boolean)) { inspect(file, git(['show', `:${file}`])); count++; }
} else if (mode === '--working') {
  const files = new Set([...git(['diff', '--name-only', '-z']).split('\0'), ...git(['ls-files', '--others', '--exclude-standard', '-z']).split('\0')]);
  for (const file of files) if (file && fs.existsSync(path.join(root, file))) { inspect(file, fs.readFileSync(path.join(root, file), 'utf8')); count++; }
} else throw new Error('Modalita controllo non valida');
if (problems.length) { console.error('Operazione bloccata:\n' + [...new Set(problems)].join('\n')); process.exitCode = 1; }
else console.log(`Controllo segreti superato (${count} file)`);
