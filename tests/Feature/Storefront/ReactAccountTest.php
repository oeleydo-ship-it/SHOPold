<?php

/*
 * React storefront S5: login/register, the customer account pages and the helpdesk. Uses synthetic
 * accounts in the test database only.
 */

use App\Models\{Cart, HelpdeskTicket, Order, Product, User, Wishlist};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Mail};

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

function zzOrder(User $user, array $extra = []): Order
{
    return Order::create(array_merge([
        'order_number' => 'ZZ-' . uniqid(), 'user_id' => $user->id, 'subtotal' => 30, 'tax' => 0, 'shipping_cost' => 5, 'discount' => 0,
        'total' => 35, 'status' => 'shipped', 'payment_status' => 'paid', 'payment_method' => 'cod',
        'shipping_name' => 'Zz', 'shipping_email' => 'zz@example.test', 'shipping_address' => '1 Test St', 'shipping_city' => 'Testville',
        'shipping_zip' => '00000', 'shipping_country' => 'United Arab Emirates',
    ], $extra));
}

test('login and register are React pages', function () {
    $this->inertia('GET', '/login')->assertOk()->assertJsonPath('component', 'Storefront/Auth/Login');
    $this->inertia('GET', '/register')->assertOk()->assertJsonPath('component', 'Storefront/Auth/Register');
});

test('logging in keeps the guest cart', function () {
    $user = User::factory()->create(['password' => Hash::make('zz-test-pass-1')]);
    $product = Product::factory()->create(['quantity' => 5]);
    // A guest cart belongs to the browser session (the test client does not keep cookies, so set the session cookie).
    $sid = str_repeat('z', 40);
    Cart::create(['session_id' => $sid])->items()->create(['product_id' => $product->id, 'quantity' => 2]);

    $this->withCookie(config('session.cookie'), $sid)->post('/login', ['email' => $user->email, 'password' => 'zz-test-pass-1'])->assertRedirect();

    $this->assertAuthenticatedAs($user);
    expect(Cart::where('user_id', $user->id)->first()?->items()->sum('quantity'))->toEqual(2);
});

