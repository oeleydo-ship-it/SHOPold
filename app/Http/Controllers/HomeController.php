<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\BlogPost;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $featuredCount = (int) setting('homepage.featured_count', 8);
        $arrivalsCount = (int) setting('homepage.arrivals_count', 8);
        $flashDealsCount = (int) setting('homepage.flash_deals_count', 4);
        $topRatedCount = (int) setting('homepage.top_rated_count', 6);
        
        $featuredProducts = Product::active()->featured()->inStock()->with(['category.translations', 'translations'])->take($featuredCount)->get();
        $newArrivals = Product::active()->inStock()->with(['category.translations', 'translations'])->latest()->take($arrivalsCount)->get();
        $categories = Category::active()->parentCategories()->withCount('products')->with('translations')->get();
        
        // Super Flash Deals - Products with highest discounts
        $flashDeals = Product::active()->inStock()->with(['category.translations', 'translations'])
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->orderByRaw('((price - sale_price) / price) DESC')
            ->take($flashDealsCount)
            ->get();
        
        // Top Rated Products - Products with highest average rating using subqueries
        $topRatedProducts = Product::active()->inStock()->with(['category.translations', 'translations'])
            ->select('products.*')
            ->selectSub(function ($query) {
                $query->from('reviews')
                    ->whereColumn('reviews.product_id', 'products.id')
                    ->where('reviews.is_approved', true)
                    ->selectRaw('COUNT(*)');
            }, 'reviews_count')
            ->selectSub(function ($query) {
                $query->from('reviews')
                    ->whereColumn('reviews.product_id', 'products.id')
                    ->where('reviews.is_approved', true)
                    ->selectRaw('COALESCE(AVG(rating), 0)');
            }, 'avg_rating')
            ->having('reviews_count', '>', 0)
            ->orderByDesc('avg_rating')
            ->orderByDesc('reviews_count')
            ->take($topRatedCount)
            ->get();

        // Hero Product Showcase - Get products for the hero slider
        $showcaseCount = (int) setting('theme.showcase_product_count', 4);
        $showcaseSource = setting('theme.showcase_product_source', 'featured');
        
        $showcaseProducts = collect([]);
        switch ($showcaseSource) {
            case 'featured':
                $showcaseProducts = Product::active()->featured()->inStock()->with(['category.translations', 'translations'])->take($showcaseCount)->get();
                break;
            case 'new_arrivals':
                $showcaseProducts = Product::active()->inStock()->with(['category.translations', 'translations'])->latest()->take($showcaseCount)->get();
                break;
            case 'best_sellers':
                $showcaseProducts = Product::active()->inStock()->with(['category.translations', 'translations'])
                    ->withCount('orderItems')
                    ->orderByDesc('order_items_count')
                    ->take($showcaseCount)
                    ->get();
                break;
            case 'on_sale':
                $showcaseProducts = Product::active()->inStock()->onSale()->with(['category.translations', 'translations'])
                    ->orderByRaw('((price - sale_price) / price) DESC')
                    ->take($showcaseCount)
                    ->get();
                break;
            case 'random':
                $showcaseProducts = Product::active()->inStock()->with(['category.translations', 'translations'])->inRandomOrder()->take($showcaseCount)->get();
                break;
            default:
                $showcaseProducts = Product::active()->featured()->inStock()->with(['category.translations', 'translations'])->take($showcaseCount)->get();
        }

        // Blog Posts for homepage
        $blogPosts = collect([]);
        if (setting('homepage.show_blog', false)) {
            $blogCount = (int) setting('homepage.blog_count', 3);
            $blogPosts = BlogPost::published()
                ->with(['category', 'author'])
                ->orderBy('published_at', 'desc')
                ->take($blogCount)
                ->get();
        }

        $data = compact('featuredProducts', 'newArrivals', 'categories', 'flashDeals', 'topRatedProducts', 'showcaseProducts', 'blogPosts');

        return \Inertia\Inertia::render('Storefront/Home', $this->reactProps($data));
    }

    /** Props for the React home page: the same queries as the Blade page, mapped for the browser. */
    protected function reactProps(array $d): array
    {
        // Re-load the chosen products with ratings/stock aggregated (ProductCards::with), keeping their order.
        $cards = function ($products) {
            $ids = collect($products)->pluck('id')->all();
            if (!$ids) {
                return [];
            }
            $loaded = \App\Support\ProductCards::with(\App\Models\Product::query())->whereIn('id', $ids)->get()->keyBy('id');

            return \App\Support\ProductCards::map(collect($ids)->map(fn ($id) => $loaded[$id] ?? null)->filter());
        };
        $img = fn ($path) => $path ? (preg_match('#^https?://#i', $path) ? $path : asset('storage/' . ltrim($path, '/'))) : null;
        $slides = function ($raw) use ($img) {
            $list = is_string($raw) ? json_decode($raw, true) : $raw;

            return collect(is_array($list) ? $list : [])->filter(fn ($s) => is_array($s))->map(fn ($s) => array_merge($s, [
                'image' => $img($s['image_path'] ?? null) ?? (isset($s['image']) && preg_match('#^https?://#i', (string) $s['image']) ? $s['image'] : null),
            ]))->values()->all();
        };

        $all = ['hero', 'features', 'categories', 'featured', 'flash_deals', 'top_rated', 'two_column_banners', 'promo', 'arrivals', 'blog', 'newsletter'];
        $order = array_values(array_filter(array_map('trim', explode(',', (string) setting('homepage.section_order', '')))));
        $sections = array_values(array_unique(array_merge(array_intersect($order, $all), $all)));

        $home = \App\View\StoreSettings::data()['homepage'];
        foreach (['flash_deals_title', 'flash_deals_button', 'flash_deals_end_date', 'blog_title', 'blog_subtitle', 'blog_columns', 'blog_button'] as $k) {
            $home[$k] = setting("homepage.{$k}");
        }
        $home['show_blog'] = (bool) setting('homepage.show_blog', false);
        foreach (['promo_bg_image', 'newsletter_bg_image', 'top_rated_promo_image', 'banner1_image', 'banner2_image'] as $k) {
            $home[$k] = $img($home[$k] ?? null);
        }
        try {
            $home['flash_deals_end_date'] = \Illuminate\Support\Carbon::parse($home['flash_deals_end_date'] ?: now()->addDays(7))->toIso8601String();
        } catch (\Throwable) {
            $home['flash_deals_end_date'] = now()->addDays(7)->toIso8601String();
        }

        $t = fn ($key, $default = null) => setting("theme.{$key}", $default);
        $mobileSlides = setting('products.mobile_hero_enabled', false) ? $slides(setting('products.mobile_hero_slides')) : [];

        return [
            'sections' => $sections,
            'home' => $home,
            'hero' => [
                'style' => (string) $t('hero_style', 'gradient'),
                'image' => $img($t('hero_image')) ?? 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200&h=600&fit=crop',
                'slides' => $slides($t('hero_slides', '[]')),
                'interval' => max(2000, (int) $t('slider_interval', 5000)),
                'navigation' => (string) $t('slider_navigation', 'both'),
                'height' => (string) $t('hero_height', 'medium'),
                'textColor' => (string) $t('hero_text_color', ''),
                'textAlign' => (string) $t('hero_text_align', 'left'),
                'bgColor' => (string) ($home['hero_bg_color'] ?? ''),
                'badgeIcon' => (string) ($home['hero_badge_icon'] ?? 'star'),
                'buttonLink' => (string) ($home['hero_button_link'] ?? '/shop'),
                'secondaryLink' => (string) ($home['hero_secondary_link'] ?? '/about'),
                'transparentHeader' => $t('header_style') === 'transparent',
                'showcase' => [
                    'bg' => (string) $t('showcase_bg_color', '#f9fafb'),
                    'card' => (string) $t('showcase_card_bg_color', '#ffffff'),
                    'imageBg' => (string) $t('showcase_image_bg_color', '#f3f4f6'),
                    'products' => $cards($d['showcaseProducts']),
                ],
                'fullwidth' => [
                    'position' => (string) $t('fullwidth_content_position', 'left'),
                    'width' => (string) $t('fullwidth_content_width', 'medium'),
                    'overlay' => (bool) $t('fullwidth_show_overlay', false),
                    'badge' => (bool) $t('fullwidth_show_badge', true),
                    'overlayStart' => (string) $t('fullwidth_overlay_start', '#000000'),
                    'overlayEnd' => (string) $t('fullwidth_overlay_end', 'transparent'),
                    'overlayOpacity' => (float) $t('fullwidth_overlay_opacity', 0.3),
                ],
                'video' => [
                    'url' => preg_match('#^https?://#i', (string) $t('video_url', '')) ? (string) $t('video_url') : null,
                    'embedId' => preg_match('/^[A-Za-z0-9_-]{1,60}$/', (string) $t('video_embed_id', '')) ? (string) $t('video_embed_id') : null,
                    'loop' => (bool) $t('video_loop', true),
                    'muted' => (bool) $t('video_muted', true),
                    'controls' => (bool) $t('video_show_controls', false),
                    'poster' => $img($t('video_fallback_image')),
                ],
                'mobile' => [
                    'slides' => $mobileSlides,
                    'height' => (string) setting('products.mobile_hero_height', 'medium'),
                    'arrows' => (bool) setting('products.mobile_hero_arrows', true),
                    'secondaryButton' => (bool) setting('products.mobile_hero_secondary_btn', false),
                    'features' => (bool) setting('products.mobile_hero_features', false),
                ],
            ],
            'categories' => $d['categories']->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->translated_name, 'slug' => $c->slug,
                'image' => $c->image ? asset('storage/' . $c->image) : null, 'count' => (int) ($c->products_count ?? 0),
            ])->values(),
            'featured' => $cards($d['featuredProducts']),
            'arrivals' => $cards($d['newArrivals']),
            'flashDeals' => $cards($d['flashDeals']),
            'topRated' => $cards($d['topRatedProducts']),
            'blogPosts' => collect($d['blogPosts'])->map(fn ($post) => [
                'id' => $post->id, 'title' => $post->title, 'slug' => $post->slug, 'excerpt' => $post->excerpt,
                'image' => $post->image_url, 'date' => ($post->published_at ?? $post->created_at)?->format('M d, Y'),
                'category' => $post->category ? ['name' => $post->category->name, 'slug' => $post->category->slug] : null,
            ])->values(),
            'seo' => ['canonical' => url('/')],
        ];
    }


    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Handle contact form submission (send email, store in DB, etc.)
        
        return back()->with('success', 'Thank you for your message. We will get back to you soon!');
    }

    public function newsletterSubscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $existing = \App\Models\Newsletter::where('email', $request->email)->first();
        
        if ($existing && $existing->isActive()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('messages.already_subscribed') ?? 'You are already subscribed!'
                ]);
            }
            return back()->with('info', __('messages.already_subscribed') ?? 'You are already subscribed!');
        }

        // Store newsletter subscription
        \App\Models\Newsletter::updateOrCreate(
            ['email' => $request->email],
            ['subscribed_at' => now(), 'unsubscribed_at' => null]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.newsletter_success') ?? 'Thank you for subscribing to our newsletter!'
            ]);
        }

        return back()->with('success', __('messages.newsletter_success') ?? 'Thank you for subscribing to our newsletter!');
    }
}
