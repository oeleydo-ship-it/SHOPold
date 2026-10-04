<?php

/*
 * Every outgoing email renders on the shared branded layout, in the store currency, with the active theme's colour,
 * and actually renders (Mail::fake() alone never renders, which once hid a missing route).
 */

use App\Mail\{NewOrderAdmin, OrderConfirmation, OrderDelivered, OrderReadyForPickup, OrderShipped, OrderStatusUpdated, VerifyEmail, WelcomeEmail};
use App\Models\{Order, OrderItem, Product};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function mailOrder(): Order
{
    $product = Product::factory()->create();
    $order = Order::create([
        'order_number' => 'ORD-' . Str::upper(Str::random(8)), 'user_id' => customer()->id,
        'subtotal' => 40, 'shipping_cost' => 5, 'tax' => 2, 'discount' => 1, 'total' => 46, 'status' => 'shipped', 'payment_status' => 'paid', 'payment_method' => 'cash_on_delivery',
        'shipping_name' => 'Test Buyer', 'shipping_email' => 'buyer@example.test', 'shipping_phone' => '555', 'shipping_address' => '1 Test Street',
        'shipping_city' => 'Testville', 'shipping_zip' => '00000', 'shipping_country' => 'Testland',
        'tracking_number' => 'TRK123', 'tracking_url' => 'javascript:alert(1)', 'delivered_at' => now(),
    ]);
    OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Blue Mug', 'variation_options' => ['Size' => 'L'], 'price' => 20, 'quantity' => 2, 'total' => 40]);

    return $order->fresh();
}

beforeEach(function () {
    $this->setting('theme.active', 'default', 'theme');
    $this->setting('themes.options.default', json_encode(['primary_color' => '#aa3366']), 'theme');
    $this->setting('store.name', 'Zz Test Store', 'store');
});

// (orders.user_id is required in this schema, so every order has an account; the templates still cope with none)
test('order emails render branded', function (string $class) {
    $order = mailOrder();
    $mail = $class === OrderStatusUpdated::class ? new $class($order, 'processing') : new $class($order);
    $html = $mail->render();

    expect($html)->toContain('Zz Test Store')
        ->toContain('#aa3366')                    // active theme colour
        ->toContain($order->order_number)
        ->not->toContain('javascript:alert(1)'); // unsafe tracking links are dropped
    if (!in_array($class, [OrderDelivered::class, OrderStatusUpdated::class], true)) {
        expect($html)->toContain('Blue Mug');
    }
    // customers get a link to their account, staff get the admin link instead
    expect(str_contains($html, route('account.orders.show', $order)))->toBe($class !== NewOrderAdmin::class);
})->with([
    OrderConfirmation::class, NewOrderAdmin::class, OrderShipped::class, OrderDelivered::class, OrderStatusUpdated::class, OrderReadyForPickup::class,
]);

test('order totals use the store currency', function () {
    $html = (new OrderConfirmation(mailOrder()))->render();
    expect($html)->toContain(format_currency(46))->toContain(format_currency(20));
});

test('account emails render branded', function () {
    $user = customer();
    expect((new WelcomeEmail($user))->render())->toContain('Zz Test Store')->toContain('#aa3366');
    expect((new VerifyEmail($user, 'https://shop.test/verify/abc'))->render())->toContain('https://shop.test/verify/abc')->toContain('#aa3366');
    expect(view('emails.guest-checkout-otp', ['otp' => '123456', 'expiry' => 10])->render())->toContain('123456')->toContain('#aa3366');
});

test('a broken theme colour falls back to a safe default', function () {
    $this->setting('themes.options.default', json_encode(['primary_color' => 'red;background:url(x)']), 'theme');
    expect((new WelcomeEmail(customer()))->render())->not->toContain('url(x)')->toContain('#2563eb');
});