test('a failed login comes back with an error on the email field', function () {
    $this->withHeaders(['X-Inertia' => 'true'])->post('/login', ['email' => 'nobody@example.test', 'password' => 'wrong-pass'])
        ->assertRedirect()->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('an Inertia login that ends in the admin app leaves the storefront app', function () {
    $admin = adminUser('super_admin');
    $admin->update(['password' => Hash::make('zz-test-pass-2')]);

    $res = $this->withHeaders(['X-Inertia' => 'true'])->post('/login', ['email' => $admin->email, 'password' => 'zz-test-pass-2']);

    $res->assertStatus(409);
    expect($res->headers->get('X-Inertia-Location'))->toBe(route('admin.dashboard'));
});

test('a storefront visit to a plain HTML page (the printable invoice) becomes a full page load', function () {
    $user = customer();
    $order = zzOrder($user);
    $res = $this->actingAs($user)->withHeaders(['X-Inertia' => 'true'])->get("/account/orders/{$order->id}/invoice/print");

    $res->assertStatus(409);
    expect($res->headers->get('X-Inertia-Location'))->toBe(url("/account/orders/{$order->id}/invoice/print"));
});

test('the invoice screen is a React page', function () {
    $user = customer();
    $order = zzOrder($user);

    $this->actingAs($user)->inertia('GET', "/account/orders/{$order->id}/invoice")
        ->assertJsonPath('component', 'Storefront/Account/Invoice')
        ->assertJsonPath('props.invoice.number', $order->order_number)
        ->assertJsonPath('props.invoice.totals.total', format_currency(35));
});

test('the account pages are React pages with the customer data', function () {
    $user = customer();
    $order = zzOrder($user);
    $this->actingAs($user);

    $this->inertia('GET', '/account')->assertOk()->assertJsonPath('component', 'Storefront/Account/Dashboard')
        ->assertJsonPath('props.stats.orders', 1)->assertJsonPath('props.recentOrders.0.number', $order->order_number);
    $this->inertia('GET', '/account/orders')->assertJsonPath('component', 'Storefront/Account/Orders')
        ->assertJsonPath('props.orders.data.0.status.label', 'Shipped');
    $detail = $this->inertia('GET', "/account/orders/{$order->id}")->assertJsonPath('component', 'Storefront/Account/OrderDetail');
    expect($detail->json('props.order.totals.total'))->toBe(format_currency(35))
        ->and($detail->json('props.order.links.invoice'))->toBe(route('account.orders.invoice', $order))
        ->and($detail->json('props.order.address.lines'))->toContain('1 Test St');
    $this->inertia('GET', '/account/profile')->assertJsonPath('props.profile.email', $user->email);
    $this->inertia('GET', '/account/password')->assertJsonPath('component', 'Storefront/Account/Password');
});

test("another customer's order stays private", function () {
    $order = zzOrder(customer());
    $this->actingAs(customer());
    $this->inertia('GET', "/account/orders/{$order->id}")->assertForbidden();
});

test('profile and password updates', function () {
    $user = User::factory()->create(['password' => Hash::make('zz-old-pass-1')]);
    $this->actingAs($user);

    $this->withHeaders(['X-Inertia' => 'true'])->put('/account/profile', ['name' => 'Zz New Name', 'email' => $user->email, 'city' => 'Zz City'])
        ->assertRedirect()->assertSessionHas('success');
    $this->withHeaders(['X-Inertia' => 'true'])->put('/account/password', ['current_password' => 'wrong', 'password' => 'zz-new-pass-1', 'password_confirmation' => 'zz-new-pass-1'])
        ->assertSessionHasErrors('current_password');
    $this->withHeaders(['X-Inertia' => 'true'])->put('/account/password', ['current_password' => 'zz-old-pass-1', 'password' => 'zz-new-pass-1', 'password_confirmation' => 'zz-new-pass-1'])
        ->assertSessionHas('success');

    $user->refresh();
    expect($user->name)->toBe('Zz New Name')->and($user->city)->toBe('Zz City')->and(Hash::check('zz-new-pass-1', $user->password))->toBeTrue();
});

test('the wishlist page shows product cards and items can be removed', function () {
    module('wishlist', true);
    $user = customer();
    $product = Product::factory()->create();
    Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);
    $this->actingAs($user);

    $this->inertia('GET', '/account/wishlist')->assertJsonPath('component', 'Storefront/Account/Wishlist')
        ->assertJsonPath('props.items.data.0.id', $product->id);
    $this->deleteJson('/account/wishlist', ['product_id' => $product->id])->assertOk();
    expect(Wishlist::count())->toBe(0);
});

test('helpdesk tickets can be opened, read and answered', function () {
    $user = customer();
    $this->actingAs($user);
    $this->inertia('GET', '/helpdesk/create')->assertJsonPath('component', 'Storefront/Helpdesk/Create');

    $this->withHeaders(['X-Inertia' => 'true'])->post('/helpdesk', ['subject' => 'Zz help', 'description' => 'Zz details', 'category' => 'other', 'priority' => 'medium'])->assertRedirect();
    $ticket = HelpdeskTicket::firstOrFail();
    $this->withHeaders(['X-Inertia' => 'true'])->post("/helpdesk/{$ticket->id}/reply", ['message' => 'Zz follow up'])->assertRedirect();

    $page = $this->inertia('GET', "/helpdesk/{$ticket->id}")->assertJsonPath('component', 'Storefront/Helpdesk/Show');
    expect($page->json('props.ticket.subject'))->toBe('Zz help')
        ->and($page->json('props.ticket.replies.0.message'))->toBe('Zz follow up')
        ->and($page->json('props.ticket.canReply'))->toBeTrue();
    $this->inertia('GET', '/helpdesk')->assertJsonPath('props.tickets.data.0.id', $ticket->id);

    $this->actingAs(customer());
    $this->inertia('GET', "/helpdesk/{$ticket->id}")->assertForbidden();
});
