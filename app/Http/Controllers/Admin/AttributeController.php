<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $query = Attribute::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $attributes = $query->withCount('values')->orderBy('sort_order')->paginate(15)->withQueryString()->through(fn ($a) => [
            'id' => $a->id,
            'name' => $a->name,
            'slug' => $a->slug,
            'type' => $a->type,
            'type_label' => $a->getTypeLabel(),
            'values_count' => $a->values_count,
            'is_visible' => (bool) $a->is_visible,
            'is_variation' => (bool) $a->is_variation,
            'is_global' => (bool) $a->is_global,
        ]);

        return Inertia::render('Admin/Attributes/Index', [
            'attributes' => $attributes,
            'filters' => $request->only('search', 'type'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Attributes/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:attributes',
            'type' => 'required|in:select,color,button,radio',
            'is_visible' => 'boolean',
            'is_variation' => 'boolean',
            'is_global' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_visible'] = $request->boolean('is_visible', true);
        $validated['is_variation'] = $request->boolean('is_variation', true);
        $validated['is_global'] = $request->boolean('is_global', true);

        $attribute = Attribute::create($validated);

        return redirect()->route('admin.attributes.edit', $attribute)->with('success', 'Attribute created. Now add values.');
    }

    public function edit(Attribute $attribute)
    {
        $attribute->load('values');

        return Inertia::render('Admin/Attributes/Form', [
            'attribute' => $attribute->only(['id', 'name', 'slug', 'type', 'is_visible', 'is_variation', 'is_global', 'sort_order']) + [
                'values' => $attribute->values->map(fn ($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                    'slug' => $v->slug,
                    'color_code' => $v->color_code,
                    'image' => $v->image ? asset('storage/' . $v->image) : null,
                    'sort_order' => $v->sort_order,
                ])->values(),
            ],
        ]);
    }

    public function update(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:attributes,slug,' . $attribute->id,
            'type' => 'required|in:select,color,button,radio',
            'is_visible' => 'boolean',
            'is_variation' => 'boolean',
            'is_global' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_visible'] = $request->boolean('is_visible', true);
        $validated['is_variation'] = $request->boolean('is_variation', true);
        $validated['is_global'] = $request->boolean('is_global', true);

        $attribute->update($validated);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute updated successfully.');
    }

    public function destroy(Attribute $attribute)
    {
        // Delete all associated values first
        foreach ($attribute->values as $value) {
            if ($value->image) {
                Storage::disk('public')->delete($value->image);
            }
        }

        $attribute->delete();

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute deleted successfully.');
    }

    // Attribute Values Management
    public function storeValue(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:7',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:1024',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['value']);
        $validated['attribute_id'] = $attribute->id;
        $validated['sort_order'] = $validated['sort_order'] ?? $attribute->values()->count();

        // Check for duplicate slug within this attribute
        $existingSlug = AttributeValue::where('attribute_id', $attribute->id)
            ->where('slug', $validated['slug'])
            ->exists();

        if ($existingSlug) {
            $validated['slug'] = $validated['slug'] . '-' . time();
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('attributes', 'public');
        }

        AttributeValue::create($validated);

        return back()->with('success', 'Value added successfully.');
    }

    public function updateValue(Request $request, AttributeValue $value)
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'color_code' => 'nullable|string|max:7',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:1024',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['value']);

        if ($request->hasFile('image')) {
            if ($value->image) {
                Storage::disk('public')->delete($value->image);
            }
            $validated['image'] = $request->file('image')->store('attributes', 'public');
        }

        $value->update($validated);

        return back()->with('success', 'Value updated successfully.');
    }

    public function destroyValue(AttributeValue $value)
    {
        if ($value->image) {
            Storage::disk('public')->delete($value->image);
        }

        $value->delete();

        return back()->with('success', 'Value deleted successfully.');
    }

    public function reorderValues(Request $request, Attribute $attribute)
    {
        $order = $request->input('order', []);

        foreach ($order as $index => $id) {
            AttributeValue::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    public function getValues(Attribute $attribute)
    {
        return response()->json($attribute->values);
    }
}
