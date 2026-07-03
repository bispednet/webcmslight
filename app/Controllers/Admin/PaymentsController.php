<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use PDO;

final class PaymentsController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function index(): void
    {
        $orders = [];
        $error = null;

        if (!$this->tableExists('product_payment_orders')) {
            $error = 'Tabella pagamenti non ancora presente. Esegui scripts/migrate-payments.php.';
        } else {
            $stmt = $this->db->query(
                'SELECT o.*, p.image_url
                 FROM product_payment_orders o
                 LEFT JOIN products p ON p.id = o.product_id
                 ORDER BY o.created_at DESC, o.id DESC
                 LIMIT 200'
            );
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $this->view('admin/payments/index', [
            'title' => 'Pagamenti',
            'orders' => $orders,
            'error' => $error,
        ]);
    }

    private function tableExists(string $table): bool
    {
        if (!preg_match('/^[a-z0-9_]+$/', $table)) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table'
        );
        $stmt->execute(['table' => $table]);

        return (int)$stmt->fetchColumn() > 0;
    }
}
