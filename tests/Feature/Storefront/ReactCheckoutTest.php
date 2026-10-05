<?php

/*
 * React storefront S4: cart, checkout and order confirmation pages. No payment gateway is contacted:
 * orders use cash on delivery, and the redirect-gateway test only checks where the browser is sent.
 */

use App\Models\{Coupon, Order, Product};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http, Mail, Notification};

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    Http::fake();
    $this->setting('payment.cod_enabled', true, 'payment');
    $this->setting('payment.tax_rate', 0, 'payment');
    $this->product = Product::factory()->create(['price' => 20, 'quantity' => 10]);
    $this->user = customer();
    $this->actingAs($this->user)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 2]);
});

function codOrder(array $extra = []): array
{
    return array_merge([
        'shipping_name' => 'Zz Buyer', 'shipping_email' => 'zz-buyer@example.test', 'shipping_address' => '1 Test St',
        'shipping_city' => 'Testville', 'shipping_zip' => '00000', 'shipping_country' => 'United Arab Emirates', 'payment_method' => 'cod',
    ], $extra);
}

test('the cart page lists the lines and totals', function () {
    $page = $this->inertia('GET', '/cart')->assertOk()->assertJsonPath('component', 'Storefront/Cart');

    expect($page->json('props.items.0.name'))->toBe($this->product->translated_name)
        ->and($page->json('props.items.0.quantity'))->toBe(2)
        ->and($page->json('props.items.0.subtotal'))->toBe(format_currency(40))
        ->and($page->json('props.totals.total'))->toBe(format_currency(40))
        ->and($page->json('props.coupon'))->toBeNull();
});

test('cart changes and coupons go back to the React cart', function () {
    $item = \App\Models\CartItem::firstOrFail();
    Coupon::create(['code' => 'ZZTEN', 'type' => 'fixed', 'value' => 10, 'is_active' => true]);

    $this->inertia('POST', '/cart/update', ['item_id' => $item->id, 'quantity' => 3])->assertRedirect();
    $this->inertia('POST', '/cart/coupon', ['coupon_code' => 'ZZTEN'])->assertRedirect();
    $page = $this->inertia('GET', '/cart');

    expect($page->json('props.items.0.quantity'))->toBe(3)
        ->and($page->json('props.coupon.code'))->toBe('ZZTEN')
        ->and($page->json('props.totals.total'))->toBe(format_currency(50));

    $this->inertia('POST', '/cart/remove', ['item_id' => $item->id])->assertRedirect();
    expect($this->inertia('GET', '/cart')->json('props.items'))->toBe([]);
});

test('checkout sends safe payment details and the shipping form defaults', function () {
    $this->setting('payment.instamojo_enabled', true, 'payment');
    $this->setting('payment.instamojo_api_key', 'zz-not-for-browsers', 'payment');

    $page = $this->inertia('GET', '/checkout')->assertOk()->assertJsonPath('component', 'Storefront/Checkout');

    expect($page->json('props.defaults.shipping_email'))->toBe($this->user->email)
        ->and(collect($page->json('props.payments'))->pluck('key')->all())->toContain('cod', 'instamojo')
        ->and($page->getContent())->not->toContain('zz-not-for-browsers')
        ->and($page->json('props.countries.list'))->toContain('United Arab Emirates')
        ->and($page->json('props.guest.isGuest'))->toBeFalse();
});

test('placing an order shows the React confirmation', function () {
    $this->inertia('POST', '/checkout', codOrder())->assertRedirect();
    $order = Order::firstOrFail();

    $page = $this->inertia('GET', "/checkout/success/{$order->id}")->assertOk()->assertJsonPath('component', 'Storefront/CheckoutSuccess');
    expect($page->json('props.order.number'))->toBe($order->order_number)
        ->and($page->json('props.order.total'))->toBe(format_currency(40))
        ->and($page->json('props.order.payment'))->toBe('Cash on Delivery');
});

test('validation errors come back to the form', function () {
    // A real Inertia visit asks for HTML, so Laravel redirects back with the errors in the session.
    $this->withHeaders(['X-Inertia' => 'true'])->post('/checkout', codOrder(['shipping_name' => '']))
        ->assertRedirect()->assertSessionHasErrors('shipping_name');
    expect(Order::count())->toBe(0);
});

test('redirect gateways leave the app with a full page load', function () {
    $this->setting('payment.paystack_enabled', true, 'payment');

    $res = $this->inertia('POST', '/checkout', codOrder(['payment_method' => 'paystack']));

    $res->assertStatus(409);
    expect($res->headers->get('X-Inertia-Location'))->toBe(route('payment.paystack.init', Order::firstOrFail()));
});

test('someone else cannot see the confirmation', function () {
    $this->inertia('POST', '/checkout', codOrder())->assertRedirect();
    $order = Order::firstOrFail();

    $this->actingAs(customer());
    $this->inertia('GET', "/checkout/success/{$order->id}")->assertForbidden();
});

test('the shipping methods endpoint gives the total for each method', function () {
    $res = $this->postJson('/checkout/shipping-methods', ['country' => 'United Arab Emirates'])->assertOk();
    foreach ($res->json('methods') as $m) {
        expect($m)->toHaveKeys(['id', 'name', 'cost_formatted', 'total_formatted']);
    }
});
