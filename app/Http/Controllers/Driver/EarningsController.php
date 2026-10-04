<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DriverEarning;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EarningsController extends Controller
{
    public function index(Request $request)
    {
        $driver = Auth::user();
        $period = $request->get('period', 'week');

        // Calculate date range
        $startDate = match($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfWeek(),
        };

        // Get earnings for period
        $earnings = DriverEarning::where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->get();

        $deliveryEarnings = $earnings->where('type', 'delivery')->sum('amount');
        $tipEarnings = $earnings->where('type', 'tip')->sum('amount');
        $bonusEarnings = $earnings->where('type', 'bonus')->sum('amount');
        $totalEarnings = $deliveryEarnings + $tipEarnings + $bonusEarnings;

        // Get deliveries count
        $deliveriesCount = Order::where('driver_id', $driver->id)
            ->where('status', 'delivered')
            ->where('delivered_at', '>=', $startDate)
            ->count();

        // Get daily breakdown for chart
        $dailyEarnings = DriverEarning::where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->whereIn('type', ['delivery', 'tip', 'bonus'])
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Recent transactions
        $recentTransactions = DriverEarning::where('driver_id', $driver->id)
            ->with('order')
            ->latest()
            ->take(20)
            ->get();

        // Calculate average per delivery
        $avgPerDelivery = $deliveriesCount > 0 ? $totalEarnings / $deliveriesCount : 0;

        return view('driver.earnings.index', compact(
            'driver', 'period', 'totalEarnings', 'deliveryEarnings', 
            'tipEarnings', 'bonusEarnings', 'deliveriesCount', 
            'dailyEarnings', 'recentTransactions', 'avgPerDelivery'
        ));
    }

    public function history(Request $request)
    {
        $driver = Auth::user();

        $query = DriverEarning::where('driver_id', $driver->id)
            ->with('order');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate(30);

        // Calculate totals
        $totals = DriverEarning::where('driver_id', $driver->id)
            ->where('status', 'completed')
            ->selectRaw('type, SUM(amount) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return view('driver.earnings.history', compact('transactions', 'totals'));
    }
}
