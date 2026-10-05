<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Str;

/** Props for the React product page (Storefront/Product), built from the same data the Blade page uses. */
class ProductPage
{
    public static function props(array $d): array
    {
        /** @var Product $p */
        $p = $d['product'];
        $p->loadMissing(['images', 'variations.attributeValues.attribute', 'tags', 'brand.translations', 'category.translations']);
        $onSale = $p->isOnSale();
        $cards = fn ($list) => ProductCards::map(
            ProductCards::with(Product::query())->whereIn('id', collect($list)->pluck('id'))->get()
        );

        return [
            'product' => [
                'id' => $p->id,
                'slug' => $p->slug,
                'url' => route('shop.show', $p->slug),
                'name' => $p->translated_name,
                'type' => $p->product_type ?? 'simple',
                'sku' => $p->sku,
                'short' => $p->translated_short_description ?: Str::limit(strip_tags(clean_html($p->translated_description)), 200),
                'description' => safe_html(clean_html($p->translated_description)),
                'features' => collect(is_array($p->features) ? $p->features : [])
                    ->filter(fn ($f) => is_array($f) && filled($f['name'] ?? null))
                    ->map(fn ($f) => ['name' => (string) $f['name'], 'value' => (string) ($f['value'] ?? '')])->values(),
                'price' => format_currency($onSale ? $p->sale_price : $p->price),
                'regular' => $onSale ? format_currency($p->price) : null,
                'priceValue' => round((float) ($onSale ? $p->sale_price : $p->price), 2), // structured data
                'discount' => $onSale ? (int) $p->discount_percent : 0,
                'saleEnds' => $onSale && $p->sale_end_date ? $p->sale_end_date->toIso8601String() : null,
                'inStock' => $p->isInStock(),
                'quantity' => (int) $p->quantity,
                'digital' => (bool) $p->is_downloadable,
                'rating' => round((float) $p->average_rating, 1),
                'reviews' => (int) $p->review_count,
                'images' => static::images($p),
                'brand' => $p->brand ? ['name' => $p->brand->translated_name ?? $p->brand->name, 'slug' => $p->brand->slug] : null,
                'category' => $p->category ? ['name' => $p->category->translated_name ?? $p->category->name, 'slug' => $p->category->slug] : null,
                'tags' => $p->tags->map(fn ($t) => [
                    'name' => $t->name, 'slug' => $t->slug,
                    'color' => preg_match('/^#[0-9a-f]{6}$/i', (string) $t->color) ? $t->color : '#6b7280',
                ])->values(),
                'attributes' => static::attributes($p),
                'variations' => $p->product_type === 'variable' ? $p->variations->where('is_active', true)->map(fn ($v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'name' => $v->variation_name,
                    'price' => format_currency(static::variationPrice($v, $p)),
                    'regular' => $v->sale_price && $v->price && $v->sale_price < $v->price ? format_currency($v->price) : null,
                    'quantity' => (int) $v->quantity,
                    'image' => $v->image ? asset('storage/' . $v->image) : null,
                    'values' => $v->attributeValues->pluck('id')->all(),
                ])->values() : [],
            ],
            'options' => static::options(),
            'reviews' => $d['reviews']->through(fn ($r) => [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'title' => $r->title,
                'comment' => $r->comment,
                'author' => $r->user?->name ?? 'Customer',
                'date' => $r->created_at?->format('M d, Y'),
            ]),
            'aiReviewAnalysis' => $d['aiReviewAnalysis'] ?: null,
            'related' => $cards($d['relatedProducts']),
            'recommended' => $cards($d['aiRecommendations']),
            'crossSell' => $d['crossSellEnabled'] ? $cards($d['crossSellProducts']) : [],
            'crossSellTitle' => (string) $d['crossSellTitle'],
            'seo' => [
                'title' => $p->translated_name,
                'description' => Str::limit(strip_tags($p->translated_short_description ?: clean_html($p->translated_description)), 160),
                'image' => $p->image_url,
                'canonical' => route('shop.show', $p->slug),
                'type' => 'product',
            ],
            'aiProduct' => [
                'id' => $p->id, 'slug' => $p->slug, 'name' => $p->translated_name, 'url' => route('shop.show', $p->slug),
                'category' => $p->category?->name, 'brand' => $p->brand?->name,
                'price' => format_currency($p->price), 'sale_price' => $onSale ? format_currency($p->sale_price) : null,
                'short_description' => strip_tags((string) $p->translated_short_description),
            ],
        ];
    }

    /** Main image, gallery images and the legacy JSON gallery, without duplicates. */
    protected static function images(Product $p): array
    {
        $all = [];
        if ($p->image) {
            $all[] = $p->image_url;
        }
        foreach ($p->images->sortBy('sort_order') as $img) {
            $all[] = asset('storage/' . $img->image);
        }
        foreach (is_array($p->gallery) ? $p->gallery : [] as $img) {
            if (is_string($img) && $img !== '') {
                $all[] = asset('storage/' . $img);
            }
        }
        $all = array_values(array_unique($all));

        return $all ?: [asset('images/no-image.png')];
    }

