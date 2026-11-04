<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use App\Services\Catalog\Suppliers\RunnerAdapter;
use PDO;

final class RunnerCheckoutVerifier
{
    public function __construct(private PDO $db, private array $catalog) {}

    public function refresh(int $id, bool $force = false): array
    {
        $statement = $this->db->prepare('SELECT * FROM products WHERE id=:id');
        $statement->execute(['id' => $id]);
        $product = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$product) throw new \DomainException('Prodotto non trovato');
        if (!CatalogSaleGuard::managed($product)) return $product;
        if (($product['supplier_key'] ?? '') !== 'runner') throw new \DomainException('Fornitore non verificabile al checkout');
        $age = max(0, (int)($this->catalog['checkout_max_age_seconds'] ?? 300));
        $latest = min(strtotime(($product['supplier_price_checked_at'] ?? '') . ' UTC') ?: 0, strtotime(($product['supplier_stock_checked_at'] ?? '') . ' UTC') ?: 0);
        if (!$force && $latest >= time() - $age && CatalogSaleGuard::reason($product, $this->catalog) === null) return $product;
        try {
            $runner = (array)($this->catalog['runner'] ?? []);
            if (function_exists('config') && config('app.env') === 'production') $runner['skip_download'] = false;
            $adapter = new RunnerAdapter($runner);
            $products = $adapter->fetchCommercialProducts();
            $found = null;
            foreach ($products as $raw) if (strcasecmp((string)$raw['sku'], (string)$product['sku']) === 0) { $found = $raw; break; }
            if ($found === null) {
                $this->db->prepare("UPDATE products SET stock_status='ritirato',stock_qty=0,supplier_status='retired',catalog_block_reason='missing_from_supplier',supplier_stock_checked_at=UTC_TIMESTAMP() WHERE id=:id")->execute(['id' => $id]);
                throw new \DomainException('Articolo rimosso dal listino del fornitore');
            }
            (new ProductImporter($this->db, $this->catalog))->refreshOne($found, 'runner', $adapter->capturedAt());
            $statement->execute(['id' => $id]);
            return $statement->fetch(PDO::FETCH_ASSOC);
        } catch (\DomainException $error) {
            throw $error;
        } catch (\Throwable $error) {
            throw new \DomainException('Listino del fornitore non verificabile: pagamento sospeso', 0, $error);
        }
    }
}
