<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Services\Security\Csrf;
use App\Support\Flash;
use App\Support\Media;
use App\Support\Uploads;
use PDO;

final class SettingsController extends Controller
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function index(): void
    {
        $stmt = $this->db->query('SELECT setting_key, setting_value FROM settings ORDER BY setting_key ASC');
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        $this->view('admin/settings/index', [
            'title' => 'Site Settings',
            'settings' => $settings,
            'notice' => Flash::pull('admin.settings.notice'),
            'error' => Flash::pull('admin.settings.error'),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function update(): void
    {
        $this->assertValidCsrf($_POST['csrf_token'] ?? null);

        try {
            $action = $this->resolveAction($_POST['action'] ?? null);
            $message = match ($action) {
                'general' => $this->handleGeneralUpdate(),
                'brand' => $this->handleBrandUpdate(),
                'seo' => $this->handleSeoUpdate(),
                'pricing' => $this->handlePricingUpdate(),
                default => throw new \InvalidArgumentException('Unknown settings action.'),
            };
            Flash::set('admin.settings.notice', $message);
        } catch (\InvalidArgumentException $e) {
            Flash::set('admin.settings.error', $e->getMessage());
        } catch (\Throwable $e) {
            Flash::set('admin.settings.error', 'Unable to save settings. Please try again.');
        }

        $this->redirect('/admin/settings');
    }

    private function assertValidCsrf(?string $token): void
    {
        if (Csrf::verify($token)) {
            return;
        }

        Flash::set('admin.settings.error', 'Session expired, please try again.');
        $this->redirect('/admin/settings');
    }

    private function resolveAction(mixed $action): string
    {
        if (!is_string($action)) {
            return '';
        }

        return strtolower(trim($action));
    }

    private function handleGeneralUpdate(): string
    {
        $payload = [
            'site_name' => $this->sanitizeShortText($_POST['site_name'] ?? ''),
            'site_tagline' => $this->sanitizeShortText($_POST['site_tagline'] ?? '', 160),
            'contact_email' => $this->sanitizeEmail($_POST['contact_email'] ?? ''),
            'business_telegram' => $this->sanitizeUrl($_POST['business_telegram'] ?? ''),
        ];

        $this->persistSettings($payload);
        return 'General settings saved.';
    }

    private function handleBrandUpdate(): string
    {
        $updates = [];

        if (isset($_FILES['site_logo']) && is_array($_FILES['site_logo']) && $this->hasFileUpload($_FILES['site_logo'])) {
            $stored = Uploads::store($_FILES['site_logo'], 'site-logo');
            $promoted = Media::promoteToSvgLibrary($stored['path'], 'logo/site-logo');
            $updates['site_logo'] = $promoted ?? Media::normalizeMediaPath($stored['path']);
        }

        if (isset($_FILES['favicon']) && is_array($_FILES['favicon']) && $this->hasFileUpload($_FILES['favicon'])) {
            $stored = Uploads::store($_FILES['favicon'], 'favicon');
            $updates['favicon_path'] = Media::normalizeMediaPath($stored['path']);
        }

        if (empty($updates)) {
            throw new \InvalidArgumentException('Upload a logo or favicon before saving.');
        }

        $this->persistSettings($updates);
        return 'Brand assets updated.';
    }

    private function handleSeoUpdate(): string
    {
        $payload = [
            'seo_meta_title' => $this->sanitizeShortText($_POST['seo_meta_title'] ?? '', 120),
            'seo_social_title' => $this->sanitizeShortText($_POST['seo_social_title'] ?? '', 120),
            'seo_meta_description' => $this->sanitizeLongText($_POST['seo_meta_description'] ?? ''),
            'seo_social_description' => $this->sanitizeLongText($_POST['seo_social_description'] ?? ''),
            'seo_twitter_description' => $this->sanitizeLongText($_POST['seo_twitter_description'] ?? '', 280),
            'seo_telegram_description' => $this->sanitizeLongText($_POST['seo_telegram_description'] ?? '', 512),
            'seo_discord_description' => $this->sanitizeLongText($_POST['seo_discord_description'] ?? '', 512),
        ];

        if (isset($_FILES['seo_share_image']) && is_array($_FILES['seo_share_image']) && $this->hasFileUpload($_FILES['seo_share_image'])) {
            $stored = Uploads::store($_FILES['seo_share_image'], 'seo-share-image');
            $path = Media::normalizeMediaPath($stored['path']);
            $payload['seo_share_image'] = $path;
            $payload['og_image'] = $path;
        }

        $this->persistSettings($payload);
        return 'SEO settings saved.';
    }


    private function hasFileUpload(array $file): bool
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        return $error !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * @param array<string,string> $settings
     */
    private function handlePricingUpdate(): string
    {
        // Percentuali inserite come numeri interi (es. 10 = 10%, 22 = IVA 22%)
        $lowPct = max(0.0, min(200.0, (float)str_replace(',', '.', (string)($_POST['markup_low'] ?? '20'))));
        $highPct = max(0.0, min($lowPct, (float)str_replace(',', '.', (string)($_POST['markup_high'] ?? '5.5'))));
        $lowCost = max(0.0, (float)str_replace(',', '.', (string)($_POST['low_cost_limit'] ?? '150')));
        $highCost = max($lowCost + 1, (float)str_replace(',', '.', (string)($_POST['high_cost_limit'] ?? '1000')));
        $absoluteMinimum = max(0.0, (float)str_replace(',', '.', (string)($_POST['minimum_sale_markup'] ?? '50')));
        $vatPct    = max(0.0, min(100.0, (float)str_replace(',', '.', (string)($_POST['vat'] ?? '22'))));
        $discPct   = max(0.0, min(100.0, (float)str_replace(',', '.', (string)($_POST['max_discount'] ?? '5'))));
        $fixed     = max(0.0, min(1000.0, (float)str_replace(',', '.', (string)($_POST['markup_fixed'] ?? '5'))));
        $minimumMarkup = max(0.0, min(200.0, (float)str_replace(',', '.', (string)($_POST['minimum_markup'] ?? '5.5'))));
        $minimumProfit = max(0.0, min(1000.0, (float)str_replace(',', '.', (string)($_POST['minimum_profit'] ?? '5'))));
        $maxAgeHours = max(1.0, min(24.0, (float)($_POST['max_age_hours'] ?? 2)));

        $this->persistSettings([
            'catalog_pricing_mode' => 'graduated',
            'catalog_markup_low' => (string)round($lowPct / 100, 4),
            'catalog_markup_high' => (string)round($highPct / 100, 4),
            'catalog_low_cost_limit' => (string)round($lowCost, 2),
            'catalog_high_cost_limit' => (string)round($highCost, 2),
            'catalog_minimum_sale_markup' => (string)round($absoluteMinimum, 2),
            'catalog_minimum_sale_markup_from_cost' => (string)round($highCost, 2),
            'catalog_markup_fixed'   => (string)round($fixed, 2),
            'catalog_vat'            => (string)round($vatPct / 100, 4),
            'catalog_max_discount'   => (string)round($discPct / 100, 4),
            'catalog_minimum_markup' => (string)round($minimumMarkup / 100, 4),
            'catalog_minimum_profit' => (string)round($minimumProfit, 2),
            'catalog_max_age_seconds' => (string)(int)round($maxAgeHours * 3600),
        ]);

        return 'Ricarico salvato. I prezzi saranno ricalcolati alla prossima sincronizzazione del catalogo.';
    }

    private function persistSettings(array $settings): void
    {
        $filtered = array_filter(
            $settings,
            static fn($value, $key) => is_string($key),
            ARRAY_FILTER_USE_BOTH
        );

        if (empty($filtered)) {
            return;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO settings (setting_key, setting_value)
                 VALUES (:key, :value)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );

            foreach ($filtered as $key => $value) {
                $stmt->execute([
                    'key' => $key,
                    'value' => $value,
                ]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function sanitizeShortText(mixed $value, int $max = 80): string
    {
        if (!is_string($value)) {
            return '';
        }

        $clean = strip_tags(trim($value));
        return mb_substr($clean, 0, $max);
    }

    private function sanitizeLongText(mixed $value, int $max = 500): string
    {
        if (!is_string($value)) {
            return '';
        }

        $clean = strip_tags(trim($value));
        return mb_substr($clean, 0, $max);
    }

    private function sanitizeEmail(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $email = filter_var(trim($value), FILTER_VALIDATE_EMAIL);
        return $email !== false ? $email : '';
    }

    private function sanitizeUrl(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $normalized = trim($value);
        if ($normalized === '') {
            return '';
        }

        $normalized = filter_var($normalized, FILTER_SANITIZE_URL) ?: '';
        if ($normalized === '') {
            return '';
        }

        if (!preg_match('/^https?:\/\//i', $normalized)) {
            $normalized = 'https://' . ltrim($normalized, '/');
        }

        return mb_substr($normalized, 0, 500);
    }
}
