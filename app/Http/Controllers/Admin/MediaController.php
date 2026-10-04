<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::query()->orderBy('created_at', 'desc');

        if ($request->filled('collection')) {
            $query->where('collection', $request->collection);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            if ($request->type === 'images') {
                $query->where('mime_type', 'like', 'image/%');
            }
        }

        $media = $query->paginate(24);
        $collections = Media::distinct()->pluck('collection');

        // Inertia visits also send X-Requested-With, so they must be told apart before the JSON branch
        if (!$request->header('X-Inertia') && ($request->ajax() || $request->wantsJson())) {
            return response()->json([
                'media' => $media,
                'collections' => $collections,
            ]);
        }

        return Inertia::render('Admin/Media/Index', [
            'media' => [
                'data' => $media->getCollection()->map(fn (Media $m) => [
                    'id' => $m->id, 'name' => $m->name, 'file_name' => $m->file_name, 'url' => $m->url,
                    'thumb' => $m->thumbnail_url, 'mime_type' => $m->mime_type, 'size' => $m->human_readable_size,
                    'collection' => $m->collection, 'created_at' => $m->created_at?->format('M d, Y'),
                ])->values(),
                'from' => $media->firstItem(), 'to' => $media->lastItem(), 'total' => $media->total(),
                'links' => $media->linkCollection()->toArray(),
            ],
            'collections' => $collections->filter()->values(),
            'filters' => ['search' => (string) $request->search, 'collection' => (string) $request->collection],
        ]);
    }

    public function store(Request $request)
    {
        // Ensure clean output for JSON responses
        if ($request->ajax() || $request->wantsJson()) {
            ob_start();
        }
        
        try {
            $request->validate([
                'file' => 'required|file|max:10240|mimes:jpeg,jpg,png,gif,webp,svg,ico', // 10MB max, images only
                'collection' => 'nullable|string|max:50',
            ]);

            $file = $request->file('file');
            
            if (!$file || !$file->isValid()) {
                throw new \Exception('Invalid file upload. The file may be too large or corrupted.');
            }
            
            $collection = $request->input('collection', 'default');
            
            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('media/' . $collection, $filename, 'public');

            if (!$path) {
                throw new \Exception('Failed to store file. Please check storage permissions.');
            }

            $media = Media::create([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'path' => $path,
                'disk' => 'public',
                'size' => $file->getSize(),
                'collection' => $collection,
                'uploaded_by' => auth()->id(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                ob_end_clean(); // Clear any buffered output
                return response()->json([
                    'success' => true,
                    'media' => $media,
                    'message' => 'File uploaded successfully',
                ]);
            }

            return redirect()->route('admin.media.index')
                ->with('success', 'File uploaded successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                ob_end_clean(); // Clear any buffered output
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                ob_end_clean(); // Clear any buffered output
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()->route('admin.media.index')
                ->with('error', $e->getMessage());
        }
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:10240|mimes:jpeg,jpg,png,gif,webp,svg',
            'collection' => 'nullable|string|max:50',
        ]);

        try {
            $collection = $request->input('collection', 'products');
            $uploaded = [];

            foreach ($request->file('files') as $file) {
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('media/' . $collection, $filename, 'public');

                if (!$path) {
                    continue; // Skip failed uploads
                }

                $media = Media::create([
                    'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'path' => $path,
                    'disk' => 'public',
                    'size' => $file->getSize(),
                    'collection' => $collection,
                    'uploaded_by' => auth()->id(),
                ]);

                $uploaded[] = $media;
            }

            return response()->json([
                'success' => true,
                'media' => $uploaded,
                'message' => count($uploaded) . ' files uploaded successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Media $media)
    {
        return response()->json($media);
    }

    public function update(Request $request, Media $media)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'collection' => 'nullable|string|max:50',
        ]);

        $media->update([
            'name' => $request->name,
            'collection' => $request->collection ?? $media->collection,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'media' => $media,
                'message' => 'Media updated successfully',
            ]);
        }

        return redirect()->route('admin.media.index')
            ->with('success', 'Media updated successfully');
    }

    public function destroy(Media $media)
    {
        $media->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);
        }

        return redirect()->route('admin.media.index')
            ->with('success', 'Media deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:media,id',
        ]);

        $count = 0;
        foreach ($request->ids as $id) {
            $media = Media::find($id);
            if ($media) {
                $media->delete();
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $count . ' files deleted successfully',
        ]);
    }

    // API endpoint for media picker modal
    public function picker(Request $request)
    {
        $query = Media::images()->orderBy('created_at', 'desc');

        if ($request->filled('collection')) {
            $query->where('collection', $request->collection);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $media = $query->paginate(20);

        return response()->json([
            'media' => $media->items(),
            'pagination' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    // Simple JSON endpoint for media list (used by mobile hero slides picker)
    public function json(Request $request)
    {
        $query = Media::images()->orderBy('created_at', 'desc');

        if ($request->filled('collection')) {
            $query->where('collection', $request->collection);
        }

        $media = $query->limit(50)->get(['id', 'name', 'path', 'mime_type']);

        return response()->json($media);
    }
}
