<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected function vendor()
    {
        return Auth::user()->vendor;
    }

    protected function vendorProductIds()
    {
        return Product::where('vendor_id', $this->vendor()->id)->pluck('id');
    }

    public function index(Request $request)
    {
        $vendorProductIds = $this->vendorProductIds();

        $query = Order::whereHas('items', function($q) use ($vendorProductIds) {
            $q->whereIn('product_id', $vendorProductIds);
        })->with(['user', 'items' => function($q) use ($vendorProductIds) {
            $q->whereIn('product_id', $vendorProductIds)->with('product');
        }]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('fulfillment')) {
            $query->whereHas('items', function($q) use ($vendorProductIds, $request) {
                $q->whereIn('product_id', $vendorProductIds)
                  ->where('fulfillment_status', $request->fulfillment);
            });
        }

        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        $orders = $query->latest()->paginate(15);

        // Calculate vendor's portion for each order
        foreach ($orders as $order) {
            $order->vendor_total = $order->items->sum('total');
            $order->vendor_items_count = $order->items->count();
            $order->pending_items = $order->items->where('fulfillment_status', 'pending')->count();
        }

        return view('vendor.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $vendorProductIds = $this->vendorProductIds();

        // Only show items that belong to this vendor
        $order->load(['user', 'items' => function($q) use ($vendorProductIds) {
            $q->whereIn('product_id', $vendorProductIds)->with(['product', 'variation']);
        }]);

        // Check if vendor has items in this order
        if ($order->items->isEmpty()) {
            abort(404);
        }

        $vendorTotal = $order->items->sum('total');
        $commission = $vendorTotal * (($this->vendor()->commission_rate ?? 0) / 100);
        $vendorEarnings = $vendorTotal - $commission;

        return view('vendor.orders.show', compact('order', 'vendorTotal', 'commission', 'vendorEarnings'));
    }

    public function updateItemStatus(Request $request, OrderItem $item)
    {
        // Verify item belongs to vendor's product
        if (!$this->vendorProductIds()->contains($item->product_id)) {
            abort(403);
        }

        $request->validate([
            'fulfillment_status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'tracking_number' => 'nullable|string|max:255',
            'shipping_carrier' => 'nullable|string|max:255',
            'vendor_note' => 'nullable|string|max:1000',
        ]);

        $updateData = [
            'fulfillment_status' => $request->fulfillment_status,
        ];

        if ($request->filled('tracking_number')) {
            $updateData['tracking_number'] = $request->tracking_number;
        }

        if ($request->filled('shipping_carrier')) {
            $updateData['shipping_carrier'] = $request->shipping_carrier;
        }

        if ($request->filled('vendor_note')) {
            $updateData['vendor_note'] = $request->vendor_note;
        }

        // Set timestamps
        if ($request->fulfillment_status === 'shipped' && !$item->shipped_at) {
            $updateData['shipped_at'] = now();
        }

        if ($request->fulfillment_status === 'delivered' && !$item->delivered_at) {
            $updateData['delivered_at'] = now();
            if (!$item->shipped_at) {
                $updateData['shipped_at'] = now();
            }
        }

        $item->update($updateData);

        // Check if all vendor items in order are fulfilled - could trigger order status update
        $this->checkOrderCompletion($item->order);

        return back()->with('success', 'Item fulfillment status updated!');
    }

    public function bulkUpdateStatus(Request $request, Order $order)
    {
        $vendorProductIds = $this->vendorProductIds();
        
        $request->validate([
            'fulfillment_status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'tracking_number' => 'nullable|string|max:255',
            'shipping_carrier' => 'nullable|string|max:255',
        ]);

        $items = $order->items()->whereIn('product_id', $vendorProductIds)->get();

        if ($items->isEmpty()) {
            abort(403);
        }

        $updateData = [
            'fulfillment_status' => $request->fulfillment_status,
        ];

        if ($request->filled('tracking_number')) {
            $updateData['tracking_number'] = $request->tracking_number;
        }

        if ($request->filled('shipping_carrier')) {
            $updateData['shipping_carrier'] = $request->shipping_carrier;
        }

        if ($request->fulfillment_status === 'shipped') {
            $updateData['shipped_at'] = now();
        }

        if ($request->fulfillment_status === 'delivered') {
            $updateData['delivered_at'] = now();
            $updateData['shipped_at'] = now();
        }

        $order->items()->whereIn('product_id', $vendorProductIds)->update($updateData);

        $this->checkOrderCompletion($order);

        return back()->with('success', 'All items updated successfully!');
    }

    protected function checkOrderCompletion(Order $order)
    {
        // Reload order items
        $order->load('items');

        // Check if all items are delivered
        $allDelivered = $order->items->every(fn($item) => $item->fulfillment_status === 'delivered');
        
        if ($allDelivered && $order->status !== 'delivered') {
            $order->update(['status' => 'delivered']);
        }

        // Check if all items are shipped (at minimum)
        $allShipped = $order->items->every(fn($item) => in_array($item->fulfillment_status, ['shipped', 'delivered']));
        
        if ($allShipped && $order->status === 'processing') {
            $order->update(['status' => 'shipped']);
        }
    }
}
