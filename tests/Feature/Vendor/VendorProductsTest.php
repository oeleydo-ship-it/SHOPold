<?php

/*
 * Vendor portal, part 1: dashboard, products, product form, variations.
 * A vendor only ever sees and changes their own products, and with product approval switched on
 * a vendor cannot publish a product themselves.
 */

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    module('multi_vendor', true);
    $this->vendor = makeVendor();
    $this->other = makeVendor();
    $this->mine = Product::factory()->create(['vendor_id' => $this->vendor->id]);
    $this->theirs = Product::factory()->create(['vendor_id' => $this->other->id]);
    $this->actingAs($this->vendor->user);
});

test('portal pages render as React pages', function (string $uri, string $component) {
    $uri = str_replace('{mine}', (string) $this->mine->id, $uri);
    $this->inertia('GET', $uri)->assertOk()->assertJsonPath('component', $component);
})->with([
    'dashboard' => ['/vendor', 'Vendor/Dashboard'],
    'products' => ['/vendor/products', 'Vendor/Products/Index'],
    'create' => ['/vendor/products/create', 'Vendor/Products/Form'],
    'edit' => ['/vendor/products/{mine}/edit', 'Vendor/Products/Form'],
]);

test('the variation edit page renders', function () {
    $this->mine->update(['product_type' => 'variable']);
    $variation = ProductVariation::factory()->create(['product_id' => $this->mine->id]);

    $this->inertia('GET', "/vendor/products/{$this->mine->id}/variations/{$variation->id}/edit")
        ->assertOk()
        ->assertJsonPath('component', 'Vendor/Products/VariationEdit')
        ->assertJsonPath('props.variation.id', $variation->id);
});

test('the product list only shows the vendor\'s own products', function () {
    $ids = collect($this->inertia('GET', '/vendor/products')->json('props.products.data'))->pluck('id');

    expect($ids->all())->toBe([$this->mine->id]);
});

test('a vendor cannot touch another vendor\'s product', function (string $method, string $suffix) {
    $this->call($method, "/vendor/products/{$this->theirs->id}{$suffix}", ['name' => 'Taken', 'description' => 'x', 'category_id' => $this->theirs->category_id, 'price' => 1, 'sku' => 'NEW-SKU', 'quantity' => 1])
        ->assertForbidden();

    expect($this->theirs->fresh()->name)->not->toBe('Taken');
    expect(Product::find($this->theirs->id))->not->toBeNull();
})->with([
    'edit' => ['GET', '/edit'],
    'update' => ['PUT', ''],
    'delete' => ['DELETE', ''],
    'toggle' => ['PATCH', '/toggle'],
    'add variation' => ['POST', '/variations'],
]);

test('a pending vendor only reaches the dashboard', function () {
    $pending = makeVendor('pending');
    $this->actingAs($pending->user);

    $this->get('/vendor')->assertOk();
    $this->get('/vendor/products')->assertRedirect('/vendor');
});

test('with approval on, a new product starts hidden and the vendor cannot publish it', function () {
    $this->setting('features.vendor_products_approval', true);

    $this->post('/vendor/products', [
        'name' => 'Approval Widget', 'description' => '<p>Nice</p>', 'category_id' => Category::factory()->create()->id,
        'price' => 10, 'quantity' => 3, 'product_type' => 'simple', 'is_featured' => 1,
    ])->assertRedirect('/vendor/products');

    $product = Product::where('name', 'Approval Widget')->firstOrFail();
    expect($product->vendor_id)->toBe($this->vendor->id)
        ->and((bool) $product->is_active)->toBeFalse()
        ->and((bool) $product->is_featured)->toBeFalse();

    $this->patch("/vendor/products/{$product->id}/toggle")->assertSessionHas('error');
    expect((bool) $product->fresh()->is_active)->toBeFalse();

    // Hiding a live product is still allowed.
    $this->patch("/vendor/products/{$this->mine->id}/toggle")->assertSessionHas('success');
    expect((bool) $this->mine->fresh()->is_active)->toBeFalse();
});

test('with approval off, the vendor can publish and hide', function () {
    $this->setting('features.vendor_products_approval', false);
    $this->mine->update(['is_active' => false]);

    $this->patch("/vendor/products/{$this->mine->id}/toggle")->assertSessionHas('success');
    expect((bool) $this->mine->fresh()->is_active)->toBeTrue();
});

test('updating a product cannot feature it or set an unknown type', function () {
    $this->mine->update(['is_featured' => false]);
    $payload = ['name' => 'Renamed', 'description' => '<p>x</p>', 'category_id' => $this->mine->category_id, 'price' => 12, 'sku' => $this->mine->sku, 'quantity' => 4];

    $this->put("/vendor/products/{$this->mine->id}", $payload + ['product_type' => 'bundle'])->assertSessionHasErrors('product_type');

    $this->put("/vendor/products/{$this->mine->id}", $payload + ['is_featured' => 1])->assertSessionHasNoErrors();
    $fresh = $this->mine->fresh();
    expect($fresh->name)->toBe('Renamed')->and((bool) $fresh->is_featured)->toBeFalse();
});

test('a variation update saves price, stock and options', function () {
    $this->mine->update(['product_type' => 'variable']);
    $variation = ProductVariation::factory()->create(['product_id' => $this->mine->id]);

    $this->put("/vendor/products/{$this->mine->id}/variations/{$variation->id}", [
        'sku' => 'VAR-NEW-1', 'price' => '19.50', 'sale_price' => '', 'quantity' => 7, 'weight' => '', 'is_active' => true, 'attribute_values' => [],
    ])->assertRedirect("/vendor/products/{$this->mine->id}/edit")->assertSessionHasNoErrors();

    $fresh = $variation->fresh();
    expect($fresh->sku)->toBe('VAR-NEW-1')->and((float) $fresh->price)->toBe(19.5)->and((int) $fresh->quantity)->toBe(7);
});
