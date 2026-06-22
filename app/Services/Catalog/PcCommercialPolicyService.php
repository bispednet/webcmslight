<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use App\Services\Ai\GeminiClient;
use PDO;

final class PcCommercialPolicyService
{
    public function __construct(private PDO $db, private ?GeminiClient $llm)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function currentPolicy(bool $force = false): array
    {
        if (!$force) {
            $cached = $this->cachedPolicy();
            if ($cached !== []) {
                return $cached;
            }
        }

        $fallback = $this->fallbackPolicy('llm_unavailable');
        if (!$this->llm instanceof GeminiClient) {
            $this->storePolicy($fallback, 'fallback', 'LLM non configurato.');
            return $fallback;
        }

        $raw = $this->llm->generate($this->policyPrompt(), 4200, 'json');
        $policy = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($policy)) {
            $this->storePolicy($fallback, 'fallback', 'LLM non ha restituito JSON valido.');
            return $fallback;
        }

        $normalized = $this->normalizePolicy($policy);
        $this->storePolicy($normalized, 'llm', (string)($normalized['summary'] ?? 'Policy commerciale generata via LLM.'));

        return $normalized;
    }

    /**
     * @param array<string,mixed> $profile
     * @param array<string,array<string,mixed>> $components
     * @return array{approved:bool,reason:string}
     */
    public function reviewBuild(array $profile, array $components, float $total, array $policy): array
    {
        if (!$this->llm instanceof GeminiClient || (string)($policy['source'] ?? '') !== 'llm') {
            return ['approved' => true, 'reason' => 'Review LLM non disponibile: approvazione demandata alla policy fallback conservativa.'];
        }

        $payload = [
            'date' => date('Y-m-d'),
            'profile' => [
                'key' => (string)($profile['key'] ?? ''),
                'use' => (string)($profile['use'] ?? ''),
                'budget' => (float)($profile['budget'] ?? 0),
            ],
            'total' => $total,
            'policy' => $policy,
            'components' => array_map(static function (array $row): array {
                return [
                    'type' => (string)($row['component_type'] ?? ''),
                    'name' => (string)($row['name'] ?? ''),
                    'price' => (float)($row['price'] ?? 0),
                    'capacity_gb' => $row['capacity_gb'] ?? null,
                    'interface_type' => $row['interface_type'] ?? null,
                    'wattage' => $row['wattage'] ?? null,
                ];
            }, $components),
        ];

        $prompt = "Sei il responsabile commerciale hardware di un negozio italiano nel 2026.\n"
            . "Valuta se questa configurazione preassemblata e' vendibile, bilanciata e difendibile commercialmente.\n"
            . "Boccia senza esitazione PC con RAM insufficiente, storage non NVMe dove non ha senso, PSU/case scadenti, GPU sottodimensionata, CPU/GPU sbilanciate, o componenti non coerenti col budget.\n"
            . "Rispondi solo JSON: {\"approved\":true|false,\"reason\":\"max 180 caratteri\"}.\n"
            . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $raw = $this->llm->generate($prompt, 260, 'json');
        $review = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($review) || !array_key_exists('approved', $review)) {
            return ['approved' => false, 'reason' => 'Review LLM non valida: build non pubblicata.'];
        }

        return [
            'approved' => (bool)$review['approved'],
            'reason' => mb_substr(trim((string)($review['reason'] ?? '')), 0, 240, 'UTF-8') ?: 'Review LLM senza motivazione.',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function cachedPolicy(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT policy_json
                 FROM pc_commercial_policies
                 WHERE source = 'llm'
                   AND generated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 ORDER BY generated_at DESC
                 LIMIT 1"
            );
            $json = $stmt ? $stmt->fetchColumn() : false;
            $decoded = is_string($json) ? json_decode($json, true) : null;

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<string,mixed> $policy
     */
    private function storePolicy(array $policy, string $source, string $notes): void
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO pc_commercial_policies (source, policy_json, notes, generated_at)
                 VALUES (:source, :policy_json, :notes, NOW())'
            );
            $stmt->execute([
                'source' => $source,
                'policy_json' => json_encode($policy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'notes' => mb_substr($notes, 0, 1000, 'UTF-8'),
            ]);
        } catch (\Throwable) {
            // Il planner deve poter continuare anche su host con schema non ancora migrato.
        }
    }

    private function policyPrompt(): string
    {
        return "Sei un buyer e system integrator PC in Italia. Data: " . date('Y-m-d') . ".\n"
            . "Genera una policy commerciale aggiornata per PC preassemblati gaming/workstation basata sul listino reale disponibile sotto.\n"
            . "La policy deve evitare configurazioni invendibili: RAM troppo bassa, storage lento, alimentatori/case scadenti, CPU/GPU sbilanciate, fasce prezzo non difendibili.\n"
            . "Non inseguire solo il prezzo: scegli standard commerciali 2026 e componenti richiesti dal mercato.\n"
            . "Rispondi solo JSON con questa struttura:\n"
            . "{ \"summary\":\"...\", \"global\":{\"reject_keywords\":[],\"prefer_keywords\":[]},"
            . "\"profiles\":{\"office-500\":{\"min_ram_gb\":8,\"min_storage_gb\":240,\"storage_interface\":\"SSD\",\"min_psu_watt\":300,\"max_psu_watt\":650,\"min_case_price\":20,\"gpu_min_rank\":0,\"allow_integrated_psu\":true,\"allow_basic_psu\":true,\"office_case_ok\":true},"
            . "\"office-800\":{...},\"workstation-1200\":{...},\"entry-gaming-1000\":{...},\"gaming-1500\":{...},\"gaming-2000\":{...},\"gaming-3000\":{...},\"gaming-4000\":{...},\"gaming-5000\":{...},\"gaming-mostruoso\":{...}}}\n"
            . "gpu_min_rank usa scala NVIDIA-like: RTX 4060=4060, RTX 5060=5060, RTX 5070=5070, RTX 5080=5080; AMD equivalente mappata in modo comparabile.\n"
            . "Listino sintetico:\n" . json_encode($this->catalogSnapshot(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string,mixed>
     */
    private function catalogSnapshot(): array
    {
        return [
            'cpus' => $this->sample('cpu', 40),
            'gpus' => $this->sample('gpu', 60),
            'ram' => $this->sample('ram', 40),
            'storage' => $this->sample('storage', 40),
            'psu' => $this->sample('psu', 40),
            'case' => $this->sample('case', 40),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function sample(string $type, int $limit): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.name, COALESCE(p.sale_price, p.price, 0) price,
                    s.platform_brand, s.socket, s.memory_type, s.form_factor, s.wattage, s.capacity_gb, s.interface_type
             FROM pc_component_specs s
             INNER JOIN products p ON p.id = s.product_id
             WHERE s.component_type = :type
               AND COALESCE(p.sale_price, p.price, 0) > 0
               AND COALESCE(p.stock_status, '') NOT IN ('esaurito','ritirato','outofstock','non disponibile')
             ORDER BY COALESCE(p.sale_price, p.price, 0) ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string,mixed> $policy
     * @return array<string,mixed>
     */
    private function normalizePolicy(array $policy): array
    {
        $fallback = $this->fallbackPolicy('normalized_fallback');
        $profiles = [];
        foreach (($fallback['profiles'] ?? []) as $key => $defaults) {
            $src = (array)($policy['profiles'][$key] ?? []);
            $profiles[$key] = [
                'min_ram_gb' => max(8, (int)($src['min_ram_gb'] ?? $defaults['min_ram_gb'])),
                'min_storage_gb' => max(128, (int)($src['min_storage_gb'] ?? $defaults['min_storage_gb'])),
                'storage_interface' => (string)($src['storage_interface'] ?? $defaults['storage_interface']),
                'min_psu_watt' => max(300, (int)($src['min_psu_watt'] ?? $defaults['min_psu_watt'])),
                'max_psu_watt' => max(450, (int)($src['max_psu_watt'] ?? $defaults['max_psu_watt'])),
                'min_case_price' => max(0, (int)($src['min_case_price'] ?? $defaults['min_case_price'])),
                'gpu_min_rank' => max(0, (int)($src['gpu_min_rank'] ?? $defaults['gpu_min_rank'])),
                'allow_integrated_psu' => (bool)($src['allow_integrated_psu'] ?? ($defaults['allow_integrated_psu'] ?? false)),
                'allow_basic_psu' => (bool)($src['allow_basic_psu'] ?? ($defaults['allow_basic_psu'] ?? false)),
                'office_case_ok' => (bool)($src['office_case_ok'] ?? ($defaults['office_case_ok'] ?? false)),
            ];
        }

        return [
            'generated_at' => date('c'),
            'source' => 'llm',
            'summary' => mb_substr((string)($policy['summary'] ?? 'Policy LLM normalizzata.'), 0, 500, 'UTF-8'),
            'global' => [
                'reject_keywords' => array_values(array_filter(array_map('strval', (array)($policy['global']['reject_keywords'] ?? [])))),
                'prefer_keywords' => array_values(array_filter(array_map('strval', (array)($policy['global']['prefer_keywords'] ?? [])))),
            ],
            'profiles' => $profiles,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function fallbackPolicy(string $reason): array
    {
        return [
            'generated_at' => date('c'),
            'source' => 'fallback',
            'summary' => 'Fallback locale: office entry permissivo ma vendibile, workstation/gaming con standard moderni e bilanciati. Motivo: ' . $reason,
            'global' => [
                'reject_keywords' => ['free silent', 'energy piv', 'gt 710', 'gt 730', 'gt 1030', 'hdd'],
                'prefer_keywords' => ['nvme', '80 plus', 'gold', 'bronze', 'mesh', 'airflow', 'argb'],
            ],
            'profiles' => [
                'office-500' => ['min_ram_gb' => 8, 'min_storage_gb' => 240, 'storage_interface' => 'SSD', 'min_psu_watt' => 300, 'max_psu_watt' => 650, 'min_case_price' => 20, 'gpu_min_rank' => 0, 'allow_integrated_psu' => true, 'allow_basic_psu' => true, 'office_case_ok' => true],
                'office-800' => ['min_ram_gb' => 16, 'min_storage_gb' => 512, 'storage_interface' => 'SSD', 'min_psu_watt' => 300, 'max_psu_watt' => 650, 'min_case_price' => 25, 'gpu_min_rank' => 0, 'allow_integrated_psu' => false, 'allow_basic_psu' => true, 'office_case_ok' => true],
                'workstation-1200' => ['min_ram_gb' => 32, 'min_storage_gb' => 1024, 'storage_interface' => 'NVMe M.2', 'min_psu_watt' => 500, 'max_psu_watt' => 750, 'min_case_price' => 45, 'gpu_min_rank' => 0, 'allow_integrated_psu' => false, 'allow_basic_psu' => false, 'office_case_ok' => true],
                'entry-gaming-1000' => ['min_ram_gb' => 16, 'min_storage_gb' => 512, 'storage_interface' => 'NVMe M.2', 'min_psu_watt' => 650, 'max_psu_watt' => 750, 'min_case_price' => 55, 'gpu_min_rank' => 4060],
                'gaming-1500' => ['min_ram_gb' => 32, 'min_storage_gb' => 1024, 'storage_interface' => 'NVMe M.2', 'min_psu_watt' => 650, 'max_psu_watt' => 750, 'min_case_price' => 55, 'gpu_min_rank' => 5060],
                'gaming-2000' => ['min_ram_gb' => 32, 'min_storage_gb' => 1024, 'storage_interface' => 'NVMe M.2', 'min_psu_watt' => 650, 'max_psu_watt' => 750, 'min_case_price' => 55, 'gpu_min_rank' => 5060],
                'gaming-3000' => ['min_ram_gb' => 32, 'min_storage_gb' => 2048, 'storage_interface' => 'NVMe M.2', 'min_psu_watt' => 750, 'max_psu_watt' => 1000, 'min_case_price' => 80, 'gpu_min_rank' => 5070],
                'gaming-4000' => ['min_ram_gb' => 32, 'min_storage_gb' => 2048, 'storage_interface' => 'NVMe M.2', 'min_psu_watt' => 850, 'max_psu_watt' => 1200, 'min_case_price' => 80, 'gpu_min_rank' => 5080],
                'gaming-5000' => ['min_ram_gb' => 48, 'min_storage_gb' => 2048, 'storage_interface' => 'NVMe M.2', 'min_psu_watt' => 850, 'max_psu_watt' => 1200, 'min_case_price' => 100, 'gpu_min_rank' => 5080],
                'gaming-mostruoso' => ['min_ram_gb' => 48, 'min_storage_gb' => 4096, 'storage_interface' => 'NVMe M.2', 'min_psu_watt' => 1000, 'max_psu_watt' => 1600, 'min_case_price' => 140, 'gpu_min_rank' => 5090],
            ],
        ];
    }
}
