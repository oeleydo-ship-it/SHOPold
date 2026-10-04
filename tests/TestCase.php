<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Settings and the theme overlay are kept in static properties; start every test from a clean slate.
        Setting::overlay([]);
        Setting::clearCache();
    }

    protected function tearDown(): void
    {
        Setting::overlay([]);
        Setting::clearCache();

        parent::tearDown();
    }

    /** Store a setting the way the admin does (also clears the settings cache). */
    protected function setting(string $key, mixed $value, string $group = 'general'): static
    {
        $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string');
        Setting::set($key, $value, $type, $group);

        return $this;
    }

    /** Make an Inertia (XHR) request so the response is the page JSON instead of the HTML shell. */
    protected function inertia(string $method, string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        $version = (string) (new \App\Http\Middleware\HandleInertiaRequests())->version(request());

        return $this->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest', 'X-Inertia-Version' => $version])
            ->json($method, $uri, $data);
    }
}
