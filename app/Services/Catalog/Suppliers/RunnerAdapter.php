<?php
declare(strict_types=1);

namespace App\Services\Catalog\Suppliers;

/**
 * Adapter per il distributore Runner S.p.A.
 *
 * Formato tracciati Runner (file .txt, campi separati da "|", una riga per prodotto):
 *   - articoli.txt  (root FTP):     Codice|CodiceProduttore|CodiceEAN|DescProd|Produttore|
 *                                   Famiglia|CatMerc|DescCatMerc|Dispo|Arrivi|InPromo|DataFinePromo
 *   - {CODICE_CLIENTE}/prezzi.txt:  Codice|PrezzoPers   (prezzo di acquisto personalizzato)
 *   - immagini.txt  (root FTP):     Codice|Img|ImgTh
 *   - descp.txt     (root FTP):     Codice|Img|ImgTh|Descrizione   (descrizione estesa)
 *
 * Il prezzo sta in una cartella nominata col proprio codice cliente (es. C111445/prezzi.txt).
 * L'adapter scarica i file via FTP in una work_dir locale, poi joina per Codice.
 */
final class RunnerAdapter implements SupplierAdapterInterface
{
    public function __construct(private array $config)
    {
    }

    public function key(): string
    {
        return 'runner';
    }

    private int $capturedAt = 0;

    public function capturedAt(): int { return $this->capturedAt; }

    public function fetchProducts(): array { return $this->loadProducts(true); }

    /** Prezzi, appartenenza al catalogo e stock vengono sempre letti insieme. */
    public function fetchCommercialProducts(): array { return $this->loadProducts(false); }

    public function fetchAvailability(): array
    {
        $out = [];
        foreach ($this->fetchCommercialProducts() as $product) $out[$product['sku']] = $product['stock'];
        return $out;
    }

    private function loadProducts(bool $details): array
    {
        $workDir = rtrim((string)($this->config['work_dir'] ?? ''), '/\\') ?: sys_get_temp_dir() . '/runner-feed';
        if (!is_dir($workDir) && !mkdir($workDir, 0700, true) && !is_dir($workDir)) throw new \RuntimeException('Directory Runner non disponibile');
        if (empty($this->config['skip_download'])) {
            $directory = $this->downloadFeed($workDir, $details);
        } else {
            $directory = $workDir;
            $this->validateSnapshot($directory);
            $oldest = min(array_map(static fn($f) => filemtime($directory . '/' . $f), ['articoli.txt','prezzi.txt','dispo.txt']));
            if (time() - $oldest > (int)($this->config['max_snapshot_age_seconds'] ?? 7200)) throw new \RuntimeException('Snapshot Runner locale scaduto');
            $this->capturedAt = $oldest;
        }
        $articoli = $this->parseArticoli($directory . '/articoli.txt');
        $prezzi = $this->parsePrezzi($directory . '/prezzi.txt');
        $dispo = $this->parseDispo($directory . '/dispo.txt');
        $immagini = $details && is_file($directory . '/immagini.txt') ? $this->parseImmagini($directory . '/immagini.txt') : [];
        $descr = $details && is_file($directory . '/descp.txt') ? $this->parseDescrizioni($directory . '/descp.txt') : [];
        $products = [];
        foreach ($articoli as $codice => $a) {
            $products[] = [
                'sku' => (string)$codice, 'name' => $a['name'], 'cost' => $prezzi[$codice] ?? 0,
                'category' => $a['category'], 'family' => $a['family'], 'brand' => $a['brand'],
                'stock' => max(0, $dispo[$codice] ?? 0), 'ean' => $a['ean'],
                'image_url' => $immagini[$codice] ?? '', 'description' => $descr[$codice] ?? $a['name'],
                'in_promo' => $a['in_promo'],
            ];
        }
        return $products;
    }

    private function validateSnapshot(string $directory): array
    {
        foreach (['articoli.txt','prezzi.txt','dispo.txt'] as $name) {
            $file = $directory . '/' . $name;
            if (!is_file($file) || filesize($file) === 0) throw new \RuntimeException('Feed Runner mancante o vuoto: ' . $name);
        }
        $counts = [
            'articles' => count($this->parseArticoli($directory . '/articoli.txt')),
            'prices' => count($this->parsePrezzi($directory . '/prezzi.txt')),
            'availability' => count($this->parseDispo($directory . '/dispo.txt')),
        ];
        $minimum = max(1, (int)($this->config['min_catalog_items'] ?? 500));
        if (min($counts) < $minimum) throw new \RuntimeException('Feed Runner incompleto: numerosita insufficiente');
        if (min($counts['prices'], $counts['availability']) < $counts['articles'] * (float)($this->config['minimum_file_coverage'] ?? 0.7)) throw new \RuntimeException('Feed Runner incompleto: prezzi o stock non coprono il catalogo');
        return $counts;
    }

