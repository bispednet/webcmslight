<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

$schema = (string)file_get_contents(dirname(__DIR__) . '/database/schema.sql');
$offset = strpos($schema, 'CREATE TABLE IF NOT EXISTS ai_conversations');
if ($offset === false) {
    throw new RuntimeException('AI concierge schema block missing.');
}

$db = Database::connection();
foreach (array_filter(array_map('trim', explode(';', substr($schema, $offset)))) as $statement) {
    $db->exec($statement);
}
$db->exec("ALTER TABLE ai_conversations MODIFY COLUMN current_step VARCHAR(80) NOT NULL DEFAULT 'opening'");

$conditions = [
    ['priority_whatsapp', 'Priorita WhatsApp', 'La richiesta qualificata arriva al negozio gia ordinata per una risposta piu rapida.', null, 35],
    ['remote_first_diagnosis', 'Prima diagnosi guidata', 'Per i problemi tecnici valutiamo prima il percorso piu sensato, anche da remoto quando possibile.', 'informatica', 30],
    ['coverage_double_check', 'Doppio controllo copertura', 'Prima del cambio operatore verifichiamo copertura e condizioni effettive.', 'tlc', 30],
    ['free_bill_check', 'Controllo bolletta', 'Prima di cambiare fornitore verifichiamo insieme la situazione reale della bolletta.', 'energia_amministrativo', 30],
];
$stmt = $db->prepare(
    'INSERT INTO ai_special_conditions (condition_key,title,description,sector,min_lead_score)
     VALUES (:key,:title,:description,:sector,:score)
     ON DUPLICATE KEY UPDATE title=VALUES(title),description=VALUES(description),sector=VALUES(sector),min_lead_score=VALUES(min_lead_score)'
);
foreach ($conditions as [$key, $title, $description, $sector, $score]) {
    $stmt->execute(compact('key', 'title', 'description', 'sector', 'score'));
}

fwrite(STDOUT, "AI concierge schema ready.\n");
