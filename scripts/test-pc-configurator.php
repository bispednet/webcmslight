<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
use App\Services\Catalog\PcComponentSpecExtractor;
use App\Services\Catalog\PcCompatibilityService;
use App\Services\Catalog\PcBuildPlanner;
use App\Services\Catalog\PcMemory;

if (config('app.env') !== 'local' || config('database.host') !== '127.0.0.1' || (int)config('database.port') !== 3307) throw new RuntimeException('Test solo sul database locale');
$env = json_decode(file_get_contents(BASE_PATH . '/.local/environment.json'), true, 512, JSON_THROW_ON_ERROR);
$admin = new PDO('mysql:host=127.0.0.1;port=3307;charset=utf8mb4', 'root', $env['mysql_root_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$name = 'bisped_pc_test_' . bin2hex(random_bytes(5));
$admin->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$db = new PDO('mysql:host=127.0.0.1;port=3307;dbname=' . $name . ';charset=utf8mb4', 'root', $env['mysql_root_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
$passed = 0;
$check = static function (bool $ok, string $label) use (&$passed): void { if (!$ok) throw new RuntimeException('FAIL ' . $label); $passed++; echo 'OK ' . $label . PHP_EOL; };
$extractor = new PcComponentSpecExtractor($db);
$infer = static fn($name, $type, $description = '') => $extractor->infer(['name' => $name, 'category' => 'componenti', 'subcategory' => $type, 'description' => $description]);
try {
    $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
    foreach (['products', 'product_features', 'pc_component_specs', 'pc_builds', 'pc_build_items'] as $table) {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS ' . $table . ' \(.+?\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;/s', $schema, $m)) throw new RuntimeException('Schema mancante ' . $table);
        $db->exec($m[0]);
    }
    $db->exec('CREATE TABLE settings(setting_key VARCHAR(100) PRIMARY KEY,setting_value TEXT)');
    $bad = $infer('KINGSTON RAM FURY IMPACTSO-DIMM DDR58GB 4800MHZ CL38', 'memorie-ram', 'Kit SODIMM con capacita fino a 64 GB.');
    $check($bad['capacity_gb'] === 8, 'SKU KF548S38IB-8: 8 GB, senza usare il massimo della famiglia');
    $check(PcMemory::metadata($bad)['ram_form_factor'] === 'SO-DIMM' && !PcMemory::metadata($bad)['desktop_ram'], 'SO-DIMM riconosciuto anche incollato al nome');
    $ssd = $infer('KINGSTON SSD NV3 500GB M.2', 'ssd-interni', 'Disponibile fino a 4 TB, diverse capacita da 500 GB a 4 TB.');
    $check($ssd['capacity_gb'] === 500, 'SSD da 500 GB non diventa 4 TB');
    $check(PcMemory::capacity('SSD NVMe 1TB', 'gamma fino a 8 TB') === 1024, 'Priorita alla capacita del singolo SKU');
    $check(PcMemory::capacity('RAM FURY DDR5', 'Capacita fino a 64 GB', true) === null, 'Capacita sconosciuta conservata se esiste solo testo promozionale');
    $kit = $infer('CORSAIR RAM DIMM DDR5 32GB (2x16GB)', 'memorie-ram');
    $check($kit['capacity_gb'] === 32 && PcMemory::metadata($kit)['ram_modules_per_unit'] === 2 && PcMemory::metadata($kit)['ram_module_capacity_gb'] === 16, 'Kit 2x16: 32 GB e due slot occupati');
    $reverse = PcMemory::ram('GEIL RAM DIMM 32GB(16GBX2) DDR5', '');
    $check($reverse['capacity_gb'] === 32 && $reverse['ram_modules_per_unit'] === 2, 'Kit con notazione inversa 16GBX2');
    $check(PcMemory::capacity('RAM DIMM 32GB (2x8GB)', '', true) === null, 'Capacita totale in conflitto con il kit: dato non confermato');
    $check(!PcMemory::ram('RAM DDR5 48GB 6000', 'Descrizione generica')['desktop_ram'], 'Formato fisico sconosciuto: esclusione prudente dal desktop');
    $check(PcMemory::ram('RAM DDR5 48GB', 'Kit di due moduli DIMM da 24 GB')['ram_modules_per_unit'] === 2, 'Quantita esplicita nella descrizione del kit');
    $check(!empty(PcMemory::ram('RAM DDR5 16GB NON-ECC DIMM', 'on-die ECC')['desktop_ram']), 'NON-ECC e on-die ECC non eliminano RAM desktop');
    $check(!empty(PcMemory::metadata($infer('RAM DIMMDDR516GB', 'memorie-ram'))['desktop_ram']), 'RAM desktop con titolo compatto');
    $check(PcMemory::motherboard('MSI AM5,2DDR5,1PCI-Ex16', '')['memory_slots'] === 2, 'Due slot da 2DDR5');
    $check(PcMemory::motherboard('MSI AM5,4DDR5,3M.2', '')['memory_slots'] === 4, 'Quattro slot da 4DDR5');
    $check(PcMemory::motherboard('SAPPHIRE AM4 DDR4', 'Dispone di due slot DDR4 per un massimo di 64 GB di RAM')['memory_slots'] === 2, 'Slot espliciti nella descrizione italiana');
    $corrected = $extractor->infer(['name' => 'MSI MB PRO H610M-E DDR5, 2DD54', 'sku' => 'PRO H610M-E', 'subcategory' => 'mainboard']);
    $check(PcMemory::metadata($corrected)['memory_slots'] === 2 && isset(PcMemory::metadata($corrected)['verified_source']), 'Refuso Runner risolto con dato documentato MSI');
    $check(PcMemory::motherboard('Scheda ATX AM5', '')['memory_slots'] === null, 'Il formato ATX non viene usato per inventare gli slot');

    $add = static function (string $title, string $type, float $price, int $stock = 100, string $description = '') use ($db, $extractor): int {
        $db->prepare("INSERT INTO products(name,slug,sku,description,icon_key,category,subcategory,price,stock_status,stock_qty,featured_order) VALUES (?,?,? ,?,'desktop','componenti',?,?,'disponibile',?,1)")
            ->execute([$title, 'test-' . bin2hex(random_bytes(5)), 'TEST-' . bin2hex(random_bytes(5)), $description, $type, $price, $stock]);
        $id = (int)$db->lastInsertId();
        $spec = $extractor->infer(['name' => $title, 'description' => $description, 'category' => 'componenti', 'subcategory' => $type]);
        if ($spec) { $keys = array_keys($spec); $db->prepare('INSERT INTO pc_component_specs(product_id,' . implode(',', $keys) . ') VALUES (' . implode(',', array_fill(0, count($keys) + 1, '?')) . ')')->execute([$id, ...array_values($spec)]); }
        return $id;
    };
    $selected = [
        'cpu' => $add('AMD CPU RYZEN 5 7600 AM5 BOX', 'cpu', 100),
        'motherboard' => $add('MSI MB AM5 B650 2DDR5 ATX', 'mainboard', 50, 100, 'RAM massima supportata: 128 GB'),
        'ram' => $add('KINGSTON RAM DIMM DDR58GB 4800MHZ', 'memorie-ram', 20, 9),
        'storage' => $add('SSD NVMe M.2 1TB', 'ssd-interni', 60),
        'psu' => $add('ALIMENTATORE 650W ATX GOLD', 'alimentatori', 70),
        'case' => $add('CASE GAMING ATX MESH', 'case', 60),
    ];
    $four = $add('MSI MB AM5 B650 4DDR5 ATX', 'mainboard', 80);
    $unknown = $add('MSI MB AM5 B650 ATX', 'mainboard', 80);
    $limited = $add('MSI MB AM5 B650 4DDR5 ATX', 'mainboard', 90, 100, 'RAM massima supportata: 16 GB');
    $sodimm = $add('KINGSTON RAM IMPACTSO-DIMM DDR58GB', 'memorie-ram', 10);
    $kitId = $add('CORSAIR RAM DIMM DDR5 32GB (2x16GB)', 'memorie-ram', 100);
    $new = static fn() => new PcCompatibilityService($db);
    $one = $new()->selectionSummary($selected);
    $two = $new()->selectionSummary($selected, ['ram' => 2]);
    $check($one['valid'] && $two['valid'], 'Configurazione completa con uno o due moduli');
    $check($two['total'] === $one['total'] + 20.0 && $two['memory']['total_gb'] === 16, 'Due moduli: prezzo doppio RAM e 16 GB totali');
    $check($two['stock']['qty'] === 4, 'Stock per PC: floor(9 moduli / 2)');
    $check(array_column($two['memory']['choices'], 'modules') === [1, 2], 'Scheda a due slot: scelta solo uno o due banchi');
    $invalid = $new()->selectionSummary($selected, ['ram' => 4]);
    $check(!$invalid['valid'] && !$invalid['stock']['available'] && $invalid['total'] === 0.0, 'Quattro moduli su due slot: configurazione e prezzo bloccati');
    $selection4 = array_replace($selected, ['motherboard' => $four]);
    $fourSummary = $new()->selectionSummary($selection4, ['ram' => 4]);
    $check($fourSummary['valid'] && $fourSummary['memory']['total_gb'] === 32 && $fourSummary['stock']['qty'] === 2, 'Quattro moduli consentiti su quattro slot, capacita e stock corretti');
    $check(!$new()->selectionIsValid(array_replace($selected, ['motherboard' => $unknown]), ['ram' => 2]), 'Numero slot sconosciuto: espansione multipla respinta');
    $check(!$new()->selectionIsValid(array_replace($selection4, ['motherboard' => $limited]), ['ram' => 4]), 'Rispetto della capacita massima della scheda madre');
    $kitSelection = array_replace($selected, ['ram' => $kitId]);
    $kitSummary = $new()->selectionSummary($kitSelection);
    $check($kitSummary['valid'] && $kitSummary['memory']['total_gb'] === 32 && $kitSummary['memory']['modules'] === 2, 'Un kit occupa due slot e viene pagato una sola volta');
    $check(!$new()->selectionIsValid($kitSelection, ['ram' => 2]), 'Due kit da due moduli non entrano in due slot');
    $kitFour = $new()->selectionSummary(array_replace($kitSelection, ['motherboard' => $four]), ['ram' => 2]);
    $check($kitFour['valid'] && $kitFour['memory']['modules'] === 4 && $kitFour['memory']['total_gb'] === 64, 'Due kit su quattro slot: quattro moduli e 64 GB');
    $check(!$new()->selectionIsValid(array_replace($selected, ['ram' => $sodimm])), 'RAM notebook esclusa dalla configurazione desktop');
    $check(!in_array($sodimm, array_column($two['options']['ram'], 'id'), true), 'RAM notebook esclusa dalle alternative');
    $check($two['options']['ram'][0]['specs']['integrated_graphics'] === null, 'Nessuna etichetta no-iGPU sulla RAM');
    $check(!$new()->selectionIsValid($selected, ['ram' => -2]) && !$new()->selectionIsValid($selected, ['ram' => 1.5]), 'Quantita negativa e frazionaria respinte');
    $check(!$new()->selectionIsValid(array_diff_key($selected, ['case' => true])), 'Una configurazione incompleta non e valida');
    $db->exec('UPDATE products SET stock_qty=1 WHERE id=' . $selected['ram']);
    $check(!$new()->selectionIsValid($selected, ['ram' => 2]), 'Stock insufficiente per due moduli: blocco server');
    $db->exec('UPDATE products SET stock_qty=9 WHERE id=' . $selected['ram']);

    $old = $add('PC VECCHIO 64', 'pc-configurabili', 999);
    $db->exec("UPDATE products SET sku='BISBUILD-OLD' WHERE id={$old}");
    $db->exec("INSERT INTO pc_builds(product_id,profile_key,target_use,base_platform) VALUES ({$old},'old','office','amd')");
    $manual = $add('PC MANUALE', 'pc-configurabili', 999);
    $db->exec("INSERT INTO pc_builds(product_id,profile_key,target_use,base_platform) VALUES ({$manual},'manual','office','amd')");
    $stats = (new PcBuildPlanner($db, $new()))->generateDailyBuilds(false, true);
    $check($stats['created'] > 0 && $stats['removed'] === 1, 'Rigenerazione completa sostituisce le vecchie build automatiche');
    $check((int)$db->query("SELECT COUNT(*) FROM products WHERE id={$manual}")->fetchColumn() === 1, 'PC manuali preservati dalla rigenerazione');
    $persisted = true;
    foreach ($db->query("SELECT p.id,p.name,p.price FROM products p JOIN pc_builds b ON b.product_id=p.id WHERE p.sku LIKE 'BISBUILD-%'")->fetchAll() as $build) {
        $config = $new()->configuratorForProduct((int)$build['id']);
        if (!$config['valid'] || abs((float)$build['price'] - $config['total']) > .001 || !str_ends_with($build['name'], $config['memory']['total_gb'] . 'GB')) $persisted = false;
    }
    $check($persisted, 'Build salvate: quantita, titolo, prezzo e compatibilita coerenti');
    $visibility = \App\Services\Catalog\CatalogSaleGuard::visibilitySql($db, (array)config('catalog', []));
    $check((int)$db->query("SELECT COUNT(*) FROM products WHERE sku LIKE 'BISBUILD-%' AND {$visibility}")->fetchColumn() === $stats['created'], 'Build nuove visibili solo con distinta validata');
    $probe = $db->query("SELECT id,price FROM products WHERE sku LIKE 'BISBUILD-%' LIMIT 1")->fetch();
    $db->exec('UPDATE products SET price=1 WHERE id=' . (int)$probe['id']);
    $check((int)$db->query('SELECT COUNT(*) FROM products WHERE id=' . (int)$probe['id'] . ' AND ' . $visibility)->fetchColumn() === 0, 'Vecchio prezzo PC incoerente con i componenti: esclusione immediata');
    $new()->refreshBuildProducts();
    $check((float)$db->query('SELECT price FROM products WHERE id=' . (int)$probe['id'])->fetchColumn() === (float)$probe['price'], 'Refresh commerciale ricalcola il prezzo del PC');
    $part = (int)$db->query('SELECT component_product_id FROM pc_build_items WHERE build_product_id=' . (int)$probe['id'] . ' LIMIT 1')->fetchColumn();
    $db->exec('UPDATE products SET stock_qty=0 WHERE id=' . $part);
    $check((int)$db->query('SELECT COUNT(*) FROM products WHERE id=' . (int)$probe['id'] . ' AND ' . $visibility)->fetchColumn() === 0, 'Componente esaurito: PC escluso immediatamente dal catalogo');
    $new()->refreshBuildProducts();
    $check((int)$db->query('SELECT stock_qty FROM products WHERE id=' . (int)$probe['id'])->fetchColumn() === 0, 'Refresh commerciale sospende il PC incompleto');
    $db->exec('UPDATE products SET stock_qty=100 WHERE id=' . $part);
    $new()->refreshBuildProducts();
    $before = serialize($db->query('SELECT * FROM products ORDER BY id')->fetchAll());
    $db->exec("CREATE TRIGGER reject_pc_insert BEFORE INSERT ON products FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='test rollback'");
    $failed = false; try { (new PcBuildPlanner($db, $new()))->generateDailyBuilds(false, true); } catch (Throwable) { $failed = true; }
    $check($failed && $before === serialize($db->query('SELECT * FROM products ORDER BY id')->fetchAll()), 'Errore durante ricostruzione: ripristino automatico delle build precedenti');
    echo "PASS {$passed} controlli configuratore in database temporaneo\n";
} finally {
    $db = null;
    if (!preg_match('/^bisped_pc_test_[a-f0-9]{10}$/', $name)) throw new RuntimeException('Nome database inatteso');
    $admin->exec("DROP DATABASE `{$name}`");
}
