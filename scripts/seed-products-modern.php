<?php
/**
 * Sostituisce il catalogo con prodotti moderni 2024-2025.
 * price    = listino ufficiale produttore
 * sale_price = prezzo Amazon (calcolato sconto vs listino)
 * Eseguire: php scripts/seed-products-modern.php
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

// ── CLEAR old catalog ────────────────────────────────────────────────────────
$pdo->exec("DELETE FROM product_features");
$pdo->exec("DELETE FROM products");

// ── HELPER ───────────────────────────────────────────────────────────────────
function slug(string $name): string {
    $s = mb_strtolower($name, 'UTF-8');
    $s = preg_replace('/[àáâãäå]/u', 'a', $s);
    $s = preg_replace('/[èéêë]/u', 'e', $s);
    $s = preg_replace('/[ìíîï]/u', 'i', $s);
    $s = preg_replace('/[òóôõö]/u', 'o', $s);
    $s = preg_replace('/[ùúûü]/u', 'u', $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

$products = [

    // ══════════════════════════════════════════════════════════
    // SMARTPHONE
    // ══════════════════════════════════════════════════════════
    [
        'name'        => 'Samsung Galaxy S25 256GB',
        'category'    => 'Smartphone',
        'price'       => 929.00,
        'sale_price'  => 849.00,
        'stock'       => 'instock',
        'sku'         => 'SM-S931B-256',
        'tags'        => 'samsung,galaxy,s25,5g,android,smartphone',
        'description' => 'Il Galaxy S25 porta l\'intelligenza artificiale Galaxy AI su ogni aspetto dello smartphone. Snapdragon 8 Elite, fotocamera da 50MP con zoom 3x, autonomia ottimizzata e design ultra-slim.',
        'features'    => ['Snapdragon 8 Elite for Galaxy', 'Galaxy AI integrata nativamente', 'Tripla fotocamera 50+12+10 MP', '5G dual-SIM', 'IP68 — resistente ad acqua e polvere', 'Autonomia: fino a 27 ore di chiamate'],
        'campaign'    => '-9% vs listino ufficiale',
    ],
    [
        'name'        => 'Apple iPhone 16 128GB',
        'category'    => 'Smartphone',
        'price'       => 959.00,
        'sale_price'  => 889.00,
        'stock'       => 'instock',
        'sku'         => 'MYE03QL-A',
        'tags'        => 'apple,iphone,16,ios,smartphone',
        'description' => 'iPhone 16 con chip A18, camera control hardware dedicato, fotocamera da 48MP con nuovi effetti visivi. La scelta per chi vive nell\'ecosistema Apple senza compromessi.',
        'features'    => ['Chip A18 (3nm)', 'Tasto Camera Control fisico', 'Fotocamera principale 48MP f/1.6', 'iOS 18 con Apple Intelligence', 'USB-C con trasferimento a 480 Mb/s', 'Vetro Ceramic Shield di ultima generazione'],
        'campaign'    => '-7% vs listino Apple',
    ],
    [
        'name'        => 'Xiaomi 14T Pro 512GB',
        'category'    => 'Smartphone',
        'price'       => 999.00,
        'sale_price'  => 749.00,
        'stock'       => 'instock',
        'sku'         => 'MZB0FLTEU',
        'tags'        => 'xiaomi,14t,pro,leica,5g,android',
        'description' => 'Xiaomi 14T Pro con sistema fotografico Leica, Dimensity 9300+, ricarica HyperCharge da 120W. Il rapporto qualità-prezzo più aggressivo della fascia alta 2024.',
        'features'    => ['MediaTek Dimensity 9300+', 'Fotocamera Leica 50+50+12 MP', 'Ricarica HyperCharge 120W (30 min al 100%)', 'Display AMOLED 144Hz 6.67"', '5G, NFC, Wi-Fi 7', 'Batteria 5000 mAh'],
        'campaign'    => '-25% vs listino — offerta limitata',
    ],
    [
        'name'        => 'Samsung Galaxy A55 5G 256GB',
        'category'    => 'Smartphone',
        'price'       => 499.00,
        'sale_price'  => 379.00,
        'stock'       => 'instock',
        'sku'         => 'SM-A556B-256',
        'tags'        => 'samsung,galaxy,a55,5g,fascia-media',
        'description' => 'Galaxy A55 5G: display Super AMOLED 120Hz, fotocamera da 50MP con stabilizzazione ottica, rivestimento in vetro e IP67. La fascia media che si comporta da top.',
        'features'    => ['Exynos 1480 — fino a 14 GPU core', 'Display Super AMOLED 120Hz 6.6"', 'Fotocamera 50MP OIS + 12MP ultra-wide', 'IP67 — resistente agli schizzi', 'NFC e 5G dual-SIM', 'Batteria 5000 mAh, ricarica 25W'],
        'campaign'    => '-24% vs listino',
    ],
    [
        'name'        => 'Google Pixel 9 128GB',
        'category'    => 'Smartphone',
        'price'       => 899.00,
        'sale_price'  => 779.00,
        'stock'       => 'instock',
        'sku'         => 'GA05763-IT',
        'tags'        => 'google,pixel,9,android,fotocamera,ai',
        'description' => 'Pixel 9 con Google Tensor G4, la migliore fotografia computazionale su Android. Magic Eraser, Best Take, Add Me e 7 anni di aggiornamenti garantiti.',
        'features'    => ['Google Tensor G4', '7 anni di aggiornamenti OS garantiti', 'Fotocamera 50MP con astrophotography', 'Google AI: Magic Eraser, Best Take, Gemini', 'Display OLED Actua 6.3" 120Hz', 'Satellite SOS di emergenza'],
        'campaign'    => '-13% vs listino Google',
    ],

    // ══════════════════════════════════════════════════════════
    // INFORMATICA — LAPTOP
    // ══════════════════════════════════════════════════════════
    [
        'name'        => 'Apple MacBook Air 13" M3 8GB/256GB',
        'category'    => 'Informatica',
        'price'       => 1299.00,
        'sale_price'  => 1149.00,
        'stock'       => 'instock',
        'sku'         => 'MRXV3T-A',
        'tags'        => 'apple,macbook,air,m3,laptop,macos',
        'description' => 'MacBook Air con chip M3: il laptop più venduto al mondo, 18h di autonomia, display Liquid Retina 13.6", peso 1.24 kg. Silenzioso (senza ventola) e velocissimo.',
        'features'    => ['Chip Apple M3 (3nm)', 'Autonomia fino a 18 ore', 'Display Liquid Retina 13.6" P3', 'Silenzioso — nessuna ventola', '8-core CPU, 10-core GPU', 'Wi-Fi 6E, Bluetooth 5.3'],
        'campaign'    => '-12% vs listino Apple Store',
    ],
    [
        'name'        => 'ASUS VivoBook 16X OLED i5/16GB/512GB',
        'category'    => 'Informatica',
        'price'       => 799.00,
        'sale_price'  => 699.00,
        'stock'       => 'instock',
        'sku'         => 'K3605VC-MB271W',
        'tags'        => 'asus,vivobook,laptop,oled,windows11',
        'description' => 'Display OLED 16" 2.5K, Intel Core i5-13500H, 16GB RAM DDR5, SSD 512GB. Il laptop per chi lavora con immagini, video e non vuole compromessi sul display.',
        'features'    => ['Display OLED 16" 2.5K 60Hz 100% DCI-P3', 'Intel Core i5-13500H 12 core', '16 GB DDR5 — upgrade fino a 32 GB', 'SSD NVMe 512 GB', 'Peso: 1.88 kg', 'Windows 11 Home incluso'],
        'campaign'    => '-13% vs listino ASUS',
    ],
    [
        'name'        => 'Lenovo IdeaPad 5 15" Ryzen 5/16GB/512GB',
        'category'    => 'Informatica',
        'price'       => 699.00,
        'sale_price'  => 579.00,
        'stock'       => 'instock',
        'sku'         => '82SG00EKIX',
        'tags'        => 'lenovo,ideapad,laptop,amd,ryzen,windows',
        'description' => 'IdeaPad 5 con AMD Ryzen 5 7530U, display IPS 15.6" Full HD, 16 GB RAM e SSD da 512 GB. Equilibrio perfetto tra prestazioni, autonomia e prezzo.',
        'features'    => ['AMD Ryzen 5 7530U 6 core', 'Display IPS 15.6" FHD 300 nit', '16 GB DDR4, SSD 512 GB PCIe', 'Autonomia dichiarata: 9 ore', 'Lettore impronte digitali integrato', 'Windows 11 Home incluso'],
        'campaign'    => '-17% vs listino Lenovo',
    ],

    // ══════════════════════════════════════════════════════════
    // GAMING — LAPTOP
    // ══════════════════════════════════════════════════════════
    [
        'name'        => 'ASUS TUF Gaming A15 RTX 4060/Ryzen 7',
        'category'    => 'Gaming',
        'price'       => 1199.00,
        'sale_price'  => 999.00,
        'stock'       => 'instock',
        'sku'         => 'FA507NV-LP103W',
        'tags'        => 'asus,tuf,gaming,laptop,rtx4060,ryzen7',
        'description' => 'TUF Gaming A15: RTX 4060 8 GB, Ryzen 7 7745HX, display 144Hz, tastiera retroilluminata. Il gaming laptop che non manda in bancarotta ma regge qualsiasi gioco AAA.',
        'features'    => ['NVIDIA GeForce RTX 4060 8 GB GDDR6', 'AMD Ryzen 7 7745HX 8 core / 16 thread', 'Display 15.6" FHD IPS 144Hz', '16 GB DDR5 RAM (upgrade a 32 GB)', 'SSD 512 GB NVMe (slot M.2 libero)', 'Certificazione MIL-STD-810H'],
        'campaign'    => '-17% vs listino — scorte limitate',
    ],
    [
        'name'        => 'Lenovo LOQ 15 RTX 4060/i5-12450HX',
        'category'    => 'Gaming',
        'price'       => 1099.00,
        'sale_price'  => 899.00,
        'stock'       => 'instock',
        'sku'         => '82XV00VFIX',
        'tags'        => 'lenovo,loq,gaming,laptop,rtx4060,intel',
        'description' => 'Lenovo LOQ 15: il gaming laptop entry/mid con RTX 4060, display 144Hz e tastiera con retroilluminazione RGB. Ideale per chi vuole giocare senza spendere oltre i 1000€.',
        'features'    => ['NVIDIA RTX 4060 8 GB con TGP 60-115W', 'Intel Core i5-12450HX 8 core', 'Display 15.6" FHD 144Hz IPS', '16 GB DDR5 RAM', 'SSD 512 GB NVMe', 'Dissipatore IdeaPad a doppia ventola'],
        'campaign'    => '-18% vs listino',
    ],

    // ══════════════════════════════════════════════════════════
    // GAMING — ACCESSORI DESKTOP
    // ══════════════════════════════════════════════════════════
    [
        'name'        => 'Samsung Odyssey G5 27" QHD 165Hz',
        'category'    => 'Gaming',
        'price'       => 349.00,
        'sale_price'  => 249.00,
        'stock'       => 'instock',
        'sku'         => 'LS27CG552EUXEN',
        'tags'        => 'samsung,monitor,gaming,qhd,165hz,curved',
        'description' => 'Monitor gaming curvo 27" 1000R, risoluzione QHD 2560×1440, 165Hz, 1ms MPRT. DisplayHDR400 e AMD FreeSync Premium per un gaming senza tearing.',
        'features'    => ['27" QHD 2560×1440 — curvo 1000R', '165Hz refresh rate, 1ms MPRT', 'AMD FreeSync Premium', 'DisplayHDR400', 'HDMI 2.0 + DisplayPort 1.2', 'Inclinazione regolabile, VESA 75×75'],
        'campaign'    => '-29% vs listino Samsung',
    ],
    [
        'name'        => 'Logitech G Pro X Superlight 2',
        'category'    => 'Gaming',
        'price'       => 169.00,
        'sale_price'  => 119.00,
        'stock'       => 'instock',
        'sku'         => '910-006638',
        'tags'        => 'logitech,mouse,gaming,wireless,pro,superlight',
        'description' => 'Il mouse wireless più leggero e preciso per i pro-player. Sensore HERO 2 da 32.000 DPI, 95 ore di autonomia, peso 60 g. Usato dai professionisti di CS2, Valorant e Apex.',
        'features'    => ['Sensore HERO 2 da 32.000 DPI', 'Peso: 60 grammi (ultra-leggero)', 'Wireless LIGHTSPEED 1ms di latenza', 'Autonomia: fino a 95 ore', '5 pulsanti programmabili', 'Compatibile PC, Mac'],
        'campaign'    => '-30% vs listino Logitech',
    ],
    [
        'name'        => 'HyperX Cloud Alpha Wireless',
        'category'    => 'Gaming',
        'price'       => 149.00,
        'sale_price'  => 109.00,
        'stock'       => 'instock',
        'sku'         => 'HHSC1A-DG-BK/G',
        'tags'        => 'hyperx,cuffie,gaming,wireless,cloud,alpha',
        'description' => 'Cloud Alpha Wireless: autonomia record di 300 ore, driver dual-camera 50mm, DTS Headphone:X Spatial Audio. La cuffie gaming wireless con la batteria più longeva del mercato.',
        'features'    => ['300 ore di autonomia wireless (record)', 'Driver dual-camera 50mm', 'DTS Headphone:X Spatial Audio', 'Microfono removibile con filtro pop', 'Compatibile PS4/PS5/PC', 'Cancellazione del rumore al microfono'],
        'campaign'    => '-27% vs listino HP/HyperX',
    ],
    [
        'name'        => 'Corsair K70 RGB MK.2 Cherry MX Red',
        'category'    => 'Gaming',
        'price'       => 149.00,
        'sale_price'  => 99.00,
        'stock'       => 'instock',
        'sku'         => 'CH-9109010-IT',
        'tags'        => 'corsair,tastiera,meccanica,gaming,rgb,cherry-mx',
        'description' => 'Tastiera meccanica gaming con switch Cherry MX Red (lineari), struttura in alluminio spazzolato, retroilluminazione RGB per tasto. USB pass-through e media control dedicati.',
        'features'    => ['Switch Cherry MX Red (lineari, silenziosi)', 'Struttura full alluminio spazzolato', 'RGB per-key con iCUE software', 'USB pass-through 2.0', 'Poggiapolsi in cuoio sintetico rimovibile', 'Layout italiano incluso'],
        'campaign'    => '-34% vs listino Corsair',
    ],

    // ══════════════════════════════════════════════════════════
    // AUDIO / WEARABLE
    // ══════════════════════════════════════════════════════════
    [
        'name'        => 'Sony WH-1000XM5',
        'category'    => 'Informatica',
        'price'       => 379.00,
        'sale_price'  => 279.00,
        'stock'       => 'instock',
        'sku'         => 'WH1000XM5B',
        'tags'        => 'sony,cuffie,anc,wireless,xm5,bluetooth',
        'description' => 'Il riferimento assoluto per la cancellazione attiva del rumore. Sony WH-1000XM5 con 8 microfoni, 30 ore di autonomia, ricarica rapida 3 min = 3 ore. Perfette per lavoro, viaggio e studio.',
        'features'    => ['ANC con 8 microfoni (leader di mercato)', 'Autonomia 30 ore con ANC attivo', 'Ricarica rapida: 3 min → 3 ore', 'LDAC Hi-Res Audio Wireless', 'Multi-point: connessione a 2 dispositivi', 'Pieghevole — borsa inclusa'],
        'campaign'    => '-26% vs listino Sony',
    ],
    [
        'name'        => 'Apple AirPods Pro 2ª gen (USB-C)',
        'category'    => 'Smartphone',
        'price'       => 279.00,
        'sale_price'  => 229.00,
        'stock'       => 'instock',
        'sku'         => 'MTJV3TY-A',
        'tags'        => 'apple,airpods,pro,anc,wireless,usb-c',
        'description' => 'AirPods Pro 2 con ricarica USB-C, Adaptive Audio che si adatta all\'ambiente in tempo reale, cancellazione attiva e Trasparenza. Perfetti con iPhone e Mac.',
        'features'    => ['Adaptive Audio — ANC + Trasparenza adattiva', 'Chip H2 Apple', 'Ricarica USB-C e MagSafe', 'Protezione IP54 cuffie e custodia', 'Audio spaziale personalizzato', 'Fino a 30h con custodia'],
        'campaign'    => '-18% vs Apple Store',
    ],
    [
        'name'        => 'Samsung Galaxy Watch 7 44mm',
        'category'    => 'Smartphone',
        'price'       => 299.00,
        'sale_price'  => 249.00,
        'stock'       => 'instock',
        'sku'         => 'SM-L315FZAAEUB',
        'tags'        => 'samsung,galaxy,watch,7,smartwatch,wear-os',
        'description' => 'Galaxy Watch 7 con chip 3nm più efficiente, monitoraggio avanzato del sonno con analisi del ciclo, sensore BioActive 3-in-1. Wear OS 5 con Galaxy AI.',
        'features'    => ['Chip 3nm — 30% più efficiente', 'Analisi sonno avanzata + apnea', 'Sensore BioActive: ECG, pressione, ossigeno', 'Galaxy AI per consigli salute', 'Cassa alluminio, vetro zaffiro', 'WR50 — nuoto'],
        'campaign'    => '-17% vs listino Samsung',
    ],

    // ══════════════════════════════════════════════════════════
    // TABLET
    // ══════════════════════════════════════════════════════════
    [
        'name'        => 'Samsung Galaxy Tab S9 FE 128GB Wi-Fi',
        'category'    => 'Informatica',
        'price'       => 449.00,
        'sale_price'  => 349.00,
        'stock'       => 'instock',
        'sku'         => 'SM-X510NZAAEUB',
        'tags'        => 'samsung,galaxy,tab,tablet,s9,fe,android',
        'description' => 'Galaxy Tab S9 FE: display 10.9" TFT 90Hz, S Pen inclusa, IP68 resistente all\'acqua. Il tablet Samsung più accessibile con le funzioni premium dei top di gamma.',
        'features'    => ['Display 10.9" TFT 90Hz', 'S Pen inclusa (valore €50)', 'IP68 — sommergibile fino a 1.5m', 'Exynos 1380 8 core', 'Batteria 8000 mAh — fino a 13 ore', 'Storage espandibile microSD'],
        'campaign'    => '-22% vs listino',
    ],

    // ══════════════════════════════════════════════════════════
    // CONNETTIVITÀ — ROUTER
    // ══════════════════════════════════════════════════════════
    [
        'name'        => 'TP-Link Archer AXE75 Wi-Fi 6E Tri-band',
        'category'    => 'Connettività',
        'price'       => 199.00,
        'sale_price'  => 149.00,
        'stock'       => 'instock',
        'sku'         => 'ARCHER-AXE75',
        'tags'        => 'tp-link,router,wifi6e,tri-band,connettività',
        'description' => 'Router tri-band Wi-Fi 6E con banda da 6GHz per i dispositivi più recenti. Velocità aggregata fino a 5.4 Gbps, 8 antenne, beamforming intelligente. Ideale per fibra FTTH.',
        'features'    => ['Wi-Fi 6E — banda 6GHz extra', 'Velocità: 574+2402+2402 Mbps', '8 antenne con beamforming', 'Porta WAN Gigabit + 4 LAN Gigabit', 'App Tether per gestione semplice', 'MU-MIMO 8×8 — fino a 200 dispositivi'],
        'campaign'    => '-25% vs listino TP-Link',
    ],
    [
        'name'        => 'FRITZ!Box 7590 AX Wi-Fi 6',
        'category'    => 'Connettività',
        'price'       => 279.00,
        'sale_price'  => 219.00,
        'stock'       => 'instock',
        'sku'         => '20002879',
        'tags'        => 'fritz,fritzbox,router,wifi6,modem,adsl,fibra',
        'description' => 'Il router-modem più amato dagli utenti italiani. FRITZ!Box 7590 AX con Wi-Fi 6, modem VDSL2/ADSL2+, centralino DECT integrato, interfaccia Fritz!OS semplice e potente.',
        'features'    => ['Wi-Fi 6 dual-band 3600 Mbps', 'Modem VDSL2 supervectoring + ADSL integrato', 'Centralino DECT per telefoni cordless', '4 porte LAN Gigabit + 1 porta WAN', 'Fritz!OS — aggiornamenti 10 anni', 'VPN, firewall, parental control inclusi'],
        'campaign'    => '-21% vs listino AVM',
    ],

    // ══════════════════════════════════════════════════════════
    // PC DESKTOP / SERVER MINI
    // ══════════════════════════════════════════════════════════
    [
        'name'        => 'Mini PC Intel N100 16GB/512GB SSD',
        'category'    => 'Informatica',
        'price'       => 249.00,
        'sale_price'  => 179.00,
        'stock'       => 'instock',
        'sku'         => 'MINIPC-N100-16-512',
        'tags'        => 'mini-pc,intel,n100,desktop,windows,ufficio',
        'description' => 'Mini PC silenzioso con Intel N100, 16 GB RAM DDR4, SSD 512 GB, doppio HDMI 4K. Consuma meno di 15W. Perfetto per ufficio, home-server, media center e scuola.',
        'features'    => ['Intel Alder Lake-N N100 4 core', '16 GB DDR4 — upgrade a 32 GB', 'SSD NVMe 512 GB', 'Dual HDMI 4K60 + USB-C DP', 'Consumo: ~12W a carico pieno', 'Windows 11 Pro incluso'],
        'campaign'    => '-28% vs listino — ideale sostituto del PC fisso',
    ],
];

// ── INSERT ───────────────────────────────────────────────────────────────────
$stmtProduct = $pdo->prepare("
    INSERT INTO products (name, slug, description, category, category_slug,
        price, sale_price, stock_status, sku, tags, featured_order, campaign_label, icon_key)
    VALUES (:name, :slug, :desc, :cat, :cat_slug,
        :price, :sale_price, :stock, :sku, :tags, :feat, :campaign, :icon_key)
");

$stmtFeature = $pdo->prepare("
    INSERT INTO product_features (product_id, feature_text, sort_order)
    VALUES (:product_id, :feature, :sort)
");

$catMap = [
    'Smartphone'    => 'smartphone',
    'Informatica'   => 'informatica',
    'Gaming'        => 'gaming',
    'Connettività'  => 'connettivita',
];
$iconMap = [
    'Smartphone'    => 'smartphone',
    'Informatica'   => 'laptop',
    'Gaming'        => 'gamepad',
    'Connettività'  => 'wifi',
    'Audio'         => 'headphones',
    'Wearable'      => 'watch',
    'Tablet'        => 'tablet',
];

foreach ($products as $i => $p) {
    $s = slug($p['name']);
    $stmtProduct->execute([
        'name'      => $p['name'],
        'slug'      => $s,
        'desc'      => $p['description'],
        'cat'       => $p['category'],
        'cat_slug'  => $catMap[$p['category']] ?? strtolower($p['category']),
        'price'     => $p['price'],
        'sale_price'=> $p['sale_price'],
        'stock'     => $p['stock'],
        'sku'       => $p['sku'],
        'tags'      => $p['tags'],
        'feat'      => $i + 1,
        'campaign'  => $p['campaign'],
        'icon_key'  => $iconMap[$p['category']] ?? 'box',
    ]);
    $productId = (int)$pdo->lastInsertId();

    foreach ($p['features'] as $j => $feat) {
        $stmtFeature->execute(['product_id' => $productId, 'feature' => $feat, 'sort' => $j]);
    }
    echo "Inserted: {$p['name']}\n";
}

echo "\nDone — " . count($products) . " products inserted.\n";
