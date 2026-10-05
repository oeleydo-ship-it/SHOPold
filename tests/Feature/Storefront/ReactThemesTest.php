<?php

/*
 * React storefront S7: themes. The theme's slug reaches the page (it selects the theme skin and component
 * overrides), and the admin customizer's live preview shows unsaved values on the React storefront.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the active theme reaches the React storefront', function () {
    app(\App\Services\ThemeService::class)->setActive('starter');

    expect($this->inertia('GET', '/')->json('props.sf.theme.slug'))->toBe('starter');
});

test('the customizer preview shows unsaved values on the React storefront, for that admin only', function () {
    $admin = adminUser('super_admin');
    $this->actingAs($admin)->postJson('/admin/themes/modern/draft', ['values' => ['primary_color' => '#123456']])->assertOk();

    expect($this->get('/')->assertOk()->getContent())->toContain('#123456') // theme colours in the page head
        ->and($this->inertia('GET', '/')->json('props.sf.theme.slug'))->toBe('modern');

    $this->actingAs(customer());
    expect($this->get('/')->getContent())->not->toContain('#123456');
});
