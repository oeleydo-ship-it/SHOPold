<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Mail\OrderStatusUpdated;
use App\Mail\OrderShipped;
use App\Mail\OrderDelivered;
use App\Mail\OrderReadyForPickup;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items']);

        if ($request->search) {
            $term = '%' . $request->search . '%';
            // Grouped so the OR does not escape the status / payment filters below.
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', $term)
                  ->orWhereHas('user', function ($u) use ($term) {
                      $u->where('name', 'like', $term)->orWhere('email', 'like', $term);
                  });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->latest()->paginate(15)->withQueryString()->through(function ($o) {
            return [
                'id' => $o->id,
                'number' => $o->order_number,
                'customer' => $o->user?->name ?? $o->shipping_name,
                'email' => $o->user?->email ?? $o->shipping_email,
                'status' => $o->status,
                'payment_status' => $o->payment_status,
                'total' => format_currency($o->total),
                'date' => $o->created_at->format('M d, Y'),
                'delivered' => $o->items->where('fulfillment_status', 'delivered')->count(),
                'pending' => $o->items->where('fulfillment_status', 'pending')->count(),
                'items' => $o->items->count(),
            ];
        });

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only('search', 'status', 'payment_status'),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product', 'items.variation', 'pickupLocation', 'driver']);
        $payments = app(PaymentGatewayService::class);
        $method = $order->payment_method ?? 'cod';
        $fmt = fn ($d) => $d?->format('M d, Y h:i A');
        $driverPortal = (bool) setting('features.driver_portal');

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'delivery_type' => $order->delivery_type,
                'created_at' => $fmt($order->created_at),
                'updated_at' => $fmt($order->updated_at),
                'shipped_at' => $fmt($order->shipped_at),
                'delivered_at' => $fmt($order->delivered_at),
                'customer_name' => $order->user->name ?? $order->shipping_name,
                'customer_email' => $order->user->email ?? $order->shipping_email,
                'shipping' => [
                    'name' => $order->shipping_name,
                    'address' => $order->shipping_address,
                    'city' => $order->shipping_city,
                    'state' => $order->shipping_state,
                    'zip' => $order->shipping_zip,
                    'country' => $order->shipping_country,
                ],
                'pickup' => $order->pickupLocation
                    ? $order->pickupLocation->only(['name', 'address', 'city', 'state', 'zip', 'country', 'phone'])
                    : null,
                'subtotal' => format_currency($order->subtotal),
                'discount' => $order->discount > 0 ? format_currency($order->discount) : null,
                'shipping_cost' => format_currency($order->shipping_cost),
                'tax' => format_currency($order->tax),
                'total' => format_currency($order->total),
                'tracking_number' => $order->tracking_number,
                'tracking_url' => $order->tracking_url,
                'notes' => $order->notes,
                'driver_notes' => $order->driver_notes,
                'driver_id' => $order->driver_id,
                'driver' => $order->driver ? [
                    'name' => $order->driver->name,
                    'phone' => $order->driver->driver_phone ?? $order->driver->phone,
                    'status' => $order->driver->driver_status,
                    'assigned_at' => $fmt($order->driver_assigned_at),
                ] : null,
                'payment' => [
                    'is_card' => $payments->isCardPayment($method),
                    'gateway' => $payments->getPaymentMethodName($method),
                    'transaction_id' => $order->transaction_id ? Str::limit($order->transaction_id, 20) : null,
                    'supports_refund' => $payments->supportsRefund($order->payment_method ?? ''),
                    'refund_id' => $order->refund_id,
                    'refunded_at' => $fmt($order->refunded_at),
                    'refund_amount' => $order->refund_amount ? format_currency($order->refund_amount) : null,
                    'refund_reason' => $order->refund_reason,
                ],
                'items' => $order->items->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->product_name,
                    'image' => ($i->variation && $i->variation->image) ? $i->variation->image_url
                        : (($i->product && $i->product->image) ? $i->product->image_url : null),
                    'options' => $i->variation_options ?: null,
                    'quantity' => $i->quantity,
                    'price' => format_currency($i->price),
                    'total' => format_currency($i->total),
                    'sku' => $i->variation?->sku,
                    'carrier' => $i->shipping_carrier,
                    'tracking_number' => $i->tracking_number,
                    'shipped_at' => $fmt($i->shipped_at),
                    'delivered_at' => $fmt($i->delivered_at),
                    'fulfillment_status' => $i->fulfillment_status,
                    'fulfillment_label' => $i->fulfillment_status_label,
                ]),
            ],
            'drivers' => $driverPortal
                ? User::where('is_driver', true)->get(['id', 'name', 'driver_status'])
                : null,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,ready_for_pickup,shipped,delivered,cancelled',
        ]);

        $previousStatus = $order->status;
        $newStatus = $request->status;

        $order->update([
            'status' => $newStatus,
            'shipped_at' => $newStatus === 'shipped' && !$order->shipped_at ? now() : $order->shipped_at,
            'delivered_at' => $newStatus === 'delivered' && !$order->delivered_at ? now() : $order->delivered_at,
        ]);

        // Update all order items' fulfillment status to match the order status
        $itemUpdateData = ['fulfillment_status' => $newStatus];
        
        if ($newStatus === 'shipped') {
            $itemUpdateData['shipped_at'] = now();
        } elseif ($newStatus === 'delivered') {
            $itemUpdateData['delivered_at'] = now();
            // Also set shipped_at if not already set
            foreach ($order->items as $item) {
                if (!$item->shipped_at) {
                    $item->update(['shipped_at' => now()]);
                }
            }
        }
        
        $order->items()->update($itemUpdateData);

        // If cancelled, restore stock
        if ($newStatus === 'cancelled' && $previousStatus !== 'cancelled') {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('quantity', $item->quantity);
                }
            }
        }

        // Customer email for the new status (honours Settings > Email > Email Notifications)
        \App\Support\OrderMailer::statusChanged($order, $previousStatus);

        return back()->with('success', 'Order status updated successfully!');
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded',
        ]);

        $order->update(['payment_status' => $request->payment_status]);

        return back()->with('success', 'Payment status updated successfully!');
    }

    /**
     * Process refund for an order
     */
    public function refund(Request $request, Order $order, PaymentGatewayService $paymentService)
    {
        $request->validate([
            'refund_reason' => 'nullable|string|max:500',
        ]);

        $result = $paymentService->processRefund($order, $request->refund_reason);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function destroy(Order $order)
    {
        // Restore stock before deleting
        foreach ($order->items as $item) {
            if ($order->status !== 'cancelled') {
                $item->product->increment('quantity', $item->quantity);
            }
        }

        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted successfully!');
    }

    /**
     * Show form to create manual order
     */
    public function create()
    {
        $users = User::where('is_admin', false)->orderBy('name')->get();
        $products = Product::where('is_active', true)->where('quantity', '>', 0)->orderBy('name')->get();
        return Inertia::render('Admin/Orders/Create', [
            'users' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email]),
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) ($p->sale_price ?? $p->price),
                'quantity' => $p->quantity,
            ]),
            'taxRate' => (float) setting('store.tax_rate', 10),
        ]);
    }

    /**
     * Store a manual order
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string|max:255',
            'shipping_country' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:50',
        ]);

        // Calculate totals
        $subtotal = 0;
        $items = [];
        
        foreach ($request->products as $productData) {
            $product = Product::find($productData['id']);
            if ($product && $product->quantity >= $productData['quantity']) {
                $price = $product->sale_price ?? $product->price;
                $items[] = [
                    'product' => $product,
                    'quantity' => $productData['quantity'],
                    'price' => $price,
                ];
                $subtotal += $price * $productData['quantity'];
            }
        }

        if (empty($items)) {
            return back()->with('error', 'No valid products selected.');
        }

        $shipping = $request->shipping_cost ?? 0;
        $tax = $subtotal * (setting('store.tax_rate', 10) / 100);
        $total = $subtotal + $shipping + $tax;

        // Create order
        $order = Order::create([
            'user_id' => $request->user_id,
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_cost' => $shipping,
            'total' => $total,
            'status' => $request->status ?? 'pending',
            'payment_status' => $request->payment_status ?? 'pending',
            'payment_method' => $request->payment_method ?? 'manual',
            'shipping_name' => $request->shipping_name,
            'shipping_address' => $request->shipping_address,
            'shipping_city' => $request->shipping_city,
            'shipping_state' => $request->shipping_state,
            'shipping_zip' => $request->shipping_zip,
            'shipping_country' => $request->shipping_country,
            'shipping_phone' => $request->shipping_phone,
            'notes' => $request->notes,
        ]);

        // Create order items and reduce stock
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product']->id,
                'product_name' => $item['product']->name,
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
            $item['product']->decrement('quantity', $item['quantity']);
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order created successfully!');
    }

    /**
     * Update tracking information
     */
    public function updateTracking(Request $request, Order $order)
    {
        $request->validate([
            'tracking_number' => 'nullable|string|max:255',
            'tracking_url' => 'nullable|url|max:500',
        ]);

        $order->update([
            'tracking_number' => $request->tracking_number,
            'tracking_url' => $request->tracking_url,
        ]);

        return back()->with('success', 'Tracking information updated!');
    }

    /**
     * Add note to order
     */
    public function addNote(Request $request, Order $order)
    {
        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $notes = $order->notes ? $order->notes . "\n\n" : '';
        $notes .= "[" . now()->format('Y-m-d H:i') . "] " . $request->note;
        
        $order->update(['notes' => $notes]);

        return back()->with('success', 'Note added to order!');
    }

    /**
     * Assign driver to order
     */
    public function assignDriver(Request $request, Order $order)
    {
        $request->validate([
            'driver_id' => 'nullable|exists:users,id',
        ]);

        $driverService = new \App\Services\DriverService();
        $driverId = $request->driver_id ?: null;
        
        // If removing driver
        if (!$driverId && $order->driver_id) {
            $driverService->unassignDriver($order);
            return back()->with('success', 'Driver removed from order.');
        }

        // If assigning driver
        if ($driverId) {
            $driver = User::find($driverId);
            if (!$driver || !$driver->is_driver) {
                return back()->with('error', 'Invalid driver selected.');
            }

            // Unassign previous driver if exists
            if ($order->driver_id && $order->driver_id !== $driverId) {
                $driverService->unassignDriver($order);
            }

            $driverService->assignDriver($order, $driver);
            return back()->with('success', 'Driver assigned successfully!');
        }

        return back();
    }
}
