<?php

/*
 * Driver portal: a driver sees and acts only on deliveries assigned to them, finishing a delivery pays them
 * exactly once, and delivered or cancelled orders stay final.
 */

use App\Models\DriverEarning;
use App\Models\DriverNotification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function driverOrder(?User $driver, array $attrs = []): Order
{
    $order = Order::create($attrs + [
        'order_number' => 'ORD-' . Str::upper(Str::random(8)), 'user_id' => customer()->id,
        'subtotal' => 40, 'shipping_cost' => 10, 'total' => 50, 'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'stripe',
        'shipping_name' => 'Test Buyer', 'shipping_email' => 'buyer@example.test', 'shipping_phone' => '555-0100', 'shipping_address' => '1 Test Street',
        'shipping_city' => 'Testville', 'shipping_zip' => '00000', 'shipping_country' => 'Testland', 'driver_id' => $driver?->id,
    ]);
    $product = Product::factory()->create();
    OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name, 'price' => 20, 'quantity' => 2, 'total' => 40, 'fulfillment_status' => 'pending']);

    return $order;
}

beforeEach(function () {
    module('driver_portal', true);
    $this->driver = User::factory()->driver()->create(['driver_commission_rate' => 20]);
    $this->other = User::factory()->driver()->create();
    $this->actingAs($this->driver);
});

test('portal pages render as React pages', function (string $uri, string $component) {
    $order = driverOrder($this->driver);
    $this->inertia('GET', str_replace('{order}', (string) $order->id, $uri))->assertOk()->assertJsonPath('component', $component);
})->with([
    'dashboard' => ['/driver', 'Driver/Dashboard'],
    'deliveries' => ['/driver/orders', 'Driver/Orders/Index'],
    'history' => ['/driver/orders/history', 'Driver/Orders/History'],
    'delivery' => ['/driver/orders/{order}', 'Driver/Orders/Show'],
    'earnings' => ['/driver/earnings', 'Driver/Earnings/Index'],
    'earnings history' => ['/driver/earnings/history', 'Driver/Earnings/History'],
    'notifications' => ['/driver/notifications', 'Driver/Notifications'],
    'profile' => ['/driver/profile', 'Driver/Profile'],
]);

test('non-drivers are kept out', function () {
    $this->actingAs(customer());
    $this->get('/driver')->assertRedirect('/login');
});

test('a driver only sees and handles their own deliveries', function () {
    $mine = driverOrder($this->driver);
    $theirs = driverOrder($this->other);

    expect(collect($this->inertia('GET', '/driver/orders')->json('props.orders.data'))->pluck('id')->all())->toBe([$mine->id]);

    $this->get("/driver/orders/{$theirs->id}")->assertForbidden();
    $this->patch("/driver/orders/{$theirs->id}/status", ['status' => 'picked_up'])->assertForbidden();
    $this->post("/driver/orders/{$theirs->id}/note", ['note' => 'x'])->assertForbidden();
    $this->post("/driver/orders/{$theirs->id}/complete")->assertForbidden();
    expect($theirs->fresh()->status)->toBe('processing');
});

test('pick up, then complete: the driver is paid once', function () {
    $order = driverOrder($this->driver);

    $this->patch("/driver/orders/{$order->id}/status", ['status' => 'picked_up'])->assertSessionHas('success');
    expect($order->fresh()->status)->toBe('shipped')->and($order->fresh()->picked_up_at)->not->toBeNull();

    $sig = 'data:image/png;base64,' . base64_encode('fake-png');
    $this->post("/driver/orders/{$order->id}/complete", ['signature' => $sig, 'driver_notes' => 'Left at door'])->assertRedirect('/driver/orders');

    $fresh = $order->fresh();
    expect($fresh->status)->toBe('delivered')
        ->and($fresh->delivery_signature)->toBe($sig)
        ->and($fresh->items()->first()->fulfillment_status)->toBe('delivered');

    // A second tap must not pay again.
    $this->post("/driver/orders/{$order->id}/complete")->assertSessionHas('error');
    // Nor can the status endpoint reopen or re-deliver it.
    $this->patch("/driver/orders/{$order->id}/status", ['status' => 'shipped'])->assertSessionHas('error');
    $this->patch("/driver/orders/{$order->id}/status", ['status' => 'delivered'])->assertSessionHas('error');

    expect(DriverEarning::where('order_id', $order->id)->count())->toBe(1)
        ->and((float) DriverEarning::where('order_id', $order->id)->value('amount'))->toBe(2.0) // 20% of 10 shipping
        ->and((int) $this->driver->fresh()->driver_total_deliveries)->toBe(1)
        ->and($order->fresh()->status)->toBe('delivered');
});

