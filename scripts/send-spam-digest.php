<?php
declare(strict_types=1);

/**
 * Riepilogo antispam giornaliero.
 *
 * Legge storage/logs/spam.log (una riga JSON per tentativo bloccato, scritte da
 * App\Services\Security\SpamGuard::logBlocked), seleziona i tentativi delle
 * ultime 24h e, SOLO se ce ne sono, invia una email HTML ben formattata agli
 * amministratori. Nessun allegato. Alla fine ruota il log scartando le righe
 * più vecchie di 30 giorni, così il file non cresce all'infinito.
 *
 * Cron giornaliero (HOST.it / DirectAdmin), es. alle 07:00:
 *   0 7 * * * /usr/local/php81/bin/php /home/uu4c5pdm/domains/bisped.net/public_html/scripts/send-spam-digest.php >> /home/uu4c5pdm/domains/bisped.net/public_html/storage/logs/spam-digest-cron.log 2>&1
 */

$root = dirname(__DIR__);
$logFile = $root . '/storage/logs/spam.log';

$config = is_file($root . '/.env.php') ? require $root . '/.env.php' : [];

// Finestra: ultime 24 ore
$windowStart = time() - 86400;
$retentionStart = time() - (30 * 86400);

if (!is_file($logFile)) {
    fwrite(STDOUT, "[spam-digest] nessun log, niente da inviare.\n");
    exit(0);
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

$recent = [];   // tentativi nelle ultime 24h
$kept = [];     // righe da conservare (ultimi 30 giorni) per la rotazione

foreach ($lines as $line) {
    $row = json_decode($line, true);
    if (!is_array($row) || empty($row['ts'])) {
        continue;
    }
    $t = strtotime((string)$row['ts']);
    if ($t === false) {
        continue;
    }
    if ($t >= $retentionStart) {
        $kept[] = $line;
    }
    if ($t >= $windowStart) {
        $recent[] = $row;
    }
}

// Rotazione log (best effort)
if (count($kept) !== count($lines)) {
    @file_put_contents($logFile, $kept ? implode("\n", $kept) . "\n" : '', LOCK_EX);
}

if (!$recent) {
    fwrite(STDOUT, "[spam-digest] 0 tentativi nelle ultime 24h, nessuna email.\n");
    exit(0);
}

// ── Destinatari ──────────────────────────────────────────────────────────────
$recipients = $config['google']['admin_emails']
    ?? $config['admin_emails']
    ?? ['negozio@bisped.net'];
$recipients = array_values(array_filter(array_map('strval', (array)$recipients)));
if (!$recipients) {
    $recipients = ['negozio@bisped.net'];
}

$fromAddress = (string)($config['mail']['from_address'] ?? 'noreply@bisped.net');
$fromName = (string)($config['mail']['from_name'] ?? 'bisp&d');

// ── Etichette leggibili dei motivi ───────────────────────────────────────────
$reasonLabels = [
    'honeypot'        => 'Campo trappola compilato (bot)',
    'ts_missing'      => 'Token di sicurezza assente',
    'ts_malformed'    => 'Token di sicurezza non valido',
    'ts_forged'       => 'Token di sicurezza manomesso',
    'ts_too_fast'     => 'Invio troppo rapido (bot automatico)',
    'ts_expired'      => 'Token scaduto',
    'content_url'     => 'Link sospetto nel messaggio',
    'content_domain'  => 'Dominio sospetto nel messaggio',
    'content_script'  => 'Testo in alfabeto non latino',
    'content_bbcode'  => 'Codice BBCode nel messaggio',
    'ip_throttle'     => 'Troppi invii dallo stesso IP',
    'turnstile'       => 'Captcha non superato',
];
$formLabels = [
    'contact'     => 'Contatti',
    'withdrawal'  => 'Recesso',
    'appointment' => 'Appuntamenti',
];

$e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

// ── Riepilogo per motivo ─────────────────────────────────────────────────────
$byReason = [];
foreach ($recent as $row) {
    $r = (string)($row['reason'] ?? '?');
    $byReason[$r] = ($byReason[$r] ?? 0) + 1;
}
arsort($byReason);

$total = count($recent);
$dateLabel = date('d/m/Y');

// ── Costruzione HTML ─────────────────────────────────────────────────────────
$rowsHtml = '';
foreach (array_reverse($recent) as $row) {
    $reason = (string)($row['reason'] ?? '');
    $form = (string)($row['form'] ?? '');
    $rowsHtml .= '<tr>'
        . '<td style="padding:8px 10px;border-bottom:1px solid #eee;white-space:nowrap;color:#555;font-size:12px">' . $e((string)($row['ts'] ?? '')) . '</td>'
        . '<td style="padding:8px 10px;border-bottom:1px solid #eee;font-size:12px">' . $e($formLabels[$form] ?? $form) . '</td>'
        . '<td style="padding:8px 10px;border-bottom:1px solid #eee;font-size:12px">' . $e($reasonLabels[$reason] ?? $reason) . '</td>'
        . '<td style="padding:8px 10px;border-bottom:1px solid #eee;font-size:12px;color:#777">' . $e((string)($row['ip'] ?? '')) . '</td>'
        . '<td style="padding:8px 10px;border-bottom:1px solid #eee;font-size:12px">' . $e((string)($row['name'] ?? '')) . '</td>'
        . '<td style="padding:8px 10px;border-bottom:1px solid #eee;font-size:12px;color:#777">' . $e((string)($row['email'] ?? '')) . '</td>'
        . '</tr>';
}

$summaryHtml = '';
foreach ($byReason as $reason => $count) {
    $label = $reasonLabels[$reason] ?? $reason;
    $summaryHtml .= '<span style="display:inline-block;background:#f4f4f5;border:1px solid #e4e4e7;border-radius:999px;padding:4px 12px;margin:0 6px 6px 0;font-size:12px;color:#3f3f46">'
        . $e($label) . ' · <strong>' . $count . '</strong></span>';
}

$html = '<!doctype html><html lang="it"><body style="margin:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#18181b">'
    . '<div style="max-width:680px;margin:0 auto;padding:24px">'
    . '<div style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e4e4e7">'
    . '<div style="background:#d11920;padding:20px 24px">'
    . '<h1 style="margin:0;color:#fff;font-size:18px">bisp&amp;d · Riepilogo antispam</h1>'
    . '<p style="margin:4px 0 0;color:#fde2e2;font-size:13px">' . $e($dateLabel) . ' — ultime 24 ore</p>'
    . '</div>'
    . '<div style="padding:24px">'
    . '<p style="margin:0 0 16px;font-size:14px">Nelle ultime 24 ore l\'antispam ha bloccato <strong>' . $total . '</strong> '
    . ($total === 1 ? 'tentativo' : 'tentativi') . ' dai form del sito. Nessuna azione richiesta: sono già stati fermati.</p>'
    . '<div style="margin:0 0 20px">' . $summaryHtml . '</div>'
    . '<table style="width:100%;border-collapse:collapse;border:1px solid #eee;border-radius:8px;overflow:hidden">'
    . '<thead><tr style="background:#fafafa;text-align:left">'
    . '<th style="padding:8px 10px;border-bottom:1px solid #eee;font-size:11px;color:#71717a;text-transform:uppercase">Data/ora</th>'
    . '<th style="padding:8px 10px;border-bottom:1px solid #eee;font-size:11px;color:#71717a;text-transform:uppercase">Form</th>'
    . '<th style="padding:8px 10px;border-bottom:1px solid #eee;font-size:11px;color:#71717a;text-transform:uppercase">Motivo</th>'
    . '<th style="padding:8px 10px;border-bottom:1px solid #eee;font-size:11px;color:#71717a;text-transform:uppercase">IP</th>'
    . '<th style="padding:8px 10px;border-bottom:1px solid #eee;font-size:11px;color:#71717a;text-transform:uppercase">Nome</th>'
    . '<th style="padding:8px 10px;border-bottom:1px solid #eee;font-size:11px;color:#71717a;text-transform:uppercase">Email</th>'
    . '</tr></thead><tbody>' . $rowsHtml . '</tbody></table>'
    . '<p style="margin:20px 0 0;font-size:12px;color:#a1a1aa">Report automatico generato da bisped.net. '
    . 'Le difese attive: honeypot, time-trap, filtro contenuti, throttle IP'
    . (!empty($config['security']['turnstile']['site_key']) ? ', Cloudflare Turnstile' : '') . '.</p>'
    . '</div></div></div></body></html>';

$subject = sprintf('[bisp&d] Antispam: %d %s bloccati nelle ultime 24h',
    $total, $total === 1 ? 'tentativo' : 'tentativi');

$headers = 'From: ' . $fromName . ' <' . $fromAddress . ">\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=utf-8\r\n";
$headers .= "X-Mailer: bisp&d CMS\r\n";

$sent = 0;
foreach ($recipients as $to) {
    if (@mail($to, $subject, $html, $headers)) {
        $sent++;
    }
}

fwrite(STDOUT, sprintf("[spam-digest] %d tentativi, email inviata a %d/%d destinatari.\n", $total, $sent, count($recipients)));
exit(0);
