<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Services\Security\Csrf;
use App\Support\Flash;
use App\Support\Session;
use PDO;

final class IngestController extends Controller
{
    private PDO $db;
    private string $ingestScript;

    public function __construct()
    {
        $this->db           = Database::connection();
        $this->ingestScript = dirname(__DIR__, 3) . '/scripts/auto-update/ingest.php';
    }

    public function index(): void
    {
        $log     = $this->getRecentLog();
        $sources = $this->loadSources();
        $this->view('admin/ingest', compact('log', 'sources'));
    }

    public function run(): void
    {
        Session::ensureStarted();
        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify(is_string($token) ? $token : null)) {
            Flash::set('ingest_error', 'Token non valido.');
            $this->redirect('/admin/ingest');
        }

        $category = $_POST['category'] ?? 'all';
        $allowed  = ['all', 'smartphone', 'informatica', 'gaming', 'connettivita', 'energia'];
        if (!in_array($category, $allowed, true)) {
            $category = 'all';
        }

        $phpBin = escapeshellarg(dirname(__DIR__, 3) . '/runtime/bin/frankenphp');
        $script = escapeshellarg($this->ingestScript);
        $flag   = $category === 'all' ? '--all' : '--source=' . escapeshellarg($category);

        // Run async — output to log file
        $logFile = dirname(__DIR__, 3) . '/storage/ingest-' . date('Ymd-His') . '.log';
        $cmd     = "{$phpBin} php-cli {$script} {$flag} --verbose > " . escapeshellarg($logFile) . " 2>&1 &";
        exec($cmd);

        Flash::set('ingest_ok', "Ingestion avviata in background (categoria: {$category}). Aggiorna tra qualche minuto.");
        $this->redirect('/admin/ingest');
    }

    private function getRecentLog(): array
    {
        return $this->db->query(
            "SELECT * FROM ingest_log ORDER BY created_at DESC LIMIT 50"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    private function loadSources(): array
    {
        $file = dirname(__DIR__, 3) . '/scripts/auto-update/sources.json';
        if (!file_exists($file)) return [];
        $data = json_decode(file_get_contents($file), true) ?? [];
        $flat = [];
        foreach ($data as $cat => $items) {
            if (str_starts_with($cat, '_')) continue;
            foreach ($items as $src) {
                $flat[] = array_merge($src, ['_category' => $cat]);
            }
        }
        return $flat;
    }
}
