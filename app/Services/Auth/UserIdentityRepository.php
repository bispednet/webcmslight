<?php
declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\Database;
use PDO;

final class UserIdentityRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function upsertGoogleUser(string $email, string $name, ?string $avatarUrl, string $subject, string $role): array
    {
        $user = $this->findUserByEmail($email);
        if (!$user) {
            $stmt = $this->db->prepare(
                'INSERT INTO users (email, display_name, avatar_url) VALUES (:email, :name, :avatar)'
            );
            $stmt->execute(['email' => $email, 'name' => $name, 'avatar' => $avatarUrl]);
            $user = $this->findUserById((int)$this->db->lastInsertId());
        } else {
            $stmt = $this->db->prepare('UPDATE users SET display_name = :name, avatar_url = :avatar WHERE id = :id');
            $stmt->execute(['id' => $user['id'], 'name' => $name, 'avatar' => $avatarUrl]);
            $user = $this->findUserById((int)$user['id']);
        }

        $this->upsertIdentity((int)$user['id'], 'google', $subject, $email, ['avatar_url' => $avatarUrl]);
        $this->ensureRole((int)$user['id'], $role);

        return $this->findUserById((int)$user['id']) ?: $user;
    }

    public function upsertWalletUser(string $chain, string $address, bool $adminAllowed): array
    {
        $provider = $chain === 'solana' ? 'solana_wallet' : 'evm_wallet';
        $subject = $chain . ':' . $address;

        $stmt = $this->db->prepare(
            'SELECT u.* FROM users u
             INNER JOIN user_identities i ON i.user_id = u.id
             WHERE i.provider = :provider AND i.provider_subject = :subject
             LIMIT 1'
        );
        $stmt->execute(['provider' => $provider, 'subject' => $subject]);
        $user = $stmt->fetch();

        if (!$user) {
            $name = strtoupper($chain) . ' ' . substr($address, 0, 6) . '...' . substr($address, -4);
            $insert = $this->db->prepare('INSERT INTO users (display_name) VALUES (:name)');
            $insert->execute(['name' => $name]);
            $user = $this->findUserById((int)$this->db->lastInsertId());
        }

        $userId = (int)$user['id'];
        $this->upsertIdentity($userId, $provider, $subject, null, ['address' => $address, 'chain' => $chain]);
        $this->upsertWallet($userId, $chain, $address, $adminAllowed);
        $this->ensureRole($userId, $adminAllowed ? 'admin' : 'cliente');

        return $this->findUserById($userId) ?: $user;
    }

    public function issueWalletNonce(string $provider, string $address, string $message): string
    {
        $nonce = bin2hex(random_bytes(32));
        $expires = (new \DateTimeImmutable('+5 minutes'))->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO auth_nonces (provider, address, nonce, message, expires_at)
             VALUES (:provider, :address, :nonce, :message, :expires_at)'
        );
        $stmt->execute([
            'provider' => $provider,
            'address' => $address,
            'nonce' => $nonce,
            'message' => $message,
            'expires_at' => $expires,
        ]);

        return $nonce;
    }

    public function consumeWalletNonce(string $provider, string $address, string $nonce): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT message FROM auth_nonces
             WHERE provider = :provider AND address = :address AND nonce = :nonce
               AND consumed_at IS NULL AND expires_at >= NOW()
             LIMIT 1'
        );
        $stmt->execute(['provider' => $provider, 'address' => $address, 'nonce' => $nonce]);
        $message = $stmt->fetchColumn();
        if (!is_string($message) || $message === '') {
            return null;
        }

        $update = $this->db->prepare('UPDATE auth_nonces SET consumed_at = NOW() WHERE nonce = :nonce');
        $update->execute(['nonce' => $nonce]);

        return $message;
    }

    public function audit(?int $userId, string $provider, string $subject, string $result, ?string $reason = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO auth_audit_log (user_id, provider, subject, result, reason, ip_address, user_agent)
             VALUES (:user_id, :provider, :subject, :result, :reason, :ip, :agent)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'provider' => $provider,
            'subject' => $subject,
            'result' => $result,
            'reason' => $reason,
            'ip' => !empty($_SERVER['REMOTE_ADDR']) ? @inet_pton((string)$_SERVER['REMOTE_ADDR']) : null,
            'agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    }

    private function findUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    private function findUserById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    private function upsertIdentity(int $userId, string $provider, string $subject, ?string $email, array $metadata): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_identities (user_id, provider, provider_subject, provider_email, metadata_json)
             VALUES (:user_id, :provider, :subject, :email, :metadata)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), provider_email = VALUES(provider_email), metadata_json = VALUES(metadata_json)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'provider' => $provider,
            'subject' => $subject,
            'email' => $email,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
        ]);
    }

    private function upsertWallet(int $userId, string $chain, string $address, bool $adminAllowed): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_wallets (user_id, chain, address, is_admin_allowed)
             VALUES (:user_id, :chain, :address, :admin)
             ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), is_admin_allowed = VALUES(is_admin_allowed)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'chain' => $chain,
            'address' => $address,
            'admin' => $adminAllowed ? 1 : 0,
        ]);
    }

    private function ensureRole(int $userId, string $role): void
    {
        $stmt = $this->db->prepare('INSERT IGNORE INTO user_roles (user_id, role_key) VALUES (:user_id, :role)');
        $stmt->execute(['user_id' => $userId, 'role' => $role]);
    }
}
