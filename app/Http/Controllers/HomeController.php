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
        
        $featuredProducts = Product::active()->featured()->inStock()->with('category')->take($featuredCount)->get();
        $newArrivals = Product::active()->inStock()->with('category')->latest()->take($arrivalsCount)->get();
        $categories = Category::active()->parentCategories()->withCount('products')->with('translations')->get();
        $bestSellers = Product::active()->inStock()->with('category')
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->take($featuredCount)
            ->get();
        
        // Super Flash Deals - Products with highest discounts
        $flashDeals = Product::active()->inStock()->with('category')
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->orderByRaw('((price - sale_price) / price) DESC')
            ->take($flashDealsCount)
            ->get();
        
        // Top Rated Products - Products with highest average rating using subqueries
        $topRatedProducts = Product::active()->inStock()->with('category')
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
                $showcaseProducts = Product::active()->featured()->inStock()->with('category')->take($showcaseCount)->get();
                break;
            case 'new_arrivals':
                $showcaseProducts = Product::active()->inStock()->with('category')->latest()->take($showcaseCount)->get();
                break;
            case 'best_sellers':
                $showcaseProducts = Product::active()->inStock()->with('category')
                    ->withCount('orderItems')
                    ->orderByDesc('order_items_count')
                    ->take($showcaseCount)
                    ->get();
                break;
            case 'on_sale':
                $showcaseProducts = Product::active()->inStock()->onSale()->with('category')
                    ->orderByRaw('((price - sale_price) / price) DESC')
                    ->take($showcaseCount)
                    ->get();
                break;
            case 'random':
                $showcaseProducts = Product::active()->inStock()->with('category')->inRandomOrder()->take($showcaseCount)->get();
                break;
            default:
                $showcaseProducts = Product::active()->featured()->inStock()->with('category')->take($showcaseCount)->get();
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

        return view('home', compact(
            'featuredProducts', 
            'newArrivals', 
            'categories', 
            'bestSellers',
            'flashDeals',
            'topRatedProducts',
            'showcaseProducts',
            'blogPosts'
        ));
    }

    public function about()
    {
        return view('pages.about');
    }

    public function contact()
    {
        return view('pages.contact');
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
