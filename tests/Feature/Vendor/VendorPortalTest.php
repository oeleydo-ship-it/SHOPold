<?php

/*
 * Vendor portal, part 2: orders, stock, payouts, store settings, payout details and registration.
 */

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorPayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function vendorOrder(Product $product, array $order = []): OrderItem
{
    $o = Order::create($order + [
        'order_number' => 'ORD-' . Str::upper(Str::random(8)), 'user_id' => customer()->id,
        'subtotal' => 100, 'total' => 100, 'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'stripe',
        'shipping_name' => 'Test Buyer', 'shipping_email' => 'buyer@example.test', 'shipping_address' => '1 Test Street',
        'shipping_city' => 'Testville', 'shipping_zip' => '00000', 'shipping_country' => 'Testland',
    ]);

    return OrderItem::create(['order_id' => $o->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => 50, 'quantity' => 2, 'total' => 100, 'fulfillment_status' => 'pending']);
}

beforeEach(function () {
    module('multi_vendor', true);
    $this->vendor = makeVendor();
    $this->other = makeVendor();
    $this->mine = Product::factory()->create(['vendor_id' => $this->vendor->id]);
    $this->theirs = Product::factory()->create(['vendor_id' => $this->other->id]);
    $this->actingAs($this->vendor->user);
});

test('portal pages render as React pages', function (string $uri, string $component) {
    $item = vendorOrder($this->mine);
    $uri = strtr($uri, ['{order}' => $item->order_id, '{mine}' => $this->mine->id]);

    $this->inertia('GET', $uri)->assertOk()->assertJsonPath('component', $component);
})->with([
    'orders' => ['/vendor/orders', 'Vendor/Orders/Index'],
    'order' => ['/vendor/orders/{order}', 'Vendor/Orders/Show'],
    'stock' => ['/vendor/stock', 'Vendor/Stock/Index'],
    'stock history' => ['/vendor/stock/history/{mine}', 'Vendor/Stock/History'],
    'payouts' => ['/vendor/payouts', 'Vendor/Payouts'],
    'store settings' => ['/vendor/settings', 'Vendor/Settings/Store'],
    'payout details' => ['/vendor/payment-settings', 'Vendor/PaymentSettings'],
]);

test('orders list only the vendor\'s own items and totals', function () {
    $mine = vendorOrder($this->mine);
    $theirs = vendorOrder($this->theirs);
    OrderItem::create(['order_id' => $mine->order_id, 'product_id' => $this->theirs->id, 'product_name' => 'Other', 'price' => 999, 'quantity' => 1, 'total' => 999]);

    $orders = $this->inertia('GET', '/vendor/orders')->json('props.orders.data');
    expect(collect($orders)->pluck('id')->all())->toBe([$mine->order_id])
        ->and($orders[0]['items'])->toBe(1);

    $items = $this->inertia('GET', "/vendor/orders/{$mine->order_id}")->json('props.order.items');
    expect(collect($items)->pluck('id')->all())->toBe([$mine->id]);

    $this->get("/vendor/orders/{$theirs->order_id}")->assertNotFound();
});

test('a vendor cannot fulfil another vendor\'s items', function () {
    $theirs = vendorOrder($this->theirs);

    $this->patch("/vendor/orders/items/{$theirs->id}/status", ['fulfillment_status' => 'shipped'])->assertForbidden();
    $this->post("/vendor/orders/{$theirs->order_id}/bulk-status", ['fulfillment_status' => 'shipped'])->assertForbidden();
    expect($theirs->fresh()->fulfillment_status)->toBe('pending');
});

test('items of an unpaid order cannot be fulfilled', function () {
    $item = vendorOrder($this->mine, ['payment_status' => 'pending', 'payment_method' => 'stripe']);

    $this->patch("/vendor/orders/items/{$item->id}/status", ['fulfillment_status' => 'shipped'])->assertSessionHas('error');
    $this->post("/vendor/orders/{$item->order_id}/bulk-status", ['fulfillment_status' => 'shipped'])->assertSessionHas('error');
    expect($item->fresh()->fulfillment_status)->toBe('pending');
});

