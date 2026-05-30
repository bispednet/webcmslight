<?php
declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\WhatsApp\WhatsAppHandoffBuilder;
use PDO;

final class ConciergeOrchestrator
{
    private ConciergeStateMachine $machine;
    private PromptInjectionGuard $guard;
    private QuoteBuilder $quotes;
    private SpecialConditionEngine $conditions;
    private WhatsAppHandoffBuilder $whatsapp;
    private ConversationComposer $composer;

    public function __construct(private PDO $db, private array $config)
    {
        $this->machine = new ConciergeStateMachine(new AgentPersonaRegistry(), new NeedClassifier());
        $this->guard = new PromptInjectionGuard();
        $this->quotes = new QuoteBuilder();
        $this->conditions = new SpecialConditionEngine($db);
        $this->whatsapp = new WhatsAppHandoffBuilder();
        $this->composer = new ConversationComposer(
            GeminiClient::fromConfig($config, 'concierge'),
            new PromptBuilder(),
            new ResponseStyleGuard()
        );
    }

    public function bootstrap(string $sessionId, string $locale, array $context): array
    {
        $publicId = $this->uuid();
        $stmt = $this->db->prepare(
            'INSERT INTO ai_conversations
             (public_id,session_id,locale,current_step,entry_context,entry_url,ip_address,user_agent,structured_data)
             VALUES (:public_id,:session_id,:locale,"opening",:entry_context,:entry_url,:ip,:agent,:data)'
        );
        $stmt->execute([
            'public_id' => $publicId,
            'session_id' => $sessionId,
            'locale' => $locale,
            'entry_context' => mb_substr((string)($context['entry_context'] ?? ''), 0, 120),
            'entry_url' => mb_substr((string)($context['entry_url'] ?? ''), 0, 500),
            'ip' => ($ip = ($_SERVER['REMOTE_ADDR'] ?? '')) ? @inet_pton($ip) : null,
            'agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'data' => '{}',
        ]);
        $conversation = $this->find($publicId, $sessionId);
        $reply = $this->machine->greeting($locale);
        $this->message((int)$conversation['id'], 'assistant', $reply['message']);

        return $this->response($publicId, $reply);
    }

    public function advance(string $publicId, string $sessionId, string $message, ?string $choice): array
    {
        $conversation = $this->find($publicId, $sessionId);
        $message = $this->guard->sanitize($message);
        if ($message === '' && $choice === null) {
            throw new \InvalidArgumentException('Scrivi un messaggio.');
        }
        if ($this->guard->isSpam($message)) {
            $this->update((int)$conversation['id'], ['status' => 'spam']);
            throw new \InvalidArgumentException('Non riesco a elaborare questa richiesta.');
        }
        $count = $this->db->prepare('SELECT COUNT(*) FROM ai_messages WHERE conversation_id=:id');
        $count->execute(['id' => $conversation['id']]);
        $max = (int)($this->config['ai_concierge']['max_messages_per_conversation'] ?? 40);
        if ((int)$count->fetchColumn() >= $max) {
            throw new \InvalidArgumentException('Per non farti perdere tempo, passiamo al negozio: completa il riepilogo WhatsApp.');
        }

        $this->message((int)$conversation['id'], 'user', $message !== '' ? $message : (string)$choice);
        $reply = $this->machine->advance($conversation, $message, $choice);
        if (!empty($reply['updates'])) {
            $this->update((int)$conversation['id'], $reply['updates']);
            $conversation = $this->find($publicId, $sessionId);
        }
        if ($message !== '' && $choice === null) {
            $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
            $reply['message'] = $this->composer->compose($reply['agent'], $message, $reply['message'], $data);
        }
        $this->message((int)$conversation['id'], 'assistant', $reply['message']);
        if ($reply['ready'] || $reply['quoteReady']) {
            $reply['quotes'] = $this->ensureLeadAndQuotes($conversation);
        }

        return $this->response($publicId, $reply);
    }

    public function handoff(string $publicId, string $sessionId): array
    {
        $conversation = $this->find($publicId, $sessionId);
        if (($conversation['current_step'] ?? '') !== 'ready') {
            throw new \InvalidArgumentException('Completa prima le domande essenziali.');
        }
        $quotes = $this->ensureLeadAndQuotes($conversation);
        $number = (string)($this->config['whatsapp']['phone_number'] ?? $this->config['ai_concierge']['whatsapp_number'] ?? '393346582116');
        $handoff = $this->whatsapp->build($number, $conversation, $quotes);
        if (($conversation['status'] ?? '') !== 'handed_to_whatsapp') {
            $this->update((int)$conversation['id'], ['status' => 'handed_to_whatsapp', 'summary' => $handoff['summary']]);
            $this->message((int)$conversation['id'], 'handoff', $handoff['summary']);
            $stmt = $this->db->prepare(
                'INSERT INTO contact_messages (name,email,message,ip_address,user_agent,status) VALUES (:name,:email,:message,:ip,:agent,"new")'
            );
            $stmt->execute([
                'name' => $conversation['customer_name'] ?: 'Lead AI Concierge',
                'email' => $conversation['customer_email'] ?: 'concierge@bisped.net',
                'message' => "[AI Concierge Lead]\n" . $handoff['summary'],
                'ip' => $conversation['ip_address'],
                'agent' => $conversation['user_agent'],
            ]);
        }

        return ['url' => $handoff['url'], 'summary' => $handoff['summary']];
    }

