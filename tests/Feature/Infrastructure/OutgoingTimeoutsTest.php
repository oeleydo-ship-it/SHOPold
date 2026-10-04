<?php

/*
 * Outgoing calls must give up before PHP's 30-second limit, otherwise a slow outside service turns a page into a
 * fatal error. These tests make every outgoing call fail and check the pages still answer.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('timeouts stay below the PHP limit', function () {
    $src = fn ($f) => file_get_contents(base_path($f));

    expect(substr_count($src('app/Services/UpdateService.php'), 'Http::connectTimeout(5)->timeout(15)'))->toBe(2)
        ->and(substr_count($src('app/Http/Controllers/Admin/SettingController.php'), '->connectTimeout(4)->timeout(8)'))->toBe(2);
    foreach (['app/Services/AiChatService.php' => 3, 'app/Services/AiFeatureService.php' => 1, 'app/Services/TranslationService.php' => 1, 'app/Http/Controllers/Admin/AiController.php' => 4] as $file => $calls) {
        expect(substr_count($src($file), '@set_time_limit(120)'))->toBe($calls);
    }
});

test('pages stay up when the update server is unreachable', function () {
    Http::fake(fn () => throw new ConnectionException('simulated timeout'));
    $this->actingAs(adminUser('super_admin'));

    $this->inertia('GET', '/admin/settings/updates')->assertOk();
});
