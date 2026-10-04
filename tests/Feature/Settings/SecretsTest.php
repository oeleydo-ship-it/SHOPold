<?php

use App\Models\Setting;
use App\Support\Secrets;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('credential settings are stored encrypted and read back decrypted', function () {
    $this->actingAs(adminUser('super_admin'));

    $this->inertia('POST', '/admin/settings', ['_group' => 'payment', 'payment_stripe_secret_key' => 'sk_test_zz_fake_value_123']);

    $raw = Setting::where('key', 'payment.stripe_secret_key')->value('value');
    expect($raw)->not->toBe('sk_test_zz_fake_value_123')
        ->and(Secrets::isEncrypted($raw))->toBeTrue()
        ->and(Secrets::decrypt($raw))->toBe('sk_test_zz_fake_value_123');
});

test('the payment settings page never sends secret values to the browser', function () {
    Setting::set('payment.stripe_secret_key', Secrets::encrypt('sk_test_zz_never_shown'), 'string', 'payment');
    $this->actingAs(adminUser('super_admin'));

    $json = $this->inertia('GET', '/admin/settings/payment')->assertOk()->getContent();

    expect($json)->not->toContain('sk_test_zz_never_shown');
});

test('every credential key is covered by Secrets::KEYS', function () {
    expect(Secrets::KEYS)->toContain('payment.stripe_secret_key', 'ai.api_key');
    foreach (Secrets::KEYS as $key) {
        expect(Secrets::isSecret($key))->toBeTrue();
    }
});
