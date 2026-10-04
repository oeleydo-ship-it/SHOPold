<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')->withCount('products')->paginate(15)->through(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'image' => $c->image ? asset('storage/' . $c->image) : null,
            'parent' => $c->parent?->name,
            'products_count' => $c->products_count,
            'is_active' => (bool) $c->is_active,
        ]);

        return Inertia::render('Admin/Categories/Index', ['categories' => $categories]);
    }

    public function create()
    {
        return Inertia::render('Admin/Categories/Form', [
            'parents' => Category::whereNull('parent_id')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $request->except('image');
        $data['slug'] = Str::slug($request->name);
        $data['is_active'] = $request->boolean('is_active', true);

        // Check for unique slug
        $slug = $data['slug'];
        $count = 1;
        while (Category::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $slug . '-' . $count++;
        }

        // Handle image - either from media picker (string path) or file upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        } elseif ($request->filled('image') && is_string($request->input('image'))) {
            // Image path from media picker
            $data['image'] = $request->input('image');
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully!');
    }

    public function edit(Category $category)
    {
        return Inertia::render('Admin/Categories/Form', [
            'parents' => Category::whereNull('parent_id')->where('id', '!=', $category->id)->get(['id', 'name']),
            'category' => $category->only(['id', 'name', 'parent_id', 'description', 'image', 'sort_order', 'is_active']),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $request->except(['image', '_token', '_method']);
        $data['is_active'] = $request->boolean('is_active', false);

        // Handle image - either from media picker (string path) or file upload
        if ($request->hasFile('image')) {
            // Delete old image if exists and it's not from media library
            if ($category->image && str_starts_with($category->image, 'categories/')) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        } elseif ($request->filled('image') && is_string($request->input('image'))) {
            // Image path from media picker - only update if different
            $newImage = $request->input('image');
            if ($newImage !== $category->image) {
                // Delete old image if it was uploaded directly (not from media library)
                if ($category->image && str_starts_with($category->image, 'categories/')) {
                    Storage::disk('public')->delete($category->image);
                }
                $data['image'] = $newImage;
            }
        } elseif (!$request->has('image') || $request->input('image') === '' || $request->input('image') === null) {
            // Image was removed
            if ($category->image && str_starts_with($category->image, 'categories/')) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = null;
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with products. Please reassign or delete products first.');
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully!');
    }
}
