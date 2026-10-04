<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\DriverEarning;
use App\Models\DriverNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $driver = Auth::user();
        
        $query = Order::where('driver_id', $driver->id)
            ->with(['user', 'items.product']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default: show active orders
            $query->whereIn('status', ['processing', 'shipped']);
        }

        $orders = $query->latest()->paginate(15);

        return view('driver.orders.index', compact('orders'));
    }

    public function history(Request $request)
    {
        $driver = Auth::user();
        
        $query = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->with(['user', 'items.product']);

        if ($request->filled('date_from')) {
            $query->whereDate('delivered_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('delivered_at', '<=', $request->date_to);
        }

        $orders = $query->latest('delivered_at')->paginate(15);

        return view('driver.orders.history', compact('orders'));
    }

    public function show(Order $order)
    {
        // Verify driver owns this order
        if ($order->driver_id !== Auth::id()) {
            abort(403);
        }

        $order->load(['user', 'items.product', 'items.variation']);

        return view('driver.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        // Verify driver owns this order
        if ($order->driver_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:picked_up,shipped,delivered',
            'driver_notes' => 'nullable|string|max:1000',
        ]);

        $updateData = [];
        $itemUpdateData = [];

        switch ($request->status) {
            case 'picked_up':
                $updateData['picked_up_at'] = now();
                $updateData['status'] = 'shipped';
                $updateData['shipped_at'] = $order->shipped_at ?? now();
                $itemUpdateData['fulfillment_status'] = 'shipped';
                $itemUpdateData['shipped_at'] = now();
                break;

            case 'shipped':
                $updateData['status'] = 'shipped';
                $updateData['shipped_at'] = $order->shipped_at ?? now();
                $itemUpdateData['fulfillment_status'] = 'shipped';
                $itemUpdateData['shipped_at'] = now();
                break;

            case 'delivered':
                $updateData['status'] = 'delivered';
                $updateData['delivered_at'] = now();
                $itemUpdateData['fulfillment_status'] = 'delivered';
                $itemUpdateData['delivered_at'] = now();
                break;
        }

        if ($request->filled('driver_notes')) {
            $notes = $order->driver_notes ? $order->driver_notes . "\n" : '';
            $notes .= "[" . now()->format('Y-m-d H:i') . "] " . $request->driver_notes;
            $updateData['driver_notes'] = $notes;
        }

        $order->update($updateData);
        
        if (!empty($itemUpdateData)) {
            $order->items()->update($itemUpdateData);
        }

        // Update driver status
        if ($request->status === 'delivered') {
            Auth::user()->update(['driver_status' => 'available']);
        }

        return back()->with('success', 'Order status updated successfully!');
    }

    public function addNote(Request $request, Order $order)
    {
        if ($order->driver_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $notes = $order->driver_notes ? $order->driver_notes . "\n" : '';
        $notes .= "[" . now()->format('Y-m-d H:i') . "] " . $request->note;
        
        $order->update(['driver_notes' => $notes]);

        return back()->with('success', 'Note added successfully!');
    }

    public function uploadProof(Request $request, Order $order)
    {
        if ($order->driver_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'proof_photo' => 'required|image|max:5120', // 5MB max
            'signature' => 'nullable|string', // Base64 signature
        ]);

        $data = [];

        if ($request->hasFile('proof_photo')) {
            // Delete old photo if exists
            if ($order->delivery_proof_photo && Storage::disk('public')->exists($order->delivery_proof_photo)) {
                Storage::disk('public')->delete($order->delivery_proof_photo);
            }
            $data['delivery_proof_photo'] = $request->file('proof_photo')->store('delivery-proofs', 'public');
        }

        if ($request->filled('signature')) {
            $data['delivery_signature'] = $request->signature;
        }

        $order->update($data);

        return back()->with('success', 'Delivery proof uploaded successfully!');
    }

    public function completeDelivery(Request $request, Order $order)
    {
        if ($order->driver_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'proof_photo' => 'nullable|image|max:5120',
            'signature' => 'nullable|string',
            'driver_notes' => 'nullable|string|max:1000',
        ]);

        $driver = Auth::user();
        $updateData = [
            'status' => 'delivered',
            'delivered_at' => now(),
        ];

        // Handle proof photo
        if ($request->hasFile('proof_photo')) {
            $updateData['delivery_proof_photo'] = $request->file('proof_photo')->store('delivery-proofs', 'public');
        }

        // Handle signature
        if ($request->filled('signature')) {
            $updateData['delivery_signature'] = $request->signature;
        }

        // Handle notes
        if ($request->filled('driver_notes')) {
            $notes = $order->driver_notes ? $order->driver_notes . "\n" : '';
            $notes .= "[" . now()->format('Y-m-d H:i') . "] " . $request->driver_notes;
            $updateData['driver_notes'] = $notes;
        }

        // Calculate driver earnings (commission from shipping cost)
        $commissionRate = $driver->driver_commission_rate ?? 10;
        $deliveryEarning = $order->shipping_cost * ($commissionRate / 100);
        $updateData['driver_earnings'] = $deliveryEarning + ($order->driver_tip ?? 0);

        $order->update($updateData);

        // Update order items
        $order->items()->update([
            'fulfillment_status' => 'delivered',
            'delivered_at' => now(),
        ]);

        // Create earning record
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

        // Update driver stats
        $driver->increment('driver_total_deliveries');
        $driver->increment('driver_total_earnings', $deliveryEarning);
        $driver->update(['driver_status' => 'available']);

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
            ->whereIn('status', ['processing', 'shipped'])
            ->count();

        return response()->json([
            'today_deliveries' => $todayDeliveries,
            'today_earnings' => $todayEarnings,
            'active_orders' => $activeOrders,
            'rating' => $driver->driver_rating,
        ]);
    }
}
