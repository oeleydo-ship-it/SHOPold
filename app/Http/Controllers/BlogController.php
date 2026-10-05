<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogCategory;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::published()->with(['author', 'category']);

        if ($request->filled('category')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        $posts = $query->latest('published_at')->paginate(setting('features.blog_posts_per_page', 10));
        $categories = BlogCategory::where('is_active', true)->withCount('publishedPosts')->get();

        return \Inertia\Inertia::render('Storefront/Blog/Index', $this->listProps($posts, $categories));
    }

    public function show($slug)
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->with(['author', 'category', 'approvedComments'])
            ->firstOrFail();

        // Increment views
        $post->increment('views');

        // Get related posts
        $relatedPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->when($post->category_id, function($q) use ($post) {
                $q->where('category_id', $post->category_id);
            })
            ->limit(3)
            ->get();

        return \Inertia\Inertia::render('Storefront/Blog/Show', [
            'post' => $this->card($post) + [
                'content' => safe_html($post->content),
                'views' => number_format($post->views),
                'longDate' => ($post->published_at ?? $post->created_at)?->format('F d, Y'),
                'publishedIso' => ($post->published_at ?? $post->created_at)?->toIso8601String(),
                'url' => route('blog.show', $post->slug),
            ],
            'related' => $relatedPosts->map(fn ($p) => $this->card($p))->values(),
            'comments' => [
                'open' => (bool) ($post->allow_comments && setting('features.blog_comments')),
                'list' => $post->approvedComments->map(fn ($c) => [
                    'id' => $c->id,
                    'author' => $c->user->name ?? $c->author_name ?? 'Guest',
                    'ago' => $c->created_at?->diffForHumans(),
                    'content' => (string) $c->content,
                ])->values(),
            ],
            'seo' => [
                'title' => $post->meta_title ?: $post->title,
                'description' => $post->meta_description ?: ($post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->content), 160)),
                'image' => $post->featured_image ? $post->image_url : null,
                'canonical' => route('blog.show', $post->slug),
                'type' => 'article',
            ],
        ]);
    }

    /**
     * Store a reader's comment. Comments are always held for moderation (status "pending").
     * Signed-in customers comment under their account; guests give a name and email.
     */
    public function storeComment(Request $request, $slug)
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        abort_unless($post->allow_comments && setting('features.blog_comments', true), 403, 'Comments are closed for this post.');

        // hidden "website" field: people never fill it in, spam bots usually do
        if (filled($request->input('website'))) {
            return back()->with('success', 'Thanks! Your comment is awaiting moderation.');
        }

        $rules = ['content' => 'required|string|min:2|max:2000'];
        if (!$request->user()) {
            $rules['author_name'] = 'required|string|max:100';
            $rules['author_email'] = 'required|email|max:255';
        }
        $data = $request->validate($rules);

        $post->comments()->create([
            'user_id' => $request->user()?->id,
            'author_name' => $request->user() ? null : strip_tags($data['author_name']),
            'author_email' => $request->user() ? null : $data['author_email'],
            'content' => strip_tags($data['content']),
            'status' => 'pending',
        ]);

        return redirect()->to(route('blog.show', $post->slug) . '#comments')
            ->with('success', 'Thanks! Your comment is awaiting moderation.');
    }

    public function category($slug)
    {
        $category = BlogCategory::where('slug', $slug)->where('is_active', true)->firstOrFail();
        
        $posts = BlogPost::published()
            ->where('category_id', $category->id)
            ->with(['author'])
            ->latest('published_at')
            ->paginate(setting('features.blog_posts_per_page', 10));

        $categories = BlogCategory::where('is_active', true)->withCount('publishedPosts')->get();

        return \Inertia\Inertia::render('Storefront/Blog/Index', $this->listProps($posts, $categories, $category));
    }

    /** Props for the React blog list (all posts, a search or one category). */
    protected function listProps($posts, $categories, ?BlogCategory $category = null): array
    {
        return [
            'posts' => $posts->through(fn ($p) => $this->card($p)),
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'count' => (int) $c->published_posts_count])->values(),
            'category' => $category ? ['id' => $category->id, 'name' => $category->name, 'description' => $category->description] : null,
            'search' => (string) request('search', ''),
            'newsletter' => (bool) setting('features.newsletter_enabled'),
            'seo' => [
                'title' => $category ? $category->name . ' - Blog' : 'Blog',
                'description' => $category?->description ?: 'Stay updated with the latest news, tips, and insights',
                'canonical' => $category ? route('blog.category', $category->slug) : route('blog.index'),
            ],
        ];
    }

    protected function card(BlogPost $p): array
    {
        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'title' => $p->title,
            'excerpt' => $p->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $p->content), 150),
            'image' => $p->featured_image ? $p->image_url : null,
            'author' => $p->author->name ?? 'Admin',
            'date' => ($p->published_at ?? $p->created_at)?->format('M d, Y'),
            'category' => $p->category ? ['name' => $p->category->name, 'slug' => $p->category->slug] : null,
        ];
    }
}
