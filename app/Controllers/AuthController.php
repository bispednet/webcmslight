<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Core\Response;
use App\Core\View;
use App\Services\Auth\AdminNonceService;
use App\Services\Auth\AdminRepository;
use App\Services\Auth\SessionGuard;
use App\Services\Auth\SolanaVerifier;
use App\Services\Auth\UserIdentityRepository;
use App\Services\Auth\WalletVerifier;
use App\Services\Security\Csrf;
use App\Support\AdminMode;
use App\Support\Flash;
use App\Support\Session;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $notice = Flash::pull('auth_notice');
        $error = Flash::pull('auth_error');
        $config = Container::get('config');
        $projectId = $config['wallet']['project_id'] ?? '';
        $rpcUrl = $config['wallet']['rpc_url'] ?? '';
        $googleConfigured = !empty($config['google']['client_id']) && !empty($config['google']['client_secret']) && !empty($config['google']['redirect_uri']);
        $csrfToken = Csrf::token();

        View::render('public/login', compact('notice', 'error', 'projectId', 'rpcUrl', 'googleConfigured', 'csrfToken'));
    }

    public function passwordLogin(): void
    {
        Session::ensureStarted();

        if (!$this->verifyFormCsrf('auth_error')) {
            $this->redirect('/login');
        }
        if (!$this->throttleAuthAction('login')) {
            Flash::set('auth_error', 'Troppi tentativi ravvicinati. Attendi un minuto e riprova.');
            $this->redirect('/login');
        }

        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        $config = Container::get('config', []);
        $users = is_array($config['auth_users'] ?? null) ? $config['auth_users'] : [];

        $matched = $this->findRegisteredPasswordUser($email, $password);
        foreach ($users as $user) {
            if ($matched) {
                break;
            }
            if (!is_array($user)) {
                continue;
            }
            if (strtolower((string)($user['email'] ?? '')) === $email && hash_equals((string)($user['password'] ?? ''), $password)) {
                $matched = $user;
                break;
            }
        }

        if (!$matched) {
            Flash::set('auth_error', 'Credenziali non valide. Controlla email e password.');
            $this->redirect('/login');
        }

        $role = (string)($matched['role'] ?? 'cliente');
        $name = (string)($matched['name'] ?? $email);

        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;

        if (in_array($role, ['admin', 'commesso'], true)) {
            $repository = new AdminRepository();
            $admin = $repository->ensurePasswordAdmin($name, $email);

            $guard = new SessionGuard();
            $guard->login((int)$admin['id']);
            AdminMode::setWallet((string)$admin['wallet_address']);
            AdminMode::disable();

            $repository->recordSession((int)$admin['id'], session_id(), $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, 480);
            $this->redirect('/admin/dashboard');
        }

        Flash::set('auth_notice', 'Accesso effettuato. Benvenuto nella tua area Bisped.');
        $this->redirect('/area-clienti');
    }

    public function register(): void
    {
        Session::ensureStarted();

        if (!$this->verifyFormCsrf('auth_error')) {
            $this->redirect('/login');
        }
        if (!$this->throttleAuthAction('register')) {
            Flash::set('auth_error', 'Troppi tentativi ravvicinati. Attendi un minuto e riprova.');
            $this->redirect('/login');
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 8) {
            Flash::set('auth_error', 'Inserisci nome, email valida e password di almeno 8 caratteri.');
            $this->redirect('/login');
        }

        $identity = new UserIdentityRepository();
        $user = $identity->upsertGoogleUser($email, $name, '', 'local:' . $email, 'cliente');
        $pdo = \App\Core\Database::connection();
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute([
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => (int)$user['id'],
        ]);

        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = 'cliente';

        Flash::set('auth_notice', 'Registrazione completata. Benvenuto nella tua area Bisped.');
        $this->redirect('/area-clienti');
    }

    public function issueNonce(): void
    {
        $nonceService = new AdminNonceService();
        $nonce = $nonceService->issueNonce();

        Response::json([
            'nonce' => $nonce,
            'message' => "Bisped Admin Login\nNonce: {$nonce}",
        ]);
    }

    public function googleRedirect(): void
    {
        Session::ensureStarted();
        $google = Container::get('config', [])['google'] ?? [];
        $clientId = (string)($google['client_id'] ?? '');
        $redirectUri = (string)($google['redirect_uri'] ?? '');

        if ($clientId === '' || $redirectUri === '') {
            Flash::set('auth_error', 'Login Google non configurato: mancano client ID o redirect URI.');
            $this->redirect('/login');
        }

        $state = bin2hex(random_bytes(24));
        $_SESSION['google_oauth_state'] = $state;

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        $this->redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    public function googleCallback(): void
    {
        Session::ensureStarted();
        $state = (string)($_GET['state'] ?? '');
        $code = (string)($_GET['code'] ?? '');
        $expectedState = (string)($_SESSION['google_oauth_state'] ?? '');
        unset($_SESSION['google_oauth_state']);

        if ($state === '' || $code === '' || !hash_equals($expectedState, $state)) {
            Flash::set('auth_error', 'Login Google non valido o scaduto.');
            $this->redirect('/login');
        }

        $google = Container::get('config', [])['google'] ?? [];
        $token = $this->exchangeGoogleCode($code, $google);
        if (!$token || empty($token['id_token'])) {
            Flash::set('auth_error', 'Google non ha restituito un token valido.');
            $this->redirect('/login');
        }

        $profile = $this->verifyGoogleToken((string)$token['id_token'], (string)($google['client_id'] ?? ''));
        if (!$profile) {
            Flash::set('auth_error', 'Token Google non verificabile.');
            $this->redirect('/login');
        }

        $email = strtolower((string)($profile['email'] ?? ''));
        $adminEmails = array_map('strtolower', (array)($google['admin_emails'] ?? []));
        $role = in_array($email, $adminEmails, true) ? 'admin' : 'cliente';
        $name = (string)($profile['name'] ?? $email);

        $identity = new UserIdentityRepository();
        $user = $identity->upsertGoogleUser($email, $name, (string)($profile['picture'] ?? ''), (string)$profile['sub'], $role);
        $identity->audit((int)$user['id'], 'google', $email, 'success', null);

        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_avatar'] = (string)($profile['picture'] ?? '');

        if ($role === 'admin') {
            $this->loginBridgeAdmin($name, $email, 'google:' . $email);
            $this->redirect('/admin/dashboard');
        }

        $this->redirect('/area-clienti');
    }

    public function issueWalletNonce(): void
    {
        $chain = strtolower((string)($_GET['chain'] ?? 'evm'));
        $address = trim((string)($_GET['address'] ?? ''));
        $provider = $chain === 'solana' ? 'solana_wallet' : 'evm_wallet';

        if ($address === '') {
            Response::json(['error' => 'Missing wallet address.'], 422);
            return;
        }

        $message = "Bisped Login\nChain: {$chain}\nAddress: {$address}\nNonce: " . bin2hex(random_bytes(16));
        $repository = new UserIdentityRepository();
        $nonce = $repository->issueWalletNonce($provider, $address, $message);

        Response::json([
            'nonce' => $nonce,
            'message' => $message,
            'chain' => $chain,
        ]);
    }

    public function verifyWallet(): void
    {
        Session::ensureStarted();
        $payload = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($payload)) {
            Response::json(['error' => 'Invalid request payload.'], 400);
            return;
        }

        $chain = strtolower((string)($payload['chain'] ?? 'evm'));
        $address = trim((string)($payload['address'] ?? ''));
        $signature = (string)($payload['signature'] ?? '');
        $nonce = (string)($payload['nonce'] ?? '');
        $provider = $chain === 'solana' ? 'solana_wallet' : 'evm_wallet';

        $identity = new UserIdentityRepository();
        $message = $identity->consumeWalletNonce($provider, $address, $nonce);
        if (!$message) {
            $identity->audit(null, $provider, $address, 'failure', 'nonce_invalid');
            Response::json(['error' => 'Nonce expired or invalid.'], 400);
            return;
        }

        $valid = $chain === 'solana'
            ? (new SolanaVerifier())->verifySignature($address, $message, $signature)
            : (new WalletVerifier())->verifyEvmSignature($address, $message, $signature);

        if (!$valid) {
            $identity->audit(null, $provider, $address, 'failure', 'signature_invalid');
            Response::json(['error' => 'Wallet signature verification failed.'], 401);
            return;
        }

        $adminAllowed = $this->isWalletAdminAllowed($chain, $address);
        $user = $identity->upsertWalletUser($chain, $chain === 'evm' ? strtolower($address) : $address, $adminAllowed);
        $identity->audit((int)$user['id'], $provider, $address, 'success', null);

        $_SESSION['user_email'] = '';
        $_SESSION['user_name'] = (string)($user['display_name'] ?? strtoupper($chain) . ' wallet');
        $_SESSION['user_role'] = $adminAllowed ? 'admin' : 'cliente';

        if ($adminAllowed) {
            $this->loginBridgeAdmin((string)$_SESSION['user_name'], null, $provider . ':' . $address);
            Response::json(['success' => true, 'redirect' => '/admin/dashboard']);
            return;
        }

        Response::json(['success' => true, 'redirect' => '/area-clienti']);
    }

    public function verify(): void
    {
        $body = file_get_contents('php://input');
        $payload = json_decode($body, true);

        if (!is_array($payload)) {
            Response::json(['error' => 'Invalid request payload.'], 400);
            return;
        }

        $nonce = $payload['nonce'] ?? '';
        $address = $payload['address'] ?? '';
        $signature = $payload['signature'] ?? '';

        if (!is_string($nonce) || !is_string($address) || !is_string($signature)) {
            Response::json(['error' => 'Missing required fields.'], 422);
            return;
        }

        $nonceService = new AdminNonceService();

        $verifier = new WalletVerifier();
        $message = "Bisped Admin Login\nNonce: {$nonce}";

        if (!$verifier->verifyEvmSignature($address, $message, $signature)) {
            Response::json(['error' => 'Signature verification failed.'], 401);
            return;
        }

        $repository = new AdminRepository();
        $admin = $repository->findByWallet($address);

        if (!$admin) {
            Response::json(['error' => 'Wallet not authorized.'], 403);
            return;
        }

        if (!$nonceService->consume($nonce, (int)$admin['id'])) {
            Response::json(['error' => 'Nonce expired or invalid.'], 400);
            return;
        }

        $guard = new SessionGuard();
        $guard->login((int)$admin['id']);
        AdminMode::setWallet($address);
        AdminMode::disable();

        $sessionId = session_id();
        $repository->recordSession((int)$admin['id'], $sessionId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);

        Response::json([
            'success' => true,
            'redirect' => '/admin/dashboard',
        ]);
    }

    public function logout(): void
    {
        Session::ensureStarted();
        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify(is_string($token) ? $token : null)) {
            Flash::set('auth_error', 'Sessione scaduta. Riprova.');
            $this->redirect('/login');
        }

        $sessionId = session_id();

        $repository = new AdminRepository();
        if ($sessionId) {
            $repository->deleteSession($sessionId);
        }

        $guard = new SessionGuard();
        $guard->logout();

        Flash::set('auth_notice', 'Sei uscito dalla tua area riservata.');
        $this->redirect('/login');
    }

    private function loginBridgeAdmin(string $name, ?string $email, string $subject): void
    {
        $repository = new AdminRepository();
        $admin = $repository->ensureBridgeAdmin($name, $email, $subject);
        $guard = new SessionGuard();
        $guard->login((int)$admin['id']);
        AdminMode::setWallet((string)$admin['wallet_address']);
        AdminMode::disable();
        $repository->recordSession((int)$admin['id'], session_id(), $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, 480);
    }

    private function verifyFormCsrf(string $flashKey): bool
    {
        $token = $_POST['csrf_token'] ?? null;
        if (Csrf::verify(is_string($token) ? $token : null)) {
            return true;
        }

        Flash::set($flashKey, 'Sessione scaduta. Ricarica la pagina e riprova.');
        return false;
    }

    private function throttleAuthAction(string $bucket): bool
    {
        $key = '_auth_rate_' . $bucket;
        $now = time();
        $attempts = $_SESSION[$key] ?? [];
        if (!is_array($attempts)) {
            $attempts = [];
        }
        $attempts = array_values(array_filter($attempts, static fn(int $ts): bool => $ts > $now - 60));
        if (count($attempts) >= 8) {
            $_SESSION[$key] = $attempts;
            return false;
        }
        $attempts[] = $now;
        $_SESSION[$key] = $attempts;
        return true;
    }

    private function findRegisteredPasswordUser(string $email, string $password): ?array
    {
        try {
            $pdo = \App\Core\Database::connection();
            $stmt = $pdo->prepare(
                'SELECT u.id, u.email, u.display_name, u.password_hash, GROUP_CONCAT(r.role_key) AS roles
                 FROM users u
                 LEFT JOIN user_roles r ON r.user_id = u.id
                 WHERE LOWER(u.email) = LOWER(:email) AND u.status = "active"
                 GROUP BY u.id, u.email, u.display_name, u.password_hash
                 LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            if (!$user || empty($user['password_hash']) || !password_verify($password, (string)$user['password_hash'])) {
                return null;
            }

            $roles = array_filter(explode(',', (string)($user['roles'] ?? '')));
            $role = in_array('admin', $roles, true) ? 'admin' : (in_array('commesso', $roles, true) ? 'commesso' : 'cliente');

            return [
                'name' => (string)($user['display_name'] ?: $email),
                'email' => $email,
                'role' => $role,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function exchangeGoogleCode(string $code, array $google): ?array
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code,
                'client_id' => (string)($google['client_id'] ?? ''),
                'client_secret' => (string)($google['client_secret'] ?? ''),
                'redirect_uri' => (string)($google['redirect_uri'] ?? ''),
                'grant_type' => 'authorization_code',
            ]),
            CURLOPT_TIMEOUT => 12,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if (!is_string($raw) || $status < 200 || $status >= 300) {
            return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private function verifyGoogleToken(string $idToken, string $clientId): ?array
    {
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if (!is_string($raw) || $status < 200 || $status >= 300) {
            return null;
        }

        $profile = json_decode($raw, true);
        if (!is_array($profile)) {
            return null;
        }
        if (($profile['aud'] ?? '') !== $clientId) {
            return null;
        }
        if (($profile['email_verified'] ?? 'false') !== 'true') {
            return null;
        }

        return $profile;
    }

    private function isWalletAdminAllowed(string $chain, string $address): bool
    {
        $wallet = Container::get('config', [])['wallet'] ?? [];
        if ($chain === 'solana') {
            return in_array($address, (array)($wallet['admin_solana_addresses'] ?? []), true);
        }

        $address = strtolower($address);
        $allowed = array_map('strtolower', array_merge(
            (array)($wallet['admin_evm_addresses'] ?? []),
            (array)($wallet['allowed_addresses'] ?? [])
        ));

        return in_array($address, $allowed, true);
    }
}
