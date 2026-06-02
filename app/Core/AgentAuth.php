<?php
declare(strict_types=1);

namespace App\Core;

final class AgentAuth
{
    public static function verify(): bool
    {
        $config = Container::get('config', []);
        $key = trim((string)($config['agent']['api_key'] ?? ''));
        if ($key === '') {
            return false;
        }

        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return hash_equals($key, trim($m[1]));
        }

        return false;
    }

    public static function requireAuth(): void
    {
        if (!self::verify()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized. Provide Authorization: Bearer {agent_api_key}.']);
            exit;
        }
    }
}
