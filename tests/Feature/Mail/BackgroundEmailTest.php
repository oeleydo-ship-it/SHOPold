<?php

/*
 * Background email: with "Send emails in the background" on, emails wait in the database queue and the worker
 * sends them; failed ones can be retried or removed by super admins. Status emails follow the notification switches
 * whoever changes the status (admin, driver, vendor).
 */

use App\Mail\{OrderConfirmation, OrderDelivered, OrderShipped};
use App\Models\{Order, OrderItem, Product, User};
use App\Support\MailQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Artisan, DB, Mail};
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function queuedOrder(array $attrs = []): Order
{
    $order = Order::create($attrs + [
        'order_number' => 'ORD-' . Str::upper(Str::random(8)), 'user_id' => customer()->id, 'subtotal' => 20, 'shipping_cost' => 5, 'total' => 25,
        'status' => 'processing', 'payment_status' => 'paid', 'payment_method' => 'stripe',
        'shipping_name' => 'X', 'shipping_email' => 'x@example.test', 'shipping_address' => '1 St', 'shipping_city' => 'C', 'shipping_zip' => '1', 'shipping_country' => 'L',
    ]);
    OrderItem::create(['order_id' => $order->id, 'product_id' => Product::factory()->create()->id, 'product_name' => 'Mug', 'price' => 20, 'quantity' => 1, 'total' => 20]);

    return $order;
}

afterEach(fn () => config(['queue.default' => 'sync']));

test('background sending queues emails in the database and the worker sends them', function () {
    config(['mail.default' => 'array']);
    $this->setting('email.queue_enabled', true, 'email');
    MailQueue::apply();
    expect(config('queue.default'))->toBe('database');

    Mail::to('buyer@example.test')->send(new OrderConfirmation(queuedOrder()));
    expect(DB::table('jobs')->count())->toBe(1)
        ->and(DB::table('jobs')->value('payload'))->toContain('OrderConfirmation');

    Artisan::call('queue:work', ['--stop-when-empty' => true]);
    expect(DB::table('jobs')->count())->toBe(0)
        ->and(app('mailer')->getSymfonyTransport()->messages())->toHaveCount(1);
});

test('with background sending off, nothing is queued', function () {
    $this->setting('email.queue_enabled', false, 'email');
    MailQueue::apply();
    expect(config('queue.default'))->toBe('sync');
});

test('super admins switch background sending on and see the queue status', function () {
    $this->actingAs(adminUser('super_admin'));
    $this->inertia('POST', '/admin/settings', ['_group' => 'email', 'email_queue_enabled' => true]);
    expect((bool) setting('email.queue_enabled'))->toBeTrue();

    $page = $this->inertia('GET', '/admin/settings/email')->assertOk();
    expect($page->json('props.queue'))->toHaveKeys(['enabled', 'waiting', 'failed', 'worker_ok']);
});

test('failed emails can be listed, retried and removed by super admins only', function () {
    // a real queued mail payload, turned into two failed jobs
    config(['queue.default' => 'database']);
    Mail::to('buyer@example.test')->queue(new OrderShipped(queuedOrder()));
    $payload = DB::table('jobs')->value('payload');
    DB::table('jobs')->delete();
    foreach (['f-1', 'f-2'] as $uuid) {
        DB::table('failed_jobs')->insert(['uuid' => $uuid, 'connection' => 'database', 'queue' => 'default', 'payload' => $payload, 'exception' => "Connection refused
#0 trace", 'failed_at' => now()]);
    }

    $this->actingAs(adminUser('manager'))->get('/admin/settings/email/failed')->assertForbidden();

    $this->actingAs(adminUser('super_admin'));
    $rows = $this->inertia('GET', '/admin/settings/email/failed')->assertOk()->json('props.failed.data');
    expect($rows)->toHaveCount(2)
        ->and($rows[0]['type'])->toBe('Order Shipped')
        ->and($rows[0]['to'])->toBe(['buyer@example.test'])
        ->and($rows[0]['error'])->toBe('Connection refused');

    $this->post('/admin/settings/email/failed/retry', ['ids' => ['f-1']])->assertSessionHas('success');
    expect(DB::table('failed_jobs')->pluck('uuid')->all())->toBe(['f-2'])
        ->and(DB::table('jobs')->count())->toBe(1);

    $this->delete('/admin/settings/email/failed', ['all' => true])->assertSessionHas('success');
    expect(DB::table('failed_jobs')->count())->toBe(0);
});

test('a driver delivery emails the customer, and the switches are honoured', function () {
    Mail::fake();
    module('driver_portal', true);
    $driver = User::factory()->driver()->create();
    $order = queuedOrder(['status' => 'shipped', 'driver_id' => $driver->id]);
    $this->actingAs($driver)->post("/driver/orders/{$order->id}/complete")->assertRedirect('/driver/orders');
    Mail::assertQueued(OrderDelivered::class, fn ($m) => $m->order->is($order));

    $this->setting('email.order_shipped', false, 'email');
    $other = queuedOrder(['driver_id' => $driver->id]);
    $this->patch("/driver/orders/{$other->id}/status", ['status' => 'picked_up']);
    Mail::assertNotQueued(OrderShipped::class);
});

test('vendor fulfilment that ships the whole order emails the customer', function () {
    Mail::fake();
    module('multi_vendor', true);
    $vendor = makeVendor();
    $order = queuedOrder();
    OrderItem::where('order_id', $order->id)->first()->product->update(['vendor_id' => $vendor->id]);

    $this->actingAs($vendor->user)->post("/vendor/orders/{$order->id}/bulk-status", ['fulfillment_status' => 'shipped'])->assertSessionHas('success');
    expect($order->fresh()->status)->toBe('shipped');
    Mail::assertQueued(OrderShipped::class);
});
