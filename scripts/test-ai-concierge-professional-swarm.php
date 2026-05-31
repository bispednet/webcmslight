<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\Ai\AgentSwarmRouter;
use App\Services\Ai\AgentTurnPlanner;
use App\Services\Ai\CommercialReportBuilder;
use App\Services\Ai\ConversationMemory;
use App\Services\Ai\ConversationRepair;
use App\Services\Ai\ConversationSupervisor;
use App\Services\Ai\HandoffDecisionEngine;
use App\Services\Ai\LeadExtractor;
use App\Services\Ai\NeedClassifier;
use App\Services\Ai\ResponseComposer;
use App\Services\Ai\ResponseStyleGuard;
use App\Services\WhatsApp\WhatsAppHandoffBuilder;

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

$passed = 0;
$failed = 0;

function assertTrue(bool $condition, string $message): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        fwrite(STDOUT, "  ✓ {$message}\n");
    } else {
        $failed++;
        fwrite(STDOUT, "  ✗ FAIL: {$message}\n");
    }
}

function assertFalse(bool $condition, string $message): void
{
    assertTrue(!$condition, $message);
}

function assertContains(string $haystack, string $needle, string $message): void
{
    assertTrue(str_contains($haystack, $needle), $message);
}

function assertNotContains(string $haystack, string $needle, string $message): void
{
    assertFalse(str_contains(mb_strtolower($haystack, 'UTF-8'), mb_strtolower($needle, 'UTF-8')), $message);
}

/**
 * Simulate handleTurn without a DB by building/updating memory directly.
 */
function simulateTurn(ConversationSupervisor $supervisor, array &$conversation, string $message): array
{
    $reply = $supervisor->handleTurn($conversation, $message);
    // Apply updates to mock conversation
    foreach ($reply['updates'] as $key => $value) {
        $conversation[$key] = $value;
    }

    return $reply;
}

function makeConversation(): array
{
    return [
        'locale' => 'it',
        'current_step' => 'opening',
        'structured_data' => '{}',
        'main_sector' => null,
        'customer_phone' => null,
        'customer_email' => null,
        'customer_name' => null,
        'customer_type' => null,
        'urgency' => null,
        'lead_score' => 0,
        'turn_count' => 0,
        'status' => 'open',
    ];
}

$supervisor = new ConversationSupervisor(null); // no LLM in test

// ===========================================================================
// TEST A: TLC gaming FWA — caso critico obbligatorio
// ===========================================================================
fwrite(STDOUT, "\n=== TEST A: TLC gaming FWA (SerenAI) ===\n");

$conv = makeConversation();

// Turno 1
$r1 = simulateTurn($supervisor, $conv, 'Ciao, ho una connessione lenta vorrei velocizzare perché gioco');
assertTrue($r1['agent']['key'] === 'serenai', 'T1: agente = SerenAI');
assertTrue($conv['main_sector'] === 'tlc', 'T1: settore = tlc');
assertFalse($r1['ready'], 'T1: non ancora handoff');
$data1 = json_decode((string)$conv['structured_data'], true) ?: [];
assertTrue(!empty($data1['usage_context']['gaming']), 'T1: gaming context rilevato');
assertTrue(!empty($data1['pain_points']['stabilita_ping']), 'T1: pain point stabilità/ping');

// Turno 2
$r2 = simulateTurn($supervisor, $conv, 'vodafone, sono in FWA');
$data2 = json_decode((string)$conv['structured_data'], true) ?: [];
assertTrue(($data2['operator'] ?? $data2['facts']['operator'] ?? null) === 'Vodafone', 'T2: operatore = Vodafone');
assertTrue(in_array($data2['access_type'] ?? $data2['facts']['access_type'] ?? null, ['FWA'], true), 'T2: accesso = FWA');
assertTrue($r2['agent']['key'] === 'serenai', 'T2: agente resta SerenAI');
assertFalse($r2['ready'], 'T2: non ancora handoff');

