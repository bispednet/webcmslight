<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
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
        $body = $this->jsonBody();
        if (!$this->validCsrf($body)) {
            Response::json(['ok' => false, 'error' => 'csrf'], 419);
            return;
        }

        $product = $this->paymentProduct(rawurldecode($slug), 'paypal');
        if ($product === null) {
            Response::json(['ok' => false, 'error' => 'product_not_payable'], 404);
            return;
        }

        if (!$this->paypal->isConfigured()) {
            Response::json(['ok' => false, 'error' => 'paypal_not_configured'], 503);
            return;
        }

        $amount = $this->amountForProduct($product);
        if ($amount === null) {
            Response::json(['ok' => false, 'error' => 'invalid_amount'], 422);
            return;
        }

        $deliveryMethod = $this->deliveryMethod($product, (string)($body['delivery_method'] ?? ''));
        $publicId = bin2hex(random_bytes(16));
        $orderId = $this->insertLocalOrder($publicId, $product, 'paypal', $deliveryMethod, $amount, 'created');

        try {
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

            Response::json([
                'ok' => true,
                'id' => $paypalOrderId,
                'public_id' => $publicId,
                'status' => $paypalOrder['status'] ?? 'CREATED',
            ]);
        } catch (\Throwable $e) {
            $this->markOrderFailed($orderId, $e->getMessage());
            Response::json(['ok' => false, 'error' => 'paypal_create_failed'], 502);
        }
    }

    public function capturePayPalOrder(string $paypalOrderId): void
    {
        $body = $this->jsonBody();
        if (!$this->validCsrf($body)) {
            Response::json(['ok' => false, 'error' => 'csrf'], 419);
            return;
        }

        $stmt = $this->db->prepare('SELECT * FROM product_payment_orders WHERE paypal_order_id = :id LIMIT 1');
        $stmt->execute(['id' => rawurldecode($paypalOrderId)]);
        $localOrder = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$localOrder) {
            Response::json(['ok' => false, 'error' => 'order_not_found'], 404);
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

            Response::json([
                'ok' => true,
                'status' => $capture['status'] ?? strtoupper($status),
                'public_id' => $localOrder['public_id'],
            ]);
        } catch (\Throwable $e) {
            $this->markOrderFailed((int)$localOrder['id'], $e->getMessage());
            Response::json(['ok' => false, 'error' => 'paypal_capture_failed'], 502);
        }
    }

    public function createCryptoRequest(string $slug): void
    {
        $body = $this->jsonBody();
        if (!$this->validCsrf($body)) {
            Response::json(['ok' => false, 'error' => 'csrf'], 419);
            return;
        }

        $product = $this->paymentProduct(rawurldecode($slug), 'crypto');
        if ($product === null) {
            Response::json(['ok' => false, 'error' => 'product_not_payable'], 404);
            return;
        }

        $amount = $this->amountForProduct($product);
        if ($amount === null) {
            Response::json(['ok' => false, 'error' => 'invalid_amount'], 422);
            return;
        }

        $deliveryMethod = $this->deliveryMethod($product, (string)($body['delivery_method'] ?? ''));
        $publicId = bin2hex(random_bytes(16));
        $this->insertLocalOrder($publicId, $product, 'crypto', $deliveryMethod, $amount, 'pending_crypto');

        Response::json([
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
        $stmt = $this->db->prepare(
            'UPDATE product_payment_orders SET status = :status, raw_response_json = :raw_response_json WHERE id = :id'
        );
        $stmt->execute([
            'status' => 'failed',
            'raw_response_json' => json_encode(['error' => mb_substr($reason, 0, 500)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'id' => $orderId,
        ]);
    }
}
