<?php

/*
 * React storefront S3: the product page (gallery, variations, reviews, related products, structured data inputs).
 */

use App\Models\{Attribute, AttributeValue, Category, Product, ProductVariation, Review};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['slug' => 'zz-cat']);
    $this->product = Product::factory()->create([
        'category_id' => $this->category->id, 'price' => 30, 'sale_price' => 24,
        'description' => '<p>Nice</p><script>alert(1)</script>', 'features' => [['name' => 'Material', 'value' => 'Cotton']],
    ]);
});

test('the product page renders as a React page with sanitised content', function () {
    $related = Product::factory()->create(['category_id' => $this->category->id]);

    $page = $this->inertia('GET', "/shop/{$this->product->slug}")->assertOk()->assertJsonPath('component', 'Storefront/Product');
    $p = $page->json('props.product');

    expect($p['price'])->toBe(format_currency(24))
        ->and($p['regular'])->toBe(format_currency(30))
        ->and($p['priceValue'])->toEqual(24)
        ->and($p['description'])->toContain('<p>Nice</p>')->not->toContain('<script')
        ->and($p['features'])->toBe([['name' => 'Material', 'value' => 'Cotton']])
        ->and(collect($page->json('props.related'))->pluck('id')->all())->toBe([$related->id])
        ->and($page->json('props.seo.canonical'))->toBe(route('shop.show', $this->product->slug));
});

test('variable products send their options and variations', function () {
    $size = Attribute::factory()->create(['name' => 'Size']);
    $s = AttributeValue::factory()->create(['attribute_id' => $size->id, 'value' => 'S']);
    $m = AttributeValue::factory()->create(['attribute_id' => $size->id, 'value' => 'M']);
    $this->product->update(['product_type' => 'variable']);
    foreach ([[$s, 5], [$m, 0]] as [$value, $qty]) {
        $v = ProductVariation::factory()->create(['product_id' => $this->product->id, 'quantity' => $qty, 'price' => 40, 'is_active' => true]);
        $v->attributeValues()->attach($value->id, ['attribute_id' => $size->id]);
    }

    $p = $this->inertia('GET', "/shop/{$this->product->slug}")->json('props.product');
    expect($p['type'])->toBe('variable')
        ->and($p['attributes'][0]['name'])->toBe('Size')
        ->and(collect($p['attributes'][0]['values'])->pluck('label')->all())->toBe(['S', 'M'])
        ->and(collect($p['variations'])->pluck('quantity')->all())->toBe([5, 0])
        ->and($p['variations'][0]['price'])->toBe(format_currency(40));
});

test('only approved reviews show, and customers can post one', function () {
    module('reviews', true);
    Review::create(['product_id' => $this->product->id, 'user_id' => customer()->id, 'rating' => 5, 'comment' => 'Approved one', 'is_approved' => true]);
    Review::create(['product_id' => $this->product->id, 'user_id' => customer()->id, 'rating' => 1, 'comment' => 'Waiting one', 'is_approved' => false]);

    $reviews = $this->inertia('GET', "/shop/{$this->product->slug}")->json('props.reviews.data');
    expect(collect($reviews)->pluck('comment')->all())->toBe(['Approved one']);

    $this->actingAs(customer())->post("/shop/{$this->product->id}/review", ['rating' => 4, 'comment' => 'Lovely shirt, fits well'])->assertSessionHas('success');
    expect(Review::where('comment', 'Lovely shirt, fits well')->value('is_approved'))->toBeFalsy();
});

test('buy now still goes to checkout', function () {
    $this->post('/cart/buy-now', ['product_id' => $this->product->id, 'quantity' => 2])->assertRedirect(route('checkout.index'));
});
