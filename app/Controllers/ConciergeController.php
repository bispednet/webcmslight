<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\Ai\GeminiClient;
use App\Services\Security\Csrf;
use App\Support\Session;

final class ConciergeController extends Controller
{
    public function reply(): void
    {
        Session::ensureStarted();
        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload) || !Csrf::verify(is_string($payload['csrf'] ?? null) ? $payload['csrf'] : null)) {
            Response::json(['error' => 'Sessione scaduta.', 'csrf' => Csrf::token()], 419);
            return;
        }

        $now = time();
        $last = (int)($_SESSION['concierge_last_request'] ?? 0);
        if ($now - $last < 2) {
            Response::json(['error' => 'Attendi un momento prima di inviare un altro messaggio.', 'csrf' => Csrf::token()], 429);
            return;
        }
        $_SESSION['concierge_last_request'] = $now;

        $messages = array_slice((array)($payload['messages'] ?? []), -10);
        $history = [];
        $userMessages = 0;
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }
            $role = ($message['role'] ?? '') === 'assistant' ? 'Concierge' : 'Cliente';
            if ($role === 'Cliente') {
                $userMessages++;
            }
            $text = trim((string)($message['text'] ?? ''));
            if ($text !== '') {
                $history[] = $role . ': ' . mb_substr($text, 0, 600, 'UTF-8');
            }
        }
        if (!$history) {
            Response::json(['error' => 'Messaggio mancante.', 'csrf' => Csrf::token()], 422);
            return;
        }

        $client = GeminiClient::fromConfig($GLOBALS['config'] ?? [], 'concierge');
        if (!$client) {
            Response::json(['reply' => 'Raccontami in breve di cosa hai bisogno e ti preparo il passaggio su WhatsApp.', 'ready' => true, 'csrf' => Csrf::token()]);
            return;
        }

        $locale = ($payload['locale'] ?? '') === 'en' ? 'en' : 'it';
        $language = $locale === 'en' ? 'English' : 'Italian';
        $prompt = <<<PROMPT
You are the concise WhatsApp concierge for bisp&d, a technology shop and lab in Piombino. Reply in {$language}.
Ask exactly one short useful question at a time. Before handoff collect, when relevant: customer name, contact preference, request sector, concrete need, urgency, and useful device/operator details. Do not diagnose, promise availability, invent prices, or ask for sensitive data. If enough information is already present, set ready true and offer the WhatsApp handoff.
Return only compact JSON: {"reply":"...","ready":false}

Conversation:
PROMPT;
        $raw = $client->generate($prompt . "\n" . implode("\n", $history), 280, 'json');
        $data = $this->decodeJson($raw);
        if (!$data) {
            Response::json(['reply' => $locale === 'en'
                ? 'Thank you. I can now prepare your WhatsApp request for our team.'
                : 'Grazie. Posso preparare la tua richiesta WhatsApp per il nostro team.', 'ready' => true, 'csrf' => Csrf::token()]);
            return;
        }
        Response::json([
            'reply' => mb_substr(trim((string)($data['reply'] ?? '')), 0, 500, 'UTF-8'),
            'ready' => $userMessages >= 3 || (bool)($data['ready'] ?? false),
            'csrf' => Csrf::token(),
        ]);
    }

    private function decodeJson(?string $raw): ?array
    {
        if (!$raw) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data) && preg_match('/\{.*\}/s', $raw, $match)) {
            $data = json_decode($match[0], true);
        }
        return is_array($data) && trim((string)($data['reply'] ?? '')) !== '' ? $data : null;
    }
}
