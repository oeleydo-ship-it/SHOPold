<?php

use App\Models\User;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Mail, Notification};

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Notification::fake();
});

test('login and register are React pages in every theme', function (string $theme) {
    app(ThemeService::class)->setActive($theme);

    $this->inertia('GET', '/login')->assertOk()->assertJsonPath('component', 'Storefront/Auth/Login')->assertJsonPath('props.allowRegistration', true);
    $this->inertia('GET', '/register')->assertOk()->assertJsonPath('component', 'Storefront/Auth/Register');
})->with(['default', 'modern', 'starter']);

test('wrong password is refused and the right one signs in', function () {
    $user = User::factory()->create(['password' => Hash::make('Correct-horse-1')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-one']);
    $this->assertGuest();

    $this->post('/login', ['email' => $user->email, 'password' => 'Correct-horse-1'])->assertRedirect();
    $this->assertAuthenticatedAs($user);

    $this->get('/login')->assertRedirect(); // already signed in
});

test('registering creates a customer account', function () {
    $this->post('/register', ['name' => 'Zz New', 'email' => 'zz-new@example.test', 'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!'])->assertRedirect();

    expect(User::where('email', 'zz-new@example.test')->first())->not->toBeNull()->is_admin->toBeFalsy();
});
