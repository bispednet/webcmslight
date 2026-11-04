<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
use App\Services\Security\Csrf;
use App\Services\Ai\GeminiClient;
use App\Support\AdminMode;

if (!in_array(config('app.env'), ['local', 'staging'], true)) { http_response_code(404); exit; }
if (!AdminMode::isAdmin()) { header('Location: /login'); exit; }
$profiles = ['concierge' => 'Chatbot', 'editorial' => 'News e articoli', 'pc_configurator' => 'Configuratore PC'];
$providers = ['gemini', 'gemrouter', 'openai', 'deepseek'];
$file = BASE_PATH . '/.local/ai-overrides.json';
$environmentFile = BASE_PATH . '/.local/environment.json';
$environment = json_decode(file_get_contents($environmentFile), true, 512, JSON_THROW_ON_ERROR);
$overrides = is_file($file) ? json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR) : [];
$message = isset($_GET['saved']) ? 'Configurazione salvata. Le nuove richieste usano subito questi parametri.' : '';
$result = '';
$error = '';
$write = static function (string $destination, array $value): void {
    $temporary = $destination . '.tmp-' . bin2hex(random_bytes(4));
    file_put_contents($temporary, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), LOCK_EX);
    chmod($temporary, 0600);
    if (!rename($temporary, $destination)) throw new RuntimeException('Salvataggio della configurazione non riuscito');
};
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!Csrf::verify((string)($_POST['csrf_token'] ?? ''))) { http_response_code(419); throw new RuntimeException('Sessione scaduta: ricarica la pagina'); }
        $action = (string)($_POST['action'] ?? 'save');
        if ($action === 'save') {
            foreach ($profiles as $key => $label) {
                $provider = (string)($_POST[$key . '_provider'] ?? 'gemini');
                $model = trim((string)($_POST[$key . '_model'] ?? ''));
                $temperature = filter_var($_POST[$key . '_temperature'] ?? '', FILTER_VALIDATE_FLOAT);
                if (!in_array($provider, $providers, true) || $model === '' || strlen($model) > 200 || preg_match('/[\r\n\x00]/', $model)) throw new InvalidArgumentException('Provider o modello non valido: ' . $label);
                if ($temperature === false || $temperature < 0 || $temperature > 2) throw new InvalidArgumentException('Temperatura non valida: ' . $label);
                $overrides['gemini'][$key]['model'] = $model;
                $overrides['gemini'][$key]['provider'] = $provider;
                $overrides['gemini'][$key]['temperature'] = $temperature;
                $overrides['ai']['profiles'][$key]['provider'] = $provider;
                if ($provider !== 'gemini') $overrides[$provider]['enabled'] = true;
            }
            foreach ($providers as $provider) {
                $key = trim((string)($_POST[$provider . '_key'] ?? ''));
                if ($key !== '') $overrides[$provider]['api_key'] = $key;
            }
            $environment['allow_ai'] = isset($_POST['allow_ai']);
            $overrides['ai_concierge']['enabled'] = $environment['allow_ai'];
            $write($file, $overrides);
            $write($environmentFile, $environment);
            header('Location: /_dev/ai?saved=1', true, 303); exit;
        } elseif ($action === 'reset') {
            $write($file, []);
            header('Location: /_dev/ai?saved=1', true, 303); exit;
        } elseif ($action === 'test') {
            $profile = (string)($_POST['profile'] ?? 'concierge');
            if (!isset($profiles[$profile])) throw new InvalidArgumentException('Profilo non valido');
            $prompt = mb_substr(trim((string)($_POST['prompt'] ?? 'Rispondi solo OK.')), 0, 2000);
            $client = GeminiClient::fromConfig($GLOBALS['config'], $profile);
            if ($client === null) throw new RuntimeException('Provider senza chiave o modello configurato');
            $result = $client->generate($prompt, 256, 'text') ?? '';
            if ($result === '') throw new RuntimeException('Il provider non ha restituito testo. Controlla chiave, modello e log locali.');
        } else throw new InvalidArgumentException('Azione non valida');
    } catch (Throwable $exception) { $error = $exception->getMessage(); }
}
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
?>
<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Configurazione AI · Bisped staging</title>
<style>body{margin:0;background:#101115;color:#eee;font:16px system-ui}main{max-width:1050px;margin:40px auto;padding:24px}a{color:#ffa7ad}h1{font-size:32px}p{color:#bbc0cc;line-height:1.6}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:18px}fieldset{border:1px solid #41434c;border-radius:12px;padding:20px;margin:20px 0;background:#1a1c23}legend{font-weight:700}label{display:block;margin:12px 0}input:not([type=checkbox]),select,textarea{box-sizing:border-box;width:100%;padding:11px;border:1px solid #505360;border-radius:7px;background:#101115;color:#fff;font:inherit}button{padding:12px 18px;border:0;border-radius:8px;background:#dc2433;color:white;font-weight:700;cursor:pointer}.notice{padding:15px;border:1px solid #41a180;border-radius:8px}.error{border-color:#ee6161}pre{white-space:pre-wrap;background:#1a1c23;padding:18px;border-radius:10px}</style></head><body><main>
<a href="/admin/dashboard">← Dashboard</a><h1>Configurazione AI</h1>
<p>Tre profili indipendenti, applicati al codice del sito. Le modifiche sono salvate solo nello staging e non vengono inviate a GitHub. La configurazione completa modificabile è in <code>.env.local.php</code>; questa pagina salva le variazioni in <code>.local/ai-overrides.json</code>.</p>
<?php if ($message): ?><p class="notice"><?= $escape($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="notice error"><?= $escape($error) ?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= $escape(Csrf::token()) ?>"><input type="hidden" name="action" value="save">
<label><input type="checkbox" name="allow_ai" <?= !empty($environment['allow_ai']) ? 'checked' : '' ?>> Abilita le chiamate ai provider AI</label>
<div class="grid"><?php foreach ($profiles as $key => $label):
    $settings = (array)config('gemini.' . $key, config('gemini.editorial', []));
    $selectedProvider = $settings['provider'] ?? config('ai.profiles.' . $key . '.provider', config('ai.provider', 'gemini'));
?><fieldset><legend><?= $escape($label) ?></legend>
<label>Provider<select name="<?= $key ?>_provider"><?php foreach ($providers as $provider): ?><option value="<?= $provider ?>" <?= $selectedProvider === $provider ? 'selected' : '' ?>><?= $provider ?></option><?php endforeach; ?></select></label>
<label>Modello<input name="<?= $key ?>_model" required maxlength="200" value="<?= $escape($settings['model'] ?? '') ?>"></label>
<label>Temperatura<input name="<?= $key ?>_temperature" type="number" min="0" max="2" step="0.05" value="<?= $escape($settings['temperature'] ?? 0.35) ?>"></label>
</fieldset><?php endforeach; ?></div>
<fieldset><legend>Chiavi dei provider</legend><p>Una chiave già presente non viene mostrata. Lascia vuoto per conservarla.</p><div class="grid">
<?php foreach ($providers as $provider): ?><label><?= $provider ?> · <?= config($provider . '.api_key', '') !== '' ? 'chiave presente' : 'chiave assente' ?><input type="password" name="<?= $provider ?>_key" autocomplete="new-password" placeholder="Nuova chiave opzionale"></label><?php endforeach; ?>
</div></fieldset><button>Salva configurazione</button></form>
<fieldset><legend>Prova del modello</legend><p>Invia una richiesta reale al provider configurato; si applicano i relativi limiti e costi API.</p>
<form method="post"><input type="hidden" name="csrf_token" value="<?= $escape(Csrf::token()) ?>"><input type="hidden" name="action" value="test"><label>Profilo<select name="profile"><?php foreach ($profiles as $key => $label): ?><option value="<?= $key ?>"><?= $escape($label) ?></option><?php endforeach; ?></select></label><label>Prompt<textarea name="prompt" rows="3" maxlength="2000">Rispondi solo OK.</textarea></label><button>Prova modello</button></form>
<?php if ($result !== ''): ?><pre><?= $escape($result) ?></pre><?php endif; ?></fieldset>
<form method="post"><input type="hidden" name="csrf_token" value="<?= $escape(Csrf::token()) ?>"><input type="hidden" name="action" value="reset"><button>Ripristina i modelli dal file .env.local.php</button></form>
</main></body></html>
