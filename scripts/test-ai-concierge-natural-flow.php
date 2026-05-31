<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\Ai\AgentPersonaRegistry;
use App\Services\Ai\ConciergeStateMachine;
use App\Services\Ai\NeedClassifier;
use App\Services\Ai\ResponseStyleGuard;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function advance(ConciergeStateMachine $machine, array &$conversation, string $message): array
{
    $reply = $machine->advance($conversation, $message);
    foreach ($reply['updates'] as $key => $value) {
        $conversation[$key] = $value;
    }

    return $reply;
}

$machine = new ConciergeStateMachine(new AgentPersonaRegistry(), new NeedClassifier());
$conversation = ['locale' => 'it', 'current_step' => 'opening', 'structured_data' => '{}'];
assertTrue(!str_contains($machine->greeting('it')['message'], 'sono SarAI'), 'Opening must not expose internal persona copy');

$reply = advance($machine, $conversation, 'Ciao, ho una connessione lenta vorrei velocizzare perchè gioco');
assertTrue($reply['agent']['key'] === 'serenai', 'Gaming connection must route to SerenAI');
assertTrue($reply['step'] === 'tlc_operator', 'First TLC question must ask operator');
$reply = advance($machine, $conversation, 'vodafone, sono in FWA');
assertTrue($reply['step'] === 'tlc_scope', 'Vodafone FWA must ask a contextual scope question');
$data = json_decode($conversation['structured_data'], true);
assertTrue($data['operator'] === 'Vodafone' && $data['access_type'] === 'FWA', 'Operator and FWA extraction failed');
$reply = advance($machine, $conversation, 'Mi sta bloccando');
assertTrue($reply['step'] === 'phone', 'Urgent TLC case must ask only phone');
$reply = advance($machine, $conversation, '3346582115');
assertTrue($reply['ready'] === true && $reply['step'] === 'ready', 'TLC lead with phone must be ready');

$conversation = ['locale' => 'it', 'current_step' => 'opening', 'structured_data' => '{}'];
$reply = advance($machine, $conversation, 'spendo troppo in azienda, chiamatemi al 3346582115');
assertTrue($reply['agent']['key'] === 'sarai' && $reply['step'] === 'urgency', 'Business energy must ask only urgency');
$data = json_decode($conversation['structured_data'], true);
assertTrue(($data['trigger'] ?? '') === 'costo_alto', 'Callback request must not become a commercial-call trigger');
assertTrue(($data['callback_requested'] ?? false) === true, 'Callback request must remain available to the handoff');
$reply = advance($machine, $conversation, 'Mi sta bloccando');
assertTrue($reply['ready'] === true, 'Urgent business energy case with phone must be ready');

$guard = new ResponseStyleGuard();
assertTrue($guard->validateAgentMessage('sarai', 'Capisco perfettamente. Posso usare queste risposte?', 'fallback') === 'fallback', 'Internal AI copy must be rejected');
assertTrue($guard->validateAgentMessage('sarai', 'Ti contatto al numero indicato.', 'fallback') === 'fallback', 'The assistant must not promise a callback while opening WhatsApp');

fwrite(STDOUT, "Natural concierge workflow tests passed\n");
