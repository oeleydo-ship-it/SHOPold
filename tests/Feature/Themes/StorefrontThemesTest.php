<?php

/*
 * Every storefront page renders under every theme with that theme's own layout, and only that theme's.
 * Markers are class names / CSS that exist only in one theme's templates.
 */

use App\Models\{BlogPost, Category, HelpdeskTicket, Page, Product, Setting, Vendor};
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const THEME_MARKERS = [
    'modern' => ['product-page--modern', 'shop-page--modern', 'category-page--modern', 'cart-page--modern', 'checkout-page--modern', 'account-page--modern', 'auth-page--modern', 'blog-page--modern', 'blog-post--modern', 'vendor-page--modern'],
    'starter' => ['product-page--starter', 'shop-page--starter', 'category-page--starter', 'cart-page--starter', 'checkout-page--starter', 'account-page--starter', 'auth-page--starter', 'blog-page--starter', 'blog-post--starter', 'vendor-page--starter'],
];

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
    'home' => ['/', null, null],
    'shop' => ['/shop', 'shop-page', null],
    'category' => ['category', 'category-page', null],
    'product' => ['product', 'product-page', null],
    'cart' => ['/cart', 'cart-page', 'customer'],
    'checkout' => ['/checkout', 'checkout-page', 'customer'],
    'account' => ['/account', 'account-page', 'customer'],
    'login' => ['/login', 'auth-page', null],
    'blog' => ['/blog', 'blog-page', null],
    'blog post' => ['post', 'blog-post', null],
    'about' => ['/about', null, null],
    'contact' => ['/contact', null, null],
    'vendor store' => ['/shop/vendor/zz-seller', 'vendor-page', null],
    'helpdesk' => ['/helpdesk', 'account-page', 'customer'],
    'new ticket' => ['/helpdesk/create', 'account-page', 'customer'],
    'ticket' => ['ticket', 'account-page', 'customer'],
]);

test('renders under every theme with its own layout only', function (string $uri, ?string $marker, ?string $as, string $theme) {
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

    $html = $this->get($uri)->assertOk()->getContent();

    expect(substr_count($html, 'x-data="{ mobileMenu: false'))->toBe(1) // exactly one site header
        ->and($html)->toContain('store-footer');
    if ($marker && $theme !== 'default') {
        expect($html)->toContain("$marker--$theme");
    }
    foreach (THEME_MARKERS as $other => $markers) {
        if ($other === $theme) continue;
        foreach ($markers as $m) {
            expect($html)->not->toContain($m);
        }
    }
})->with('pages')->with(['default', 'modern', 'starter']);

test('the product card is themed on listing pages', function () {
    app(ThemeService::class)->setActive('modern');
    $this->get('/shop')->assertOk()->assertSee('rounded-3xl shadow-sm hover:shadow-xl', false);

    app(ThemeService::class)->setActive('starter');
    Setting::overlay([]);
    $this->get('/shop')->assertOk()->assertSee('rounded-none transition-colors duration-200', false);
});

test('the vendor store page shows the store and only safe social links', function (string $theme) {
    app(ThemeService::class)->setActive($theme);
    $this->get('/shop/vendor/zz-seller')->assertOk()
        ->assertSee('Zz Seller')->assertSee('Hand-made things')
        ->assertSee('https://facebook.com/zz', false)
        ->assertDontSee('javascript:alert(1)', false);
})->with(['default', 'modern', 'starter']);
