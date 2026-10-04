<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('is_driver', true)
            ->withCount(['driverOrders as active_orders_count' => function ($q) {
                $q->whereIn('status', ['processing', 'shipped']);
            }])
            ->withCount(['driverOrders as completed_orders_count' => function ($q) {
                $q->where('status', 'delivered');
            }]);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('driver_phone', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('driver_status', $request->status);
        }

        $drivers = $query->latest()->paginate(15)->withQueryString();

        // Stats
        $totalDrivers = User::where('is_driver', true)->count();
        $availableDrivers = User::where('is_driver', true)->where('driver_status', 'available')->count();
        $busyDrivers = User::where('is_driver', true)->where('driver_status', 'busy')->count();
        $pendingDeliveries = Order::whereNotNull('driver_id')->whereIn('status', ['processing', 'shipped'])->count();

        return Inertia::render('Admin/Drivers/Index', [
            'drivers' => [
                'data' => $drivers->getCollection()->map(fn ($d) => [
                    'id' => $d->id, 'name' => $d->name, 'email' => $d->email, 'phone' => $d->driver_phone ?? $d->phone, 'vehicle' => $d->driver_vehicle,
                    'status' => $d->driver_status, 'active' => (int) $d->active_orders_count, 'completed' => (int) $d->completed_orders_count,
                ])->values(),
                'links' => $drivers->linkCollection()->toArray(), 'from' => $drivers->firstItem(), 'to' => $drivers->lastItem(), 'total' => $drivers->total(),
            ],
            'stats' => ['total' => $totalDrivers, 'available' => $availableDrivers, 'busy' => $busyDrivers, 'pending' => $pendingDeliveries],
            'filters' => ['search' => (string) $request->search, 'status' => (string) $request->status],
        ]);
    }

    public function show(User $driver)
    {
        if (!$driver->is_driver) {
            abort(404);
        }

        $driver->load(['driverOrders' => function ($q) {
            $q->with('user')->latest()->limit(20);
        }]);

        $stats = [
            'total_deliveries' => $driver->driverOrders()->count(),
            'completed' => $driver->driverOrders()->where('status', 'delivered')->count(),
            'active' => $driver->driverOrders()->whereIn('status', ['processing', 'shipped'])->count(),
            'this_month' => $driver->driverOrders()->where('status', 'delivered')
                ->whereMonth('delivered_at', now()->month)
                ->whereYear('delivered_at', now()->year)
                ->count(),
            'total_tips' => $driver->driverOrders()->sum('driver_tip'),
        ];

        return Inertia::render('Admin/Drivers/Show', [
            'driver' => [
                'id' => $driver->id, 'name' => $driver->name, 'email' => $driver->email, 'phone' => $driver->driver_phone ?? $driver->phone,
                'vehicle' => $driver->driver_vehicle, 'license' => $driver->driver_license, 'status' => $driver->driver_status,
                'joined' => $driver->created_at?->format('M d, Y'), 'earnings' => format_currency($driver->driver_total_earnings ?? 0),
                'rating' => number_format((float) ($driver->driver_rating ?? 5), 1), 'rating_raw' => (float) ($driver->driver_rating ?? 5),
                'ratings_count' => (int) ($driver->driver_total_ratings ?? 0), 'commission_rate' => (float) ($driver->driver_commission_rate ?? 10),
            ],
            'stats' => [
                'total_deliveries' => $stats['total_deliveries'], 'completed' => $stats['completed'], 'active' => $stats['active'],
                'this_month' => $stats['this_month'], 'total_tips' => format_currency($stats['total_tips'] ?? 0),
            ],
            'orders' => $driver->driverOrders->map(fn ($o) => [
                'id' => $o->id, 'number' => $o->order_number, 'customer' => $o->user?->name ?? 'Guest',
                'place' => trim("{$o->shipping_city}, {$o->shipping_country}", ', '), 'total' => format_currency($o->total), 'status' => $o->status,
            ])->values(),
        ]);
    }

    public function updateCommission(Request $request, User $driver)
    {
        if (!$driver->is_driver) {
            abort(404);
        }

        $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        $driver->update(['driver_commission_rate' => $request->commission_rate]);

        return back()->with('success', 'Commission rate updated successfully!');
    }

    public function addBonus(Request $request, User $driver)
    {
        if (!$driver->is_driver) {
            abort(404);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $driverService = new \App\Services\DriverService();
        $driverService->addBonus($driver, $request->amount, $request->description);

        return back()->with('success', 'Bonus added successfully!');
    }
}
