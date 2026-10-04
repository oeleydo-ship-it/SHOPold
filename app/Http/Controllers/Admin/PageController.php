<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::with('parent')
            ->orderBy('menu_order')
            ->orderBy('title')
            ->paginate(20);
            
        return Inertia::render('Admin/Pages/Index', [
            'pages' => $pages->through(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'image' => $p->featured_image ? $p->featured_image_url : null,
                'parent' => $p->parent?->title,
                'template' => ucfirst($p->template),
                'is_active' => (bool) $p->is_active,
                'show_in_menu' => (bool) $p->show_in_menu,
                'url' => route('page.show', $p->slug),
            ]),
        ]);
    }

    public function create()
    {
        $templates = Page::getTemplates();
        $parentPages = Page::parentPages()->active()->get();
        
        return Inertia::render('Admin/Pages/Form', [
            'templates' => $templates,
            'parents' => $parentPages->map(fn ($p) => ['id' => $p->id, 'title' => $p->title])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|max:2048',
            'template' => 'required|string',
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'menu_order' => 'nullable|integer',
            'parent_id' => 'nullable|exists:pages,id',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('pages', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_in_menu'] = $request->boolean('show_in_menu');

        Page::create($validated);

        return redirect()->route('admin.pages.index')->with('success', 'Page created successfully!');
    }

    public function edit(Page $page)
    {
        $templates = Page::getTemplates();
        $parentPages = Page::parentPages()->where('id', '!=', $page->id)->active()->get();
        
        return Inertia::render('Admin/Pages/Form', [
            'templates' => $templates,
            'parents' => $parentPages->map(fn ($p) => ['id' => $p->id, 'title' => $p->title])->values(),
            'page' => [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'excerpt' => $page->excerpt,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'meta_keywords' => $page->meta_keywords,
            'template' => $page->template,
            'is_active' => (bool) $page->is_active,
            'show_in_menu' => (bool) $page->show_in_menu,
            'menu_order' => $page->menu_order,
            'parent_id' => $page->parent_id,
            'image' => $page->featured_image ? $page->featured_image_url : null,
                'url' => route('page.show', $page->slug),
            ],
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:pages,slug,' . $page->id,
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|max:2048',
            'template' => 'required|string',
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'menu_order' => 'nullable|integer',
            'parent_id' => 'nullable|exists:pages,id',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        if ($request->hasFile('featured_image')) {
            // Delete old image
            if ($page->featured_image) {
                Storage::disk('public')->delete($page->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')->store('pages', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_in_menu'] = $request->boolean('show_in_menu');

        $page->update($validated);

        return redirect()->route('admin.pages.index')->with('success', 'Page updated successfully!');
    }

    public function destroy(Page $page)
    {
        if ($page->featured_image) {
            Storage::disk('public')->delete($page->featured_image);
        }
        
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted successfully!');
    }
}
