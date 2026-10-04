<?php

/*
 * Card payments through Stripe (stripe-php v22). Stripe's HTTP client is replaced by a fake that records requests
 * and returns canned responses, so nothing reaches Stripe. Covers: exact amounts (rounding, zero-decimal
 * currencies), refund when the order can't be saved after charging, and the signed webhook.
 */

use App\Models\{Order, Product};
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http, Mail, Notification};
use Stripe\HttpClient\ClientInterface;

uses(RefreshDatabase::class);

class FakeStripeClient implements ClientInterface
{
    public array $requests = [];

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $this->requests[] = ['method' => strtoupper($method), 'path' => parse_url($absUrl, PHP_URL_PATH), 'params' => $params];
        $body = str_contains($absUrl, '/v1/refunds')
            ? ['id' => 're_test_1', 'object' => 'refund', 'status' => 'succeeded']
            : ['id' => 'pi_test_1', 'object' => 'payment_intent', 'status' => 'succeeded', 'amount' => $params['amount'] ?? 0];

        return [json_encode($body), 200, []];
    }
}

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    Http::fake();
    $this->stripe = new FakeStripeClient();
    \Stripe\ApiRequestor::setHttpClient($this->stripe);
    $this->setting('payment.stripe_enabled', true, 'payment');
    $this->setting('payment.stripe_secret_key', 'sk_test_zz_fake', 'payment');
    $this->setting('payment.tax_rate', 0, 'payment');
    $this->product = Product::factory()->create(['price' => 19.99, 'quantity' => 10]);
});

afterEach(fn () => \Stripe\ApiRequestor::setHttpClient(null));

function stripeCheckout($test): \Illuminate\Testing\TestResponse
{
    return $test->post('/checkout', [
        'shipping_name' => 'Zz Buyer', 'shipping_email' => 'zz-buyer@example.test', 'shipping_phone' => '555', 'shipping_address' => '1 St',
        'shipping_city' => 'C', 'shipping_zip' => '1', 'shipping_country' => 'United Arab Emirates', 'payment_method' => 'stripe',
        'stripe_payment_method' => 'pm_card_visa',
    ]);
}

test('amounts are rounded to the smallest unit, and zero-decimal currencies are not multiplied', function () {
    expect(PaymentGatewayService::stripeAmount(19.99, 'usd'))->toBe(1999)
        ->and(PaymentGatewayService::stripeAmount(0.1 + 0.2, 'eur'))->toBe(30)
        ->and(PaymentGatewayService::stripeAmount(1500, 'JPY'))->toBe(1500);
});

test('a card payment charges the exact total and marks the order paid', function () {
    $this->actingAs(customer())->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);
    stripeCheckout($this)->assertRedirect();

    $order = Order::firstOrFail();
    $charge = collect($this->stripe->requests)->firstWhere('path', '/v1/payment_intents');
    expect($charge['params']['amount'])->toBe(PaymentGatewayService::stripeAmount((float) $order->total, 'usd'))
        ->and($order->payment_status)->toBe('paid')
        ->and($order->transaction_id)->toBe('pi_test_1');
});

test('if the order cannot be saved after charging, the payment is refunded', function () {
    $this->actingAs(customer())->post('/cart/add', ['product_id' => $this->product->id, 'quantity' => 1]);
    Order::creating(fn () => throw new RuntimeException('database hiccup'));

    stripeCheckout($this)->assertSessionHas('error', fn ($m) => str_contains($m, 'refunded'));

    expect(Order::count())->toBe(0)
        ->and(collect($this->stripe->requests)->pluck('path')->all())->toBe(['/v1/payment_intents', '/v1/refunds'])
        ->and($this->stripe->requests[1]['params']['payment_intent'])->toBe('pi_test_1');
});

describe('webhook', function () {
    beforeEach(function () {
        $this->secret = 'whsec_zz_test';
        $this->setting('payment.stripe_webhook_secret', $this->secret, 'payment');
        $this->order = Order::create([
            'order_number' => 'ORD-WH1', 'user_id' => customer()->id, 'subtotal' => 20, 'total' => 20, 'status' => 'processing',
            'payment_status' => 'pending', 'payment_method' => 'stripe', 'transaction_id' => 'pi_wh_1',
            'shipping_name' => 'X', 'shipping_email' => 'x@example.test', 'shipping_address' => '1', 'shipping_city' => 'C', 'shipping_zip' => '1', 'shipping_country' => 'L',
        ]);
    });

    function stripeEvent($test, string $type, array $object, ?string $secret = null): \Illuminate\Testing\TestResponse
    {
        $payload = json_encode(['id' => 'evt_1', 'object' => 'event', 'type' => $type, 'data' => ['object' => $object]]);
        $t = time();
        $sig = hash_hmac('sha256', "{$t}.{$payload}", $secret ?? $test->secret);

        return $test->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => "t={$t},v1={$sig}", 'CONTENT_TYPE' => 'application/json'], $payload);
    }

    test('a signed payment_intent.succeeded marks the order paid; a bad signature is rejected', function () {
        stripeEvent($this, 'payment_intent.succeeded', ['id' => 'pi_wh_1', 'object' => 'payment_intent'], 'whsec_wrong')->assertStatus(400);
        expect($this->order->fresh()->payment_status)->toBe('pending');

        stripeEvent($this, 'payment_intent.succeeded', ['id' => 'pi_wh_1', 'object' => 'payment_intent'])->assertOk();
        expect($this->order->fresh()->payment_status)->toBe('paid');
    });

    test('a late failure does not undo a paid order', function () {
        $this->order->update(['payment_status' => 'paid']);
        stripeEvent($this, 'payment_intent.payment_failed', ['id' => 'pi_wh_1', 'object' => 'payment_intent'])->assertOk();
        expect($this->order->fresh()->payment_status)->toBe('paid');
    });

    test('partial and full refunds are told apart', function () {
        $this->order->update(['payment_status' => 'paid']);
        stripeEvent($this, 'charge.refunded', ['id' => 'ch_1', 'object' => 'charge', 'payment_intent' => 'pi_wh_1', 'amount' => 2000, 'amount_refunded' => 500, 'refunded' => false])->assertOk();
        expect($this->order->fresh()->payment_status)->toBe('partially_refunded');

        stripeEvent($this, 'charge.refunded', ['id' => 'ch_1', 'object' => 'charge', 'payment_intent' => 'pi_wh_1', 'amount' => 2000, 'amount_refunded' => 2000, 'refunded' => true])->assertOk();
        expect($this->order->fresh()->payment_status)->toBe('refunded');
    });
});
