<?php

namespace App\Http\Controllers;

use App\Models\DigitalFile;
use App\Models\DownloadLog;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    /**
     * Show downloads page for an order
     */
    public function orderDownloads(Order $order)
    {
        // Verify ownership
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Check if order is paid
        if (!in_array($order->payment_status, ['paid', 'completed'])) {
            return redirect()->route('account.orders')
                ->with('error', 'Downloads are only available for paid orders.');
        }

        $digitalItems = $order->items()
            ->where('is_digital', true)
            ->with(['product.digitalFiles' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            }])
            ->get();

        return view('account.downloads', compact('order', 'digitalItems'));
    }

    /**
     * Download a digital file
     */
    public function download(Request $request, $token, DigitalFile $file)
    {
        // Find order item by token
        $orderItem = OrderItem::where('download_token', $token)
            ->where('product_id', $file->product_id)
            ->first();

        if (!$orderItem) {
            abort(404, 'Download not found.');
        }

        // Verify order ownership
        $order = $orderItem->order;
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Check payment status
        if (!in_array($order->payment_status, ['paid', 'completed'])) {
            return back()->with('error', 'Downloads are only available for paid orders.');
        }

        // Check download limit
        if ($orderItem->downloads_remaining !== null && $orderItem->downloads_remaining <= 0) {
            return back()->with('error', 'Download limit exceeded for this file.');
        }

        // Check expiry
        if ($orderItem->download_expiry && now()->gt($orderItem->download_expiry)) {
            return back()->with('error', 'Download link has expired.');
        }

        // Log the download
        DownloadLog::create([
            'user_id' => Auth::id(),
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'digital_file_id' => $file->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Decrement download count if limited
        if ($orderItem->downloads_remaining !== null) {
            $orderItem->decrement('downloads_remaining');
        }

        // Handle URL-based files (redirect)
        if ($file->file_type === 'url') {
            return redirect()->away($file->file_url);
        }

        // Handle local file download
        if (!$file->fileExists()) {
            return back()->with('error', 'File not found. Please contact support.');
        }

        $filePath = $file->full_path;
        $fileName = $file->file_name ?: ($file->name . '.' . $file->file_extension);

        return response()->download($filePath, $fileName);
    }

    /**
     * Generate download token for order item
     */
    public static function generateDownloadToken(OrderItem $orderItem): string
    {
        $token = Str::random(64);
        $orderItem->update(['download_token' => $token]);
        return $token;
    }

    /**
     * Setup digital downloads for an order
     */
    public static function setupOrderDownloads(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = $item->product;
            
            if (!$product || !$product->is_downloadable) {
                continue;
            }

            // Get first digital file for limits (or use product defaults)
            $firstFile = $product->digitalFiles()->first();
            $downloadLimit = $firstFile?->download_limit ?? setting('products.digital_download_limit');
            $expiryDays = $firstFile?->download_expiry_days ?? setting('products.digital_download_expiry_days');

            $item->update([
                'is_digital' => true,
                'downloads_remaining' => $downloadLimit,
                'download_expiry' => $expiryDays ? now()->addDays($expiryDays) : null,
                'download_token' => Str::random(64),
            ]);
        }
    }
}
