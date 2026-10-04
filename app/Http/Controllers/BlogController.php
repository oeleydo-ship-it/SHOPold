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

        return view('blog.index', compact('posts', 'categories'));
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

        return view('blog.show', compact('post', 'relatedPosts'));
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

        return view('blog.index', compact('posts', 'categories', 'category'));
    }
}
