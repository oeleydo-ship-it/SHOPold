<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tag;
use App\Models\Attribute;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'vendor'])->withCount('variations');

        if ($request->search) {
            $term = '%' . $request->search . '%';
            // Grouped so the OR does not escape the category / status / vendor filters below.
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('sku', 'like', $term);
            });
        }

        if ($request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        if ($request->vendor !== null && $request->vendor !== '') {
            if ($request->vendor === 'admin') {
                $query->whereNull('vendor_id');
            } else {
                $query->where('vendor_id', $request->vendor);
            }
        }

        $products = $query->latest()->paginate(15)->withQueryString()->through(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'slug' => $p->slug,
            'image' => $p->image ? $p->image_url : null,
            'category' => $p->category?->name,
            'type' => $p->product_type,
            'variations' => $p->variations_count,
            'quantity' => $p->quantity,
            'is_active' => (bool) $p->is_active,
            'price' => format_currency($p->price),
            'sale_price' => $p->sale_price ? format_currency($p->sale_price) : null,
            'vendor' => $p->vendor ? [
                'id' => $p->vendor->id,
                'name' => $p->vendor->store_name,
                'logo' => $p->vendor->logo ? $p->vendor->logo_url : null,
            ] : null,
        ]);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => $request->only('search', 'category', 'status', 'vendor'),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'vendors' => \App\Models\Vendor::orderBy('store_name')->get(['id', 'store_name']),
        ]);
    }

    /**
     * Lookup data shared by the create and edit forms.
     */
    private function formOptions(): array
    {
        $languages = setting('store.available_languages', ['en' => 'English']);
        if (is_string($languages)) {
            $languages = json_decode($languages, true) ?? ['en' => 'English'];
        }

        return [
            'categories' => Category::active()->get(['id', 'name']),
            'brands' => Brand::active()->orderBy('name')->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'color']),
            'attributes' => Attribute::with('values')->orderBy('sort_order')->get()->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => $a->type,
                'type_label' => $a->getTypeLabel(),
                'values' => $a->values->map(fn ($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                    'color_code' => $v->color_code,
                ])->values(),
            ]),
            'options' => [
                'digitalProducts' => (bool) setting('features.digital_products'),
                'hasAiKey' => (bool) (setting('ai.api_key') || setting('features.ai_api_key')),
                'languages' => $languages,
                'defaultLocale' => setting('store.default_language', 'en'),
            ],
        ];
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Create', $this->formOptions());
    }

    public function store(Request $request)
    {
        $isDigital = $request->input('product_type') === 'digital';
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_start_date' => 'nullable|date',
            'sale_end_date' => 'nullable|date|after_or_equal:sale_start_date',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'required|string|unique:products',
            'barcode' => 'nullable|string|max:100',
            'quantity' => $isDigital ? 'nullable|integer|min:0' : 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'image' => 'nullable',
            'gallery' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_virtual' => 'boolean',
            'manage_stock' => 'boolean',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'new_digital_files' => 'nullable|array',
            'new_digital_files.*.name' => 'nullable|string|max:255',
            'new_digital_files.*.file_type' => 'nullable|in:upload,url',
            'new_digital_files.*.file' => 'nullable|file|max:512000',
            'new_digital_files.*.file_url' => 'nullable|url',
        ]);

        $data = $request->except(['image', 'gallery', 'gallery_present', 'tags', 'new_digital_files', 'features', 'attribute_values', 'attributes']);
        $data['slug'] = Str::slug($request->name) . '-' . Str::random(5);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['is_virtual'] = $isDigital ? true : $request->boolean('is_virtual', false);
        $data['manage_stock'] = $isDigital ? false : $request->boolean('manage_stock', true);
        $data['product_type'] = $request->input('product_type', 'simple');
        $data['is_downloadable'] = $isDigital;
        
        // Handle product features
        if ($request->has('features')) {
            $features = array_filter($request->features, function($feature) {
                return !empty($feature['name']) && !empty($feature['value']);
            });
            $data['features'] = array_values($features);
        }
        
        // Digital products don't need inventory tracking
        if ($isDigital) {
            $data['quantity'] = 0;
            $data['manage_stock'] = false;
        }

        // Handle main image - can be file upload or media path
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->filled('image') && is_string($request->image)) {
            $data['image'] = $request->image;
        }

        // Handle gallery - store as JSON array of paths
        if ($request->has('gallery') && is_array($request->gallery)) {
            $galleryPaths = [];
            foreach ($request->gallery as $item) {
                if (is_string($item)) {
                    $galleryPaths[] = $item;
                }
            }
            $data['gallery'] = $galleryPaths;
        }

        $product = Product::create($data);

        // Sync tags
        if ($request->has('tags')) {
            $product->tags()->sync($request->tags);
        }

        // Attribute values picked for variable products
        if ($request->input('product_type') === 'variable' && is_array($request->attribute_values)) {
            $syncData = [];
            foreach ($request->attribute_values as $attributeId => $valueIds) {
                foreach ((array) $valueIds as $valueId) {
                    if ($valueId) {
                        $syncData[$valueId] = ['attribute_id' => $attributeId];
                    }
                }
            }
            $product->attributeValues()->sync($syncData);
        }

        // Handle digital files for digital products
        if ($isDigital && $request->has('new_digital_files')) {
            foreach ($request->new_digital_files as $index => $fileData) {
                if (empty($fileData['name'])) continue;
                
                $digitalFileData = [
                    'product_id' => $product->id,
                    'name' => $fileData['name'],
                    'file_type' => $fileData['file_type'] ?? 'url',
                    'download_limit' => $fileData['download_limit'] ?? null,
                    'download_expiry_days' => $fileData['download_expiry_days'] ?? null,
                    'sort_order' => $index,
                    'is_active' => true,
                ];

                if ($fileData['file_type'] === 'upload' && $request->hasFile("new_digital_files.{$index}.file")) {
                    $file = $request->file("new_digital_files.{$index}.file");
                    $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('digital-products/' . $product->id, $fileName, 'local');
                    
                    $digitalFileData['file_path'] = $path;
                    $digitalFileData['file_name'] = $file->getClientOriginalName();
                    $digitalFileData['file_extension'] = $file->getClientOriginalExtension();
                    $digitalFileData['file_size'] = $file->getSize();
                } elseif ($fileData['file_type'] === 'url' && !empty($fileData['file_url'])) {
                    $digitalFileData['file_url'] = $fileData['file_url'];
                    $urlPath = parse_url($fileData['file_url'], PHP_URL_PATH);
                    if ($urlPath) {
                        $digitalFileData['file_name'] = basename($urlPath);
                        $digitalFileData['file_extension'] = pathinfo($urlPath, PATHINFO_EXTENSION);
                    }
                } else {
                    continue; // Skip if no file or URL provided
                }

                \App\Models\DigitalFile::create($digitalFileData);
            }
        }

        // Log initial inventory
        if ($product->quantity > 0) {
            InventoryLog::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'add',
                'quantity_before' => 0,
                'quantity_change' => $product->quantity,
                'quantity_after' => $product->quantity,
                'note' => 'Initial stock on product creation',
            ]);
        }

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product created successfully!')
            ->with('view_product_url', route('shop.show', $product->slug));
    }

    public function edit(Product $product)
    {
        $product->load(['tags', 'variations.attributeValues.attribute', 'attributeValues', 'digitalFiles', 'translations']);
        $date = fn ($d) => $d?->format('Y-m-d\TH:i');

        return Inertia::render('Admin/Products/Edit', $this->formOptions() + [
            'product' => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'product_type' => $product->product_type ?: 'simple',
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'cost_price' => $product->cost_price,
                'sale_start_date' => $date($product->sale_start_date),
                'sale_end_date' => $date($product->sale_end_date),
                'quantity' => $product->quantity,
                'low_stock_threshold' => $product->low_stock_threshold ?? 5,
                'manage_stock' => (bool) $product->manage_stock,
                'is_active' => (bool) $product->is_active,
                'is_featured' => (bool) $product->is_featured,
                'is_virtual' => (bool) $product->is_virtual,
                'is_downloadable' => (bool) $product->is_downloadable,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'meta_keywords' => $product->meta_keywords,
                'image' => $product->image,
                'gallery' => $product->gallery ?? [],
                'features' => $product->features ?? [],
                'tags' => $product->tags->pluck('id'),
                'attribute_values' => $product->attributeValues
                    ->groupBy('attribute_id')
                    ->map(fn ($g) => $g->pluck('id')->values()),
                'cross_sell' => $product->cross_sell_products_list->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'image' => $p->image_url,
                    'price' => $p->current_price,
                ])->values(),
                'translations' => $product->translations->keyBy('locale')->map(fn ($t) => $t->only([
                    'name', 'short_description', 'description', 'meta_title', 'meta_keywords', 'meta_description',
                ])),
                'variations' => $product->variations->map(fn ($v, $i) => [
                    'id' => $v->id,
                    'name' => $v->variation_name ?: 'Variation #' . ($i + 1),
                    'sku' => $v->sku,
                    'price' => format_currency($v->price ?? $product->price),
                    'sale_price' => $v->sale_price ? format_currency($v->sale_price) : null,
                    'quantity' => $v->quantity,
                    'is_active' => (bool) $v->is_active,
                    'attributes' => $v->attributeValues->map(fn ($av) => $av->attribute->name . ': ' . $av->value)->values(),
                ]),
                'digital_files' => $product->digitalFiles->map(fn ($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'file_type' => $f->file_type,
                    'file_name' => $f->file_name,
                    'file_url' => $f->file_url ? Str::limit($f->file_url, 50) : null,
                    'size' => $f->file_type === 'upload' ? $f->formatted_size : null,
                    'is_active' => (bool) $f->is_active,
                ]),
            ],
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'product_type' => 'nullable|in:simple,variable,digital',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_start_date' => 'nullable|date',
            'sale_end_date' => 'nullable|date|after_or_equal:sale_start_date',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:100',
            'quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'image' => 'nullable',
            'gallery' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_virtual' => 'boolean',
            'manage_stock' => 'boolean',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'attributes' => 'nullable|array',
            'attribute_values' => 'nullable|array',
            'cross_sell_products' => 'nullable|array',
            'cross_sell_products.*' => 'integer|exists:products,id',
            'new_digital_files' => 'nullable|array',
            'new_digital_files.*.name' => 'nullable|string|max:255',
            'new_digital_files.*.file_type' => 'nullable|in:upload,url',
            'new_digital_files.*.file' => 'nullable|file|max:512000',
            'new_digital_files.*.file_url' => 'nullable|url',
            'delete_digital_files' => 'nullable|array',
            'delete_digital_files.*' => 'integer',
        ]);

        $oldQuantity = $product->quantity;
        $isDigital = $request->input('product_type') === 'digital';
        
        $data = $request->except(['image', 'gallery', '_token', '_method', 'delete_images', 'gallery_present', 'tags', 'attributes', 'attribute_values', 'cross_sell_products', 'features']);
        $data['is_active'] = $request->boolean('is_active', false);
        $data['is_featured'] = $request->boolean('is_featured', false);
        $data['is_virtual'] = $isDigital ? true : $request->boolean('is_virtual', false);
        $data['manage_stock'] = $isDigital ? false : $request->boolean('manage_stock', true);
        $data['product_type'] = $request->input('product_type', 'simple');
        
        // Handle product features
        if ($request->has('features')) {
            $features = array_filter($request->features, function($feature) {
                return !empty($feature['name']) && !empty($feature['value']);
            });
            $data['features'] = array_values($features);
        } else {
            $data['features'] = [];
        }
        
        // For variable products, quantity is managed per variation
        if ($data['product_type'] === 'variable') {
            $data['quantity'] = $data['quantity'] ?? 0;
        }
        
        // Digital products don't need inventory tracking
        if ($isDigital) {
            $data['quantity'] = 0;
            $data['manage_stock'] = false;
        }

        // Handle main image - can be file upload or media path
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        } elseif ($request->filled('image') && is_string($request->image)) {
            $data['image'] = $request->image;
        }

        // Handle gallery - store as JSON array of paths. The React form sends gallery_present so
        // that removing every image (which sends no gallery fields) still clears the gallery.
        if (($request->has('gallery') && is_array($request->gallery)) || $request->boolean('gallery_present')) {
            $galleryPaths = [];
            foreach ((array) $request->gallery as $item) {
                if (is_string($item)) {
                    $galleryPaths[] = $item;
                }
            }
            $data['gallery'] = $galleryPaths;
        }

        // Handle cross-sell products
        if ($request->has('cross_sell_products')) {
            $crossSellIds = array_filter($request->cross_sell_products, function ($id) use ($product) {
                return $id != $product->id; // Exclude current product
            });
            $data['cross_sell_products'] = array_values(array_map('intval', $crossSellIds));
        } else {
            $data['cross_sell_products'] = [];
        }

        $product->update($data);

        // Sync tags
        $product->tags()->sync($request->tags ?? []);

        // Sync attribute values for variable products
        if ($request->input('product_type') === 'variable' && $request->has('attribute_values')) {
            $syncData = [];
            foreach ($request->attribute_values as $attributeId => $valueIds) {
                if (is_array($valueIds)) {
                    foreach ($valueIds as $valueId) {
                        if ($valueId) {
                            $syncData[$valueId] = ['attribute_id' => $attributeId];
                        }
                    }
                }
            }
            $product->attributeValues()->sync($syncData);
        }

        // Log inventory change if quantity changed (only for simple products)
        $newQuantity = $request->quantity ?? 0;
        if ($request->input('product_type') === 'simple' && $oldQuantity != $newQuantity) {
            InventoryLog::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => 'adjust',
                'quantity_before' => $oldQuantity,
                'quantity_change' => $newQuantity - $oldQuantity,
                'quantity_after' => $newQuantity,
                'note' => 'Manual stock adjustment',
            ]);
        }

        // Handle translations
        if ($request->has('translations')) {
            foreach ($request->translations as $locale => $translationData) {
                // Only save if at least one field has content
                $hasContent = collect($translationData)->filter(fn($v) => !empty($v))->isNotEmpty();
                if ($hasContent) {
                    $product->translations()->updateOrCreate(
                        ['locale' => $locale],
                        array_filter($translationData)
                    );
                }
            }
        }

        // Handle digital files deletion
        if ($request->has('delete_digital_files')) {
            foreach ($request->delete_digital_files as $fileId) {
                $digitalFile = $product->digitalFiles()->find($fileId);
                if ($digitalFile) {
                    if ($digitalFile->file_type === 'upload' && $digitalFile->file_path) {
                        Storage::disk('local')->delete($digitalFile->file_path);
                    }
                    $digitalFile->delete();
                }
            }
        }

        // Handle new digital files
        if ($request->has('new_digital_files')) {
            foreach ($request->new_digital_files as $index => $fileData) {
                if (empty($fileData['name'])) continue;
                
                $digitalFileData = [
                    'product_id' => $product->id,
                    'name' => $fileData['name'],
                    'file_type' => $fileData['file_type'] ?? 'url',
                    'download_limit' => $fileData['download_limit'] ?? null,
                    'download_expiry_days' => $fileData['download_expiry_days'] ?? null,
                    'sort_order' => $product->digitalFiles()->count(),
                    'is_active' => true,
                ];

                if ($fileData['file_type'] === 'upload' && $request->hasFile("new_digital_files.{$index}.file")) {
                    $file = $request->file("new_digital_files.{$index}.file");
                    $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('digital-products/' . $product->id, $fileName, 'local');
                    
                    $digitalFileData['file_path'] = $path;
                    $digitalFileData['file_name'] = $file->getClientOriginalName();
                    $digitalFileData['file_extension'] = $file->getClientOriginalExtension();
                    $digitalFileData['file_size'] = $file->getSize();
                } elseif ($fileData['file_type'] === 'url' && !empty($fileData['file_url'])) {
                    $digitalFileData['file_url'] = $fileData['file_url'];
                    $urlPath = parse_url($fileData['file_url'], PHP_URL_PATH);
                    if ($urlPath) {
                        $digitalFileData['file_name'] = basename($urlPath);
                        $digitalFileData['file_extension'] = pathinfo($urlPath, PATHINFO_EXTENSION);
                    }
                } else {
                    continue; // Skip if no file or URL provided
                }

                \App\Models\DigitalFile::create($digitalFileData);
            }

            // Mark product as downloadable if it has digital files
            if ($product->digitalFiles()->count() > 0) {
                $product->update(['is_downloadable' => true, 'is_virtual' => true]);
            }
        }

        // Update downloadable status based on remaining files
        if ($product->digitalFiles()->count() === 0 && $product->product_type !== 'digital') {
            $product->update(['is_downloadable' => false]);
        }

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product updated successfully!')
            ->with('view_product_url', route('shop.show', $product->slug));
    }

    public function destroy(Product $product)
    {
        // Delete main image
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        
        // Delete gallery images
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image);
        }
        
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully!');
    }

    /**
     * Delete a single gallery image
     */
    public function deleteImage(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            abort(403);
        }

        Storage::disk('public')->delete($image->image);
        $image->delete();

        return back()->with('success', 'Image deleted successfully!');
    }

    /**
     * Store a new variation
     */
    public function storeVariation(Request $request, Product $product)
    {
        try {
            $validated = $request->validate([
                'sku' => 'required|string|unique:product_variations,sku',
                'price' => 'nullable|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'quantity' => 'nullable|integer|min:0',
            ]);

            $variation = $product->variations()->create([
                'sku' => $validated['sku'],
                'price' => !empty($validated['price']) ? $validated['price'] : null,
                'sale_price' => !empty($validated['sale_price']) ? $validated['sale_price'] : null,
                'quantity' => $validated['quantity'] ?? 0,
                'is_active' => true,
                'sort_order' => $product->variations()->count(),
            ]);

            // Ensure product type is set to variable when adding variations
            if ($product->product_type !== 'variable') {
                $product->update(['product_type' => 'variable']);
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'variation' => $variation]);
            }

            return back()->with('success', 'Variation created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->errors()], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            throw $e;
        }
    }

    /**
     * Edit variation form
     */
    public function editVariation(Product $product, $variationId)
    {
        $variation = $product->variations()->with('attributeValues.attribute')->find($variationId);
        
        if (!$variation) {
            return redirect()->route('admin.products.edit', $product)
                ->with('error', 'Variation not found. It may have been deleted.');
        }
        
        $attributes = Attribute::with('values')->orderBy('sort_order')->get();
        
        return Inertia::render('Admin/Products/VariationEdit', [
            'product' => ['id' => $product->id, 'name' => $product->name, 'image' => $product->image_url],
            'variation' => [
                'id' => $variation->id, 'sku' => $variation->sku, 'price' => $variation->price, 'sale_price' => $variation->sale_price,
                'quantity' => (int) $variation->quantity, 'weight' => $variation->weight, 'is_active' => (bool) $variation->is_active,
                'name' => $variation->variation_name ?? $variation->name,
                'image' => $variation->image,
                'image_url' => $variation->image ? asset('storage/' . $variation->image) : null,
                'values' => $variation->attributeValues->mapWithKeys(fn ($v) => [$v->attribute_id => $v->id]),
            ],
            'attributes' => $attributes->map(fn ($a) => [
                'id' => $a->id, 'name' => $a->name,
                'values' => $a->values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value])->values(),
            ])->values(),
        ]);
    }

    /**
     * Update a variation
     */
    public function updateVariation(Request $request, Product $product, $variationId)
    {
        $variation = $product->variations()->findOrFail($variationId);
        
        $validated = $request->validate([
            'sku' => 'required|string|unique:product_variations,sku,' . $variation->id,
            'price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'quantity' => 'integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            // an uploaded file, or a path picked from the media library (relative to the public disk, no "..")
            'image' => ['nullable', function ($attr, $value, $fail) use ($request) {
                if ($request->hasFile('image')) {
                    if (!$request->file('image')->isValid() || !str_starts_with((string) $request->file('image')->getMimeType(), 'image/')) {
                        $fail('The image must be an image file.');
                    }
                } elseif (!is_string($value) || !preg_match('#^[A-Za-z0-9_\-][A-Za-z0-9_\-./]*$#', $value) || str_contains($value, '..')) {
                    $fail('Pick the image from the media library.');
                }
            }],
            'remove_image' => 'nullable|boolean',
            'attribute_values' => 'nullable|array',
        ]);

        $updateData = [
            'sku' => $validated['sku'],
            'price' => ($validated['price'] ?? null) ?: null,
            'sale_price' => ($validated['sale_price'] ?? null) ?: null,
            'quantity' => $validated['quantity'] ?? 0,
            'weight' => ($validated['weight'] ?? null) ?: null,
            'is_active' => $request->boolean('is_active', true),
        ];

        // Handle image - can be file upload or media path
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($variation->image) {
                Storage::disk('public')->delete($variation->image);
            }
            $updateData['image'] = $request->file('image')->store('products/variations', 'public');
        } elseif ($request->filled('image') && is_string($request->image)) {
            $updateData['image'] = $request->image;
        } elseif ($request->boolean('remove_image')) {
            $updateData['image'] = null; // media-library files are shared, so only the link is removed
        }

        $variation->update($updateData);

        // Sync attribute values using the helper method
        if ($request->has('attribute_values')) {
            $valueIds = collect($request->attribute_values)->flatten()->filter()->toArray();
            $variation->syncAttributeValues($valueIds);
            
            // Update variation name based on attribute values
            $variation->updateVariationName();
        }

        // Ensure product type stays as variable
        if ($product->product_type !== 'variable') {
            $product->update(['product_type' => 'variable']);
        }

        return redirect()->route('admin.products.edit', $product)->with('success', 'Variation updated successfully.');
    }

    /**
     * Delete a variation
     */
    public function destroyVariation(Request $request, Product $product, $variationId)
    {
        $variation = $product->variations()->findOrFail($variationId);
        
        if ($variation->image) {
            Storage::disk('public')->delete($variation->image);
        }
        
        $variation->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Variation deleted successfully.']);
        }

        return back()->with('success', 'Variation deleted successfully.');
    }

    /**
     * Search products for cross-sell selector
     * Returns JSON array of matching active products
     */
    public function searchProducts(Request $request)
    {
        $query = $request->get('q', '');
        $exclude = $request->get('exclude');

        $products = Product::active()
            ->when($query, function ($q) use ($query) {
                $q->where(function ($subQuery) use ($query) {
                    $subQuery->where('name', 'like', '%' . $query . '%')
                        ->orWhere('sku', 'like', '%' . $query . '%');
                });
            })
            ->when($exclude, function ($q) use ($exclude) {
                $q->where('id', '!=', $exclude);
            })
            ->select(['id', 'name', 'image', 'price', 'sale_price', 'sale_start_date', 'sale_end_date'])
            ->limit(20)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->image_url,
                    'price' => $product->current_price,
                ];
            });

        return response()->json($products);
    }
}
