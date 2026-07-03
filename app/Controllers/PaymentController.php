<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Response;
use App\Services\Cms\ContentRepository;
use App\Services\Payments\PayPalClient;
use App\Services\Security\Csrf;
use PDO;

final class PaymentController
{
    private ContentRepository $content;
    private PayPalClient $paypal;
    private PDO $db;

    public function __construct()
    {
        $this->content = new ContentRepository();
        $this->paypal = new PayPalClient();
        $this->db = Database::connection();
    }

    public function createPayPalOrder(string $slug): void
    {
        try {
            $body = $this->jsonBody();
            if (!$this->validCsrf($body)) {
                $this->jsonWithFreshCsrf(['ok' => false, 'error' => 'csrf', 'message' => 'Sessione scaduta: ricarica la pagina e riprova.'], 419);
                return;
            }

            $product = $this->paymentProduct(rawurldecode($slug), 'paypal');
            if ($product === null) {
                $this->jsonWithFreshCsrf(['ok' => false, 'error' => 'product_not_payable', 'message' => 'Prodotto non abilitato al pagamento PayPal.'], 404);
                return;
            }

            if (!$this->paypal->isConfigured()) {
                $this->jsonWithFreshCsrf(['ok' => false, 'error' => 'paypal_not_configured', 'message' => 'PayPal non configurato sul server.'], 503);
                return;
            }

            $amount = $this->amountForProduct($product);
            if ($amount === null) {
                $this->jsonWithFreshCsrf(['ok' => false, 'error' => 'invalid_amount', 'message' => 'Importo prodotto non valido.'], 422);
                return;
            }

            $deliveryMethod = $this->deliveryMethod($product, (string)($body['delivery_method'] ?? ''));
            $publicId = bin2hex(random_bytes(16));
            $orderId = $this->insertLocalOrder($publicId, $product, 'paypal', $deliveryMethod, $amount, 'created');

            $paypalOrder = $this->paypal->createOrder($product, $amount, $deliveryMethod, $publicId);
            $paypalOrderId = (string)($paypalOrder['id'] ?? '');
            if ($paypalOrderId === '') {
                throw new \RuntimeException('PayPal did not return an order id.');
            }

            $stmt = $this->db->prepare(
                'UPDATE product_payment_orders
                 SET paypal_order_id = :paypal_order_id, raw_response_json = :raw_response_json
                 WHERE id = :id'
            );
            $stmt->execute([
                'paypal_order_id' => $paypalOrderId,
                'raw_response_json' => json_encode($paypalOrder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'id' => $orderId,
            ]);

            $this->jsonWithFreshCsrf([
                'ok' => true,
                'id' => $paypalOrderId,
                'public_id' => $publicId,
                'status' => $paypalOrder['status'] ?? 'CREATED',
            ]);
        } catch (\Throwable $e) {
            Logger::error('PayPal order creation failed', ['error' => $e->getMessage(), 'slug' => $slug]);
            if (isset($orderId)) {
                $this->markOrderFailed((int)$orderId, $e->getMessage());
            }
            $this->jsonWithFreshCsrf([
                'ok' => false,
                'error' => 'paypal_create_failed',
                'message' => 'PayPal non ha creato l\'ordine. Contatta il negozio o riprova.',
            ], 502);
        }
    }

    public function capturePayPalOrder(string $paypalOrderId): void
    {
        $body = $this->jsonBody();
        if (!$this->validCsrf($body)) {
            $this->jsonWithFreshCsrf(['ok' => false, 'error' => 'csrf', 'message' => 'Sessione scaduta: ricarica la pagina e riprova.'], 419);
            return;
        }

        $stmt = $this->db->prepare('SELECT * FROM product_payment_orders WHERE paypal_order_id = :id LIMIT 1');
        $stmt->execute(['id' => rawurldecode($paypalOrderId)]);
        $localOrder = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$localOrder) {
            $this->jsonWithFreshCsrf(['ok' => false, 'error' => 'order_not_found', 'message' => 'Ordine locale non trovato: contatta il negozio.'], 404);
            return;
        }

        try {
            $capture = $this->paypal->captureOrder((string)$localOrder['paypal_order_id']);
            $captureInfo = $capture['purchase_units'][0]['payments']['captures'][0] ?? [];
            $payer = (array)($capture['payer'] ?? []);
            $payerName = trim((string)($payer['name']['given_name'] ?? '') . ' ' . (string)($payer['name']['surname'] ?? ''));
            $status = strtoupper((string)($capture['status'] ?? '')) === 'COMPLETED' ? 'completed' : 'approved';

            $update = $this->db->prepare(
                'UPDATE product_payment_orders
                 SET status = :status,
                     paypal_capture_id = :paypal_capture_id,
                     payer_email = :payer_email,
                     payer_name = :payer_name,
                     raw_response_json = :raw_response_json
                 WHERE id = :id'
            );
            $update->execute([
                'status' => $status,
                'paypal_capture_id' => (string)($captureInfo['id'] ?? ''),
                'payer_email' => (string)($payer['email_address'] ?? ''),
                'payer_name' => $payerName ?: null,
                'raw_response_json' => json_encode($capture, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'id' => (int)$localOrder['id'],
            ]);

            $this->jsonWithFreshCsrf([
                'ok' => true,
                'status' => $capture['status'] ?? strtoupper($status),
                'public_id' => $localOrder['public_id'],
            ]);
        } catch (\Throwable $e) {
            Logger::error('PayPal order capture failed', [
                'error' => $e->getMessage(),
                'paypal_order_id' => (string)($localOrder['paypal_order_id'] ?? ''),
                'local_order_id' => (int)($localOrder['id'] ?? 0),
            ]);
            $this->markOrderFailed((int)$localOrder['id'], $e->getMessage());
            $this->jsonWithFreshCsrf([
                'ok' => false,
                'error' => 'paypal_capture_failed',
                'message' => 'PayPal ha approvato il pagamento, ma la conferma server non e riuscita. Contatta il negozio prima di riprovare.',
            ], 502);
        }
    }

    public function createCryptoRequest(string $slug): void
    {
        $body = $this->jsonBody();
        if (!$this->validCsrf($body)) {
            $this->jsonWithFreshCsrf(['ok' => false, 'error' => 'csrf'], 419);
            return;
        }

        $product = $this->paymentProduct(rawurldecode($slug), 'crypto');
        if ($product === null) {
            $this->jsonWithFreshCsrf(['ok' => false, 'error' => 'product_not_payable'], 404);
            return;
        }

        $amount = $this->amountForProduct($product);
        if ($amount === null) {
            $this->jsonWithFreshCsrf(['ok' => false, 'error' => 'invalid_amount'], 422);
            return;
        }

        $deliveryMethod = $this->deliveryMethod($product, (string)($body['delivery_method'] ?? ''));
        $publicId = bin2hex(random_bytes(16));
        $this->insertLocalOrder($publicId, $product, 'crypto', $deliveryMethod, $amount, 'pending_crypto');

        $this->jsonWithFreshCsrf([
            'ok' => true,
            'public_id' => $publicId,
            'message' => 'Richiesta crypto registrata. Il negozio confermera wallet, rete e disponibilita prima del pagamento.',
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function jsonWithFreshCsrf(array $payload, int $status = 200): void
    {
        $payload['csrf'] = Csrf::token();
        Response::json($payload, $status);
    }

    /**
     * @param array<string,mixed> $body
     */
    private function validCsrf(array $body): bool
    {
        $token = (string)($body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        return Csrf::verify($token);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function paymentProduct(string $slug, string $method): ?array
    {
        $product = $this->content->getProductBySlug($slug);
        if (!$product) {
            return null;
        }

        if ((int)($product['is_payment_product'] ?? 0) !== 1 || (string)($product['payment_method'] ?? 'none') !== $method) {
            return null;
        }

        return $product;
    }

    /**
     * @param array<string,mixed> $product
     */
    private function amountForProduct(array $product): ?string
    {
        $amount = $product['sale_price'] ?: $product['price'];
        if ($amount === null || !is_numeric($amount) || (float)$amount <= 0) {
            return null;
        }

        return number_format((float)$amount, 2, '.', '');
    }

    /**
     * @param array<string,mixed> $product
     */
    private function deliveryMethod(array $product, string $requested): string
    {
        $configured = (string)($product['delivery_method'] ?? 'pickup');
        if ($configured === 'shipping') {
            return 'shipping';
        }
        if ($configured === 'both' && $requested === 'shipping') {
            return 'shipping';
        }

        return 'pickup';
    }

    /**
     * @param array<string,mixed> $product
     */
    private function insertLocalOrder(string $publicId, array $product, string $paymentMethod, string $deliveryMethod, string $amount, string $status): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO product_payment_orders
                (public_id, product_id, product_name, product_slug, payment_method, delivery_method, status, currency, amount)
             VALUES
                (:public_id, :product_id, :product_name, :product_slug, :payment_method, :delivery_method, :status, :currency, :amount)'
        );
        $stmt->execute([
            'public_id' => $publicId,
            'product_id' => (int)$product['id'],
            'product_name' => (string)$product['name'],
            'product_slug' => (string)$product['slug'],
            'payment_method' => $paymentMethod,
            'delivery_method' => $deliveryMethod,
            'status' => $status,
            'currency' => strtoupper((string)config('paypal.currency', 'EUR')),
            'amount' => $amount,
        ]);

        return (int)$this->db->lastInsertId();
    }

    private function markOrderFailed(int $orderId, string $reason): void
    {
        try {
            $stmt = $this->db->prepare(
                'UPDATE product_payment_orders SET status = :status, raw_response_json = :raw_response_json WHERE id = :id'
            );
            $stmt->execute([
                'status' => 'failed',
                'raw_response_json' => json_encode(['error' => mb_substr($reason, 0, 500)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            Logger::error('Unable to mark payment order as failed', ['error' => $e->getMessage(), 'order_id' => $orderId]);
        }
    }
}
