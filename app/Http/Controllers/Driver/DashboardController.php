<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $driver = Auth::user();
        
        // Get assigned orders
        $assignedOrders = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['processing', 'shipped'])
            ->with(['user', 'items'])
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

        return Inertia::render('Driver/Dashboard', [
            'name' => $driver->name,
            'stats' => [
                'today_deliveries' => $todayDeliveries,
                'month_deliveries' => $monthDeliveries,
                'pending_pickups' => $pendingPickups,
                'today_earnings' => format_currency($todayEarnings),
                'week_earnings' => format_currency($weekEarnings),
                'rating' => $driver->driver_rating !== null ? round((float) $driver->driver_rating, 1) : null,
                'total_deliveries' => (int) $driver->driver_total_deliveries,
            ],
            'orders' => $assignedOrders->map(fn ($o) => OrderController::card($o))->values(),
        ]);
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
