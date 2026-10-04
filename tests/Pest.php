<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/** An admin user with the given role: super_admin, manager or editor. */
function adminUser(string $role = 'super_admin'): \App\Models\User
{
    return \App\Models\User::factory()->admin($role)->create();
}

/** A regular, verified customer. */
function customer(): \App\Models\User
{
    return \App\Models\User::factory()->create();
}

/** Switch a module (Admin > Modules) on or off. */
function module(string $key, bool $on): void
{
    \App\Models\Setting::set(\App\Support\Modules::get($key)['toggle'], $on, 'boolean', 'features');
}

/** A vendor store (approved by default) owned by a fresh customer account. */
function makeVendor(string $status = 'approved'): \App\Models\Vendor
{
    $user = customer();
    $slug = 'store-' . $user->id;

    return \App\Models\Vendor::create(['user_id' => $user->id, 'store_name' => "Store {$user->id}", 'slug' => $slug, 'email' => "{$slug}@example.test", 'status' => $status, 'commission_rate' => 10]);
}
