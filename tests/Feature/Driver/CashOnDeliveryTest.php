<?php

/*
 * Cash on delivery: the driver must confirm the cash was collected, and completing the delivery then marks the order paid.
 */

use App\Models\{Order, OrderItem, Product, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    module('driver_portal', true);
    $this->driver = User::factory()->driver()->create();
    $this->order = Order::create([
        'order_number' => 'ORD-' . Str::upper(Str::random(8)), 'user_id' => customer()->id, 'subtotal' => 45, 'shipping_cost' => 5, 'total' => 50,
        'status' => 'shipped', 'payment_status' => 'pending', 'payment_method' => 'cod', 'driver_id' => $this->driver->id,
        'shipping_name' => 'X', 'shipping_email' => 'x@example.test', 'shipping_address' => '1 St', 'shipping_city' => 'C', 'shipping_zip' => '1', 'shipping_country' => 'L',
    ]);
    OrderItem::create(['order_id' => $this->order->id, 'product_id' => Product::factory()->create()->id, 'product_name' => 'X', 'price' => 45, 'quantity' => 1, 'total' => 45]);
    $this->actingAs($this->driver);
});

test('a COD delivery needs the cash confirmation', function () {
    $this->post("/driver/orders/{$this->order->id}/complete")->assertSessionHasErrors('cash_collected');
    $this->patch("/driver/orders/{$this->order->id}/status", ['status' => 'delivered'])->assertSessionHasErrors('cash_collected');

    expect($this->order->fresh()->status)->toBe('shipped')->and($this->order->fresh()->payment_status)->toBe('pending');
});

test('confirming the cash marks the order delivered and paid', function () {
    $this->post("/driver/orders/{$this->order->id}/complete", ['cash_collected' => '1', 'driver_notes' => 'Front door'])->assertRedirect('/driver/orders');

    $fresh = $this->order->fresh();
    expect($fresh->status)->toBe('delivered')
        ->and($fresh->payment_status)->toBe('paid')
        ->and($fresh->driver_notes)->toContain('Cash collected')->toContain('Front door');
});

test('prepaid orders need no cash confirmation', function () {
    $this->order->update(['payment_method' => 'stripe', 'payment_status' => 'paid']);
    $this->post("/driver/orders/{$this->order->id}/complete")->assertRedirect('/driver/orders');
    expect($this->order->fresh()->status)->toBe('delivered');
});
