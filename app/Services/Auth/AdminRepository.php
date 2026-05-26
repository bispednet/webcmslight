<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Database;
use PDO;

final class AdminRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByWallet(string $address): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE LOWER(wallet_address) = LOWER(:address) LIMIT 1');
        $stmt->execute(['address' => $address]);
        $admin = $stmt->fetch();

        return $admin ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM admins WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute(['email' => $email]);
        $admin = $stmt->fetch();

        return $admin ?: null;
    }

    public function ensurePasswordAdmin(string $name, string $email): array
    {
        $admin = $this->findByEmail($email);
        if ($admin) {
            return $admin;
        }

        $wallet = '0x' . substr(sha1('bisped-local-admin:' . strtolower($email)), 0, 40);
        $stmt = $this->db->prepare(
            'INSERT INTO admins (display_name, wallet_address, email)
             VALUES (:display_name, :wallet_address, :email)'
        );
        $stmt->execute([
            'display_name' => $name,
            'wallet_address' => $wallet,
            'email' => $email,
        ]);

        return $this->findByEmail($email) ?: [
            'id' => (int)$this->db->lastInsertId(),
            'display_name' => $name,
            'wallet_address' => $wallet,
            'email' => $email,
        ];
    }

    public function ensureBridgeAdmin(string $name, ?string $email, string $subject): array
    {
        if ($email) {
            $admin = $this->findByEmail($email);
            if ($admin) {
                return $admin;
            }
        }

        $wallet = '0x' . substr(sha1('bisped-admin-bridge:' . strtolower($subject)), 0, 40);
        $stmt = $this->db->prepare(
            'INSERT INTO admins (display_name, wallet_address, email)
             VALUES (:display_name, :wallet_address, :email)'
        );
        $stmt->execute([
            'display_name' => $name,
            'wallet_address' => $wallet,
            'email' => $email,
        ]);

        return [
            'id' => (int)$this->db->lastInsertId(),
            'display_name' => $name,
            'wallet_address' => $wallet,
            'email' => $email,
        ];
    }

    public function recordSession(int $adminId, string $sessionToken, ?string $ip, ?string $userAgent, int $ttlMinutes = 60): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO admin_sessions (admin_id, session_token, ip_address, user_agent, expires_at)
             VALUES (:admin_id, :token, :ip, :agent, :expires_at)'
        );

        $expires = (new \DateTimeImmutable("+{$ttlMinutes} minutes"))->format('Y-m-d H:i:s');

        $stmt->execute([
            'admin_id' => $adminId,
            'token' => $sessionToken,
            'ip' => $ip ? @inet_pton($ip) : null,
            'agent' => substr($userAgent ?? '', 0, 250),
            'expires_at' => $expires,
        ]);
    }

    public function deleteSession(string $sessionToken): void
    {
        $stmt = $this->db->prepare('DELETE FROM admin_sessions WHERE session_token = :token');
        $stmt->execute(['token' => $sessionToken]);
    }
}
