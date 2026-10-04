<?php

/*
 * Vendor payout details (bank account numbers etc.) are encrypted at rest, never serialised,
 * still readable from rows written before encryption, and shown to super admins when paying out.
 */

use App\Models\{Order, OrderItem, Product, VendorPayout};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    module('multi_vendor', true);
    $this->vendor = makeVendor();
    $this->actingAs($this->vendor->user);
});

test('saved payout details are encrypted in the database', function () {
    $this->put('/vendor/payment-settings', [
        'preferred_method' => 'bank_transfer', 'bank_name' => 'Zz Bank', 'bank_account_name' => 'Vera', 'bank_account_number' => 'ACCT-99887766',
    ])->assertSessionHasNoErrors();

    $raw = DB::table('vendors')->where('id', $this->vendor->id)->value('payment_info');
    expect($raw)->not->toContain('ACCT-99887766')->not->toContain('Zz Bank')
        ->and($this->vendor->fresh()->payment_info['bank']['account_number'])->toBe('ACCT-99887766')
        ->and($this->vendor->fresh()->toArray())->not->toHaveKey('payment_info')
        ->and(json_encode($this->vendor->fresh()))->not->toContain('ACCT-99887766');
});

test('rows written before encryption are still read', function () {
    DB::table('vendors')->where('id', $this->vendor->id)->update(['payment_info' => json_encode(['preferred_method' => 'paypal', 'paypal' => ['email' => 'old@example.test']])]);

    expect($this->vendor->fresh()->payment_info['paypal']['email'])->toBe('old@example.test');
    $this->inertia('GET', '/vendor/payment-settings')->assertJsonPath('props.info.paypal_email', 'old@example.test');
});

test('a payout request keeps an encrypted snapshot that super admins can read', function () {
    $this->vendor->update(['payment_info' => ['preferred_method' => 'bank_transfer', 'bank' => ['name' => 'Zz Bank', 'account_name' => 'Vera', 'account_number' => 'ACCT-11223344']]]);
    $product = Product::factory()->create(['vendor_id' => $this->vendor->id]);
    $order = Order::create([
        'order_number' => 'ORD-' . Str::upper(Str::random(8)), 'user_id' => customer()->id, 'subtotal' => 100, 'total' => 100,
        'status' => 'delivered', 'payment_status' => 'paid', 'payment_method' => 'stripe',
        'shipping_name' => 'X', 'shipping_email' => 'x@example.test', 'shipping_address' => '1 St', 'shipping_city' => 'C', 'shipping_zip' => '1', 'shipping_country' => 'L',
    ]);
    OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'X', 'price' => 100, 'quantity' => 1, 'total' => 100, 'fulfillment_status' => 'delivered']);

    $this->post('/vendor/payouts/request', ['amount' => 50])->assertSessionHasNoErrors();

    $payout = VendorPayout::firstOrFail();
    $raw = DB::table('vendor_payouts')->where('id', $payout->id)->first();
    expect($raw->payment_details)->not->toContain('ACCT-11223344')
        ->and((string) $raw->notes)->not->toContain('ACCT-11223344')
        ->and($payout->payToLines())->toContain('Account number: ACCT-11223344');

    $this->actingAs(adminUser('super_admin'));
    $rows = $this->inertia('GET', '/admin/payouts')->json('props.payouts.data');
    expect($rows[0]['pay_to'])->toContain('Account number: ACCT-11223344');
});

test('the migration moves bank details out of old payout notes', function () {
    $migration = require database_path('migrations/2026_10_04_120000_encrypt_vendor_payout_details.php');
    $parse = (fn ($n) => $this->parseGeneratedNote($n))->call($migration, "Bank: Zz Bank\nAccount: Vera\nNumber: 123");

    expect($parse)->toBe(['bank' => ['name' => 'Zz Bank', 'account_name' => 'Vera', 'account_number' => '123']])
        ->and((fn ($n) => $this->parseGeneratedNote($n))->call($migration, 'Paid by hand, see email'))->toBeNull();
});
