<?php

/*
 * Checkout works in every theme with the same form, and a cash-on-delivery order goes through.
 * Mail, notifications and outgoing HTTP are faked; nothing leaves the machine.
 */

use App\Models\{Order, Page, Product};
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http, Mail, Notification};

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    Http::fake();
    $this->customer = customer();
    $this->product = Product::factory()->create(['price' => 20, 'quantity' => 10]);
    $this->setting('payment.cod_enabled', true, 'payment');
    $this->actingAs($this->customer)->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 2]);
});

function checkoutFieldNames(string $html): array
{
    $form = substr($html, strpos($html, 'x-ref="checkoutForm"'));
    $form = substr($form, 0, strpos($form, '</form>'));
    preg_match_all('/name="([a-z_\[\]]+)"/', $form, $m);
    $names = array_values(array_unique($m[1]));
    sort($names);

    return $names;
}

test('every theme shows the same checkout form with one form, one script and Place Order', function () {
    $fields = [];
    foreach (['default', 'modern', 'starter'] as $theme) {
        app(ThemeService::class)->setActive($theme);
        $html = $this->get('/checkout')->assertOk()->getContent();

        expect(substr_count($html, 'x-ref="checkoutForm"'))->toBe(1)
            ->and(substr_count($html, 'function checkoutForm'))->toBe(1)
            ->and($html)->toContain('x-data="checkoutForm()"')->toContain('Place Order');
        $fields[$theme] = checkoutFieldNames($html);
    }

    expect($fields['modern'])->toBe($fields['default'])->and($fields['starter'])->toBe($fields['default'])
        ->and($fields['default'])->toContain('shipping_name', 'shipping_email', 'payment_method');
});

test('a cash-on-delivery order is placed and confirmed in every theme', function () {
    app(ThemeService::class)->setActive('modern');
    $this->post('/checkout', [
        'shipping_name' => 'Zz Buyer', 'shipping_email' => $this->customer->email, 'shipping_phone' => '0500000000',
        'shipping_address' => '1 Test St', 'shipping_city' => 'Testville', 'shipping_zip' => '12345',
        'shipping_country' => 'United Arab Emirates', 'payment_method' => 'cod',
    ])->assertRedirect();

    $order = Order::where('user_id', $this->customer->id)->firstOrFail();
    expect((float) $order->subtotal)->toBe(40.0)->and($order->payment_method)->toBe('cod');

    foreach (['default', 'modern', 'starter'] as $theme) {
        app(ThemeService::class)->setActive($theme);
        $this->get("/checkout/success/{$order->id}")->assertOk()->assertSee($order->order_number)->assertSee('Cash on Delivery');
    }

    $this->actingAs(customer())->get("/checkout/success/{$order->id}")->assertForbidden();
});
