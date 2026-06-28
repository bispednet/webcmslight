<?php
declare(strict_types=1);

namespace App\Services\Security;

use App\Core\Container;

/**
 * Antispam riusabile per i form pubblici (contatti, appuntamenti, recesso).
 *
 * Difese, in ordine:
 *  1. Honeypot — campo nascosto "website": se compilato è un bot.
 *  2. Time-trap — campo firmato "form_ts": rifiuta invii troppo veloci
 *     (i bot compilano e inviano in <1s) o con token scaduto/contraffatto.
 *  3. Euristiche contenuto — URL/BBCode/script non latini (cirillico, CJK).
 *  4. Cloudflare Turnstile — captcha invisibile, attivo SOLO se in .env.php
 *     sono presenti security.turnstile.site_key e secret_key.
 *  5. Throttle per IP — max N invii/ora per indirizzo (rete di sicurezza).
 *
 * L'esito distingue:
 *  - silent=true  → trattare come "successo finto" (non rivelare al bot la difesa)
 *  - silent=false → errore legittimo da mostrare all'utente (es. captcha mancante)
 */
final class SpamGuard
{
    private const MIN_FILL_SECONDS = 3;
    private const MAX_TOKEN_AGE = 10800; // 3h
    private const HONEYPOT_FIELD = 'website';
    private const TIMESTAMP_FIELD = 'form_ts';
    private const IP_MAX_PER_HOUR = 6;

    /**
     * @param array<string,mixed> $post
     * @return array{ok:bool,silent:bool,reason:string}
     */
    public static function check(array $post, ?string $ip, ?string $remoteIpForTurnstile = null): array
    {
        // 1. Honeypot
        if (trim((string)($post[self::HONEYPOT_FIELD] ?? '')) !== '') {
            return self::fail(true, 'honeypot');
        }

        // 2. Time-trap
        $tsResult = self::verifyTimestamp((string)($post[self::TIMESTAMP_FIELD] ?? ''));
        if ($tsResult !== null) {
            // token assente/contraffatto o invio istantaneo: bot → silenzioso
            return self::fail(true, $tsResult);
        }

        // 3. Euristiche contenuto
        $haystack = trim(
            (string)($post['name'] ?? '') . "\n" .
            (string)($post['message'] ?? '') . "\n" .
            (string)($post['notes'] ?? '') . "\n" .
            (string)($post['topic'] ?? '')
        );
        $contentReason = self::scanContent($haystack);
        if ($contentReason !== null) {
            return self::fail(true, $contentReason);
        }

        // 4. Turnstile (solo se configurato)
        if (self::turnstileEnabled()) {
            $token = (string)($post['cf-turnstile-response'] ?? '');
            if (!self::verifyTurnstile($token, $remoteIpForTurnstile ?? $ip)) {
                // captcha mancante/non superato: mostra errore con possibilità di riprovare
                return self::fail(false, 'turnstile');
            }
        }

        // 5. Throttle per IP
        if ($ip !== null && $ip !== '' && self::ipThrottled($ip)) {
            return self::fail(true, 'ip_throttle');
        }

        return ['ok' => true, 'silent' => false, 'reason' => ''];
    }

    // ── Campi nascosti per le view ──────────────────────────────────────────

    /** HTML dei campi nascosti (honeypot + time-trap) da inserire in ogni form. */
    public static function hiddenFields(): string
    {
        $ts = htmlspecialchars(self::makeTimestamp(), ENT_QUOTES, 'UTF-8');
        return '<input type="text" name="' . self::HONEYPOT_FIELD . '" tabindex="-1" autocomplete="off" '
            . 'class="hidden" aria-hidden="true" style="position:absolute;left:-9999px" value="">'
            . '<input type="hidden" name="' . self::TIMESTAMP_FIELD . '" value="' . $ts . '">';
    }

    /** Widget Turnstile (vuoto se non configurato). Va dentro il <form>. */
    public static function turnstileWidget(): string
    {
        if (!self::turnstileEnabled()) {
            return '';
        }
        $siteKey = htmlspecialchars(self::config('site_key'), ENT_QUOTES, 'UTF-8');
        return '<div class="cf-turnstile" data-sitekey="' . $siteKey . '" data-theme="auto"></div>';
    }

    /** Script Turnstile da inserire una volta in fondo alla pagina (vuoto se non configurato). */
    public static function turnstileScript(): string
    {
        if (!self::turnstileEnabled()) {
            return '';
        }
        return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }

    public static function isEnabled(): bool
    {
        return self::turnstileEnabled();
    }

    // ── Time-trap ───────────────────────────────────────────────────────────

    private static function makeTimestamp(): string
    {
        $ts = (string)time();
        return $ts . '.' . hash_hmac('sha256', $ts, self::appKey());
    }

