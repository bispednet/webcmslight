<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\Ai\AgentPersonaRegistry;
use App\Services\Ai\ConciergeStateMachine;
use App\Services\Ai\NeedClassifier;
use App\Services\WhatsApp\WhatsAppHandoffBuilder;

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
$reply = advance($machine, $conversation, 'non mi va il cell, non so se ho finito i giga');
$data = json_decode($conversation['structured_data'], true);
assertTrue($reply['agent']['key'] === 'serenai', 'Mobile data issue must route to SerenAI');
assertTrue($reply['ready'] === true, 'An actionable mobile issue must open WhatsApp immediately');
assertTrue(($data['service_kind'] ?? '') === 'mobile_data', 'Mobile data context must be retained');
assertTrue(empty($data['usage_context']['gaming']), 'The system must never infer gaming from an unrelated TLC request');

$conversation = ['locale' => 'it', 'current_step' => 'opening', 'structured_data' => '{}'];
$reply = advance($machine, $conversation, 'boh non so');
assertTrue($reply['ready'] === false && $reply['step'] === 'understand_need', 'A vague first message gets one open clarification');
$reply = advance($machine, $conversation, 'nessuna delle cose che hai detto');
assertTrue($reply['ready'] === true, 'A vague customer must not be trapped in a clarification loop');

$conversation = ['locale' => 'it', 'current_step' => 'opening', 'structured_data' => '{}'];
$reply = advance($machine, $conversation, 'ho bisogno di una nuova linea internet, posso scrivervi io su whatsapp?');
$data = json_decode($conversation['structured_data'], true);
assertTrue($reply['ready'] === true && $reply['agent']['key'] === 'serenai', 'Direct WhatsApp request must be honored without phone collection');
assertTrue(empty($conversation['customer_phone']), 'Outbound WhatsApp handoff must not require a phone number');
assertTrue(($data['request_type'] ?? '') === 'new_line', 'New line intent must be extracted');

$conversation = ['locale' => 'it', 'current_step' => 'opening', 'structured_data' => '{}'];
advance($machine, $conversation, 'la linea fastweb lagga quando gioco');
advance($machine, $conversation, 'chi ti ha detto che gioco?');
$data = json_decode($conversation['structured_data'], true);
assertTrue(empty($data['usage_context']['gaming']), 'An explicit customer correction must clear a previously collected fact');

$builder = new WhatsAppHandoffBuilder();
$handoff = $builder->build('393346582116', [
    'main_sector' => 'tlc',
    'customer_phone' => null,
    'urgency' => null,
    'structured_data' => json_encode([
        'need_summary' => 'Verifica linea mobile o traffico dati',
        'service_kind' => 'mobile_data',
        'symptoms' => ['mobile_not_working' => true, 'data_allowance_uncertain' => true],
    ], JSON_UNESCAPED_UNICODE),
], []);
assertTrue(!str_contains($handoff['summary'], 'gaming'), 'WhatsApp summary must not contain inferred gaming');
assertTrue(!str_contains($handoff['summary'], 'Telefono cliente: non indicato'), 'WhatsApp summary must omit unavailable phone data');

fwrite(STDOUT, "Natural concierge workflow tests passed\n");
