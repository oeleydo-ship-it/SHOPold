<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DriverEarning;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
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

        return Inertia::render('Driver/Earnings/Index', [
            'period' => in_array($period, ['today', 'week', 'month', 'year'], true) ? $period : 'week',
            'summary' => [
                'total' => format_currency($totalEarnings),
                'delivery' => format_currency($deliveryEarnings),
                'tips' => format_currency($tipEarnings),
                'bonus' => format_currency($bonusEarnings),
                'deliveries' => $deliveriesCount,
                'average' => format_currency($avgPerDelivery),
                'lifetime' => format_currency($driver->driver_total_earnings ?? 0),
            ],
            'daily' => $dailyEarnings->map(fn ($d) => [
                'date' => Carbon::parse($d->date)->format('M d'),
                'value' => (float) $d->total,
                'label' => format_currency($d->total),
            ])->values(),
            'transactions' => $recentTransactions->map(fn ($t) => $this->transaction($t))->values(),
        ]);
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

        return Inertia::render('Driver/Earnings/History', [
            'transactions' => $transactions->withQueryString()->through(fn ($t) => $this->transaction($t)),
            'totals' => collect(['delivery', 'tip', 'bonus'])->mapWithKeys(fn ($k) => [$k => format_currency($totals[$k] ?? 0)]),
            'filters' => $request->only('type', 'date_from', 'date_to'),
        ]);
    }

    protected function transaction(DriverEarning $t): array
    {
        return [
            'id' => $t->id,
            'type' => $t->type,
            'amount' => format_currency($t->amount),
            'description' => $t->description,
            'status' => $t->status,
            'order' => $t->order ? ['id' => $t->order->id, 'number' => $t->order->order_number] : null,
            'date' => $t->created_at->format('M d, Y H:i'),
        ];
    }
}
