<?php

/*
 * Phase 7: the invoice screen and the variation edit screen are React pages.
 */

use App\Models\{Order, OrderItem, Product, ProductVariation};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('the invoice screen is a React page with the store currency', function () {
    $product = Product::factory()->create();
    $order = Order::create([
        'order_number' => 'ORD-' . Str::upper(Str::random(8)), 'user_id' => customer()->id,
        'subtotal' => 40, 'shipping_cost' => 5, 'tax' => 0, 'discount' => 3, 'total' => 42, 'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'stripe',
        'shipping_name' => 'Test Buyer', 'shipping_email' => 'buyer@example.test', 'shipping_address' => '1 Test Street',
        'shipping_city' => 'Testville', 'shipping_zip' => '00000', 'shipping_country' => 'Testland',
    ]);
    OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => 20, 'quantity' => 2, 'total' => 40]);

    $this->actingAs(adminUser('manager'));
    $page = $this->inertia('GET', "/admin/invoices/{$order->id}")->assertOk()->assertJsonPath('component', 'Admin/Invoices/Show');

    expect($page->json('props.invoice.total'))->toBe(format_currency(42))
        ->and($page->json('props.invoice.discount'))->toBe(format_currency(3))
        ->and($page->json('props.invoice.items.0.sku'))->toBe($product->sku);

    $this->actingAs(adminUser('editor'))->get("/admin/invoices/{$order->id}")->assertForbidden();
});

describe('variation edit', function () {
    beforeEach(function () {
        $this->product = Product::factory()->variable()->create();
        $this->variation = ProductVariation::factory()->create(['product_id' => $this->product->id]);
        $this->url = "/admin/products/{$this->product->id}/variations/{$this->variation->id}";
        $this->actingAs(adminUser('super_admin'));
    });

    test('renders as a React page', function () {
        $this->inertia('GET', "{$this->url}/edit")->assertOk()
            ->assertJsonPath('component', 'Admin/Products/VariationEdit')
            ->assertJsonPath('props.variation.id', $this->variation->id);
    });

    test('saves without a weight and with a media-library image', function () {
        $this->put($this->url, ['sku' => 'VAR-EDIT-1', 'price' => '9.5', 'quantity' => 3, 'is_active' => true, 'image' => 'media/products/shirt.jpg', 'attribute_values' => []])
            ->assertSessionHasNoErrors()->assertRedirect("/admin/products/{$this->product->id}/edit");

        $fresh = $this->variation->fresh();
        expect($fresh->sku)->toBe('VAR-EDIT-1')->and($fresh->image)->toBe('media/products/shirt.jpg');

        $this->put($this->url, ['sku' => 'VAR-EDIT-1', 'quantity' => 3, 'remove_image' => true])->assertSessionHasNoErrors();
        expect($this->variation->fresh()->image)->toBeNull();
    });

    test('rejects image paths outside the media library', function (string $path) {
        $this->put($this->url, ['sku' => 'VAR-EDIT-2', 'quantity' => 1, 'image' => $path])->assertSessionHasErrors('image');
    })->with(['../../.env', '/etc/passwd', 'https://evil.example/x.png', 'javascript:alert(1)']);
});
