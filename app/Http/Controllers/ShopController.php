<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Tag;
use App\Models\Review;
use App\Services\AiFeatureService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        // ProductCards::with adds what the cards need (ratings, stock, translations) without a query per card.
        $query = \App\Support\ProductCards::with(Product::active()->inStock()->with(['category', 'brand', 'tags', 'translations']));

        // Category filter
        if ($request->category) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        // Brand filter
        if ($request->brand) {
            $brand = Brand::where('slug', $request->brand)->first();
            if ($brand) {
                $query->where('brand_id', $brand->id);
            }
        }

        // Tag filter
        if ($request->tag) {
            $tag = Tag::where('slug', $request->tag)->first();
            if ($tag) {
                $query->whereHas('tags', function ($q) use ($tag) {
                    $q->where('tags.id', $tag->id);
                });
            }
        }

        // Search filter - use AI smart search if enabled
        $aiSearchUsed = false;
        if ($request->search) {
            $search = $request->search;
            
            // Try AI-powered smart search if enabled
            if (setting('ai.smart_search') && setting('ai.api_key')) {
                try {
                    $aiService = app(AiFeatureService::class);
                    $smartResult = $aiService->smartSearch($search, 100);
                    
                    if ($smartResult['ai_enhanced'] && $smartResult['products']->isNotEmpty()) {
                        // Use AI-analyzed search results
                        $productIds = $smartResult['products']->pluck('id')->toArray();
                        $query->whereIn('id', $productIds);
                        $aiSearchUsed = true;
                    }
                } catch (\Exception $e) {
                    // Fall back to regular search on error
                }
            }
            
            // Fallback to regular search
            if (!$aiSearchUsed) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }
        }

        // Price filter
        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        // On Sale filter
        if ($request->on_sale) {
            $query->onSale();
        }

        // Sort
        switch ($request->sort) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Products per page - from request or settings
        $defaultPerPage = (int) setting('shop.products_per_page', 12);
        $perPage = $request->input('per_page', $defaultPerPage);
        $perPage = in_array($perPage, [12, 24, 36, 48]) ? $perPage : $defaultPerPage;
        
        // Grid columns from request or settings
        $defaultColumns = setting('shop.grid_columns', '3');
        $gridColumns = $request->input('columns', $defaultColumns);

        $products = $query->paginate($perPage)->withQueryString();
        $categories = Category::active()->withCount('products')->with('translations')->get();
        $brands = Brand::active()->withCount('products')->with('translations')->orderBy('name')->get();
        $tags = Tag::withCount('products')->orderBy('name')->get();

        $data = compact('products', 'categories', 'brands', 'tags', 'perPage', 'gridColumns', 'aiSearchUsed');

        return \Inertia\Inertia::render('Storefront/Shop', [
            'products' => $products->through(fn ($p) => \App\Support\ProductCards::card($p)),
            'categories' => $this->categoryList($categories),
            'filters' => [
                'search' => (string) $request->query('search', ''),
                'category' => (string) $request->query('category', ''),
                'brand' => (string) $request->query('brand', ''),
                'tag' => (string) $request->query('tag', ''),
                'min_price' => (string) $request->query('min_price', ''),
                'max_price' => (string) $request->query('max_price', ''),
                'on_sale' => (bool) $request->query('on_sale'),
                'sort' => (string) $request->query('sort', 'newest'),
            ],
            'priceLabel' => ($request->min_price || $request->max_price)
                ? ($request->min_price ? format_currency((float) $request->min_price) : '0') . ' - ' . ($request->max_price ? format_currency((float) $request->max_price) : '∞')
                : null,
            'perPage' => (int) $perPage,
            'gridColumns' => in_array((string) $gridColumns, ['2', '3', '4'], true) ? (string) $gridColumns : '4',
            'aiSearchUsed' => $aiSearchUsed,
            'options' => [
                'showPerPage' => (bool) setting('shop.show_per_page', true),
                'showGridOptions' => (bool) setting('shop.show_grid_options', true),
                'descDesktop' => (bool) setting('shop.show_description_desktop', true),
                'descMobile' => (bool) setting('shop.show_description_mobile', true),
            ],
            'seo' => [
                'title' => $request->search ? __('messages.search') . ': ' . $request->search : __('messages.shop'),
                'canonical' => route('shop.index', array_filter($request->only('category', 'brand', 'tag'))),
            ],
        ]);
    }

    /** Category list for filters and pills (name, slug, product count). */
    protected function categoryList($categories): array
    {
        return $categories->map(fn ($c) => [
            'id' => $c->id, 'name' => $c->translated_name, 'slug' => $c->slug, 'count' => (int) ($c->products_count ?? 0),
        ])->values()->all();
    }

    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->active()
            ->with(['brand', 'tags', 'category', 'variations.attributeValues.attribute', 'translations'])
            ->firstOrFail();
        $product->increment('views');

        $relatedProducts = Product::active()->inStock()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        $reviews = $product->approvedReviews()->with('user')->latest()->paginate(5);

        // AI-powered product recommendations
        $aiRecommendations = collect([]);
        $aiReviewAnalysis = null;
        
        if (setting('ai.recommendations') && setting('ai.api_key')) {
            try {
                $aiService = app(AiFeatureService::class);
                $recResult = $aiService->getRecommendations(auth()->id(), $product->id, 4);
                if ($recResult['success']) {
                    $aiRecommendations = $recResult['products'];
                }
            } catch (\Exception $e) {
                // Silently fail - recommendations are optional
            }
        }

        // AI-powered review analysis
        if (setting('ai.review_analysis') && setting('ai.api_key') && $reviews->total() > 0) {
            try {
                $aiService = app(AiFeatureService::class);
                $analysisResult = $aiService->analyzeProductReviews($product->id);
                if ($analysisResult['success']) {
                    $aiReviewAnalysis = $analysisResult['analysis'];
                }
            } catch (\Exception $e) {
                // Silently fail - analysis is optional
            }
        }

        // Cross-sell products - only load if cross-selling is enabled
        $crossSellProducts = collect([]);
        $crossSellEnabled = setting('shop.cross_sell_enabled', false);
        $crossSellTitle = setting('shop.cross_sell_title', 'Frequently Bought Together');
        
        if ($crossSellEnabled && !empty($product->cross_sell_products)) {
            $crossSellLimit = (int) setting('shop.cross_sell_limit', 4);
            $crossSellProducts = Product::whereIn('id', $product->cross_sell_products)
                ->active()
                ->take($crossSellLimit)
                ->get();
        }

        $data = compact('product', 'relatedProducts', 'reviews', 'crossSellProducts', 'crossSellEnabled', 'crossSellTitle', 'aiRecommendations', 'aiReviewAnalysis');

        return \Inertia\Inertia::render('Storefront/Product', \App\Support\ProductPage::props($data));
    }

    public function category($slug)
    {
        $category = Category::where('slug', $slug)->active()->with('translations')->firstOrFail();
        $products = \App\Support\ProductCards::with(Product::active()->inStock()->where('category_id', $category->id))
            ->paginate(12);
        $categories = Category::active()->withCount('products')->with('translations')->get();

        return \Inertia\Inertia::render('Storefront/Category', [
            'category' => [
                'id' => $category->id,
                'name' => $category->translated_name,
                'slug' => $category->slug,
                'description' => $category->translated_description,
                'image' => $category->image ? asset('storage/' . $category->image) : null,
            ],
            'products' => $products->through(fn ($p) => \App\Support\ProductCards::card($p)),
            'categories' => $this->categoryList($categories),
            'seo' => [
                'title' => $category->translated_name,
                'description' => \Illuminate\Support\Str::limit(strip_tags((string) $category->translated_description), 155),
                'image' => $category->image ? asset('storage/' . $category->image) : null,
                'canonical' => route('shop.category', $category->slug),
            ],
        ]);
    }

    public function vendor($slug)
    {
        $vendor = \App\Models\Vendor::where('slug', $slug)
            ->where('status', 'approved')
            ->firstOrFail();
        
        $cards = \App\Support\ProductCards::with(Product::active()->inStock()->where('vendor_id', $vendor->id))->paginate(12);

        return \Inertia\Inertia::render('Storefront/Vendor', [
            'vendor' => [
                'name' => $vendor->store_name,
                'description' => \Illuminate\Support\Str::limit((string) $vendor->description, 200),
                'logo' => $vendor->logo_url,
                'banner' => $vendor->banner ? asset('storage/' . $vendor->banner) : null,
                'rating' => round((float) $vendor->average_rating, 1),
                'ratingCount' => (int) $vendor->rating_count,
                'location' => collect([$vendor->city, $vendor->country])->filter()->implode(', '),
                // only real web links
                'social' => collect(['facebook', 'twitter', 'instagram'])
                    ->mapWithKeys(fn ($k) => [$k => trim((string) ($vendor->social_links[$k] ?? ''))])
                    ->filter(fn ($url) => preg_match('#^https?://#i', $url)),
            ],
            'products' => $cards->through(fn ($p) => \App\Support\ProductCards::card($p)),
            'seo' => [
                'title' => $vendor->store_name,
                'description' => \Illuminate\Support\Str::limit((string) $vendor->description, 155),
                'image' => $vendor->logo_url,
                'canonical' => route('shop.vendor', $vendor->slug),
            ],
        ]);
    }

    public function storeReview(Request $request, Product $product)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string|min:10',
        ]);

        $product->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Thank you for your review! It will be published after moderation.');
    }
}