test('a paid item can be shipped with tracking', function () {
    $item = vendorOrder($this->mine);

    $this->patch("/vendor/orders/items/{$item->id}/status", ['fulfillment_status' => 'shipped', 'tracking_number' => 'TRK1', 'shipping_carrier' => 'Post'])
        ->assertSessionHas('success');

    $fresh = $item->fresh();
    expect($fresh->fulfillment_status)->toBe('shipped')->and($fresh->tracking_number)->toBe('TRK1')->and($fresh->shipped_at)->not->toBeNull()
        ->and($fresh->order->status)->toBe('shipped');
});

test('stock can only be changed on the vendor\'s own products', function () {
    $this->post('/vendor/stock/update', ['item_id' => $this->theirs->id, 'item_type' => 'product', 'quantity' => 1])->assertForbidden();
    $this->get("/vendor/stock/history/{$this->theirs->id}")->assertForbidden();

    $this->post('/vendor/stock/update', ['item_id' => $this->mine->id, 'item_type' => 'product', 'quantity' => 9])->assertSessionHas('success');
    expect((int) $this->mine->fresh()->quantity)->toBe(9);

    $ids = collect($this->inertia('GET', '/vendor/stock')->json('props.items.data'))->pluck('product_id')->unique()->all();
    expect($ids)->toBe([$this->mine->id]);
});

test('payout details are saved and required for the chosen method', function () {
    $this->put('/vendor/payment-settings', ['preferred_method' => 'paypal'])->assertSessionHasErrors('paypal_email');

    $this->put('/vendor/payment-settings', ['preferred_method' => 'paypal', 'paypal_email' => 'payouts@example.test'])->assertSessionHasNoErrors();
    expect($this->vendor->fresh()->payment_info['paypal']['email'])->toBe('payouts@example.test');
});

test('a payout request cannot exceed the balance minus pending requests', function () {
    $this->vendor->update(['payment_info' => ['preferred_method' => 'paypal', 'paypal' => ['email' => 'payouts@example.test']]]);
    // 100 sold on a delivered, paid order at 10% commission = 90 earned.
    $item = vendorOrder($this->mine, ['status' => 'delivered']);
    $item->update(['fulfillment_status' => 'delivered']);
    $balance = (float) $this->vendor->fresh()->calculated_balance;
    expect($balance)->toBeGreaterThanOrEqual(50.0);

    $this->post('/vendor/payouts/request', ['amount' => 50])->assertSessionHasNoErrors();
    // The same money cannot be requested again while the first request is pending.
    $this->post('/vendor/payouts/request', ['amount' => 50])->assertSessionHasErrors('amount');

    expect(VendorPayout::where('vendor_id', $this->vendor->id)->count())->toBe(1);
});

test('store settings update the profile but not the store status', function () {
    $this->put('/vendor/settings', [
        'store_name' => 'Renamed Store', 'phone' => '555', 'email' => 'shop@example.test', 'address' => '1 Road', 'city' => 'Town', 'country' => 'Land',
        'status' => 'approved', 'commission_rate' => 0, 'social_links' => ['facebook' => 'https://facebook.com/x', 'evil' => 'javascript:alert(1)'],
    ])->assertSessionHasNoErrors();

    $fresh = $this->vendor->fresh();
    expect($fresh->store_name)->toBe('Renamed Store')
        ->and((float) $fresh->commission_rate)->toBe(10.0)
        ->and($fresh->social_links)->toBe(['facebook' => 'https://facebook.com/x']);
});

test('a customer can apply for a store once', function () {
    $user = customer();
    $this->actingAs($user);

    $this->inertia('GET', '/vendor/register')->assertOk()->assertJsonPath('component', 'Vendor/Register');

    $payload = ['store_name' => 'My New Store!', 'phone' => '555', 'address' => '1 Road', 'city' => 'Town', 'country' => 'Land', 'status' => 'approved'];
    // A different name with the same slug as an existing store gets a suffix instead of failing.
    $this->other->update(['slug' => 'my-new-store']);
    $this->post('/vendor/register', $payload)->assertRedirect('/vendor');

    $vendor = $user->fresh()->vendor;
    expect($vendor->status)->toBe('pending')->and($vendor->slug)->toEndWith('-2');

    $this->post('/vendor/register', ['store_name' => 'Second Store'] + $payload)->assertRedirect('/vendor');
    expect(Vendor::where('user_id', $user->id)->count())->toBe(1);
});
