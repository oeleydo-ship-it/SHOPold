<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Attribute;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    protected function vendor()
    {
        return Auth::user()->vendor;
    }

    protected function approvalRequired(): bool
    {
        return (bool) setting('features.vendor_products_approval', true);
    }

    /** Choices shared by the create and edit forms. */
    protected function formOptions(): array
    {
        $categories = [];
        foreach (Category::active()->with(['children' => fn ($q) => $q->where('is_active', true)])->whereNull('parent_id')->orderBy('name')->get() as $c) {
            $categories[] = ['id' => $c->id, 'name' => $c->name, 'depth' => 0];
            foreach ($c->children as $child) {
                $categories[] = ['id' => $child->id, 'name' => $child->name, 'depth' => 1];
            }
        }

        return [
            'categories' => $categories,
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'attributes' => Attribute::with('values')->orderBy('sort_order')->get()->map(fn ($a) => [
                'id' => $a->id, 'name' => $a->name,
                'values' => $a->values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value, 'color' => $a->type === 'color' ? $v->color_code : null])->values(),
            ])->values(),
            'approvalRequired' => $this->approvalRequired(),
            'maxUploadMb' => 500,
        ];
    }

    public function index(Request $request)
    {
        $vendor = $this->vendor();
        
        $query = Product::where('vendor_id', $vendor->id)
            ->with(['category', 'images'])
            ->withCount('variations');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('type')) {
            $query->where('product_type', $request->type);
        }

        $products = $query->latest()->paginate(15)->withQueryString();
        $categories = Category::active()->get(['id', 'name']);
        $approval = $this->approvalRequired();

        return Inertia::render('Vendor/Products/Index', [
            'products' => $products->through(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'type' => $p->product_type ?? 'simple', 'variations' => (int) $p->variations_count,
                'image' => $p->image_url, 'category' => $p->category?->name,
                'price' => format_currency($p->price), 'sale_price' => $p->sale_price ? format_currency($p->sale_price) : null,
                'stock' => (int) $p->quantity, 'low_stock' => (int) ($p->low_stock_threshold ?? 5),
                'is_active' => (bool) $p->is_active,
                'awaiting_approval' => !$p->is_active && $approval,
                'can_activate' => !$approval, // with approval on, only the store can (re)activate a product
            ]),
            'categories' => $categories,
            'filters' => $request->only(['search', 'status', 'type', 'category']),
            'approvalRequired' => $approval,
        ]);
    }

    public function create()
    {
        return Inertia::render('Vendor/Products/Form', $this->formOptions() + ['product' => null]);
    }

    public function store(Request $request)
    {
        $vendor = $this->vendor();

        $isDigital = $request->input('product_type') === 'digital';
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'product_type' => 'nullable|in:simple,variable,digital',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_start_date' => 'nullable|date',
            'sale_end_date' => 'nullable|date|after_or_equal:sale_start_date',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|unique:products,sku',
            'barcode' => 'nullable|string|max:100',
            'quantity' => $isDigital ? 'nullable|integer|min:0' : 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'images.*' => 'image|max:2048',
            'new_digital_files' => 'nullable|array',
            'new_digital_files.*.name' => 'nullable|string|max:255',
            'new_digital_files.*.file_type' => 'nullable|in:upload,url',
            'new_digital_files.*.file' => 'nullable|file|max:512000',
            'new_digital_files.*.file_url' => 'nullable|url',
        ]);

        // Check if products need approval
        $needsApproval = setting('features.vendor_products_approval', true);

        // Handle product features
        $features = [];
        if ($request->has('features')) {
            $features = array_filter($request->features, function($feature) {
                return !empty($feature['name']) && !empty($feature['value']);
            });
            $features = array_values($features);
        }

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(5),
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'sale_start_date' => $request->sale_start_date,
            'sale_end_date' => $request->sale_end_date,
            'cost_price' => $request->cost_price,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'features' => $features,
            'sku' => $request->sku ?? strtoupper(Str::random(8)),
            'barcode' => $request->barcode,
            'quantity' => $isDigital ? 0 : $request->quantity,
            'low_stock_threshold' => $request->low_stock_threshold ?? 5,
            'manage_stock' => $isDigital ? false : $request->boolean('manage_stock', true),
            'weight' => $request->weight,
            'dimensions' => $request->dimensions,
            'is_active' => !$needsApproval,
            'is_featured' => false, // featuring products is the store's decision
            'is_virtual' => $isDigital ? true : $request->boolean('is_virtual', false),
            'is_downloadable' => $isDigital,
            'product_type' => $request->product_type ?? 'simple',
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
        ]);

        // Handle images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $path,
                    'sort_order' => $index,
                    'is_primary' => $index === 0,
                ]);
                
                // Set first image as main product image
                if ($index === 0) {
                    $product->update(['image' => $path]);
                }
            }
        }

        // Sync attribute values for variable products
        if ($request->input('product_type') === 'variable' && $request->has('attribute_values')) {
            $allValueIds = collect($request->attribute_values)->flatten()->filter()->toArray();
            $product->attributeValues()->sync($allValueIds);
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

        $message = $needsApproval 
            ? 'Product created successfully. It will be visible after admin approval.'
            : 'Product created successfully!';

        return redirect()->route('vendor.products.index')->with('success', $message);
    }

    public function edit(Product $product)
    {
        // Ensure vendor can only edit their own products
        if ($product->vendor_id !== $this->vendor()->id) {
            abort(403, 'Unauthorized action.');
        }

        $categories = Category::active()->with('children')->whereNull('parent_id')->get();
        $brands = Brand::orderBy('name')->get();
        $attributes = Attribute::with('values')->orderBy('sort_order')->get();
        $product->load(['images', 'variations.attributeValues.attribute', 'attributeValues', 'digitalFiles']);

        unset($categories, $brands, $attributes); // the form options come from formOptions()
        $product->load(['images', 'digitalFiles', 'attributeValues', 'variations' => fn ($q) => $q->orderBy('sort_order')]);
        $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d') : null;

        return Inertia::render('Vendor/Products/Form', $this->formOptions() + ['product' => [
            'id' => $product->id, 'url' => route('shop.show', $product->slug), 'is_active' => (bool) $product->is_active,
            'product_type' => $product->product_type ?? 'simple', 'name' => $product->name, 'description' => $product->description,
            'short_description' => $product->short_description, 'category_id' => $product->category_id, 'brand_id' => $product->brand_id,
            'price' => $product->price, 'sale_price' => $product->sale_price, 'sale_start_date' => $date($product->sale_start_date), 'sale_end_date' => $date($product->sale_end_date),
            'cost_price' => $product->cost_price, 'sku' => $product->sku, 'barcode' => $product->barcode, 'quantity' => $product->quantity,
            'low_stock_threshold' => $product->low_stock_threshold, 'manage_stock' => (bool) ($product->manage_stock ?? true),
            'weight' => $product->weight, 'dimensions' => $product->dimensions,
            'meta_title' => $product->meta_title, 'meta_description' => $product->meta_description, 'meta_keywords' => $product->meta_keywords,
            'features' => array_values(array_filter((array) ($product->features ?? []), fn ($f) => is_array($f))),
            'attribute_values' => (object) $product->attributeValues->groupBy('attribute_id')->map(fn ($g) => $g->pluck('id')->values())->all(),
            'images' => $product->images->sortBy('sort_order')->map(fn ($i) => ['id' => $i->id, 'url' => $i->image_url, 'primary' => (bool) $i->is_primary || $i->image === $product->image])->values(),
            'digital_files' => $product->digitalFiles->map(fn ($d) => [
                'id' => $d->id, 'name' => $d->name, 'file_type' => $d->file_type, 'file_name' => $d->file_name, 'file_url' => $d->file_url,
                'size' => $d->file_size ? $d->formatted_size : null, 'download_limit' => $d->download_limit, 'download_expiry_days' => $d->download_expiry_days,
            ])->values(),
            'variations' => $product->variations->map(fn ($v) => [
                'id' => $v->id, 'sku' => $v->sku, 'name' => $v->variation_name ?? $v->name, 'quantity' => (int) $v->quantity, 'is_active' => (bool) $v->is_active,
                'price' => $v->price !== null ? format_currency($v->price) : null,
            ])->values(),
        ]]);
    }

    public function update(Request $request, Product $product)
    {
        // Ensure vendor can only update their own products
        if ($product->vendor_id !== $this->vendor()->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'product_type' => 'nullable|in:simple,variable,digital',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'sale_start_date' => 'nullable|date',
            'sale_end_date' => 'nullable|date|after_or_equal:sale_start_date',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'barcode' => 'nullable|string|max:100',
            'quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'images.*' => 'image|max:2048',
            'new_digital_files' => 'nullable|array',
            'new_digital_files.*.name' => 'nullable|string|max:255',
            'new_digital_files.*.file_type' => 'nullable|in:upload,url',
            'new_digital_files.*.file' => 'nullable|file|max:512000',
            'new_digital_files.*.file_url' => 'nullable|url',
            'delete_digital_files' => 'nullable|array',
            'delete_digital_files.*' => 'integer',
        ]);

        $isDigital = $request->input('product_type') === 'digital';
        
        // Handle product features
        $features = [];
        if ($request->has('features')) {
            $features = array_filter($request->features, function($feature) {
                return !empty($feature['name']) && !empty($feature['value']);
            });
            $features = array_values($features);
        }
        
        $data = [
            'name' => $request->name,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'sale_start_date' => $request->sale_start_date,
            'sale_end_date' => $request->sale_end_date,
            'cost_price' => $request->cost_price,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'features' => $features,
            'sku' => $request->sku,
            'barcode' => $request->barcode,
            'quantity' => $isDigital ? 0 : ($request->quantity ?? $product->quantity),
            'low_stock_threshold' => $request->low_stock_threshold ?? 5,
            'manage_stock' => $isDigital ? false : $request->boolean('manage_stock', true),
            'weight' => $request->weight,
            'dimensions' => $request->dimensions,
            'is_virtual' => $isDigital ? true : $request->boolean('is_virtual', false),
            'product_type' => $request->input('product_type', 'simple'),
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
        ];

        $product->update($data);

        // Sync attribute values for variable products
        if ($request->input('product_type') === 'variable' && $request->has('attribute_values')) {
            $allValueIds = collect($request->attribute_values)->flatten()->filter()->toArray();
            $product->attributeValues()->sync($allValueIds);
        }

        // Handle new images
        if ($request->hasFile('images')) {
            $maxSort = $product->images()->max('sort_order') ?? -1;
            $hasPrimaryImage = $product->image || $product->images()->where('is_primary', true)->exists();
            
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                $isPrimary = !$hasPrimaryImage && $index === 0;
                
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $path,
                    'sort_order' => $maxSort + $index + 1,
                    'is_primary' => $isPrimary,
                ]);
                
                // Set first uploaded image as main product image if product doesn't have one
                if ($isPrimary) {
                    $product->update(['image' => $path]);
                    $hasPrimaryImage = true;
                }
            }
        }

        // Handle image deletions
        if ($request->has('delete_images')) {
            $deletedPrimary = false;
            foreach ($request->delete_images as $imageId) {
                $img = ProductImage::find($imageId);
                if ($img && $img->product_id === $product->id) {
                    if ($img->is_primary || $product->image === $img->image) {
                        $deletedPrimary = true;
                    }
                    Storage::disk('public')->delete($img->image);
                    $img->delete();
                }
            }
            
            // If primary was deleted, set new primary from remaining images
            if ($deletedPrimary) {
                $firstImage = $product->images()->orderBy('sort_order')->first();
                if ($firstImage) {
                    $firstImage->update(['is_primary' => true]);
                    $product->update(['image' => $firstImage->image]);
                } else {
                    $product->update(['image' => null]);
                }
            }
        }
        
        // Ensure product has main image set from ProductImage if available
        if (!$product->image) {
            $primaryImage = $product->images()->where('is_primary', true)->first() 
                          ?? $product->images()->orderBy('sort_order')->first();
            if ($primaryImage) {
                $product->update(['image' => $primaryImage->image]);
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

        return redirect()->route('vendor.products.edit', $product)->with('success', 'Product updated successfully!');
    }

    public function destroy(Product $product)
    {
        // Ensure vendor can only delete their own products
        if ($product->vendor_id !== $this->vendor()->id) {
            abort(403, 'Unauthorized action.');
        }

        // Delete main image
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        // Delete gallery images
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image);
        }

        // Delete variation images
        foreach ($product->variations as $variation) {
            if ($variation->image) {
                Storage::disk('public')->delete($variation->image);
            }
        }

        $product->delete();

        return redirect()->route('vendor.products.index')->with('success', 'Product deleted successfully!');
    }

    public function toggleStatus(Product $product)
    {
        // Ensure vendor can only toggle their own products
        if ($product->vendor_id !== $this->vendor()->id) {
            abort(403, 'Unauthorized action.');
        }

        // With product approval switched on, publishing is the store's decision: a vendor can hide a product
        // but not make it visible again (otherwise the approval step could be skipped).
        if (!$product->is_active && $this->approvalRequired()) {
            return back()->with('error', 'This product needs approval from the store before it can be shown.');
        }

        $product->update([
            'is_active' => !$product->is_active
        ]);

        return back()->with('success', $product->is_active ? 'Product is now visible in the store.' : 'Product hidden from the store.');
    }

    /**
     * Store a new variation
     */
    public function storeVariation(Request $request, Product $product)
    {
        // Ensure vendor can only add variations to their own products
        if ($product->vendor_id !== $this->vendor()->id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

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
        // Ensure vendor can only edit variations of their own products
        if ($product->vendor_id !== $this->vendor()->id) {
            abort(403, 'Unauthorized action.');
        }

        $variation = $product->variations()->with('attributeValues.attribute')->find($variationId);
        
        if (!$variation) {
            return redirect()->route('vendor.products.edit', $product)
                ->with('error', 'Variation not found. It may have been deleted.');
        }
        
        $attributes = Attribute::with('values')->orderBy('sort_order')->get();
        
        return Inertia::render('Vendor/Products/VariationEdit', [
            'product' => ['id' => $product->id, 'name' => $product->name],
            'variation' => [
                'id' => $variation->id, 'sku' => $variation->sku, 'price' => $variation->price, 'sale_price' => $variation->sale_price,
                'quantity' => (int) $variation->quantity, 'weight' => $variation->weight, 'is_active' => (bool) $variation->is_active,
                'name' => $variation->variation_name ?? $variation->name,
                'values' => $variation->attributeValues->mapWithKeys(fn ($v) => [$v->attribute_id => $v->id]),
            ],
            'attributes' => $attributes->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'values' => $a->values->map(fn ($v) => ['id' => $v->id, 'value' => $v->value])->values()])->values(),
        ]);
    }

    /**
     * Update a variation
     */
    public function updateVariation(Request $request, Product $product, $variationId)
    {
        // Ensure vendor can only update variations of their own products
        if ($product->vendor_id !== $this->vendor()->id) {
            abort(403, 'Unauthorized action.');
        }

        $variation = $product->variations()->findOrFail($variationId);
        
        $validated = $request->validate([
            'sku' => 'required|string|unique:product_variations,sku,' . $variation->id,
            'price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'quantity' => 'integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'attribute_values' => 'nullable|array',
        ]);

        $variation->update([
            'sku' => $validated['sku'],
            'price' => ($validated['price'] ?? null) ?: null,
            'sale_price' => ($validated['sale_price'] ?? null) ?: null,
            'quantity' => $validated['quantity'] ?? 0,
            'weight' => $validated['weight'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Sync attribute values
        if ($request->has('attribute_values')) {
            $valueIds = collect($request->attribute_values)->flatten()->filter()->toArray();
            if (method_exists($variation, 'syncAttributeValues')) {
                $variation->syncAttributeValues($valueIds);
            } else {
                $variation->attributeValues()->sync($valueIds);
            }
            
            // Update variation name based on attribute values
            if (method_exists($variation, 'updateVariationName')) {
                $variation->updateVariationName();
            }
        }

        // Ensure product type stays as variable
        if ($product->product_type !== 'variable') {
            $product->update(['product_type' => 'variable']);
        }

        return redirect()->route('vendor.products.edit', $product)->with('success', 'Variation updated successfully.');
    }

    /**
     * Delete a variation
     */
    public function destroyVariation(Request $request, Product $product, $variationId)
    {
        // Ensure vendor can only delete variations of their own products
        if ($product->vendor_id !== $this->vendor()->id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

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
}
