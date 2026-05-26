<?php
declare(strict_types=1);

namespace App\Models;

final class Product extends Model
{
    protected string $table = 'products';

    protected array $fillable = [
        'name',
        'slug',
        'description',
        'icon_key',
        'external_link',
        'hero_title',
        'hero_subtitle',
        'cta_text',
        'cta_link',
        'category',
        'tags',
        'sku',
        'price',
        'sale_price',
        'campaign_label',
        'stock_status',
        'content_html',
        'featured_order',
    ];
}