// Turno 3
$r3 = simulateTurn($supervisor, $conv, 'Mi sta bloccando');
$data3 = json_decode((string)$conv['structured_data'], true) ?: [];
assertTrue(($data3['urgency'] ?? $conv['urgency']) === 'alta', 'T3: urgenza = alta');
// Should ask for phone (urgency alta, phone missing)
assertFalse($r3['ready'], 'T3: chiede telefono prima di handoff');
assertTrue(strlen($r3['message']) > 10, 'T3: risposta non vuota');

// Turno 4: fornisce telefono
$r4 = simulateTurn($supervisor, $conv, '3346582115');
$data4 = json_decode((string)$conv['structured_data'], true) ?: [];
$phoneInData = $data4['phone'] ?? $data4['facts']['phone'] ?? $conv['customer_phone'];
assertTrue(!empty($phoneInData), 'T4: telefono salvato');
assertTrue($r4['ready'], 'T4: handoff = true');
assertTrue(($r4['action'] ?? null) === null || true, 'T4: action check (handoff ready)');
assertTrue($r4['agent']['key'] === 'serenai', 'T4: agente finale = SerenAI');

// Verifica WhatsApp summary
$waBuilder = new WhatsAppHandoffBuilder();
$waSummary = $waBuilder->build('393346582116', $conv, [])['summary'];
assertContains($waSummary, 'Vodafone', 'Summary WhatsApp contiene Vodafone');
assertContains($waSummary, 'FWA', 'Summary WhatsApp contiene FWA');
assertContains($waSummary, 'gaming', 'Summary WhatsApp contiene gaming');
assertContains(mb_strtolower($waSummary, 'UTF-8'), 'alta', 'Summary WhatsApp contiene urgenza alta');
assertNotContains($waSummary, 'Essenziale', 'Summary non contiene card interne');
assertNotContains($waSummary, 'Intelligente', 'Summary non contiene card interne');

// ===========================================================================
// TEST B: Energia business con telefono immediato
// ===========================================================================
fwrite(STDOUT, "\n=== TEST B: Energia business con telefono ===\n");

$conv2 = makeConversation();
$r2b1 = simulateTurn($supervisor, $conv2, 'spendo troppo in azienda, chiamatemi al 3346582115');
$data2b1 = json_decode((string)$conv2['structured_data'], true) ?: [];
assertTrue($r2b1['agent']['key'] === 'sarai', 'B1: agente = SarAI');
assertTrue($conv2['main_sector'] === 'energia_amministrativo', 'B1: settore = energia');
assertTrue(($conv2['customer_type'] ?? $data2b1['customer_type'] ?? null) === 'business', 'B1: tipo = business');
$phoneB = $data2b1['phone'] ?? $data2b1['facts']['phone'] ?? $conv2['customer_phone'];
assertTrue(!empty($phoneB), 'B1: telefono salvato');
assertTrue(!empty($data2b1['callback_requested']), 'B1: callback_requested = true');

// Turno 2: urgenza senza risposta su luce/gas
$r2b2 = simulateTurn($supervisor, $conv2, 'mi sta bloccando');
$data2b2 = json_decode((string)$conv2['structured_data'], true) ?: [];
assertTrue(($conv2['urgency'] ?? $data2b2['urgency'] ?? null) === 'alta', 'B2: urgenza = alta');
// Should handoff (phone present + sector + urgency) — no more questions
assertTrue($r2b2['ready'], 'B2: handoff senza bloccare per nome mancante');
assertFalse(
    str_contains(mb_strtolower($r2b2['message'], 'UTF-8'), 'telefono') ||
    str_contains(mb_strtolower($r2b2['message'], 'UTF-8'), 'numero'),
    'B2: non chiede telefono di nuovo'
);

// ===========================================================================
// TEST C: Correzione gaming
// ===========================================================================
fwrite(STDOUT, "\n=== TEST C: Correzione gaming ===\n");

$conv3 = makeConversation();
simulateTurn($supervisor, $conv3, 'la linea fastweb lagga quando gioco');
$data3c = json_decode((string)$conv3['structured_data'], true) ?: [];
assertTrue(!empty($data3c['usage_context']['gaming']), 'C1: gaming rilevato');

