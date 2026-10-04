<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\DigitalFile;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DigitalFileController extends Controller
{
    protected function vendor()
    {
        return Auth::user()->vendor;
    }

    /**
     * Store a new digital file
     */
    public function store(Request $request, Product $product)
    {
        // Verify ownership
        if ($product->vendor_id !== $this->vendor()->id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        try {
            $rules = [
                'name' => 'required|string|max:255',
                'file_type' => 'required|in:upload,url',
                'download_limit' => 'nullable|integer|min:1',
                'download_expiry_days' => 'nullable|integer|min:1',
            ];

            // Add conditional validation based on file type
            if ($request->file_type === 'upload') {
                $rules['file'] = 'required|file|max:512000'; // 500MB max
            } else {
                $rules['file_url'] = 'required|url';
            }

            $request->validate($rules);

            $data = [
                'product_id' => $product->id,
                'name' => $request->name,
                'file_type' => $request->file_type,
                'download_limit' => $request->download_limit,
                'download_expiry_days' => $request->download_expiry_days,
                'sort_order' => $product->digitalFiles()->count(),
                'is_active' => true,
            ];

            if ($request->file_type === 'upload' && $request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('digital-products/' . $product->id, $fileName, 'local');
                
                if (!$path) {
                    throw new \Exception('Failed to store file. Please check storage permissions.');
                }
                
                $data['file_path'] = $path;
                $data['file_name'] = $file->getClientOriginalName();
                $data['file_extension'] = $file->getClientOriginalExtension();
                $data['file_size'] = $file->getSize();
            } elseif ($request->file_type === 'url') {
                $data['file_url'] = $request->file_url;
                $urlPath = parse_url($request->file_url, PHP_URL_PATH);
                if ($urlPath) {
                    $data['file_name'] = basename($urlPath);
                    $data['file_extension'] = pathinfo($urlPath, PATHINFO_EXTENSION);
                }
            }

            $digitalFile = DigitalFile::create($data);

            // Ensure product is marked as downloadable and virtual
            $product->update([
                'is_downloadable' => true,
                'is_virtual' => true,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'file' => $digitalFile,
                    'message' => 'Digital file added successfully.',
                ]);
            }

            return back()->with('success', 'Digital file added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Digital file upload error (vendor): ' . $e->getMessage());
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error uploading file: ' . $e->getMessage(),
                ], 500);
            }
            return back()->with('error', 'Error uploading file: ' . $e->getMessage());
        }
    }

    /**
     * Update a digital file
     */
    public function update(Request $request, Product $product, DigitalFile $file)
    {
        // Verify ownership
        if ($product->vendor_id !== $this->vendor()->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($file->product_id !== $product->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'download_limit' => 'nullable|integer|min:1',
            'download_expiry_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $file->update([
            'name' => $request->name,
            'download_limit' => $request->download_limit,
            'download_expiry_days' => $request->download_expiry_days,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'file' => $file,
                'message' => 'Digital file updated successfully.',
            ]);
        }

        return back()->with('success', 'Digital file updated successfully.');
    }

    /**
     * Delete a digital file
     */
    public function destroy(Request $request, Product $product, DigitalFile $file)
    {
        // Verify ownership
        if ($product->vendor_id !== $this->vendor()->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($file->product_id !== $product->id) {
            abort(403);
        }

        // Delete the actual file if it's a local upload
        if ($file->file_type === 'upload' && $file->file_path) {
            Storage::disk('local')->delete($file->file_path);
        }

        $file->delete();

        // Check if product still has digital files
        if ($product->digitalFiles()->count() === 0) {
            $product->update(['is_downloadable' => false]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Digital file deleted successfully.',
            ]);
        }

        return back()->with('success', 'Digital file deleted successfully.');
    }
}
