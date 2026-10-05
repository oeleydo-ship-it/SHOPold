<?php

use App\Models\{BlogComment, BlogPost};
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    module('blog', true);
    $this->setting('features.blog_comments', true, 'features');
    $this->post = BlogPost::factory()->create();
    $this->url = "/blog/{$this->post->slug}/comments";
});

test('a guest comment is stored as pending with tags stripped', function () {
    $this->post($this->url, ['author_name' => 'Zz Guest', 'author_email' => 'guest@example.test', 'content' => '<b>Hi</b> <script>alert(1)</script>nice'])
        ->assertRedirect(route('blog.show', $this->post->slug) . '#comments');

    $c = BlogComment::first();
    expect($c->status)->toBe('pending')->and($c->user_id)->toBeNull()->and($c->content)->not->toContain('<')->and($c->content)->toContain('nice');
});

test('a customer comments under their account', function () {
    $user = customer();
    $this->actingAs($user)->post($this->url, ['content' => 'From a customer']);

    expect(BlogComment::first())->user_id->toBe($user->id)->author_email->toBeNull()->status->toBe('pending');
});

test('spam trap, missing fields, closed comments and floods are refused', function () {
    $this->post($this->url, ['author_name' => 'Bot', 'author_email' => 'bot@example.test', 'content' => 'buy', 'website' => 'http://spam.test']);
    $this->post($this->url, ['author_name' => '', 'author_email' => 'nope', 'content' => '']);
    expect(BlogComment::count())->toBe(0);

    $this->post->update(['allow_comments' => false]);
    $this->post($this->url, ['author_name' => 'A', 'author_email' => 'a@example.test', 'content' => 'closed?'])->assertForbidden();
    $this->post->update(['allow_comments' => true]);

    $statuses = collect(range(1, 7))->map(fn ($i) => $this->post($this->url, ['author_name' => 'F', 'author_email' => 'f@example.test', 'content' => "flood $i"])->status());
    expect($statuses)->toContain(429);
});

test('only approved comments are shown', function () {
    $this->post->comments()->create(['author_name' => 'Pending', 'author_email' => 'p@example.test', 'content' => 'zz pending text', 'status' => 'pending']);
    $this->post->comments()->create(['author_name' => 'Approved', 'author_email' => 'a@example.test', 'content' => 'zz approved <text>', 'status' => 'approved']);

    // sent as plain text (React escapes it), never as HTML
    $list = $this->inertia('GET', "/blog/{$this->post->slug}")->assertOk()->json('props.comments.list');
    expect(collect($list)->pluck('content')->all())->toBe(['zz approved <text>']);
});

test('editors moderate comments; managers cannot', function () {
    $c = $this->post->comments()->create(['author_name' => 'X', 'author_email' => 'x@example.test', 'content' => 'x', 'status' => 'pending']);

    $this->actingAs(adminUser('manager'));
    $this->inertia('PATCH', "/admin/blog/comments/{$c->id}", ['status' => 'approved'])->assertForbidden();

    $this->actingAs(adminUser('editor'));
    $this->inertia('PATCH', "/admin/blog/comments/{$c->id}", ['status' => 'approved']);
    expect($c->fresh()->status)->toBe('approved');
    $this->inertia('PATCH', "/admin/blog/comments/{$c->id}", ['status' => 'spam']);
    expect($c->fresh()->status)->toBe('spam');
    $this->inertia('PATCH', "/admin/blog/comments/{$c->id}", ['status' => 'rejected']); // not a valid status
    expect($c->fresh()->status)->toBe('spam');

    $this->inertia('GET', '/admin/blog')->assertJsonPath('props.posts.data.0.pending_comments', 0);
    $this->inertia('DELETE', "/admin/blog/comments/{$c->id}");
    expect(BlogComment::find($c->id))->toBeNull();
});
