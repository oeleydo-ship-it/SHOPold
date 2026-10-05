<?php

/*
 * React storefront S6: blog, CMS pages and vendor store pages.
 */

use App\Models\{BlogPost, Page, Product};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    module('blog', true);
});

test('the blog list and a post are React pages with sanitised content', function () {
    $post = BlogPost::factory()->create(['title' => 'Zz Post', 'content' => '<p>Hello</p><script>alert(1)</script>']);
    $other = BlogPost::factory()->create();

    $list = $this->inertia('GET', '/blog')->assertOk()->assertJsonPath('component', 'Storefront/Blog/Index');
    expect(collect($list->json('props.posts.data'))->pluck('id')->all())->toContain($post->id, $other->id);

    $show = $this->inertia('GET', "/blog/{$post->slug}")->assertOk()->assertJsonPath('component', 'Storefront/Blog/Show');
    expect($show->json('props.post.content'))->toContain('<p>Hello</p>')->not->toContain('<script')
        ->and($show->json('props.seo.canonical'))->toBe(route('blog.show', $post->slug))
        ->and($post->fresh()->views)->toBe(1);
});

test('blog search and comments', function () {
    $this->setting('features.blog_comments', true, 'features');
    $post = BlogPost::factory()->create(['title' => 'Zz Findable']);
    BlogPost::factory()->create(['title' => 'Something else']);

    $found = $this->inertia('GET', '/blog?search=Findable')->json('props.posts.data');
    expect(collect($found)->pluck('id')->all())->toBe([$post->id]);

    $this->withHeaders(['X-Inertia' => 'true'])->post("/blog/{$post->slug}/comments", ['author_name' => 'Zz', 'author_email' => 'zz@example.test', 'content' => 'Nice post'])
        ->assertStatus(409)->assertHeader('X-Inertia-Redirect', route('blog.show', $post->slug) . '#comments') // Inertia keeps the #fragment
        ->assertSessionHas('success');
    expect($post->comments()->value('status'))->toBe('pending');
    expect($this->inertia('GET', "/blog/{$post->slug}")->json('props.comments.list'))->toBe([]); // held for moderation
});

test('CMS pages render with the template and safe HTML', function () {
    Page::create(['title' => 'Zz About', 'slug' => 'about-us', 'content' => '<h2>Us</h2><img src=x onerror=alert(1)>', 'template' => 'full-width', 'is_active' => true]);

    $page = $this->inertia('GET', '/about')->assertOk()->assertJsonPath('component', 'Storefront/Page');
    expect($page->json('props.page.template'))->toBe('full-width')
        ->and($page->json('props.page.content'))->toContain('<h2>Us</h2>')->not->toContain('onerror');
});

test('the vendor store page lists only that vendor\'s products', function () {
    module('multi_vendor', true);
    $vendor = makeVendor();
    $mine = Product::factory()->create(['vendor_id' => $vendor->id, 'quantity' => 3]);
    Product::factory()->create(['quantity' => 3]);

    $page = $this->inertia('GET', "/shop/vendor/{$vendor->slug}")->assertOk()->assertJsonPath('component', 'Storefront/Vendor');
    expect(collect($page->json('props.products.data'))->pluck('id')->all())->toBe([$mine->id])
        ->and($page->json('props.vendor.name'))->toBe($vendor->store_name);
});
