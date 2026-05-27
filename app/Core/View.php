<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Container;
use App\Support\I18n;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $config = Container::get('config', []);
        $viewPath = BASE_PATH . '/app/Views/' . $template . '.php';

        if (!is_file($viewPath)) {
            throw new \RuntimeException(sprintf('View "%s" not found.', $template));
        }

        extract($data, EXTR_SKIP);
        $appName = $config['app']['name'] ?? 'Bisped';

        ob_start();
        require $viewPath;
        echo I18n::translateHtml((string)ob_get_clean());
    }

    public static function renderPartial(string $template, array $data = []): void
    {
        $partialPath = BASE_PATH . '/app/Views/' . $template . '.php';

        if (!is_file($partialPath)) {
            throw new \RuntimeException(sprintf('Partial "%s" not found.', $template));
        }

        extract($data, EXTR_SKIP);

        require $partialPath;
    }
}
