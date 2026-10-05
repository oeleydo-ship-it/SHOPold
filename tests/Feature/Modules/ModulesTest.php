<?php

/*
 * Admin > Modules: switching a module off closes its storefront pages/endpoints (404) and hides its UI.
 */

use App\Models\{BlogPost, Order, Product, Vendor};
use App\Support\Modules;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (array_keys(Modules::all()) as $key) {
        module($key, true);
    }
    $this->product = Product::factory()->create();
    $this->customer = customer();
});

test('only super admins can toggle modules', function () {
    $this->actingAs(adminUser('manager'));
    $this->inertia('POST', '/admin/modules/blog/toggle', ['enabled' => false])->assertForbidden();
    expect(Modules::enabled('blog'))->toBeTrue();

    $this->actingAs(adminUser('super_admin'));
    $this->inertia('POST', '/admin/modules/blog/toggle', ['enabled' => false]);
    expect(Modules::enabled('blog'))->toBeFalse();
    $this->inertia('POST', '/admin/modules/nope/toggle', ['enabled' => true])->assertNotFound();
});

test('module options are clamped and unknown keys ignored', function () {
    $this->actingAs(adminUser('super_admin'));
    $this->inertia('PUT', '/admin/modules/loyalty/options', ['values' => ['features.points_expiry' => 99999, 'features.min_points_redeem' => 250, 'features.evil' => 1]]);

    expect((int) setting('features.points_expiry'))->toBe(3650)
        ->and((int) setting('features.min_points_redeem'))->toBe(250)
        ->and(setting('features.evil'))->toBeNull();
});

dataset('gated endpoints', [
    'wishlist page' => ['wishlist', 'GET', '/account/wishlist', true],
    'add to wishlist' => ['wishlist', 'POST', '/account/wishlist', true],
    'review' => ['reviews', 'POST', 'review', true],
    'newsletter' => ['newsletter', 'POST', '/newsletter/subscribe', false],
    'AI chat API' => ['ai_chat', 'GET', '/api/ai-chat/history', false],
    'vendor store page' => ['multi_vendor', 'GET', 'vendor-store', false],
    'vendor portal' => ['multi_vendor', 'GET', '/vendor', true],
    'driver portal' => ['driver_portal', 'GET', '/driver', true],
    'blog' => ['blog', 'GET', '/blog', false],
]);

test('endpoints of a switched-off module return 404', function (string $module, string $method, string $uri, bool $signedIn) {
    if ($uri === 'review') $uri = "/shop/{$this->product->id}/review";
    if ($uri === 'vendor-store') {
        $vendor = Vendor::create(['user_id' => customer()->id, 'store_name' => 'Zz Store', 'slug' => 'zz-store', 'email' => 'store@example.test', 'status' => 'approved']);
        $uri = "/shop/vendor/{$vendor->slug}";
    }
    if ($signedIn) $this->actingAs($this->customer);

    module($module, false);
    $this->call($method, $uri, ['product_id' => $this->product->id, 'email' => 'x@example.test', 'rating' => 5, 'comment' => 'x'])->assertNotFound();

    module($module, true);
    expect($this->call($method, $uri, ['product_id' => $this->product->id, 'email' => 'x@example.test', 'rating' => 5, 'comment' => 'x'])->status())->not->toBe(404);
})->with('gated endpoints');

test('switched-off modules disappear from the storefront', function () {
    $this->actingAs($this->customer);
    // the React pages show wishlist buttons, the review form and newsletter forms from these flags
    $page = fn () => $this->inertia('GET', "/shop/{$this->product->slug}")->assertOk();

    expect($page()->json('props.sf.features.wishlist'))->toBeTrue()->and($page()->json('props.options.reviewsEnabled'))->toBeTrue();
    module('wishlist', false);
    module('reviews', false);
    expect($page()->json('props.sf.features.wishlist'))->toBeFalse()->and($page()->json('props.options.reviewsEnabled'))->toBeFalse();

    module('newsletter', true);
    expect($this->inertia('GET', '/')->json('props.sf.features.newsletter'))->toBeTrue();
    module('newsletter', false);
    expect($this->inertia('GET', '/')->json('props.sf.features.newsletter'))->toBeFalse();
});