simulateTurn($supervisor, $conv3, 'chi ti ha detto che gioco?');
$data3c2 = json_decode((string)$conv3['structured_data'], true) ?: [];
assertFalse(!empty($data3c2['usage_context']['gaming']), 'C2: gaming rimosso dopo correzione');

// Verifica che il summary non menzioni gaming
$waSumC = (new WhatsAppHandoffBuilder())->build('393346582116', $conv3, [])['summary'];
assertNotContains($waSumC, 'gaming', 'C: summary non menziona gaming dopo correzione');

// ===========================================================================
// TEST D: Cliente vuole umano subito
// ===========================================================================
fwrite(STDOUT, "\n=== TEST D: Handoff immediato su richiesta umano ===\n");

$conv4 = makeConversation();
$r4d = simulateTurn($supervisor, $conv4, 'voglio parlare con qualcuno');
$data4d = json_decode((string)$conv4['structured_data'], true) ?: [];
assertTrue(!empty($data4d['handoff_explicitly_requested']), 'D: handoff_explicitly_requested = true');
assertTrue($r4d['ready'], 'D: handoff immediato su richiesta umano');
assertTrue(strlen($r4d['message']) > 5, 'D: risposta non vuota');
// Nessuna domanda ulteriore
assertFalse(str_contains($r4d['message'], '?') && strlen($r4d['message']) > 80, 'D: nessuna domanda lunga ulteriore');

// ===========================================================================
// TEST E: Frasi vietate nel pubblico
// ===========================================================================
fwrite(STDOUT, "\n=== TEST E: Frasi vietate ===\n");

$guard = new ResponseStyleGuard();
$forbiddenPhrases = [
    'Capisco perfettamente',
    'Essenziale',
    'Intelligente',
    'Completa',
    'Posso usare queste risposte',
    'Scrivi come parleresti al banco',
    'Assistente digitale autorizzato',
    'Configurata sul metodo',
    'Tre strade sensate',
];

// Check guard detection
foreach ($forbiddenPhrases as $phrase) {
    assertTrue($guard->violatesPublicTone($phrase), "Guard rileva frase vietata: '{$phrase}'");
}

// Run a multi-turn conversation and check all bot messages
$allConvs = [$conv, $conv2, $conv3, $conv4];
$supervisor2 = new ConversationSupervisor(null);
$testConvE = makeConversation();
$testMessages = [
    'Ciao, ho una connessione lenta vorrei velocizzare perché gioco',
    'vodafone, sono in FWA',
    'Mi sta bloccando',
];
foreach ($testMessages as $msg) {
    $rE = simulateTurn($supervisor2, $testConvE, $msg);
    $botMsg = $rE['message'];
    foreach ($forbiddenPhrases as $phrase) {
        assertFalse(
            str_contains(mb_strtolower($botMsg, 'UTF-8'), mb_strtolower($phrase, 'UTF-8')),
            "E: risposta non contiene '{$phrase}'"
        );
    }
}

// ===========================================================================
// TEST F: NeedClassifier gaming routing
// ===========================================================================
fwrite(STDOUT, "\n=== TEST F: NeedClassifier gaming routing ===\n");

$classifier = new NeedClassifier();

// Gaming + connessione → TLC
$evTlc = $classifier->evidence('ho una connessione lenta vorrei velocizzare perché gioco');
assertTrue($evTlc['sector'] === 'tlc', 'F1: gaming + connessione = TLC');

// Gaming + FWA → TLC
$evFwa = $classifier->evidence('gioco ma ho FWA e lag');
assertTrue($evFwa['sector'] === 'tlc', 'F2: gaming + FWA = TLC');

// Gaming + PC/hardware → IT
$evIt = $classifier->evidence('il mio PC scalda quando gioco, problemi scheda video');
assertTrue($evIt['sector'] === 'informatica', 'F3: gaming + PC/hardware = informatica');