    /** @return string|null reason se invalido, null se ok */
    private static function verifyTimestamp(string $value): ?string
    {
        if ($value === '' || !str_contains($value, '.')) {
            return 'ts_missing';
        }
        [$ts, $sig] = explode('.', $value, 2);
        if (!ctype_digit($ts)) {
            return 'ts_malformed';
        }
        $expected = hash_hmac('sha256', $ts, self::appKey());
        if (!hash_equals($expected, $sig)) {
            return 'ts_forged';
        }
        $elapsed = time() - (int)$ts;
        if ($elapsed < self::MIN_FILL_SECONDS) {
            return 'ts_too_fast';
        }
        if ($elapsed > self::MAX_TOKEN_AGE) {
            return 'ts_expired';
        }
        return null;
    }

    // ── Euristiche contenuto ────────────────────────────────────────────────

    private static function scanContent(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        // URL / domini sospetti / BBCode (spam di link)
        if (preg_match('~https?://|www\.|\[/?url|\[/?link~i', $text)) {
            return 'content_url';
        }
        if (preg_match('~\b[a-z0-9-]{1,30}\.(ru|cn|top|xyz|club|online|site|buzz|tk|info|loan|work)\b~i', $text)) {
            return 'content_domain';
        }

        // Script non latini frequenti negli spam (cirillico / CJK / arabo)
        if (preg_match_all('~[\p{Cyrillic}\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}\p{Arabic}\p{Thai}]~u', $text) >= 5) {
            return 'content_script';
        }

        // BBCode / marker tipici dei bot
        if (preg_match('~\[(b|i|u|color|img)\]~i', $text)) {
            return 'content_bbcode';
        }

        return null;
    }

    // ── Cloudflare Turnstile ────────────────────────────────────────────────

    private static function turnstileEnabled(): bool
    {
        return self::config('site_key') !== '' && self::config('secret_key') !== '';
    }

    private static function verifyTurnstile(string $token, ?string $ip): bool
    {
        if ($token === '') {
            return false;
        }
        $payload = http_build_query(array_filter([
            'secret'   => self::config('secret_key'),
            'response' => $token,
            'remoteip' => $ip,
        ]));

        $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $raw = false;

        if (function_exists('curl_init')) {
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
        }
        if ($raw === false) {
            $ctx = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $payload,
                'timeout' => 5,
            ]]);
            $raw = @file_get_contents($endpoint, false, $ctx);
        }
        if (!is_string($raw) || $raw === '') {
            // In caso di errore di rete non blocchiamo i clienti veri: lasciamo passare.
            return true;
        }
        $data = json_decode($raw, true);
        return is_array($data) && ($data['success'] ?? false) === true;
    }

    // ── Throttle per IP ─────────────────────────────────────────────────────

    private static function ipThrottled(string $ip): bool
    {
        $file = self::storageDir() . '/spam_throttle.json';
        $now = time();
        $window = 3600;

        $fp = @fopen($file, 'c+');
        if ($fp === false) {
            return false; // se non possiamo scrivere, non blocchiamo
        }
        @flock($fp, LOCK_EX);
        $contents = stream_get_contents($fp) ?: '';
        $map = json_decode($contents, true);
        if (!is_array($map)) {
            $map = [];
        }

        // prune finestra
        $hits = array_values(array_filter(
            is_array($map[$ip] ?? null) ? $map[$ip] : [],
            static fn($t) => is_int($t) && ($now - $t) < $window
        ));
        $throttled = count($hits) >= self::IP_MAX_PER_HOUR;
        $hits[] = $now;
        $map[$ip] = $hits;

        // prune IP scaduti per non far crescere il file all'infinito
        foreach ($map as $k => $v) {
            $kept = array_filter(is_array($v) ? $v : [], static fn($t) => is_int($t) && ($now - $t) < $window);
            if ($kept) {
                $map[$k] = array_values($kept);
            } else {
                unset($map[$k]);
            }
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($map));
        @flock($fp, LOCK_UN);
        fclose($fp);

        return $throttled;
    }

    // ── Logging ─────────────────────────────────────────────────────────────

    public static function logBlocked(string $form, string $reason, ?string $ip, array $post): void
    {
        $entry = [
            'ts'      => date('Y-m-d H:i:s'),
            'form'    => $form,
            'reason'  => $reason,
            'ip'      => $ip ?? '-',
            'name'    => mb_substr((string)($post['name'] ?? ''), 0, 80),
            'email'   => mb_substr((string)($post['email'] ?? ''), 0, 120),
            'message' => mb_substr(trim((string)($post['message'] ?? ($post['notes'] ?? ''))), 0, 200),
        ];
        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        @file_put_contents(self::storageDir(true) . '/spam.log', $line, FILE_APPEND | LOCK_EX);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private static function fail(bool $silent, string $reason): array
    {
        return ['ok' => false, 'silent' => $silent, 'reason' => $reason];
    }

    private static function config(string $key): string
    {
        $config = Container::get('config', []);
        $val = $config['security']['turnstile'][$key] ?? '';
        return is_string($val) ? trim($val) : '';
    }

    private static function appKey(): string
    {
        $config = Container::get('config', []);
        $key = $config['app']['key'] ?? 'bisped-fallback-key';
        return is_string($key) && $key !== '' ? $key : 'bisped-fallback-key';
    }

    private static function storageDir(bool $logs = false): string
    {
        $base = dirname(__DIR__, 3) . '/storage';
        $dir = $logs ? $base . '/logs' : $base . '/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }
}
