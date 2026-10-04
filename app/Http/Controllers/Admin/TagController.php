<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index(Request $request)
    {
        $query = Tag::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $tags = $query->withCount('products')->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Tags/Index', [
            'tags' => $tags->through(fn ($t) => $t->only(['id', 'name', 'slug', 'color', 'products_count'])),
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Tags/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags',
            'color' => 'nullable|string|max:7',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        Tag::create($validated);

        return redirect()->route('admin.tags.index')->with('success', 'Tag created successfully.');
    }

    public function edit(Tag $tag)
    {
        return Inertia::render('Admin/Tags/Form', ['tag' => $tag->only(['id', 'name', 'slug', 'color'])]);
    }

    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug,' . $tag->id,
            'color' => 'nullable|string|max:7',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        $tag->update($validated);

        return redirect()->route('admin.tags.index')->with('success', 'Tag updated successfully.');
    }

    public function destroy(Tag $tag)
    {
        $tag->products()->detach();
        $tag->delete();

        return redirect()->route('admin.tags.index')->with('success', 'Tag deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return back()->with('error', 'No tags selected.');
        }

        Tag::whereIn('id', $ids)->each(function ($tag) {
            $tag->products()->detach();
            $tag->delete();
        });

        return back()->with('success', count($ids) . ' tags deleted successfully.');
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        $tags = Tag::where('name', 'like', '%' . $query . '%')
            ->limit(10)
            ->get(['id', 'name', 'color']);

        return response()->json($tags);
    }
}