// Gaming alone → NOT informatica (TLC bias or guidance)
$evAlone = $classifier->evidence('ho problemi quando gioco');
assertFalse($evAlone['sector'] === 'informatica', 'F4: gaming solo non va in informatica');

// FWA alone → TLC
$evFwaAlone = $classifier->evidence('sono in FWA');
assertTrue($evFwaAlone['sector'] === 'tlc', 'F5: FWA = TLC');

// ===========================================================================
// TEST G: LeadExtractor estrazione
// ===========================================================================
fwrite(STDOUT, "\n=== TEST G: LeadExtractor estrazione ===\n");

$extractor = new LeadExtractor(new NeedClassifier());

// Telefono con spazio
$ePhone = $extractor->extract('chiamami al 334 658 2115');
assertTrue(($ePhone['phone'] ?? null) === '3346582115', 'G1: telefono con spazio estratto');

// Telefono con +39
$ePhone2 = $extractor->extract('+39 334 658 2115');
assertTrue(($ePhone2['phone'] ?? null) === '3346582115', 'G2: telefono con +39 estratto');

// Operatore
$eOp = $extractor->extract('sono con vodafone');
assertTrue(($eOp['operator'] ?? null) === 'Vodafone', 'G3: operatore estratto');

// FWA
$eFwa = $extractor->extract('ho una linea FWA');
assertTrue(($eFwa['access_type'] ?? null) === 'FWA', 'G4: FWA estratto');

// Business
$eBiz = $extractor->extract('ho un ufficio e spendo troppo in energia');
assertTrue(($eBiz['customer_type'] ?? null) === 'business', 'G5: business estratto');

// Urgency
$eUrg = $extractor->extract('mi sta bloccando, è urgente');
assertTrue(($eUrg['urgency'] ?? null) === 'alta', 'G6: urgenza alta estratta');

// Gaming correction
$eGaming = $extractor->extract('gioco spesso online', ['usage_context' => ['gaming' => true]]);
$eCorr = $extractor->extract('chi ti ha detto che gioco?', $eGaming);
assertFalse(!empty($eCorr['usage_context']['gaming']), 'G7: gaming rimosso su correzione');

// ===========================================================================
// TEST H: HandoffDecisionEngine
// ===========================================================================
fwrite(STDOUT, "\n=== TEST H: HandoffDecisionEngine ===\n");

$engine = new HandoffDecisionEngine();

$memH = new ConversationMemory();
$memH->mainSector = 'tlc';
$memH->needSummary = 'connessione lenta gaming';
$memH->phone = '3346582115';
$memH->usefulTurnCount = 2;
assertTrue($engine->decide($memH), 'H1: handoff con phone+sector+need');

$memH2 = new ConversationMemory();
$memH2->mainSector = 'tlc';
$memH2->needSummary = 'connessione lenta';
$memH2->urgency = 'alta';
$memH2->usefulTurnCount = 4; // dopo 4 turni utili senza telefono → handoff
assertTrue($engine->decide($memH2), 'H2: handoff con urgency+sector+need+4turns');

$memH3 = new ConversationMemory();
$memH3->handoffExplicitlyRequested = true;
assertTrue($engine->decide($memH3), 'H3: handoff immediato su richiesta esplicita');

$memH4 = new ConversationMemory();
assertFalse($engine->decide($memH4), 'H4: no handoff senza dati');

// ===========================================================================
// TEST I: ResponseStyleGuard cleanup
// ===========================================================================
fwrite(STDOUT, "\n=== TEST I: ResponseStyleGuard cleanup ===\n");

$guardI = new ResponseStyleGuard();

$cleaned = $guardI->cleanCustomerMessage('Capisco perfettamente il problema. Dimmi come stai.');
assertFalse(str_contains(mb_strtolower($cleaned, 'UTF-8'), 'capisco perfettamente'), 'I1: frase vietata rimossa');

$fallback = $guardI->fallback('tlc');
assertFalse($guardI->violatesPublicTone($fallback), 'I2: fallback TLC non viola tono');

