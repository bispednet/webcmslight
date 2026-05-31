<?php
declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\WhatsApp\WhatsAppHandoffBuilder;
use PDO;

final class ConciergeOrchestrator
{
    private ConversationSupervisor $supervisor;
    private PromptInjectionGuard $guard;
    private WhatsAppHandoffBuilder $whatsapp;
    private AgentPersonaRegistry $agents;

    public function __construct(private PDO $db, private array $config)
    {
        $conciergeClient = GeminiClient::fromConfig($config, 'concierge');
        $prompts = new PromptBuilder();
        $analyzer = new ConversationalAnalyzer($conciergeClient, $prompts);
        $this->supervisor = new ConversationSupervisor($analyzer);
        $this->guard = new PromptInjectionGuard();
        $this->whatsapp = new WhatsAppHandoffBuilder();
        $this->agents = new AgentPersonaRegistry();
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
        $reply = $this->supervisor->greeting($locale);
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
            throw new \InvalidArgumentException('Per non farti perdere tempo, ti apro WhatsApp con il riepilogo di quello che ci siamo detti.');
        }

        $userMessage = $message !== '' ? $message : (string)$choice;
        $this->message((int)$conversation['id'], 'user', $userMessage);

        // Delegate to supervisor
        $reply = $this->supervisor->handleTurn($conversation, $userMessage);

        if (!empty($reply['updates'])) {
            $this->update((int)$conversation['id'], $reply['updates']);
            $conversation = $this->find($publicId, $sessionId);
        }
        $this->message((int)$conversation['id'], 'assistant', $reply['message']);

        if ($reply['ready']) {
            $handoff = $this->buildHandoff($publicId, $sessionId, $conversation, $reply);
            $reply['handoff'] = $handoff;
            $reply['action'] = 'redirect_whatsapp';
        }

        return $this->response($publicId, $reply);
    }

    public function handoff(string $publicId, string $sessionId): array
    {
        $conversation = $this->find($publicId, $sessionId);
        // Allow handoff even if not fully ready (customer may have requested it manually)
        $number = (string)($this->config['whatsapp']['phone_number'] ?? $this->config['ai_concierge']['whatsapp_number'] ?? '');
        $handoff = $this->whatsapp->build($number, $conversation, []);
        if (($conversation['status'] ?? '') !== 'handed_to_whatsapp') {
            $this->update((int)$conversation['id'], ['status' => 'handed_to_whatsapp', 'summary' => $handoff['summary']]);
            $this->message((int)$conversation['id'], 'handoff', $handoff['summary']);
            $this->persistContactMessage($conversation, $handoff['summary']);
        }

        return ['url' => $handoff['url'], 'summary' => $handoff['summary']];
    }

    private function buildHandoff(string $publicId, string $sessionId, array $conversation, array $reply): array
    {
        if (($conversation['status'] ?? '') === 'handed_to_whatsapp') {
            // Already handed off, just return url
            $number = (string)($this->config['whatsapp']['phone_number'] ?? $this->config['ai_concierge']['whatsapp_number'] ?? '');

            return $this->whatsapp->build($number, $conversation, [])['url'] ? $this->whatsapp->build($number, $conversation, []) : ['url' => '', 'summary' => ''];
        }

        $number = (string)($this->config['whatsapp']['phone_number'] ?? $this->config['ai_concierge']['whatsapp_number'] ?? '');

        // Re-fetch after update to get latest structured_data
        $conversation = $this->find($publicId, $sessionId);
        $handoff = $this->whatsapp->build($number, $conversation, []);

        $this->update((int)$conversation['id'], ['status' => 'handed_to_whatsapp', 'summary' => $handoff['summary']]);
        $this->message((int)$conversation['id'], 'handoff', $handoff['summary']);
        $this->persistContactMessage($conversation, $handoff['summary']);

        // Persist commercial report in ai_conversation_reports if table exists
        $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
        if (!empty($data['commercial_report'])) {
            $this->persistReport((int)$conversation['id'], 'commercial_report', $data['commercial_report']);
        }
        if (!empty($data['analytics'])) {
            $this->persistReport((int)$conversation['id'], 'analytics', null, $data['analytics']);
        }
        $this->persistReport((int)$conversation['id'], 'whatsapp_summary', $handoff['summary']);

        // Persist lead record
        $this->ensureLead($conversation);

        return ['url' => $handoff['url'], 'summary' => $handoff['summary']];
    }

    private function ensureLead(array $conversation): void
    {
        $data = json_decode((string)($conversation['structured_data'] ?? '{}'), true) ?: [];
        $sector = (string)($conversation['main_sector'] ?: 'guidance');
        $agent = $this->agents->forSector($sector);
        $lead = $this->db->prepare(
            'INSERT INTO ai_leads (conversation_id,status,name,phone,email,customer_type,sector,need_summary,urgency,lead_score,assigned_to)
             VALUES (:conversation_id,"qualified",:name,:phone,:email,:customer_type,:sector,:need,:urgency,:score,:assigned)
             ON DUPLICATE KEY UPDATE status="qualified",name=VALUES(name),phone=VALUES(phone),lead_score=VALUES(lead_score)'
        );
        $lead->execute([
            'conversation_id' => $conversation['id'],
            'name' => $conversation['customer_name'],
            'phone' => $conversation['customer_phone'],
            'email' => $conversation['customer_email'],
            'customer_type' => $conversation['customer_type'],
            'sector' => $sector,
            'need' => (string)($data['need_summary'] ?? 'Da approfondire'),
            'urgency' => $conversation['urgency'],
            'score' => (int)$conversation['lead_score'],
            'assigned' => $agent['key'],
        ]);
    }

    private function persistContactMessage(array $conversation, string $summary): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO contact_messages (name,email,message,ip_address,user_agent,status) VALUES (:name,:email,:message,:ip,:agent,"new")'
        );
        $stmt->execute([
            'name' => $conversation['customer_name'] ?: 'Lead AI Concierge',
            'email' => $conversation['customer_email'] ?: 'concierge@bisped.net',
            'message' => "[AI Concierge Lead]\n" . $summary,
            'ip' => $conversation['ip_address'],
            'agent' => $conversation['user_agent'],
        ]);
    }

    private function persistReport(int $conversationId, string $type, ?string $content, ?array $contentJson = null): void
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO ai_conversation_reports (conversation_id,report_type,content,content_json) VALUES (:id,:type,:content,:json)
                 ON DUPLICATE KEY UPDATE content=VALUES(content),content_json=VALUES(content_json)'
            );
            $stmt->execute([
                'id' => $conversationId,
                'type' => $type,
                'content' => $content,
                'json' => $contentJson ? json_encode($contentJson, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (\Throwable) {
            // Table may not exist yet; graceful degradation
        }
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
            'choices' => [],
            'quotes' => [],
            'ready' => $reply['ready'],
            'action' => $reply['action'] ?? null,
            'handoff' => $reply['handoff'] ?? null,
            'agent' => $reply['agent'] ?? $this->agents->byKey('sarai'),
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