    private function ensureLeadAndQuotes(array $conversation): array
    {
        $sector = (string)($conversation['main_sector'] ?: 'guidance');
        $score = (int)$conversation['lead_score'];
        $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
        $condition = $this->conditions->find($sector, $score);
        $lead = $this->db->prepare(
            'INSERT INTO ai_leads (conversation_id,status,name,phone,email,customer_type,sector,need_summary,urgency,lead_score,assigned_to)
             VALUES (:conversation_id,"qualified",:name,:phone,:email,:customer_type,:sector,:need,:urgency,:score,:assigned)
             ON DUPLICATE KEY UPDATE status="qualified",name=VALUES(name),phone=VALUES(phone),lead_score=VALUES(lead_score)'
        );
        $lead->execute([
            'conversation_id' => $conversation['id'], 'name' => $conversation['customer_name'], 'phone' => $conversation['customer_phone'],
            'email' => $conversation['customer_email'], 'customer_type' => $conversation['customer_type'], 'sector' => $sector,
            'need' => (string)($data['need'] ?? 'Da approfondire'), 'urgency' => $conversation['urgency'], 'score' => $score,
            'assigned' => (new AgentPersonaRegistry())->forSector($sector)['key'],
        ]);
        $leadId = (int)$this->db->query('SELECT id FROM ai_leads WHERE conversation_id=' . (int)$conversation['id'])->fetchColumn();
        $quotes = $this->quotes->build($sector, $condition, $data);
        $this->db->prepare('DELETE FROM ai_quotes WHERE conversation_id=:id')->execute(['id' => $conversation['id']]);
        $stmt = $this->db->prepare(
            'INSERT INTO ai_quotes (conversation_id,lead_id,quote_level,title,summary,items_json,special_condition,disclaimers)
             VALUES (:conversation_id,:lead_id,:level,:title,:summary,:items,:condition,:disclaimers)'
        );
        foreach ($quotes as $quote) {
            $stmt->execute([
                'conversation_id' => $conversation['id'], 'lead_id' => $leadId, 'level' => $quote['level'], 'title' => $quote['title'],
                'summary' => $quote['summary'], 'items' => json_encode($quote['items'], JSON_UNESCAPED_UNICODE),
                'condition' => $quote['condition'], 'disclaimers' => 'Prezzi, disponibilita, copertura e condizioni finali richiedono verifica umana.',
            ]);
        }

        return $quotes;
    }

    private function find(string $publicId, string $sessionId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM ai_conversations WHERE public_id=:public_id AND session_id=:session_id LIMIT 1');
        $stmt->execute(['public_id' => $publicId, 'session_id' => $sessionId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Conversazione non trovata.');
        }

        return $row;
    }

    private function update(int $id, array $updates): void
    {
        $allowed = ['status', 'customer_name', 'customer_phone', 'customer_type', 'main_sector', 'urgency', 'lead_score', 'current_step', 'structured_data', 'consent_privacy', 'summary'];
        $sets = [];
        $params = ['id' => $id];
        foreach ($updates as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $sets[] = "{$key}=:{$key}";
                $params[$key] = $value;
            }
        }
        if ($sets) {
            $this->db->prepare('UPDATE ai_conversations SET ' . implode(',', $sets) . ' WHERE id=:id')->execute($params);
        }
    }

    private function message(int $id, string $role, string $content): void
    {
        $this->db->prepare('INSERT INTO ai_messages (conversation_id,role,content) VALUES (:id,:role,:content)')
            ->execute(compact('id', 'role', 'content'));
    }

    private function response(string $publicId, array $reply): array
    {
        return [
            'conversation_id' => $publicId,
            'reply' => $reply['message'],
            'step' => $reply['step'],
            'choices' => $reply['choices'],
            'quotes' => $reply['quotes'] ?? [],
            'ready' => $reply['ready'],
            'quote_ready' => $reply['quoteReady'] ?? false,
            'agent' => $reply['agent'] ?? (new AgentPersonaRegistry())->byKey('sarai'),
            'transition' => $reply['transition'] ?? null,
        ];
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
