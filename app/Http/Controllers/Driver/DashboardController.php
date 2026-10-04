<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $driver = Auth::user();
        
        // Get assigned orders
        $assignedOrders = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['processing', 'shipped'])
            ->with(['user', 'items.product'])
            ->latest()
            ->get();

        // Get completed deliveries today
        $todayDeliveries = Order::where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->count();

        // Get total deliveries this month
        $monthDeliveries = Order::where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->whereMonth('delivered_at', now()->month)
            ->whereYear('delivered_at', now()->year)
            ->count();

        // Get pending pickups
        $pendingPickups = Order::where('driver_id', $driver->id)
            ->where('status', 'processing')
            ->whereNull('picked_up_at')
            ->count();

        // Get today's earnings
        $todayEarnings = \App\Models\DriverEarning::where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');

        // Get week's earnings
        $weekEarnings = \App\Models\DriverEarning::where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->startOfWeek())
            ->sum('amount');

        // Get unread notifications count
        $unreadNotifications = \App\Models\DriverNotification::where('driver_id', $driver->id)
            ->where('is_read', false)
            ->count();

        return view('driver.dashboard', compact(
            'driver',
            'assignedOrders',
            'todayDeliveries',
            'monthDeliveries',
            'pendingPickups',
            'todayEarnings',
            'weekEarnings',
            'unreadNotifications'
        ));
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:available,busy,offline',
        ]);

        Auth::user()->update(['driver_status' => $request->status]);

        return back()->with('success', 'Status updated successfully!');
    }
}
