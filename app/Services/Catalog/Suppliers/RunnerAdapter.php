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

    public function fetchProducts(): array
    {
        $workDir = rtrim((string)($this->config['work_dir'] ?? ''), '/');
        if ($workDir === '') {
            $workDir = sys_get_temp_dir() . '/runner-feed';
        }
        if (!is_dir($workDir)) {
            mkdir($workDir, 0755, true);
        }

        // Scarica i tracciati se non in modalità "solo-locale"
        if (empty($this->config['skip_download'])) {
            $this->downloadFeed($workDir);
        }

        $articoli = $this->parseArticoli($workDir . '/articoli.txt');
        $prezzi   = $this->parsePrezzi($workDir . '/prezzi.txt');
        $immagini = $this->parseImmagini($workDir . '/immagini.txt');
        $descr    = $this->parseDescrizioni($workDir . '/descp.txt');

        // La disponibilità autorevole è in dispo.txt (aggiornato ogni ora);
        // se presente, sovrascrive il valore di articoli.txt.
        $dispo = $this->parseDispo($workDir . '/dispo.txt');

        $products = [];
        foreach ($articoli as $codice => $a) {
            $cost = $prezzi[$codice] ?? 0.0;
            if ($cost <= 0) {
                continue; // niente prezzo personalizzato = non vendibile per noi
            }
            $stock = $dispo[$codice] ?? $a['stock'];
            $products[] = [
                'sku'         => $codice,
                'name'        => $a['name'],
                'cost'        => $cost,
                'category'    => $a['category'],
                'family'      => $a['family'],
                'brand'       => $a['brand'],
                'stock'       => $stock,
                'ean'         => $a['ean'],
                'image_url'   => $immagini[$codice] ?? '',
                'description' => $descr[$codice] ?? $a['name'],
                'in_promo'    => $a['in_promo'],
            ];
        }

        return $products;
    }

    /**
     * Modalità leggera: scarica solo dispo.txt e restituisce [sku => quantità].
     * Usata dal cron disponibilità (ogni 6h) senza scaricare l'intero listino.
     *
     * @return array<string,int>
     */
    public function fetchAvailability(): array
    {
        $workDir = rtrim((string)($this->config['work_dir'] ?? ''), '/') ?: sys_get_temp_dir() . '/runner-feed';
        if (!is_dir($workDir)) {
            mkdir($workDir, 0755, true);
        }
        if (empty($this->config['skip_download'])) {
            $this->downloadOne($workDir, 'dispo.txt');
        }

        return $this->parseDispo($workDir . '/dispo.txt');
    }

    // ── FTP download ───────────────────────────────────────────────────────
    private function downloadFeed(string $workDir): void
    {
        $host = (string)($this->config['ftp_host'] ?? '');
        $user = (string)($this->config['ftp_user'] ?? '');
        $pass = (string)($this->config['ftp_pass'] ?? '');
        $code = (string)($this->config['customer_code'] ?? $user);
        $port = (int)($this->config['ftp_port'] ?? 21);
        $ssl  = !empty($this->config['ftp_ssl']);

        if ($host === '' || $user === '' || $pass === '') {
            throw new \RuntimeException('FTP Runner non configurato: imposta catalog.runner.ftp_host/ftp_user/ftp_pass in .env.php');
        }

        $conn = $ssl ? @ftp_ssl_connect($host, $port, 20) : @ftp_connect($host, $port, 20);
        if (!$conn) {
            throw new \RuntimeException("Connessione FTP Runner fallita ({$host}:{$port}). Verifica host e che non sia dietro proxy.");
        }
        if (!@ftp_login($conn, $user, $pass)) {
            ftp_close($conn);
            throw new \RuntimeException('Login FTP Runner fallito: credenziali errate.');
        }
        ftp_pasv($conn, true);

        // File in root
        foreach (['articoli.txt', 'immagini.txt', 'descp.txt'] as $file) {
            @ftp_get($conn, $workDir . '/' . $file, $file, FTP_BINARY);
        }
        // dispo.txt: disponibilità autorevole aggiornata ogni ora
        @ftp_get($conn, $workDir . '/dispo.txt', 'dispo.txt', FTP_BINARY);

        // prezzi.txt nella cartella del codice cliente
        if (!@ftp_get($conn, $workDir . '/prezzi.txt', $code . '/prezzi.txt', FTP_BINARY)) {
            // fallback: alcuni account espongono prezzi.txt in root
            @ftp_get($conn, $workDir . '/prezzi.txt', 'prezzi.txt', FTP_BINARY);
        }

        ftp_close($conn);
    }

    /**
     * Scarica un singolo file dal server FTP Runner (per il sync disponibilità).
     */
    private function downloadOne(string $workDir, string $file): void
    {
        $host = (string)($this->config['ftp_host'] ?? '');
        $user = (string)($this->config['ftp_user'] ?? '');
        $pass = (string)($this->config['ftp_pass'] ?? '');
        $port = (int)($this->config['ftp_port'] ?? 21);
        $ssl  = !empty($this->config['ftp_ssl']);
        if ($host === '' || $user === '' || $pass === '') {
            throw new \RuntimeException('FTP Runner non configurato.');
        }
        $conn = $ssl ? @ftp_ssl_connect($host, $port, 20) : @ftp_connect($host, $port, 20);
        if (!$conn || !@ftp_login($conn, $user, $pass)) {
            if ($conn) {
                ftp_close($conn);
            }
            throw new \RuntimeException('Connessione/login FTP Runner fallita.');
        }
        ftp_pasv($conn, true);
        @ftp_get($conn, $workDir . '/' . $file, $file, FTP_BINARY);
        ftp_close($conn);
    }

    // ── Parsing ────────────────────────────────────────────────────────────
    /**
     * @return array<string, array{name:string,brand:string,family:string,category:string,stock:int,ean:string,in_promo:bool}>
     */
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
            $out[$codice] = (int)preg_replace('/\D+/', '', trim($f[1]));
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
            $out[trim($f[0])] = $this->parsePrice(trim($f[1]));
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
        if ($value === '') {
            return 0.0;
        }
        $value = preg_replace('/[^\d,.\-]/', '', $value) ?? $value;
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return (float)$value;
    }
}
