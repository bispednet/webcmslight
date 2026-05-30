<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Response;
use App\Services\Ai\ConciergeOrchestrator;
use App\Services\Security\Csrf;
use App\Support\Session;

final class AiConciergeController extends Controller
{
    public function bootstrap(): void
    {
        Session::ensureStarted();
        try {
            $result = $this->service()->bootstrap(session_id(), $this->locale(), [
                'entry_context' => $_GET['context'] ?? '',
                'entry_url' => $_SERVER['HTTP_REFERER'] ?? '',
            ]);
            Response::json($result + ['csrf' => Csrf::token()]);
        } catch (\Throwable $e) {
            $this->error($e);
        }
    }

    public function message(): void
    {
        $this->advance(null);
    }

    public function choice(): void
    {
        $payload = $this->payload();
        $this->advance(is_string($payload['choice'] ?? null) ? $payload['choice'] : '');
    }

    public function whatsappHandoff(): void
    {
        try {
            $payload = $this->authorizedPayload();
            $result = $this->service()->handoff((string)($payload['conversation_id'] ?? ''), session_id());
            Response::json($result + ['csrf' => Csrf::token()]);
        } catch (\Throwable $e) {
            $this->error($e);
        }
    }

    private function advance(?string $choice): void
    {
        try {
            $payload = $this->authorizedPayload();
            if ($choice === null) {
                $choice = is_string($payload['choice'] ?? null) ? $payload['choice'] : null;
            }
            $message = is_string($payload['message'] ?? null) ? $payload['message'] : '';
            $result = $this->service()->advance((string)($payload['conversation_id'] ?? ''), session_id(), $message, $choice ?: null);
            Response::json($result + ['csrf' => Csrf::token()]);
        } catch (\Throwable $e) {
            $this->error($e);
        }
    }

    private function authorizedPayload(): array
    {
        Session::ensureStarted();
        $payload = $this->payload();
        if (!Csrf::verify(is_string($payload['csrf'] ?? null) ? $payload['csrf'] : null)) {
            throw new \RuntimeException('Sessione scaduta. Riapri il concierge.');
        }
        $now = time();
        $requests = array_filter((array)($_SESSION['ai_concierge_requests'] ?? []), static fn ($time): bool => $now - (int)$time < 60);
        $limit = (int)(Container::get('config', [])['ai_concierge']['rate_limit_per_minute'] ?? 12);
        if (count($requests) >= $limit) {
            throw new \RuntimeException('Troppe richieste ravvicinate. Attendi un minuto.');
        }
        $requests[] = $now;
        $_SESSION['ai_concierge_requests'] = $requests;

        return $payload;
    }

    private function payload(): array
    {
        $payload = json_decode((string)file_get_contents('php://input'), true);

        return is_array($payload) ? $payload : [];
    }

    private function service(): ConciergeOrchestrator
    {
        return new ConciergeOrchestrator(Database::connection(), Container::get('config', []));
    }

    private function locale(): string
    {
        $path = parse_url((string)($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH) ?: '';

        return str_starts_with($path, '/en') || ($_GET['locale'] ?? '') === 'en' ? 'en' : 'it';
    }

    private function error(\Throwable $e): void
    {
        $status = $e instanceof \InvalidArgumentException ? 422 : 400;
        Response::json(['error' => $e->getMessage(), 'csrf' => Csrf::token()], $status);
    }
}
