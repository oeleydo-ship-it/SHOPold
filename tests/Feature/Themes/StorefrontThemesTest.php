<?php

/*
 * Every storefront page renders as a React page under every theme. The page carries the active theme's slug
 * (StoreLayout puts "sf-theme-<slug>" on the page; resources/css/storefront-themes.css styles each theme).
 */

use App\Models\{BlogPost, Category, HelpdeskTicket, Page, Product, Vendor};
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    module('blog', true);
    module('multi_vendor', true);
    $this->category = Category::factory()->create();
    $this->product = Product::factory()->create(['category_id' => $this->category->id]);
    $this->post = BlogPost::factory()->create();
    $this->vendor = Vendor::create(['user_id' => customer()->id, 'store_name' => 'Zz Seller', 'slug' => 'zz-seller', 'email' => 'seller@example.test', 'status' => 'approved',
        'description' => 'Hand-made things', 'social_links' => ['facebook' => 'https://facebook.com/zz', 'twitter' => 'javascript:alert(1)']]);
    Product::factory()->create(['vendor_id' => $this->vendor->id, 'category_id' => $this->category->id]);
    Page::create(['title' => 'About us', 'slug' => 'about-us', 'content' => '<p>About</p>', 'template' => 'default', 'is_active' => true]);
    Page::create(['title' => 'Contact us', 'slug' => 'contact-us', 'content' => '<p>Contact</p>', 'template' => 'full-width', 'is_active' => true]);
});

dataset('pages', [
    'home' => ['/', 'Storefront/Home', null],
    'shop' => ['/shop', 'Storefront/Shop', null],
    'category' => ['category', 'Storefront/Category', null],
    'product' => ['product', 'Storefront/Product', null],
    'cart' => ['/cart', 'Storefront/Cart', 'customer'],
    'checkout' => ['/checkout', 'Storefront/Checkout', 'customer'],
    'account' => ['/account', 'Storefront/Account/Dashboard', 'customer'],
    'login' => ['/login', 'Storefront/Auth/Login', null],
    'blog' => ['/blog', 'Storefront/Blog/Index', null],
    'blog post' => ['post', 'Storefront/Blog/Show', null],
    'about' => ['/about', 'Storefront/Page', null],
    'contact' => ['/contact', 'Storefront/Page', null],
    'vendor store' => ['/shop/vendor/zz-seller', 'Storefront/Vendor', null],
    'helpdesk' => ['/helpdesk', 'Storefront/Helpdesk/Index', 'customer'],
    'new ticket' => ['/helpdesk/create', 'Storefront/Helpdesk/Create', 'customer'],
    'ticket' => ['ticket', 'Storefront/Helpdesk/Show', 'customer'],
]);

test('renders under every theme', function (string $uri, string $component, ?string $as, string $theme) {
    app(ThemeService::class)->setActive($theme);
    $uri = match ($uri) {
        'category' => "/shop/category/{$this->category->slug}",
        'product' => "/shop/{$this->product->slug}",
        'post' => "/blog/{$this->post->slug}",
        default => $uri,
    };
    if ($as === 'customer') {
        $this->actingAs($user = customer());
        if ($uri === 'ticket') {
            $ticket = HelpdeskTicket::create(['user_id' => $user->id, 'subject' => 'Zz broken mug', 'description' => 'It arrived cracked', 'priority' => 'medium', 'status' => 'open', 'category' => 'general']);
            $uri = "/helpdesk/{$ticket->id}";
        }
        if ($uri === '/checkout' || $uri === '/cart') {
            $this->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);
        }
    }

    // the HTML shell (theme colours, fonts) ...
    expect($this->get($uri)->assertOk()->getContent())->toContain('data-page="app"')->toContain('--sf-primary-500');
    // ... and the page itself, which knows its theme
    $this->inertia('GET', $uri)->assertOk()
        ->assertJsonPath('component', $component)
        ->assertJsonPath('props.sf.theme.slug', $theme);
})->with('pages')->with(['default', 'modern', 'starter']);

test('every bundled theme has a skin in the storefront stylesheet', function () {
    $css = file_get_contents(resource_path('css/storefront-themes.css'));
    expect($css)->toContain('.sf-theme-modern .sf-card')->toContain('.sf-theme-starter .sf-card')
        ->and(file_get_contents(resource_path('css/storefront.css')))->toContain('@import "./storefront-themes.css"');
});

test('the vendor store page shows the store and only safe social links', function () {
    $vendor = $this->inertia('GET', '/shop/vendor/zz-seller')->assertOk()->json('props.vendor');

    expect($vendor['name'])->toBe('Zz Seller')
        ->and($vendor['description'])->toBe('Hand-made things')
        ->and($vendor['social'])->toBe(['facebook' => 'https://facebook.com/zz']);
});
