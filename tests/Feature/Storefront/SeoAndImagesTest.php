<?php

/*
 * S8: sitemap.xml, robots.txt and the resized-image endpoint used by product cards.
 * Images are written to a fake public disk only.
 */

use App\Models\{BlogPost, Category, Product};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Cache, Storage};

uses(RefreshDatabase::class);

test('the sitemap lists public pages and leaves out inactive ones', function () {
    Cache::forget('seo.sitemap');
    module('blog', true);
    $cat = Category::factory()->create(['slug' => 'zz-sitemap-cat']);
    $live = Product::factory()->create(['category_id' => $cat->id, 'slug' => 'zz-live-product']);
    Product::factory()->create(['slug' => 'zz-hidden-product', 'is_active' => false]);
    $post = BlogPost::factory()->create();

    $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

    expect($xml)->toContain(route('shop.show', $live->slug))
        ->toContain(route('shop.category', $cat->slug))
        ->toContain(route('blog.show', $post->slug))
        ->not->toContain('zz-hidden-product');
    expect(simplexml_load_string($xml))->not->toBeFalse();
});

test('robots.txt keeps crawlers out of private areas and points to the sitemap', function () {
    $txt = $this->get('/robots.txt')->assertOk()->getContent();
    expect($txt)->toContain('Disallow: /admin')->toContain('Disallow: /checkout')->toContain('Sitemap: ' . url('sitemap.xml'));
});

test('images are resized to WebP once and served with long caching', function () {
    Storage::fake('public');
    $img = imagecreatetruecolor(1200, 800);
    ob_start();
    imagejpeg($img);
    Storage::disk('public')->put('products/zz photo.jpg', ob_get_clean());

    $url = \App\Http\Controllers\ImageController::url('products/zz photo.jpg', 480);
    expect($url)->toBe(url('img/480/products/zz%20photo.jpg'));

    $res = $this->get($url)->assertOk();
    expect($res->headers->get('Cache-Control'))->toContain('max-age=2592000')
        ->and($res->headers->get('Set-Cookie'))->toBeNull();
    $thumb = Storage::disk('public')->path('thumbs/480/products/zz photo.jpg.webp');
    expect(is_file($thumb))->toBeTrue()->and(getimagesize($thumb)[0])->toBe(480);
});

test('the image endpoint only serves allowed widths and files inside public storage', function () {
    Storage::fake('public');
    Storage::disk('public')->put('a.jpg', 'not really an image');

    $this->get('/img/333/a.jpg')->assertNotFound();          // width not offered
    $this->get('/img/480/../../.env')->assertNotFound();     // outside public storage
    $this->get('/img/480/..%2F..%2F.env')->assertNotFound();
    $this->get('/img/480/missing.jpg')->assertNotFound();
    $this->get('/img/480/a.txt')->assertNotFound();
});
