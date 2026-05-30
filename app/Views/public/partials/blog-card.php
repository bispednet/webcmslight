<?php
/** @var array $post */

use App\Support\I18n;

$locale = I18n::currentLocale();
$imgUrl = trim((string)($post['image_url'] ?? ''));
$slug   = htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8');
$rawTitle = $locale === 'en' && trim((string)($post['title_en'] ?? '')) !== '' ? $post['title_en'] : $post['title'];
$rawSnippet = $locale === 'en' && trim((string)($post['snippet_en'] ?? '')) !== '' ? $post['snippet_en'] : ($post['snippet'] ?? '');
$decodeEntities = static function (string $value): string {
    for ($iteration = 0; $iteration < 3; $iteration++) {
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($decoded === $value) break;
        $value = $decoded;
    }
    return $value;
};
$title  = htmlspecialchars($decodeEntities((string)$rawTitle), ENT_QUOTES, 'UTF-8');
$date   = htmlspecialchars(date('d/m/Y', strtotime($post['published_at'])), ENT_QUOTES, 'UTF-8');
$snippet = htmlspecialchars($decodeEntities((string)$rawSnippet), ENT_QUOTES, 'UTF-8');
$postUrl = ($locale === 'en' ? '/en/blog/' : '/blog/') . $slug;
?>

<a href="<?= $postUrl ?>" class="block group h-full">
    <div class="blog-card h-full">
        <?php if ($imgUrl !== ''): ?>
            <div style="aspect-ratio:16/9;overflow:hidden;background:#fff">
                <img loading="lazy"
                     src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= $title ?>"
                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
            </div>
        <?php else: ?>
            <div class="flex items-center justify-center" style="aspect-ratio:16/9;background:var(--c-surface)">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-12 h-12 opacity-20" style="color:var(--c-muted)">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-4.5 5.25h4.5m2.25-12.75H6.375c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h11.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125Z"/>
                </svg>
            </div>
        <?php endif; ?>
        <div class="p-5 flex flex-col flex-1">
            <p class="text-xs mb-2" style="color:var(--c-muted)"><?= $date ?></p>
            <h3 class="font-display font-bold text-base leading-snug mb-2 transition-colors group-hover:text-red-400 line-clamp-2" style="color:var(--c-acc)"><?= $title ?></h3>
            <p class="text-sm leading-6 line-clamp-3 flex-1" style="color:var(--c-muted)"><?= $snippet ?></p>
            <span class="mt-4 text-xs font-black uppercase tracking-widest transition-colors group-hover:text-red-400" style="color:var(--bisped-red)">Leggi →</span>
        </div>
    </div>
</a>
