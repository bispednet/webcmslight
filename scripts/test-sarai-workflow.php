<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\Ai\AgentPersonaRegistry;
use App\Services\Ai\ConciergeStateMachine;
use App\Services\Ai\NeedClassifier;
use App\Services\Ai\ResponseStyleGuard;

function applyReply(array $conversation, array $reply): array
{
    foreach (($reply['updates'] ?? []) as $key => $value) {
        $conversation[$key] = $value;
    }

    return $conversation;
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$machine = new ConciergeStateMachine(new AgentPersonaRegistry(), new NeedClassifier());
$conversation = ['locale' => 'it', 'current_step' => 'opening', 'structured_data' => '{}'];
$opening = $machine->greeting('it');
assertTrue(str_contains($opening['message'], 'sono SarAI'), 'SarAI opening missing');

$reply = $machine->advance($conversation, 'voglio capire se pago troppo la luce');
$conversation = applyReply($conversation, $reply);
assertTrue($reply['step'] === 'sarai_home_profile', 'Energy flow must ask about the home');
assertTrue(!str_contains(mb_strtolower($reply['message']), 'migliore offerta'), 'Forbidden sales phrase detected');

$reply = $machine->advance(['locale' => 'it', 'current_step' => 'opening', 'structured_data' => '{}'], 'il computer non parte e mi serve per lavorare');
assertTrue(($reply['agent']['key'] ?? '') === 'andreai', 'Technical request must route to AndreAI');
assertTrue(str_contains((string)($reply['transition'] ?? ''), 'AndreAI'), 'Visible AndreAI transition missing');

$conversation = ['locale' => 'it', 'current_step' => 'opening', 'structured_data' => '{}'];
foreach ([
    'voglio capire se pago troppo la luce',
    'appartamento',
    'siamo in 4',
    'pompa di calore, induzione e due climatizzatori',
    'pago 220 ogni due mesi',
    'ho una proposta scritta di Edison',
    'voglio capire se è seria',
] as $message) {
    $reply = $machine->advance($conversation, $message);
    $conversation = applyReply($conversation, $reply);
}
$data = json_decode((string)$conversation['structured_data'], true);
assertTrue(($reply['step'] ?? '') === 'privacy_notice', 'Complete SarAI flow must reach summary consent');
assertTrue((float)($data['current_cost_amount'] ?? 0) === 220.0, 'Current cost extraction failed');
assertTrue(($data['current_cost_period'] ?? '') === 'bimestre', 'Current cost period extraction failed');
assertTrue(!empty($data['has_heat_pump']) && !empty($data['has_induction']) && !empty($data['has_air_conditioning']), 'Device extraction failed');

$guard = new ResponseStyleGuard();
assertTrue(
    $guard->validateAgentMessage('sarai', 'Gentile cliente, siamo lieti di proporle la migliore offerta.', 'fallback') === 'fallback',
    'SarAI style guard must reject corporate sales copy'
);

fwrite(STDOUT, "SarAI workflow tests passed\n");
