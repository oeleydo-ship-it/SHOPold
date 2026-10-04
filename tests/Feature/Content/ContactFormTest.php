<?php

/*
 * The contact form lives inside the "contact-us" CMS page HTML. It must keep working in every theme:
 * the form survives sanitising and carries the visitor's real CSRF token.
 * (Laravel skips the CSRF check in tests, so the token itself is compared with the session's.)
 */

use App\Models\Page;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Page::create(['title' => 'Contact us', 'slug' => 'contact-us', 'template' => 'full-width', 'is_active' => true,
        'content' => '<form action="/contact" method="POST" class="mt-8"><input type="hidden" name="_token" value=""><input type="text" name="name" required class="w-full"><input type="email" name="email" required><textarea name="message" rows="5" required></textarea><button type="submit">Send</button></form>']);
});

test('the contact form shows with the session token and submits, in every theme', function (string $theme) {
    app(ThemeService::class)->setActive($theme);
    $html = $this->get('/contact')->assertOk()->getContent();

    expect($html)->toContain('<form action="/contact" method="POST" class="mt-8">')
        ->toContain('name="_token" value="' . session()->token() . '"')
        ->toContain('class="w-full"');

    $this->post('/contact', ['name' => 'Zz', 'email' => 'zz@example.test', 'subject' => 'Hi', 'message' => 'Hello'])->assertRedirect();
})->with(['default', 'modern', 'starter']);