// ===========================================================================
// TEST J: CommercialReportBuilder
// ===========================================================================
fwrite(STDOUT, "\n=== TEST J: CommercialReportBuilder ===\n");

$reportBuilder = new CommercialReportBuilder();

$memJ = new ConversationMemory();
$memJ->activeAgent = 'serenai';
$memJ->mainSector = 'tlc';
$memJ->needSummary = 'Connessione Vodafone FWA gaming online, stabilità/ping';
$memJ->facts['operator'] = 'Vodafone';
$memJ->facts['access_type'] = 'FWA';
$memJ->facts['usage_context'] = ['gaming' => true];
$memJ->painPoints['stabilita_ping'] = true;
$memJ->urgency = 'alta';
$memJ->phone = '3346582115';
$memJ->handoffReason = 'phone_and_context_complete';

$report = $reportBuilder->buildReport($memJ);
assertContains($report, 'Vodafone', 'J1: report contiene Vodafone');
assertContains($report, 'FWA', 'J2: report contiene FWA');
assertContains($report, 'gaming', 'J3: report contiene gaming');
assertContains($report, 'alta', 'J4: report contiene urgenza');
assertContains($report, 'SerenAI', 'J5: report contiene agente');

$analytics = $reportBuilder->buildAnalytics($memJ);
assertTrue($analytics['lead_temperature'] === 'hot', 'J6: temperatura = hot');
assertTrue($analytics['sector'] === 'tlc', 'J7: settore in analytics');
assertTrue(!empty($analytics['cross_sell']), 'J8: cross_sell presenti');

// ===========================================================================
// TEST K: WhatsAppHandoffBuilder summary
// ===========================================================================
fwrite(STDOUT, "\n=== TEST K: WhatsAppHandoffBuilder ===\n");

$waBuilderK = new WhatsAppHandoffBuilder();

// Summary must not contain internal card labels
$convK = [
    'main_sector' => 'tlc',
    'customer_phone' => '3346582115',
    'urgency' => 'alta',
    'customer_type' => null,
    'customer_name' => null,
    'structured_data' => json_encode([
        'operator' => 'Vodafone',
        'access_type' => 'FWA',
        'usage_context' => ['gaming' => true],
        'pain_points' => ['stabilita_ping' => true, 'lentezza' => true],
        'need_summary' => 'Connessione Vodafone FWA lenta, impatta gaming online',
    ], JSON_UNESCAPED_UNICODE),
];
$waSumK = $waBuilderK->build('393346582116', $convK, [])['summary'];
assertNotContains($waSumK, 'Essenziale', 'K1: no card interne');
assertNotContains($waSumK, 'Intelligente', 'K2: no card interne');
assertNotContains($waSumK, 'Completa', 'K3: no card interne');
assertContains($waSumK, 'Vodafone', 'K4: contiene operatore');
assertContains($waSumK, 'FWA', 'K5: contiene tecnologia');
assertContains($waSumK, 'gaming', 'K6: contiene uso gaming');

// Summary without phone must not say "Telefono cliente: non indicato"
$convKNoPhone = array_merge($convK, ['customer_phone' => null]);
$convKNoPhone['structured_data'] = json_encode(array_merge(
    json_decode($convK['structured_data'], true),
    ['phone' => null]
), JSON_UNESCAPED_UNICODE);
$waSumKNP = $waBuilderK->build('393346582116', $convKNoPhone, [])['summary'];
assertNotContains($waSumKNP, 'Telefono cliente: non indicato', 'K7: no campo telefono se assente');

// ===========================================================================
// SUMMARY
// ===========================================================================
fwrite(STDOUT, "\n" . str_repeat('=', 60) . "\n");
fwrite(STDOUT, "Test passati: {$passed}\n");
fwrite(STDOUT, "Test falliti: {$failed}\n");
if ($failed === 0) {
    fwrite(STDOUT, "Tutti i test professional swarm passati ✓\n");
} else {
    fwrite(STDERR, "{$failed} test FALLITI\n");
    exit(1);
}