test('marking delivered through the status endpoint also records earnings', function () {
    $order = driverOrder($this->driver, ['status' => 'shipped']);

    $this->patch("/driver/orders/{$order->id}/status", ['status' => 'delivered'])->assertRedirect('/driver/orders');

    expect($order->fresh()->status)->toBe('delivered')
        ->and(DriverEarning::where('order_id', $order->id)->count())->toBe(1);
});

test('cancelled orders cannot be picked up or delivered', function () {
    $order = driverOrder($this->driver, ['status' => 'cancelled']);

    $this->patch("/driver/orders/{$order->id}/status", ['status' => 'picked_up'])->assertSessionHas('error');
    $this->post("/driver/orders/{$order->id}/complete")->assertSessionHas('error');
    expect($order->fresh()->status)->toBe('cancelled')->and(DriverEarning::count())->toBe(0);
});

test('a signature must be a PNG data URL', function () {
    $order = driverOrder($this->driver, ['status' => 'shipped']);

    $this->post("/driver/orders/{$order->id}/complete", ['signature' => 'javascript:alert(1)'])->assertSessionHasErrors('signature');
    expect($order->fresh()->status)->toBe('shipped');
});

test('availability, profile and schedule are saved', function () {
    $this->post('/driver/status', ['status' => 'busy'])->assertSessionHas('success');
    expect($this->driver->fresh()->driver_status)->toBe('busy');

    $this->put('/driver/profile', ['name' => 'Dee Driver', 'email' => $this->driver->email, 'driver_phone' => '555', 'driver_commission_rate' => 99])->assertSessionHasNoErrors();
    expect($this->driver->fresh()->name)->toBe('Dee Driver')->and((float) $this->driver->fresh()->driver_commission_rate)->toBe(20.0);

    $this->put('/driver/profile/schedule', ['schedule' => ['monday' => ['enabled' => true, 'start' => '08:00', 'end' => '16:00'], 'funday' => ['enabled' => true]]])->assertSessionHasNoErrors();
    $schedule = $this->driver->fresh()->driver_availability_schedule;
    expect($schedule['monday'])->toEqual(['enabled' => true, 'start' => '08:00', 'end' => '16:00'])
        ->and($schedule)->not->toHaveKey('funday')
        ->and($schedule['tuesday']['enabled'])->toBeFalse();

    $this->put('/driver/profile/schedule', ['schedule' => ['monday' => ['enabled' => true, 'start' => 'soon']]])->assertSessionHasErrors();
});

test('notifications can be read only by their driver', function () {
    $mine = DriverNotification::create(['driver_id' => $this->driver->id, 'type' => 'order', 'title' => 'New delivery', 'message' => 'x', 'is_read' => false]);
    $theirs = DriverNotification::create(['driver_id' => $this->other->id, 'type' => 'order', 'title' => 'New delivery', 'message' => 'x', 'is_read' => false]);

    expect($this->inertia('GET', '/driver')->json('props.driver.unread'))->toBe(1);
    $this->post("/driver/notifications/{$theirs->id}/read")->assertForbidden();
    $this->post("/driver/notifications/{$mine->id}/read");
    expect($mine->fresh()->is_read)->toBeTrue()->and($theirs->fresh()->is_read)->toBeFalse();
});
