<?php

/*
 * React storefront: home, shop and category pages render as Inertia pages. Shared props carry the store data
 * the header/footer need, product cards load without a query per card, and SEO tags are in the server HTML.
 */

use App\Models\{Category, Product, Review, Setting, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['name' => 'Zz Gadgets', 'slug' => 'zz-gadgets']);
    $this->products = Product::factory()->count(3)->create(['category_id' => $this->category->id, 'is_featured' => true]);
    $this->products[0]->update(['price' => 19.99, 'sale_price' => 14.99]);
    Review::create(['product_id' => $this->products[0]->id, 'user_id' => customer()->id, 'rating' => 4, 'comment' => 'Good', 'is_approved' => true]);
});

test('storefront pages are React pages with the shared store data', function (string $uri, string $component) {
    // a normal visit gets the HTML shell with the page data (before the Inertia headers are set below)
    expect($this->get($uri)->assertOk()->getContent())->toContain('data-page="app"');

    $page = $this->inertia('GET', $uri)->assertOk()->assertJsonPath('component', $component);

    expect($page->json('props.sf.store.name'))->not->toBeEmpty()
        ->and($page->json('props.sf.lang.shop'))->not->toBeEmpty()
        ->and($page->json('props.sf.cart.count'))->toBe(0);

})->with([
    'home' => ['/', 'Storefront/Home'],
    'shop' => ['/shop', 'Storefront/Shop'],
    'search' => ['/shop?search=zz', 'Storefront/Shop'],
    'category' => ['/shop/category/zz-gadgets', 'Storefront/Category'],
]);

test('product cards carry prices, sale, rating and stock without a query per product', function () {
    Product::factory()->count(20)->create(['category_id' => $this->category->id]);

    DB::enableQueryLog();
    $cards = $this->inertia('GET', '/shop?per_page=24')->assertOk()->json('props.products.data');
    $queries = count(DB::getQueryLog());

    $sale = collect($cards)->firstWhere('id', $this->products[0]->id);
    expect($sale['price'])->toBe(format_currency(14.99))
        ->and($sale['regular'])->toBe(format_currency(19.99))
        ->and($sale['discount'])->toBe(25)
        ->and($sale['reviews'])->toBe(1)
        ->and((float) $sale['rating'])->toBe(4.0)
        ->and($sale['inStock'])->toBeTrue()
        ->and($sale['category']['slug'])->toBe('zz-gadgets')
        ->and(count($cards))->toBe(23)
        ->and($queries)->toBeLessThan(30); // the Blade grid needed several queries per card
});

test('filters and sorting work on the React shop', function () {
    $other = Category::factory()->create(['slug' => 'zz-other']);
    Product::factory()->create(['category_id' => $other->id, 'name' => 'Zz Other Thing']);

    $ids = collect($this->inertia('GET', '/shop?category=zz-gadgets&sort=price_low')->json('props.products.data'))->pluck('id');
    expect($ids->sort()->values()->all())->toBe($this->products->pluck('id')->sort()->values()->all());
    expect($this->inertia('GET', '/shop?on_sale=1')->json('props.products.data.0.id'))->toBe($this->products[0]->id);
});

test('the home page sends its sections in the configured order', function () {
    $this->setting('homepage.section_order', 'featured,hero,bogus', 'homepage');

    $page = $this->inertia('GET', '/')->assertOk();
    expect(array_slice($page->json('props.sections'), 0, 2))->toBe(['featured', 'hero'])
        ->and($page->json('props.sections'))->not->toContain('bogus')
        ->and(collect($page->json('props.featured'))->pluck('id')->all())->toContain($this->products[0]->id);
});

test('SEO tags are in the server HTML without server-side rendering', function () {
    $this->category->update(['description' => 'Zz gadget description']);

    $html = $this->get('/shop/category/zz-gadgets')->assertOk()->getContent();
    expect($html)->toContain('<title data-inertia>Zz Gadgets - ')
        ->toContain('content="Zz gadget description"')
        ->toContain('rel="canonical" href="' . route('shop.category', 'zz-gadgets') . '"')
        ->toContain('--sf-primary-600:');
});

test('wishlist and cart answer JSON for the React cards', function () {
    $user = customer();
    module('wishlist', true);
    $this->actingAs($user)->postJson('/account/wishlist', ['product_id' => $this->products[1]->id])
        ->assertOk()->assertJson(['success' => true]);
    $this->postJson('/account/wishlist', ['product_id' => $this->products[1]->id])->assertJsonPath('message', 'Product is already in your wishlist.');

    $this->postJson('/cart/add', ['product_id' => $this->products[1]->id], ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()->assertJsonPath('cart_count', 1);
});

test('admin, vendor and driver pages keep their own app and never use SSR', function () {
    $this->actingAs(adminUser('super_admin'));
    $html = $this->get('/admin')->assertOk()->getContent();
    expect($html)->not->toContain('storefront.jsx')->toContain('resources/js/app.jsx');
});