    /** Nessun file della precedente fotografia viene riutilizzato dopo un errore. */
    private function downloadFeed(string $workDir, bool $details): string
    {
        $host = (string)($this->config['ftp_host'] ?? '');
        $user = (string)($this->config['ftp_user'] ?? '');
        $password = (string)($this->config['ftp_pass'] ?? '');
        $code = (string)($this->config['customer_code'] ?? $user);
        if ($host === '' || $user === '' || $password === '') throw new \RuntimeException('Credenziali FTP Runner mancanti');
        $connection = !empty($this->config['ftp_ssl']) ? @ftp_ssl_connect($host, (int)($this->config['ftp_port'] ?? 21), 20) : @ftp_connect($host, (int)($this->config['ftp_port'] ?? 21), 20);
        if (!$connection) throw new \RuntimeException('Connessione FTP Runner non disponibile');
        $directory = $workDir . '/batch-' . bin2hex(random_bytes(8));
        mkdir($directory, 0700, true);
        $capturedAt = time();
        try {
            $loginWarning = '';
            set_error_handler(static function (int $severity, string $message) use (&$loginWarning): bool { $loginWarning = $message; return true; }, E_WARNING);
            try { $loggedIn = ftp_login($connection, $user, $password); }
            finally { restore_error_handler(); }
            if (!$loggedIn) throw new \RuntimeException(str_contains(strtolower($loginWarning), 'tls authentication not allowed')
                ? 'Runner rifiuta TLS esplicito: verificare host, porta e ftp_ssl'
                : 'Login FTP Runner rifiutato: verificare host, credenziali e protocollo');
            if (!ftp_pasv($connection, true)) throw new \RuntimeException('Modalita FTP passiva non disponibile');
            foreach (['articoli.txt','dispo.txt'] as $name) {
                if (!@ftp_get($connection, $directory . '/' . $name, $name, FTP_BINARY)) throw new \RuntimeException('Download Runner fallito: ' . $name);
            }
            if (!@ftp_get($connection, $directory . '/prezzi.txt', $code . '/prezzi.txt', FTP_BINARY)
                && !@ftp_get($connection, $directory . '/prezzi.txt', 'prezzi.txt', FTP_BINARY)) throw new \RuntimeException('Download Runner fallito: prezzi.txt');
            if ($details) foreach (['immagini.txt','descp.txt'] as $name) @ftp_get($connection, $directory . '/' . $name, $name, FTP_BINARY);
            $counts = $this->validateSnapshot($directory);
            $pointer = $workDir . '/current.json';
            $previous = is_file($pointer) ? json_decode(file_get_contents($pointer), true) : null;
            foreach ($counts as $key => $count) if (!empty($previous['counts'][$key]) && $count < $previous['counts'][$key] * (float)($this->config['min_snapshot_ratio'] ?? 0.7)) throw new \RuntimeException('Feed Runner ridotto in modo anomalo: fotografia non attivata');
            $manifest = ['directory' => basename($directory), 'captured_at' => $capturedAt, 'counts' => $counts];
            $temporary = $pointer . '.' . bin2hex(random_bytes(4)) . '.tmp';
            file_put_contents($temporary, json_encode($manifest, JSON_THROW_ON_ERROR), LOCK_EX);
            if (!rename($temporary, $pointer)) throw new \RuntimeException('Attivazione snapshot Runner fallita');
            $this->capturedAt = $capturedAt;
            $this->pruneSnapshots($workDir, $directory);
            return $directory;
        } finally { ftp_close($connection); }
    }

