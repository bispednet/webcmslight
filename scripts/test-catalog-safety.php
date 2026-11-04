<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\Catalog\CatalogPricing;
use App\Services\Catalog\CatalogSaleGuard;
use App\Services\Catalog\ProductImporter;
use App\Services\Catalog\RunnerCheckoutVerifier;
use App\Services\Catalog\Suppliers\RunnerAdapter;
use App\Services\Catalog\Suppliers\SupplierAdapterInterface;

if (config('app.env') !== 'local' || config('database.host') !== '127.0.0.1' || (int)config('database.port') !== 3307) throw new RuntimeException('Test consentito solo sul MySQL locale');
$local = json_decode(file_get_contents(BASE_PATH . '/.local/environment.json'), true, 512, JSON_THROW_ON_ERROR);
$admin = new PDO('mysql:host=127.0.0.1;port=3307;charset=utf8mb4', 'root', $local['mysql_root_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$name = 'bisped_catalog_test_' . bin2hex(random_bytes(5));
$admin->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$db = new PDO('mysql:host=127.0.0.1;port=3307;dbname=' . $name . ';charset=utf8mb4', 'root', $local['mysql_root_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
$passed = 0;
$check = static function (bool $ok, string $label) use (&$passed): void { if (!$ok) throw new RuntimeException('FAIL: ' . $label); $passed++; echo 'OK ' . $label . PHP_EOL; };
$throws = static function (callable $test): bool { try { $test(); return false; } catch (Throwable) { return true; } };
$fixtureDirectory = BASE_PATH . '/.local/catalog-safety-fixtures-' . bin2hex(random_bytes(5));
mkdir($fixtureDirectory, 0700, true);
$configuration = ['vat' => .22, 'markup_default' => .10, 'markup_fixed' => 5, 'minimum_markup' => .05, 'min_catalog_items' => 1, 'min_snapshot_ratio' => .1, 'require_image' => false, 'max_age_seconds' => 7200];
$raw = static fn(string $sku, float $cost = 100, int $stock = 3): array => ['sku' => $sku, 'name' => 'Notebook prova ' . $sku, 'cost' => $cost, 'stock' => $stock, 'family' => 'NOTEBOOK E TABLET', 'category' => 'NOTEBOOK', 'image_url' => '', 'description' => 'Prova locale'];
$adapter = static fn(array $rows, string $supplier = 'runner'): SupplierAdapterInterface => new class($rows, $supplier) implements SupplierAdapterInterface {
    public function __construct(private array $rows, private string $supplier) {}
    public function key(): string { return $this->supplier; }
    public function fetchProducts(): array { return $this->rows; }
};
$row = static function (string $sku) use ($db): array { $q = $db->prepare('SELECT * FROM products WHERE sku=:sku'); $q->execute(['sku' => $sku]); return $q->fetch() ?: []; };
$files = static function (string $articles, string $prices, string $availability) use ($fixtureDirectory): void {
    foreach (['articoli.txt' => $articles, 'prezzi.txt' => $prices, 'dispo.txt' => $availability] as $file => $content) file_put_contents($fixtureDirectory . '/' . $file, $content);
};
try {
    $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
    if (!preg_match('/CREATE TABLE IF NOT EXISTS products \(.+?\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;/s', $schema, $match)) throw new RuntimeException('Schema products non trovato');
    $db->exec($match[0]);
    $db->exec('CREATE TABLE settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT)');
    $pricing = ['pricing_mode' => 'graduated', 'markup_low' => .20, 'markup_high' => .055, 'low_cost_limit' => 150, 'high_cost_limit' => 1000, 'markup_fixed' => 5, 'vat' => .22, 'minimum_markup' => .055, 'minimum_profit' => 5, 'minimum_sale_markup' => 50, 'minimum_sale_markup_from_cost' => 1000];
    foreach ([10 => 20.90, 150 => 225.90, 300 => 436.90, 500 => 701.90, 750 => 1010.90, 1000 => 1293.90, 1500 => 1936.90, 1690 => 2181.90] as $cost => $expected) {
        $actual = CatalogPricing::salePrice($cost, 'notebook', $pricing);
        $check(abs($actual - $expected) < .001, 'Prezzo graduato esatto per costo ' . $cost);
        $sql = CatalogPricing::minimumPriceSql('supplier_cost', $pricing);
        $sqlMinimum = (float)$db->query('SELECT ' . $sql . ' FROM (SELECT ' . $cost . ' AS supplier_cost) AS test')->fetchColumn();
        $check(abs($sqlMinimum - CatalogPricing::minimumPrice($cost, $pricing)) < .001, 'Protezione SQL coerente con PHP per costo ' . $cost);
        if ($cost >= 1000) $check($actual / 1.22 - $cost >= 50, 'Almeno 50 EUR di ricarico netto nella fascia alta');
    }
    $check(CatalogPricing::markupForCost(150, $pricing) === .20 && CatalogPricing::markupForCost(1000, $pricing) === .055 && CatalogPricing::markupForCost(5000, $pricing) === .055, 'Limiti e fascia alta costante');
    $check(abs(CatalogPricing::markupForCost(575, $pricing) - .1275) < .000001, 'Discesa lineare della percentuale');
    $check(CatalogPricing::salePrice(150, 'notebook', $pricing + ['markup' => ['notebook' => 2], 'fixed' => ['notebook' => 999]]) === 225.90, 'Quota fissa comune e vecchi override categoria ignorati');
    $previous = 0.0; $monotonic = true;
    for ($cost = 1; $cost <= 2000; $cost++) { $next = CatalogPricing::salePrice($cost, '', $pricing); if ($next < $previous) $monotonic = false; $previous = $next; }
    $check($monotonic, 'Prezzi non decrescenti anche nel passaggio tra fasce');
    $check($throws(fn() => CatalogPricing::markupForCost(10, ['high_cost_limit' => 100])), 'Configurazione fasce invertite rifiutata');
    $importer = new ProductImporter($db, $configuration);
    $importer->import($adapter([$raw('A')]), false);
    $a = $row('A');
    $check($a['supplier_key'] === 'runner' && $a['supplier_status'] === 'active', 'Provenienza esplicita e articolo validato');
    $check(CatalogSaleGuard::reason($a, $configuration) === null, 'Articolo fresco acquistabile');
    $db->exec("UPDATE products SET sale_price=1,featured_order=1 WHERE sku='A'");
    $importer->import($adapter([$raw('A', 200, 7), $raw('REMOVED', 40, 5)]));
    $a = $row('A');
    $check($a['sale_price'] === null && (float)$a['supplier_cost'] === 200.0, 'Vecchia promozione rimossa quando cambia il costo');
    $check((float)$a['price'] >= CatalogPricing::minimumPrice(200, $configuration), 'Prezzo mai sotto costo, IVA e ricarico minimo');
    $check((int)$a['stock_qty'] === 7, 'Stock aggiornato anche dopo promozione manuale in vetrina');
    $db->exec("UPDATE products SET featured_order=1 WHERE sku='REMOVED'");
    $db->exec("INSERT INTO products (name,slug,description,icon_key,sku,price,stock_status,stock_qty,featured_order) VALUES ('Manuale','manuale','Prodotto manuale di prova','desktop','MANUAL',50,'disponibile',4,1)");
    $importer->import($adapter([$raw('A', 210, 2)]));
    $removed = $row('REMOVED');
    $check($removed['supplier_status'] === 'retired' && (int)$removed['stock_qty'] === 0, 'Articolo assente ritirato anche se featured_order e cambiato');
    $check((int)$row('MANUAL')['stock_qty'] === 4 && $row('MANUAL')['supplier_key'] === null, 'Articolo manuale preservato');
    $importer->syncAvailability(['REMOVED' => 99, 'A' => 2]);
    $check($row('REMOVED')['supplier_status'] === 'retired' && (int)$row('REMOVED')['stock_qty'] === 0, 'Il solo stock non riattiva un articolo ritirato');
    $importer->import($adapter([$raw('A', 210, 0)]));
    $check($row('A')['stock_status'] === 'esaurito' && CatalogSaleGuard::reason($row('A'), $configuration) !== null, 'Esaurito non acquistabile');
    $importer->import($adapter([$raw('A', 0, 99)]));
    $check($row('A')['supplier_status'] === 'blocked' && $row('A')['price'] === null && (int)$row('A')['stock_qty'] === 0, 'Prezzo di costo mancante blocca la vendita');
    $before = serialize($db->query('SELECT * FROM products ORDER BY id')->fetchAll());
    $check($throws(fn() => $importer->import($adapter([]))), 'Feed vuoto rifiutato');
    $check($before === serialize($db->query('SELECT * FROM products ORDER BY id')->fetchAll()), 'Feed vuoto non modifica il catalogo');
    $check($throws(fn() => $importer->import($adapter([$raw('A'), $raw('A')]))), 'SKU duplicati rifiutati');
    $strict = new ProductImporter($db, array_replace($configuration, ['min_catalog_items' => 500]));
    $check($throws(fn() => $strict->import($adapter([$raw('A')]))), 'Feed parziale rifiutato prima delle modifiche');
    $check($before === serialize($db->query('SELECT * FROM products ORDER BY id')->fetchAll()), 'Errore del feed conserva lo stato precedente');
    $importer->import($adapter([$raw('A', 100, 3)]));
    $fresh = $row('A'); $expired = $fresh; $expired['supplier_price_checked_at'] = gmdate('Y-m-d H:i:s', time() - 7201);
    $check(CatalogSaleGuard::reason($expired, $configuration) === 'supplier_data_expired', 'Prezzo scaduto blocca il checkout');
    $expired = $fresh; $expired['supplier_stock_checked_at'] = gmdate('Y-m-d H:i:s', time() - 7201);
    $check(CatalogSaleGuard::reason($expired, $configuration) === 'supplier_data_expired', 'Stock scaduto blocca il checkout');
    $unsafe = $fresh; $unsafe['sale_price'] = 10;
    $check(CatalogSaleGuard::reason($unsafe, $configuration) === 'below_minimum_price', 'Una promozione sotto soglia viene respinta dal server');
    $check(CatalogSaleGuard::reason($fresh, $configuration, null, 10) === 'below_minimum_price', 'Anche l importo gia approvato e ricontrollato prima dell incasso');
    $db->exec("UPDATE products SET sale_price=1 WHERE sku='A'");
    $visibility = CatalogSaleGuard::visibilitySql($db, $configuration);
    $check((int)$db->query("SELECT COUNT(*) FROM products WHERE sku='A' AND {$visibility}")->fetchColumn() === 0, 'Articolo sotto soglia escluso dal catalogo pubblico');
    $check(CatalogSaleGuard::publicProduct($unsafe, $configuration)['price'] === null, 'La scheda non mostra un prezzo non verificato');
    $importer->import($adapter([$raw('A', 100, 3)]));
    $importer->import($adapter([$raw('OTHER', 80, 2)], 'nexths'));
    $check($row('A')['supplier_status'] === 'active', 'Un altro fornitore non ritira gli articoli Runner');
    $configuration['minimum_markup'] = .25;
    $check(CatalogPricing::salePrice(100, 'notebook', $configuration) >= 152.50, 'La soglia prevale su un ricarico commerciale inferiore');
    $check($throws(fn() => CatalogPricing::salePrice(-1, 'notebook', $configuration)), 'Costo negativo rifiutato');

    $article = "Codice|CodiceProduttore|EAN|DescProd|Produttore|Famiglia|Cat|DescCatMerc|Dispo\nA|PN|123|Notebook A|Brand|NOTEBOOK E TABLET|N|NOTEBOOK|99\n";
    $files($article, "Codice|PrezzoPers\nA|1,690.00\n", "Codice|Dispo\nA|-5\n");
    $runnerConfig = ['work_dir' => $fixtureDirectory, 'skip_download' => true, 'min_catalog_items' => 1];
    $parsed = (new RunnerAdapter($runnerConfig))->fetchCommercialProducts()[0];
    $check((float)$parsed['cost'] === 1690.0, 'Prezzo con separatori internazionali interpretato correttamente');
    $check($parsed['stock'] === 0, 'Quantita negativa non trasformata in disponibilita positiva');
    $files($article, "Codice|PrezzoPers\nA|1.690\n", "Codice|Dispo\nA|3\n");
    $check((new RunnerAdapter($runnerConfig))->fetchCommercialProducts()[0]['cost'] == 0, 'Formato prezzo ambiguo rifiutato anziche dividere il costo per mille');
    $files($article, "Codice|PrezzoPers\nA|1.690,00\n", "Codice|Dispo\nOTHER|5\n");
    $parsed = (new RunnerAdapter($runnerConfig))->fetchCommercialProducts()[0];
    $check((float)$parsed['cost'] === 1690.0 && $parsed['stock'] === 0, 'Formato italiano e SKU assente dallo stock autorevole');
    $files($article, "Codice|PrezzoPers\nOTHER|5\n", "Codice|Dispo\nA|3\n");
    $check((new RunnerAdapter($runnerConfig))->fetchCommercialProducts()[0]['cost'] == 0, 'Articolo senza prezzo presente nella fotografia ma non vendibile');
    $files($article, "Codice|PrezzoPers\nA|50\nA|60\n", "Codice|Dispo\nA|3\n");
    $check($throws(fn() => (new RunnerAdapter($runnerConfig))->fetchCommercialProducts()), 'Duplicati nel listino prezzi rifiutati');
    unlink($fixtureDirectory . '/dispo.txt');
    $check($throws(fn() => (new RunnerAdapter($runnerConfig))->fetchCommercialProducts()), 'File stock mancante: nessun fallback a giacenze obsolete');
    $files(str_replace('A|PN|', 'NOT-A|PN|', $article), "Codice|PrezzoPers\nNOT-A|50\n", "Codice|Dispo\nNOT-A|3\n");
    $verifier = new RunnerCheckoutVerifier($db, array_replace($configuration, ['runner' => $runnerConfig]));
    $check($throws(fn() => $verifier->refresh((int)$row('A')['id'], true)), 'Controllo pre-incasso respinge lo SKU sparito dal fornitore');
    $check($row('A')['supplier_status'] === 'retired', 'Ritiro registrato quando il checkout scopre la rimozione');
    $files($article, "Codice|PrezzoPers\nA|300\n", "Codice|Dispo\nA|2\n");
    $updated = $verifier->refresh((int)$row('A')['id'], true);
    $check((float)$updated['supplier_cost'] === 300.0 && (float)$updated['price'] >= CatalogPricing::minimumPrice(300, $configuration), 'Il checkout aggiorna il costo prima di valutare un incasso');
    $check(CatalogSaleGuard::reason($updated, $configuration, null, 150) === 'below_minimum_price', 'Vecchio importo non incassabile dopo un aumento del fornitore');
    $missingImage = new ProductImporter($db, array_replace($configuration, ['require_image' => true]));
    $missingImage->import($adapter([$raw('A', 310, 2)]));
    $check((float)$row('A')['supplier_cost'] === 310.0 && (int)$row('A')['stock_qty'] === 2, 'Foto mancante non impedisce aggiornamento di costo e stock');
    $badTransport = array_replace($runnerConfig, ['skip_download' => false, 'ftp_host' => '', 'ftp_user' => '', 'ftp_pass' => '']);
    $check($throws(fn() => (new RunnerAdapter($badTransport))->fetchCommercialProducts()), 'Errore di download non riutilizza la cache precedente');
    $importer->import($adapter([$raw('A', 100, 3), $raw('TX-FAIL', 10, 1)]));
    $beforeFailure = serialize($db->query('SELECT * FROM products ORDER BY id')->fetchAll());
    $check($throws(fn() => $importer->import($adapter([$raw('A', 200, 3), $raw('TX-FAIL', 10000000000, 1)]))), 'Errore database interrompe la fotografia commerciale');
    $check($beforeFailure === serialize($db->query('SELECT * FROM products ORDER BY id')->fetchAll()), 'Aggiornamenti commerciali annullati insieme in caso di errore');
    $db->exec('CREATE TABLE pc_commercial_policies (id INT AUTO_INCREMENT PRIMARY KEY, source VARCHAR(30), policy_json JSON, notes TEXT, generated_at DATETIME)');
    $clientA = new \App\Services\Ai\GeminiClient('test-key-not-real', 'pc_configurator', 'model-a', 10, 10000, 1000, 0, 10);
    $clientB = new \App\Services\Ai\GeminiClient('test-key-not-real', 'pc_configurator', 'model-b', 10, 10000, 1000, 0, 10);
    $db->prepare("INSERT INTO pc_commercial_policies (source,policy_json,generated_at) VALUES ('llm',:policy,NOW())")->execute(['policy' => json_encode(['source' => 'llm', 'model_signature' => $clientA->configurationSignature()])]);
    $cached = new ReflectionMethod(\App\Services\Catalog\PcCommercialPolicyService::class, 'cachedPolicy');
    $check($cached->invoke(new \App\Services\Catalog\PcCommercialPolicyService($db, $clientA)) !== [], 'Cache PC riutilizzata per lo stesso modello');
    $check($cached->invoke(new \App\Services\Catalog\PcCommercialPolicyService($db, $clientB)) === [], 'Cambio modello PC invalida la policy precedente');
    echo "PASS {$passed} controlli catalogo in database temporaneo\n";
} finally {
    $db = null;
    if (!preg_match('/^bisped_catalog_test_[a-f0-9]{10}$/', $name)) throw new RuntimeException('Nome database test inatteso');
    $admin->exec("DROP DATABASE `{$name}`");
    foreach (['articoli.txt', 'prezzi.txt', 'dispo.txt'] as $file) if (is_file($fixtureDirectory . '/' . $file)) unlink($fixtureDirectory . '/' . $file);
    rmdir($fixtureDirectory);
}
