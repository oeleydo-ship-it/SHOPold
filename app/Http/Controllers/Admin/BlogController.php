<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::with(['author', 'category'])->withCount(['comments as pending_comments' => fn ($q) => $q->where('status', 'pending')]);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $posts = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Blog/Index', [
            'posts' => $posts->through(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'excerpt' => $p->excerpt ? Str::limit($p->excerpt, 90) : null,
                'category' => $p->category?->name,
                'author' => $p->author?->name,
                'status' => $p->status,
                'views' => (int) $p->views,
                'pending_comments' => (int) $p->pending_comments,
                'date' => ($p->published_at ?? $p->created_at)->format('M d, Y'),
                'url' => route('blog.show', $p->slug),
            ]),
            'categories' => BlogCategory::all(['id', 'name']),
            'filters' => $request->only('search', 'status', 'category'),
        ]);
    }

    public function create()
    {
        $categories = BlogCategory::where('is_active', true)->get();
        return Inertia::render('Admin/Blog/Form', [
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'featured_image' => 'nullable|string',
            'status' => 'required|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'allow_comments' => 'boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);
        $validated['author_id'] = auth()->id();
        $validated['allow_comments'] = $request->boolean('allow_comments');

        if ($validated['status'] === 'published' && !$validated['published_at']) {
            $validated['published_at'] = now();
        }

        BlogPost::create($validated);

        return redirect()->route('admin.blog.index')->with('success', 'Post created successfully!');
    }

    public function show(BlogPost $blog)
    {
        $blog->load(['author', 'category', 'comments']);
        return Inertia::render('Admin/Blog/Show', [
            'post' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'excerpt' => $blog->excerpt,
                'content' => $blog->content,
                'status' => $blog->status,
                'views' => (int) $blog->views,
                'allow_comments' => (bool) $blog->allow_comments,
                'meta_title' => $blog->meta_title,
                'meta_description' => $blog->meta_description,
                'image' => $blog->featured_image ? asset('storage/' . $blog->featured_image) : null,
                'author' => $blog->author?->name,
                'category' => $blog->category?->name,
                'created_at' => $blog->created_at->format('M d, Y'),
                'updated_at' => $blog->updated_at->format('M d, Y'),
                'published_at' => $blog->published_at?->format('M d, Y'),
                'url' => route('blog.show', $blog->slug),
                'comments' => $blog->comments->map(fn ($c) => [
                    'id' => $c->id,
                    'author' => $c->author_name ?? $c->user?->name ?? 'Anonymous',
                    'email' => $c->author_email ?? $c->user?->email,
                    'status' => $c->status,
                    'content' => $c->content,
                    'ago' => $c->created_at->diffForHumans(),
                ])->values(),
            ],
        ]);
    }

    public function edit(BlogPost $blog)
    {
        $categories = BlogCategory::where('is_active', true)->get();
        return Inertia::render('Admin/Blog/Form', [
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'post' => [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'excerpt' => $blog->excerpt,
                'content' => $blog->content,
                'category_id' => $blog->category_id,
                'featured_image' => $blog->featured_image,
                'status' => $blog->status,
                'published_at' => $blog->published_at?->format('Y-m-d\TH:i'),
                'meta_title' => $blog->meta_title,
                'meta_description' => $blog->meta_description,
                'allow_comments' => (bool) $blog->allow_comments,
            ],
        ]);
    }

    public function update(Request $request, BlogPost $blog)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug,' . $blog->id,
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:blog_categories,id',
            'featured_image' => 'nullable|string',
            'status' => 'required|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'allow_comments' => 'boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);
        $validated['allow_comments'] = $request->boolean('allow_comments');

        if ($validated['status'] === 'published' && !$blog->published_at && !$validated['published_at']) {
            $validated['published_at'] = now();
        }

        $blog->update($validated);

        return redirect()->route('admin.blog.index')->with('success', 'Post updated successfully!');
    }

    public function destroy(BlogPost $blog)
    {
        $blog->delete();
        return redirect()->route('admin.blog.index')->with('success', 'Post deleted successfully!');
    }

    // Categories
    public function categories()
    {
        $categories = BlogCategory::withCount('posts')->get();
        return Inertia::render('Admin/Blog/Categories', [
            'categories' => $categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'is_active' => (bool) $c->is_active,
                'posts_count' => $c->posts_count,
            ])->values(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_categories',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        BlogCategory::create($validated);

        return redirect()->route('admin.blog.categories')->with('success', 'Category created!');
    }

    public function updateCategory(Request $request, BlogCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return redirect()->route('admin.blog.categories')->with('success', 'Category updated!');
    }

    public function destroyCategory(BlogCategory $category)
    {
        $category->delete();
        return redirect()->route('admin.blog.categories')->with('success', 'Category deleted!');
    }

    /** Approve, mark as spam (hidden) or re-queue a reader comment. The column allows pending / approved / spam. */
    public function updateComment(Request $request, \App\Models\BlogComment $comment)
    {
        $data = $request->validate(['status' => 'required|in:approved,spam,pending']);
        $comment->update(['status' => $data['status']]);

        return back()->with('success', 'Comment ' . ($data['status'] === 'approved' ? 'approved.' : ($data['status'] === 'spam' ? 'marked as spam.' : 'moved back to pending.')));
    }

    public function destroyComment(\App\Models\BlogComment $comment)
    {
        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }
}