    private function pruneSnapshots(string $workDir, string $current): void
    {
        $root = realpath($workDir);
        if ($root === false) return;
        $directories = glob($root . DIRECTORY_SEPARATOR . 'batch-*', GLOB_ONLYDIR) ?: [];
        usort($directories, static fn($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($directories, 3) as $directory) {
            $resolved = realpath($directory);
            if ($resolved === false || is_link($directory) || $resolved === realpath($current)
                || !str_starts_with(strtolower($resolved), strtolower($root . DIRECTORY_SEPARATOR))
                || !preg_match('/^batch-[a-f0-9]{16}$/', basename($resolved)) || filemtime($directory) > time() - 86400) continue;
            foreach (['articoli.txt','prezzi.txt','dispo.txt','immagini.txt','descp.txt'] as $name) if (is_file($resolved . '/' . $name)) unlink($resolved . '/' . $name);
            @rmdir($resolved);
        }
    }

    private function parseArticoli(string $path): array
    {
        $out = [];
        foreach ($this->readPipeLines($path) as $f) {
            // Codice|CodiceProduttore|CodiceEAN|DescProd|Produttore|Famiglia|CatMerc|DescCatMerc|Dispo|Arrivi|InPromo|DataFinePromo
            if (count($f) < 9) {
                continue;
            }
            $codice = trim($f[0]);
            // Salta l'header (prima riga: "Codice|CodiceProduttore|...")
            if ($codice === '' || strcasecmp($codice, 'Codice') === 0) {
                continue;
            }
            if (isset($out[$codice])) throw new \RuntimeException('SKU duplicato in articoli.txt');
            $out[$codice] = [
                'name'     => trim($f[3] ?? ''),
                'brand'    => trim($f[4] ?? ''),
                'family'   => trim($f[5] ?? ''),         // Famiglia merceologica
                'category' => trim($f[7] ?? ''),         // DescCatMerc
                'stock'    => (int)trim($f[8] ?? '0'),   // Dispo
                'ean'      => trim($f[2] ?? ''),
                'in_promo' => trim($f[10] ?? '0') === '1',
            ];
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    private function parseDispo(string $path): array
    {
        $out = [];
        foreach ($this->readPipeLines($path) as $f) {
            // Codice|Dispo
            if (count($f) < 2) {
                continue;
            }
            $codice = trim($f[0]);
            if ($codice === '' || strcasecmp($codice, 'Codice') === 0) {
                continue;
            }
            if (isset($out[$codice])) throw new \RuntimeException('SKU duplicato in dispo.txt');
            $value = trim($f[1]);
            $out[$codice] = preg_match('/^-?\d+\+?$/', $value) ? max(0, (int)$value) : 0;
        }

        return $out;
    }

    /**
     * @return array<string, float>
     */
    private function parsePrezzi(string $path): array
    {
        $out = [];
        foreach ($this->readPipeLines($path) as $f) {
            // Codice|PrezzoPers
            if (count($f) < 2) {
                continue;
            }
            $sku = trim($f[0]);
            if ($sku === '' || strcasecmp($sku, 'Codice') === 0) continue;
            if (isset($out[$sku])) throw new \RuntimeException('SKU duplicato in prezzi.txt');
            $out[$sku] = $this->parsePrice(trim($f[1]));
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function parseImmagini(string $path): array
    {
        $out = [];
        foreach ($this->readPipeLines($path) as $f) {
            // Codice|Img|ImgTh
            if (count($f) < 2) {
                continue;
            }
            $img = trim($f[1]);
            if ($img !== '') {
                $out[trim($f[0])] = $img;
            }
        }

        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function parseDescrizioni(string $path): array
    {
        $out = [];
        foreach ($this->readPipeLines($path) as $f) {
            // Codice|Img|ImgTh|Descrizione
            if (count($f) < 4) {
                continue;
            }
            $d = trim($f[3]);
            if ($d !== '') {
                $out[trim($f[0])] = $d;
            }
        }

        return $out;
    }

    /**
     * Legge un file pipe-delimited Runner riga per riga.
     *
     * @return \Generator<int, array<int,string>>
     */
    private function readPipeLines(string $path): \Generator
    {
        if (!is_file($path)) {
            return;
        }
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return;
        }
        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }
            // Runner usa encoding latin1 su alcuni file: normalizza a UTF-8
            if (!mb_check_encoding($line, 'UTF-8')) {
                $line = mb_convert_encoding($line, 'UTF-8', 'Windows-1252');
            }
            yield explode('|', $line);
        }
        fclose($handle);
    }

    private function parsePrice(string $value): float
    {
        $value = trim(preg_replace('/^(?:EUR|€)\s*|\s*(?:EUR|€)$/i', '', trim($value)) ?? '');
        if (!preg_match('/^[0-9][0-9,.]*$/D', $value)) return 0;
        $comma = strrpos($value, ','); $dot = strrpos($value, '.');
        if ($comma !== false && $dot !== false) {
            $decimal = $comma > $dot ? ',' : '.';
            $last = max($comma, $dot);
            $fraction = substr($value, $last + 1);
            $integer = substr($value, 0, $last);
            $thousands = $decimal === ',' ? '.' : ',';
            if (!in_array(strlen($fraction), [1,2,4], true) || !preg_match('/^\d{1,3}(?:' . preg_quote($thousands, '/') . '\d{3})+$/D', $integer)) return 0;
            $value = str_replace($decimal === ',' ? '.' : ',', '', $value);
            $value = str_replace($decimal, '.', $value);
        } elseif ($comma !== false) $value = str_replace(',', '.', $value);
        if (!preg_match('/^\d+(?:\.\d{1,4})?$/', $value)) return 0;
        if (str_contains($value, '.') && strlen(substr($value, strrpos($value, '.') + 1)) === 3) return 0;
        $price = (float)$value;
        return is_finite($price) && $price > 0 ? $price : 0;
    }
}