    /** Options of a variable product grouped by attribute (only values that some variation uses). */
    protected static function attributes(Product $p): array
    {
        if ($p->product_type !== 'variable') {
            return [];
        }
        $groups = [];
        foreach ($p->variations->where('is_active', true) as $v) {
            foreach ($v->attributeValues as $val) {
                $a = $val->attribute;
                if (!$a) {
                    continue;
                }
                $groups[$a->id] ??= ['id' => $a->id, 'name' => $a->name, 'type' => $a->type, 'values' => []];
                $groups[$a->id]['values'][$val->id] = [
                    'id' => $val->id,
                    'label' => $val->value,
                    'color' => preg_match('/^#[0-9a-f]{3,8}$/i', (string) $val->color_code) ? $val->color_code : null,
                ];
            }
        }

        return array_values(array_map(fn ($g) => array_merge($g, ['values' => array_values($g['values'])]), $groups));
    }

    protected static function variationPrice($v, Product $p): float
    {
        if ($v->sale_price && $v->price && $v->sale_price < $v->price) {
            return (float) $v->sale_price;
        }

        return (float) ($v->price ?: ($p->isOnSale() ? $p->sale_price : $p->price));
    }

    /** What the product page shows (Settings > Products / Shop / Mobile). */
    public static function options(): array
    {
        $s = fn ($k, $d = null) => setting($k, $d);
        $custom = (bool) $s('products.use_custom_button_colors', false);
        $titleSize = ['xl' => 20, '2xl' => 24, '3xl' => 30, '4xl' => 36][$s('shop.product_title_size', '2xl')] ?? 24;
        $priceSize = ['lg' => 18, 'xl' => 20, '2xl' => 24, '3xl' => 30][$s('shop.price_size', '2xl')] ?? 24;
        $hex = fn ($v, $d) => preg_match('/^#[0-9a-f]{6}$/i', (string) $v) ? $v : $d;

        return [
            'showBrand' => (bool) $s('products.show_brand', true),
            'showCategory' => (bool) $s('products.show_category', true),
            'showTags' => (bool) $s('products.show_tags', true),
            'showSku' => (bool) $s('products.show_sku', true),
            'showStockQty' => (bool) $s('products.show_stock_quantity', false),
            'showAddToCart' => (bool) $s('products.show_add_to_cart', true),
            'showBuyNow' => (bool) $s('products.show_buy_now', true),
            'showWishlist' => (bool) $s('products.show_wishlist', true),
            'showShare' => (bool) $s('products.show_share', true),
            'share' => array_keys(array_filter([
                'facebook' => $s('products.share_facebook', true), 'twitter' => $s('products.share_twitter', true),
                'whatsapp' => $s('products.share_whatsapp', true), 'pinterest' => $s('products.share_pinterest', true),
                'linkedin' => $s('products.share_linkedin', false), 'email' => $s('products.share_email', true), 'copy' => $s('products.share_copy', true),
            ])),
            'stickyBar' => (bool) $s('products.mobile_sticky_buttons', true),
            'mobileLayout' => (string) $s('products.mobile_button_layout', 'full'),
            'mobileQuantity' => (bool) $s('products.mobile_show_quantity', false),
            'reviewsEnabled' => Modules::enabled('reviews'),
            'buttons' => $custom ? [
                'cartBg' => $hex($s('products.add_to_cart_bg_color'), '#ffffff'), 'cartText' => $hex($s('products.add_to_cart_text_color'), '#2563eb'),
                'cartBorder' => $hex($s('products.add_to_cart_border_color'), '#2563eb'), 'cartHover' => $hex($s('products.add_to_cart_hover_bg'), '#eff6ff'),
                'buyBg' => $hex($s('products.buy_now_bg_color'), '#2563eb'), 'buyText' => $hex($s('products.buy_now_text_color'), '#ffffff'),
                'buyHover' => $hex($s('products.buy_now_hover_bg'), '#1d4ed8'),
            ] : null,
            'type' => [
                'titleFont' => preg_replace('/[^A-Za-z0-9 ]/', '', (string) $s('shop.product_title_font', '')),
                'titleWeight' => (int) $s('shop.product_title_weight', 700) ?: 700,
                'titleColor' => $hex($s('shop.product_title_color'), '#1f2937'),
                'titlePx' => $titleSize, 'titleMobilePx' => (int) $s('products.mobile_detail_title_px', 20),
                'pricePx' => $priceSize, 'priceMobilePx' => (int) $s('products.mobile_price_px', 16),
                'priceColor' => $hex($s('shop.price_color'), '#1f2937'), 'saleColor' => $hex($s('shop.sale_price_color'), '#dc2626'),
                'brandColor' => $hex($s('shop.brand_color'), '#2563eb'),
                'brandUpper' => $s('shop.brand_style', 'uppercase') === 'uppercase',
                'shortPx' => (int) $s('products.desktop_short_desc_px', 16), 'shortMobilePx' => (int) $s('products.mobile_short_desc_px', 14),
                'longPx' => (int) $s('products.desktop_long_desc_px', 16), 'longMobilePx' => (int) $s('products.mobile_long_desc_px', 14),
            ],
        ];
    }
}
