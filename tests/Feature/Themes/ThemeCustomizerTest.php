<?php

use App\Models\Setting;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->themes = app(ThemeService::class);
});

test('the three bundled themes are found with their manifests', function () {
    $all = $this->themes->getAll();

    expect($all)->toHaveKeys(['default', 'modern', 'starter'])
        ->and($all['modern']['defaults']['primary_color'])->toBe('#7c3aed')
        ->and(array_keys($this->themes->fields('modern')))->not->toContain('footer_style'); // hidden by theme.json
});

test('only super admins manage themes', function () {
    $this->actingAs(adminUser('manager'));
    $this->inertia('GET', '/admin/themes')->assertForbidden();
    $this->inertia('POST', '/admin/themes/modern/activate')->assertForbidden();
    expect($this->themes->getActivated())->toBe('default');
});

test('customizer values are validated and saved per theme', function () {
    $this->actingAs(adminUser('super_admin'));

    $this->inertia('PUT', '/admin/themes/modern/customize', ['values' => [
        'primary_color' => '#112233', 'border_radius' => 'lg', 'sticky_header' => false, 'font_size' => 18, 'bogus' => 'x',
    ]]);
    $saved = $this->themes->savedOptions('modern');
    expect($saved)->toMatchArray(['primary_color' => '#112233', 'border_radius' => 'lg', 'sticky_header' => false, 'font_size' => 18])
        ->and($saved)->not->toHaveKey('bogus')
        ->and($this->themes->savedOptions('starter'))->toBe([]);

    // invalid values are refused and the saved ones kept
    $this->inertia('PUT', '/admin/themes/modern/customize', ['values' => [
        'primary_color' => 'red', 'border_radius' => 'giant', 'font_size' => 99, 'custom_css' => '</style><script>alert(1)</script>',
    ]]);
    expect($this->themes->savedOptions('modern'))->toMatchArray(['primary_color' => '#112233', 'border_radius' => 'lg', 'font_size' => 18])
        ->and($this->themes->savedOptions('modern'))->not->toHaveKey('custom_css');
});

test('the storefront follows the activated theme and its own customizer values', function () {
    $this->themes->setActive('modern');
    $this->themes->saveOptions('modern', ['primary_color' => '#112233']);
    $this->get('/')->assertOk()->assertSee("500: '#112233'", false);

    $this->themes->setActive('starter');
    Setting::overlay([]);
    $this->get('/')->assertOk()->assertSee("500: '#059669'", false)->assertDontSee('#112233', false);
});

test('a theme preview is visible only to the admin who started it', function () {
    $admin = adminUser('super_admin');
    $req = \Illuminate\Http\Request::create('/');
    $req->setLaravelSession($session = app('session')->driver());
    $session->put('theme_preview', 'modern');
    app()->instance('request', $req);

    $req->setUserResolver(fn () => $admin);
    expect($this->themes->getActive())->toBe('modern')->and($this->themes->getActivated())->toBe('default');

    $req->setUserResolver(fn () => null);
    expect($this->themes->getActive())->toBe('default');
});

test('unknown or path-like theme names are rejected', function () {
    $this->actingAs(adminUser('super_admin'));
    $this->inertia('GET', '/admin/themes/nope/customize')->assertNotFound();
    $this->inertia('GET', '/admin/themes/..%2Fadmin/customize')->assertNotFound();
});
