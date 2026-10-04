<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DriverEarning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OrderController extends Controller
{
    /** Orders a driver can still act on. Delivered and cancelled orders are final. */
    protected const ACTIVE = ['processing', 'shipped'];

    /** Signature from the in-app pad: a PNG data URL, capped at ~300 KB. */
    protected const SIGNATURE_RULE = ['nullable', 'string', 'max:400000', 'regex:/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/'];

    /** Card used by the order lists and the dashboard. */
    public static function card(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->order_number,
            'status' => $order->status,
            'picked_up' => (bool) $order->picked_up_at,
            'customer' => $order->shipping_name ?? $order->user?->name,
            'address' => trim("{$order->shipping_address}, {$order->shipping_city}", ', '),
            'items' => $order->items->sum('quantity'),
            'total' => format_currency($order->total),
            'cod' => self::cashDue($order) && in_array($order->status, self::ACTIVE, true),
            'date' => $order->created_at->format('M d, H:i'),
            'delivered_at' => $order->delivered_at ? \Illuminate\Support\Carbon::parse($order->delivered_at)->format('M d, Y H:i') : null,
            'earnings' => $order->status === 'delivered' && $order->driver_earnings !== null ? format_currency($order->driver_earnings) : null,
        ];
    }

    /** A cash-on-delivery order whose money the driver still has to collect. */
    protected static function cashDue(Order $order): bool
    {
        return $order->payment_method === 'cod' && $order->payment_status !== 'paid';
    }

    protected function own(Order $order): void
    {
        if ((int) $order->driver_id !== (int) Auth::id()) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $driver = Auth::user();

        $query = Order::where('driver_id', $driver->id)
            ->with(['user', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default: show active orders
            $query->whereIn('status', self::ACTIVE);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Driver/Orders/Index', [
            'orders' => $orders->through(fn ($o) => self::card($o)),
            'filters' => $request->only('status'),
        ]);
    }

    public function history(Request $request)
    {
        $driver = Auth::user();

        $query = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->with(['user', 'items']);

        if ($request->filled('date_from')) {
            $query->whereDate('delivered_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('delivered_at', '<=', $request->date_to);
        }

        $orders = $query->latest('delivered_at')->paginate(15)->withQueryString();

        return Inertia::render('Driver/Orders/History', [
            'orders' => $orders->through(fn ($o) => self::card($o)),
            'filters' => $request->only('date_from', 'date_to'),
        ]);
    }

    public function show(Order $order)
    {
        $this->own($order);

        $order->load(['user', 'items.product', 'items.variation']);
        $place = trim(implode(', ', array_filter([$order->shipping_address, $order->shipping_city, $order->shipping_state, $order->shipping_zip, $order->shipping_country])));

        return Inertia::render('Driver/Orders/Show', [
            'order' => self::card($order) + [
                'active' => in_array($order->status, self::ACTIVE, true),
                'phone' => $order->shipping_phone,
                'shipping' => [
                    'name' => $order->shipping_name,
                    'address' => $order->shipping_address,
                    'city' => trim("{$order->shipping_city}, {$order->shipping_state} {$order->shipping_zip}", ', '),
                    'country' => $order->shipping_country,
                ],
                'map' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($place),
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'customer_note' => $order->notes,
                'driver_notes' => $order->driver_notes,
                'proof_photo' => $order->delivery_proof_photo ? asset('storage/' . $order->delivery_proof_photo) : null,
                'signature' => $order->delivery_signature,
                'lines' => $order->items->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->product_name ?? $i->product?->name,
                    'options' => (object) ($i->variation_options ?? []),
                    'quantity' => (int) $i->quantity,
                    'image' => $i->product?->image_url,
                ])->values(),
            ],
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->own($order);

        $request->validate([
            'status' => 'required|in:picked_up,shipped,delivered',
            'driver_notes' => 'nullable|string|max:1000',
        ]);

        if (!in_array($order->status, self::ACTIVE, true)) {
            return back()->with('error', 'This order is already ' . $order->status . '.');
        }

        // Delivering always goes through completeDelivery, which also records the driver's earnings.
        if ($request->status === 'delivered') {
            return $this->completeDelivery($request, $order);
        }

        $updateData = [
            'status' => 'shipped',
            'shipped_at' => $order->shipped_at ?? now(),
        ];
        if ($request->status === 'picked_up') {
            $updateData['picked_up_at'] = $order->picked_up_at ?? now();
        }

        if ($request->filled('driver_notes')) {
            $updateData['driver_notes'] = $this->appendNote($order, $request->driver_notes);
        }

        $previous = $order->status;
        $order->update($updateData);
        $order->items()->where('fulfillment_status', '!=', 'cancelled')->update(['fulfillment_status' => 'shipped', 'shipped_at' => now()]);
        \App\Support\OrderMailer::statusChanged($order, $previous);

        return back()->with('success', $request->status === 'picked_up' ? 'Marked as picked up.' : 'Order status updated successfully!');
    }

    public function addNote(Request $request, Order $order)
    {
        $this->own($order);

        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $order->update(['driver_notes' => $this->appendNote($order, $request->note)]);

        return back()->with('success', 'Note added successfully!');
    }

    public function uploadProof(Request $request, Order $order)
    {
        $this->own($order);

        $request->validate([
            'proof_photo' => 'required|image|max:5120', // 5MB max
            'signature' => self::SIGNATURE_RULE,
        ]);

        $data = ['delivery_proof_photo' => $this->storeProof($request, $order)];

        if ($request->filled('signature')) {
            $data['delivery_signature'] = $request->signature;
        }

        $order->update($data);

        return back()->with('success', 'Delivery proof uploaded successfully!');
    }

    public function completeDelivery(Request $request, Order $order)
    {
        $this->own($order);

        $request->validate([
            'proof_photo' => 'nullable|image|max:5120',
            'signature' => self::SIGNATURE_RULE,
            'driver_notes' => 'nullable|string|max:1000',
            // cash on delivery: the driver confirms the money was collected before the order counts as paid
            'cash_collected' => self::cashDue($order) ? 'accepted' : 'nullable',
        ], ['cash_collected.accepted' => 'Confirm that you collected ' . format_currency($order->total) . ' in cash.']);

        $driver = Auth::user();

        // Lock the order so a double tap (or a replayed request) can't complete it, and pay the driver, twice.
        $done = DB::transaction(function () use ($request, $order, $driver) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();
            if (!in_array($order->status, self::ACTIVE, true)) {
                return false;
            }

            $updateData = [
                'status' => 'delivered',
                'delivered_at' => now(),
                'shipped_at' => $order->shipped_at ?? now(),
            ];
            if (self::cashDue($order)) {
                $updateData['payment_status'] = 'paid';
                $updateData['driver_notes'] = $this->appendNote($order, 'Cash collected: ' . format_currency($order->total));
                $order->driver_notes = $updateData['driver_notes']; // so a note added below follows this one
            }

            if ($request->hasFile('proof_photo')) {
                $updateData['delivery_proof_photo'] = $this->storeProof($request, $order);
            }

            if ($request->filled('signature')) {
                $updateData['delivery_signature'] = $request->signature;
            }

            if ($request->filled('driver_notes')) {
                $updateData['driver_notes'] = $this->appendNote($order, $request->driver_notes);
            }

            // Calculate driver earnings (commission from shipping cost)
            $commissionRate = $driver->driver_commission_rate ?? 10;
            $deliveryEarning = round($order->shipping_cost * ($commissionRate / 100), 2);
            $updateData['driver_earnings'] = $deliveryEarning + ($order->driver_tip ?? 0);

            $order->update($updateData);

            $order->items()->where('fulfillment_status', '!=', 'cancelled')->update([
                'fulfillment_status' => 'delivered',
                'delivered_at' => now(),
            ]);

            if ($deliveryEarning > 0) {
                DriverEarning::create([
                    'driver_id' => $driver->id,
                    'order_id' => $order->id,
                    'type' => 'delivery',
                    'amount' => $deliveryEarning,
                    'description' => "Delivery commission for order {$order->order_number}",
                    'status' => 'completed',
                ]);
            }

            $driver->increment('driver_total_deliveries');
            $driver->increment('driver_total_earnings', $deliveryEarning);
            $driver->update(['driver_status' => 'available']);

            return true;
        });

        if (!$done) {
            return redirect()->route('driver.orders.show', $order)->with('error', 'This order was already completed.');
        }

        \App\Support\OrderMailer::statusChanged($order->fresh(), $order->status);

        return redirect()->route('driver.orders.index')
            ->with('success', 'Delivery completed successfully!');
    }

    public function getStats()
    {
        $driver = Auth::user();

        $todayDeliveries = Order::where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->count();

        $todayEarnings = DriverEarning::where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');

        $activeOrders = Order::where('driver_id', $driver->id)
            ->whereIn('status', self::ACTIVE)
            ->count();

        return response()->json([
            'today_deliveries' => $todayDeliveries,
            'today_earnings' => $todayEarnings,
            'active_orders' => $activeOrders,
            'rating' => $driver->driver_rating,
        ]);
    }

    protected function appendNote(Order $order, string $note): string
    {
        $notes = $order->driver_notes ? $order->driver_notes . "\n" : '';

        return $notes . '[' . now()->format('Y-m-d H:i') . '] ' . $note;
    }

    protected function storeProof(Request $request, Order $order): string
    {
        if ($order->delivery_proof_photo && Storage::disk('public')->exists($order->delivery_proof_photo)) {
            Storage::disk('public')->delete($order->delivery_proof_photo);
        }

        return $request->file('proof_photo')->store('delivery-proofs', 'public');
    }
}
