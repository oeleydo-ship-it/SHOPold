<?php

/*
 * Vendor and driver emails: new order (each vendor sees only their own items), store approved/rejected/suspended,
 * payout sent/failed, delivery assigned. Each follows its switch in Settings > Email.
 */

use App\Mail\{DriverAssigned, VendorNewOrder, VendorPayoutUpdated, VendorStatusChanged};
use App\Models\{Order, OrderItem, Product, User, VendorPayout};
use App\Support\PartnerMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    module('multi_vendor', true);
    module('driver_portal', true);
    $this->a = makeVendor();
    $this->b = makeVendor();
    $this->order = Order::create([
        'order_number' => 'ORD-' . Str::upper(Str::random(8)), 'user_id' => customer()->id, 'subtotal' => 30, 'total' => 30,
        'status' => 'processing', 'payment_status' => 'pending', 'payment_method' => 'cod',
        'shipping_name' => 'Bea', 'shipping_email' => 'bea@example.test', 'shipping_phone' => '555', 'shipping_address' => '12 High St',
        'shipping_city' => 'Testville', 'shipping_zip' => '1', 'shipping_country' => 'Testland',
    ]);
    foreach ([[$this->a, 'Alpha Mug'], [$this->b, 'Beta Lamp'], [null, 'Store Pen']] as [$vendor, $name]) {
        $p = Product::factory()->create(['vendor_id' => $vendor?->id]);
        OrderItem::create(['order_id' => $this->order->id, 'product_id' => $p->id, 'product_name' => $name, 'price' => 10, 'quantity' => 1, 'total' => 10]);
    }
});

test('each vendor gets one email with only their items', function () {
    PartnerMailer::vendorNewOrder($this->order);

    Mail::assertQueuedCount(2);
    Mail::assertQueued(VendorNewOrder::class, fn ($m) => $m->vendor->is($this->a) && $m->hasTo($this->a->email));

    $html = (new VendorNewOrder($this->order, $this->a))->render();
    expect($html)->toContain('Alpha Mug')->not->toContain('Beta Lamp')->not->toContain('Store Pen')
        ->toContain('Testville')->not->toContain('12 High St')->not->toContain('bea@example.test')
        ->toContain(route('vendor.orders.show', $this->order));
});

test('the switch turns vendor order emails off', function () {
    $this->setting('email.vendor_new_order', false, 'email');
    PartnerMailer::vendorNewOrder($this->order);
    Mail::assertNothingQueued();
});

test('approving, rejecting or suspending a store emails the vendor', function (string $status) {
    $vendor = makeVendor('pending');
    $this->actingAs(adminUser('super_admin'))->patch("/admin/vendors/{$vendor->id}/status", ['status' => $status]);

    Mail::assertQueued(VendorStatusChanged::class, fn ($m) => $m->vendor->is($vendor) && $m->hasTo($vendor->user->email));
    expect((new VendorStatusChanged($vendor->fresh()))->render())->toContain($vendor->store_name);
})->with(['approved', 'rejected', 'suspended']);

test('no email when the store status does not really change', function () {
    $this->actingAs(adminUser('super_admin'))->patch("/admin/vendors/{$this->a->id}/status", ['status' => 'approved']);
    Mail::assertNotQueued(VendorStatusChanged::class);
});

test('completed and failed payouts email the vendor, processing does not', function () {
    $payout = VendorPayout::create(['vendor_id' => $this->a->id, 'amount' => 75, 'payment_method' => 'paypal', 'status' => 'pending']);
    $this->actingAs(adminUser('super_admin'));

    $this->patch("/admin/payouts/{$payout->id}/status", ['status' => 'processing']);
    Mail::assertNotQueued(VendorPayoutUpdated::class);

    $this->patch("/admin/payouts/{$payout->id}/status", ['status' => 'completed', 'transaction_id' => 'TX-1']);
    Mail::assertQueued(VendorPayoutUpdated::class, fn ($m) => $m->payout->is($payout));
    expect((new VendorPayoutUpdated($payout->fresh()))->render())->toContain(format_currency(75))->toContain('TX-1');
});

test('assigning a delivery emails the driver with the address and cash to collect', function () {
    $driver = User::factory()->driver()->create();
    $this->actingAs(adminUser('manager'))->patch("/admin/orders/{$this->order->id}/assign-driver", ['driver_id' => $driver->id]);

    Mail::assertQueued(DriverAssigned::class, fn ($m) => $m->driver->is($driver) && $m->hasTo($driver->email));
    expect((new DriverAssigned($this->order->fresh(), $driver))->render())
        ->toContain('12 High St')->toContain('Cash on delivery')->toContain(format_currency(30))
        ->toContain(route('driver.orders.show', $this->order));
});
