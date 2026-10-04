<?php

use App\Models\{Order, Product};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http, Mail, Notification};

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    Http::fake();
    $customer = customer();
    $product = Product::factory()->create(['price' => 24.99]);
    $this->actingAs($customer)->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);
    $this->setting('payment.cod_enabled', true, 'payment');
    $this->post('/checkout', ['shipping_name' => 'Zz', 'shipping_email' => $customer->email, 'shipping_address' => '1 St', 'shipping_city' => 'C',
        'shipping_zip' => '1', 'shipping_country' => 'United Arab Emirates', 'payment_method' => 'cod']);
    $this->order = Order::firstOrFail();
});

test('managers and super admins download the invoice as a PDF file', function (string $role) {
    $response = $this->actingAs(adminUser($role))->get("/admin/invoices/{$this->order->id}/download")->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF')
        ->and($response->headers->get('content-disposition'))->toContain('attachment')
        ->toContain('invoice-' . preg_replace('/[^A-Za-z0-9_-]/', '', $this->order->order_number) . '.pdf');
})->with(['manager', 'super_admin']);

test('editors cannot download invoices', function () {
    $this->actingAs(adminUser('editor'))->get("/admin/invoices/{$this->order->id}/download")->assertForbidden();
});
