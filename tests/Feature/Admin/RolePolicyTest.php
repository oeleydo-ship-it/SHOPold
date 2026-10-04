<?php

/*
 * Admin access policy. Who may reach each /admin route is written down here independently of routes/web.php,
 * so a route added to the wrong group (or to none) fails this test.
 *
 *  open        every admin (dashboard, catalogue, media picker/upload, AI generate)
 *  manager     manager + super admin (orders, invoices, shipping, users, reports, helpdesk, drivers...)
 *  editor      editor + super admin (pages, blog, media library)
 *  super_admin settings, vendors, payouts, loyalty, translations, themes, modules
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function adminPolicy(string $name): string
{
    $n = preg_replace('/^admin\./', '', $name);
    $starts = fn (array $p) => (bool) array_filter($p, fn ($x) => str_starts_with($n, $x));

    if (in_array($n, ['settings.shipping', 'settings.update'], true)) return 'manager';
    if ($starts(['settings.', 'vendors.', 'payouts.', 'loyalty.', 'translations.', 'themes.', 'modules.']) || $n === 'ai.test') return 'super_admin';
    if (in_array($n, ['media.picker', 'media.json', 'media.store', 'ai.generate', 'dashboard'], true)) return 'open';
    if ($starts(['media.', 'pages.', 'blog.'])) return 'editor';
    if ($starts(['orders.', 'transactions.', 'invoices.', 'shipping-labels.', 'shipping.', 'delivery.', 'coupons.', 'newsletters.', 'reports.', 'helpdesk.', 'ai-conversations.', 'drivers.', 'users.'])) return 'manager';
    if ($starts(['products.', 'categories.', 'brands.', 'tags.', 'attributes.', 'stock.', 'reviews.'])) return 'open';

    return 'UNCLASSIFIED';
}

test('every admin route is in the role group its policy says', function () {
    $mismatches = [];
    $unclassified = [];

    foreach (Route::getRoutes() as $route) {
        if (!str_starts_with($route->uri(), 'admin') || !$route->getName()) {
            continue;
        }
        $want = adminPolicy($route->getName());
        if ($want === 'UNCLASSIFIED') {
            $unclassified[] = $route->getName();
            continue;
        }
        $gates = array_values(array_filter($route->gatherMiddleware(), fn ($m) => is_string($m) && str_starts_with($m, 'admin.role:')));
        $actual = $gates ? substr($gates[0], strlen('admin.role:')) : 'open';
        if ($actual !== $want) {
            $mismatches[] = "{$route->getName()}: route={$actual} policy={$want}";
        }
    }

    expect($unclassified)->toBe([], 'new admin routes must be added to adminPolicy()');
    expect($mismatches)->toBe([]);
});

test('every admin route requires a signed-in admin', function () {
    $unprotected = collect(Route::getRoutes())
        ->filter(fn ($r) => str_starts_with($r->uri(), 'admin') && $r->getName())
        ->reject(fn ($r) => in_array('admin', $r->gatherMiddleware(), true) && in_array('auth', $r->gatherMiddleware(), true))
        ->map(fn ($r) => $r->getName())->values()->all();

    expect($unprotected)->toBe([]);
});

test('roles reach exactly the pages their group allows', function (string $role, string $uri, int $status) {
    $this->actingAs(adminUser($role));
    $this->inertia('GET', $uri)->assertStatus($status);
})->with([
    ['editor', '/admin', 200], ['manager', '/admin', 200],
    ['editor', '/admin/products', 200], ['manager', '/admin/products', 200],
    ['editor', '/admin/orders', 403], ['manager', '/admin/orders', 200],
    ['editor', '/admin/users', 403], ['manager', '/admin/users', 200],
    ['manager', '/admin/pages', 403], ['editor', '/admin/pages', 200],
    ['manager', '/admin/media', 403], ['editor', '/admin/media', 200],
    ['manager', '/admin/settings', 403], ['editor', '/admin/settings', 403], ['super_admin', '/admin/settings', 200],
    ['manager', '/admin/settings/shipping', 200], ['editor', '/admin/settings/shipping', 403],
    ['manager', '/admin/themes', 403], ['super_admin', '/admin/themes', 200],
    ['manager', '/admin/modules', 403], ['super_admin', '/admin/modules', 200],
]);

test('customers and guests cannot open the admin', function () {
    $this->get('/admin')->assertRedirect();                       // guests: to the login page
    $this->actingAs(customer())->get('/admin')->assertRedirect();  // customers: sent back to the storefront
    $this->actingAs(customer())->get('/admin/settings')->assertRedirect();
});

test('a manager cannot create a super admin or promote themselves', function () {
    $manager = adminUser('manager');
    $this->actingAs($manager);

    $this->inertia('POST', '/admin/users', [
        'name' => 'Escalation', 'email' => 'escalate@example.test', 'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        'is_admin' => true, 'admin_role' => 'super_admin',
    ])->assertStatus(403);
    expect(User::where('email', 'escalate@example.test')->exists())->toBeFalse();

    $this->inertia('PUT', "/admin/users/{$manager->id}", ['name' => $manager->name, 'email' => $manager->email, 'is_admin' => true, 'admin_role' => 'super_admin']);
    expect($manager->fresh()->admin_role)->toBe('manager');
});

test('a manager may only change shipping settings', function () {
    $this->actingAs(adminUser('manager'));

    $this->inertia('POST', '/admin/settings', ['_group' => 'payment', 'payment_currency' => 'EUR']);
    expect(setting('payment.currency'))->not->toBe('EUR');

    $this->inertia('POST', '/admin/settings', ['_group' => 'shipping', 'shipping_flat_rate' => '7.5']);
    expect((string) \App\Models\Setting::getFresh('shipping.flat_rate'))->toBe('7.5');
});
