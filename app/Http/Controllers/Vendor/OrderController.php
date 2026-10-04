<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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

    /** Items may only be fulfilled once the order is paid (or is cash on delivery) and not cancelled. */
    protected function canFulfill(Order $order): bool
    {
        return $order->status !== 'cancelled'
            && ($order->payment_status === 'paid' || $order->payment_method === 'cod');
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

        $orders = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Vendor/Orders/Index', [
            'orders' => $orders->through(fn ($order) => [
                'id' => $order->id,
                'number' => $order->order_number,
                'customer' => $order->user->name ?? $order->shipping_name,
                'items' => $order->items->count(),
                'pending_items' => $order->items->where('fulfillment_status', 'pending')->count(),
                'total' => format_currency($order->items->sum('total')),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'date' => $order->created_at->format('M d, Y'),
            ]),
            'filters' => $request->only(['search', 'status', 'fulfillment']),
            'fulfillmentStatuses' => OrderItem::fulfillmentStatuses(),
        ]);
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

        return Inertia::render('Vendor/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'date' => $order->created_at->format('M d, Y H:i'),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'notes' => $order->notes,
                'can_fulfill' => $this->canFulfill($order),
                'customer' => [
                    'name' => $order->user->name ?? $order->shipping_name,
                    'email' => $order->user->email ?? $order->shipping_email,
                    'phone' => $order->shipping_phone,
                ],
                'shipping' => [
                    'name' => $order->shipping_name,
                    'address' => $order->shipping_address,
                    'city' => trim("{$order->shipping_city}, {$order->shipping_state} {$order->shipping_zip}", ', '),
                    'country' => $order->shipping_country,
                ],
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->product_name ?? $item->product?->name,
                    'product_id' => $item->product_id,
                    'image' => $item->product?->image_url,
                    'options' => (object) ($item->variation_options ?? []),
                    'quantity' => (int) $item->quantity,
                    'price' => format_currency($item->price),
                    'total' => format_currency($item->total),
                    'status' => $item->fulfillment_status,
                    'status_label' => $item->fulfillment_status_label,
                    'tracking_number' => $item->tracking_number,
                    'shipping_carrier' => $item->shipping_carrier,
                    'vendor_note' => $item->vendor_note,
                    'shipped_at' => $item->shipped_at?->format('M d, Y'),
                    'delivered_at' => $item->delivered_at?->format('M d, Y'),
                ])->values(),
            ],
            'totals' => [
                'sales' => format_currency($vendorTotal),
                'commission' => format_currency($commission),
                'rate' => (float) ($this->vendor()->commission_rate ?? 0),
                'earnings' => format_currency($vendorTotal - $commission),
            ],
            'fulfillmentStatuses' => OrderItem::fulfillmentStatuses(),
        ]);
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

        if (!$this->canFulfill($item->order)) {
            return back()->with('error', 'This order cannot be fulfilled until it is paid.');
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

        if (!$this->canFulfill($order)) {
            return back()->with('error', 'This order cannot be fulfilled until it is paid.');
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
        $previous = $order->status;

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

        \App\Support\OrderMailer::statusChanged($order, $previous);
    }
}
