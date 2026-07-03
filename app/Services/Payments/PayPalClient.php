<?php
declare(strict_types=1);

namespace App\Services\Payments;

use RuntimeException;

final class PayPalClient
{
    private string $clientId;
    private string $clientSecret;
    private string $environment;
    private string $currency;
    private string $brandName;
    private string $payeeEmail;

    public function __construct()
    {
        $config = (array)config('paypal', []);
        $this->clientId = trim((string)($config['client_id'] ?? ''));
        $this->clientSecret = trim((string)($config['client_secret'] ?? ''));
        $this->environment = strtolower(trim((string)($config['environment'] ?? 'sandbox')));
        $this->currency = strtoupper(trim((string)($config['currency'] ?? 'EUR')));
        $this->brandName = trim((string)($config['brand_name'] ?? config('app.name', 'Bisped')));
        $this->payeeEmail = trim((string)($config['payee_email'] ?? ''));
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    /**
     * @return array{configured:bool,client_id:string,currency:string,environment:string}
     */
    public function publicConfig(): array
    {
        return [
            'configured' => $this->isConfigured(),
            'client_id' => $this->clientId,
            'currency' => $this->currency,
            'environment' => $this->environment === 'live' ? 'live' : 'sandbox',
        ];
    }

    /**
     * @param array<string,mixed> $product
     * @return array<string,mixed>
     */
    public function createOrder(array $product, string $amount, string $deliveryMethod, string $localOrderId): array
    {
        $accessToken = $this->accessToken();
        $shippingPreference = $deliveryMethod === 'shipping' ? 'GET_FROM_FILE' : 'NO_SHIPPING';
        $itemName = mb_substr((string)($product['name'] ?? 'Prodotto Bisped'), 0, 127);
        $sku = mb_substr((string)($product['sku'] ?? ''), 0, 127);

        $purchaseUnit = [
            'reference_id' => $localOrderId,
            'custom_id' => $localOrderId,
            'description' => $itemName,
            'amount' => [
                'currency_code' => $this->currency,
                'value' => $amount,
                'breakdown' => [
                    'item_total' => [
                        'currency_code' => $this->currency,
                        'value' => $amount,
                    ],
                ],
            ],
            'items' => [[
                'name' => $itemName,
                'quantity' => '1',
                'category' => 'PHYSICAL_GOODS',
                'unit_amount' => [
                    'currency_code' => $this->currency,
                    'value' => $amount,
                ],
            ]],
        ];

        if ($sku !== '') {
            $purchaseUnit['items'][0]['sku'] = $sku;
        }

        if ($this->payeeEmail !== '') {
            $purchaseUnit['payee'] = ['email_address' => $this->payeeEmail];
        }

        return $this->request('POST', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [$purchaseUnit],
            'application_context' => [
                'brand_name' => $this->brandName ?: 'Bisped',
                'shipping_preference' => $shippingPreference,
                'user_action' => 'PAY_NOW',
            ],
        ], $accessToken);
    }

    /**
     * @return array<string,mixed>
     */
    public function captureOrder(string $paypalOrderId): array
    {
        $paypalOrderId = trim($paypalOrderId);
        if ($paypalOrderId === '' || !preg_match('/^[A-Z0-9-]{6,64}$/i', $paypalOrderId)) {
            throw new RuntimeException('Invalid PayPal order id.');
        }

        return $this->request('POST', '/v2/checkout/orders/' . rawurlencode($paypalOrderId) . '/capture', new \stdClass(), $this->accessToken());
    }

    private function accessToken(): string
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('PayPal credentials are missing.');
        }

        [$status, $body, $error] = $this->sendHttpRequest(
            'POST',
            $this->baseUrl() . '/v1/oauth2/token',
            [
                'Accept: application/json',
                'Accept-Language: en_US',
                'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type: application/x-www-form-urlencoded',
            ],
            'grant_type=client_credentials',
            20
        );

        $data = json_decode((string)$body, true);
        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('PayPal authentication failed' . $this->errorSuffix($data, $status, $error));
        }
        if (!is_array($data) || empty($data['access_token'])) {
            throw new RuntimeException('PayPal authentication returned an invalid response.');
        }

        return (string)$data['access_token'];
    }

    /**
     * @param array<string,mixed>|\stdClass|null $payload
     * @return array<string,mixed>
     */
    private function request(string $method, string $path, array|\stdClass|null $payload, string $accessToken): array
    {
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ];

        [$status, $body, $error] = $this->sendHttpRequest(
            $method,
            $this->baseUrl() . $path,
            $headers,
            $payload !== null ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            30
        );

        $data = json_decode((string)$body, true);
        if ($body === false || $status < 200 || $status >= 300 || !is_array($data)) {
            $message = is_array($data) && isset($data['message']) ? (string)$data['message'] : 'PayPal request failed.';
            throw new RuntimeException($message . $this->errorSuffix($data, $status, $error));
        }

        return $data;
    }

    /**
     * @param array<int,string> $headers
     * @return array{0:int,1:string|false,2:string}
     */
    private function sendHttpRequest(string $method, string $url, array $headers, ?string $body, int $timeout): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new RuntimeException('Unable to initialize PayPal request.');
            }

            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => $timeout,
            ]);

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $responseBody = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            return [$status, $responseBody, $error];
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
                'ignore_errors' => true,
                'timeout' => $timeout,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);
        $status = 0;
        foreach (($http_response_header ?? []) as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $match)) {
                $status = (int)$match[1];
                break;
            }
        }

        return [$status, $responseBody, $responseBody === false ? 'HTTP stream request failed.' : ''];
    }

    /**
     * @param mixed $data
     */
    private function errorSuffix(mixed $data, int $status, string $transportError): string
    {
        $bits = [];
        if ($status > 0) {
            $bits[] = 'HTTP ' . $status;
        }
        if (is_array($data)) {
            if (!empty($data['name'])) {
                $bits[] = (string)$data['name'];
            }
            if (!empty($data['debug_id'])) {
                $bits[] = 'debug_id=' . (string)$data['debug_id'];
            }
            if (!empty($data['details'][0]['issue'])) {
                $bits[] = 'issue=' . (string)$data['details'][0]['issue'];
            }
        }
        if ($transportError !== '') {
            $bits[] = $transportError;
        }

        return $bits ? ' (' . implode('; ', $bits) . ')' : '.';
    }

    private function baseUrl(): string
    {
        return $this->environment === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}
