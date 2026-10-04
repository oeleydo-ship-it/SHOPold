<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $vendor = Auth::user()->vendor;
        
        if (!$vendor) {
            return redirect()->route('vendor.register');
        }

        // Get vendor's products
        $totalProducts = Product::where('vendor_id', $vendor->id)->count();
        $activeProducts = Product::where('vendor_id', $vendor->id)->where('is_active', true)->count();
        
        // Get orders containing vendor's products
        $vendorProductIds = Product::where('vendor_id', $vendor->id)->pluck('id');
        $orderItems = OrderItem::whereIn('product_id', $vendorProductIds);
        
        // This month stats
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        $thisMonthSales = OrderItem::whereIn('product_id', $vendorProductIds)
            ->whereHas('order', function($q) use ($thisMonth) {
                $q->where('created_at', '>=', $thisMonth)
                  ->where('payment_status', 'paid');
            })->sum('total');
            
        $lastMonthSales = OrderItem::whereIn('product_id', $vendorProductIds)
            ->whereHas('order', function($q) use ($lastMonth, $thisMonth) {
                $q->whereBetween('created_at', [$lastMonth, $thisMonth])
                  ->where('payment_status', 'paid');
            })->sum('total');
        
        $salesGrowth = $lastMonthSales > 0 
            ? (($thisMonthSales - $lastMonthSales) / $lastMonthSales) * 100 
            : ($thisMonthSales > 0 ? 100 : 0);

        // Recent orders
        $recentOrders = Order::whereHas('items', function($q) use ($vendorProductIds) {
            $q->whereIn('product_id', $vendorProductIds);
        })->with(['user', 'items' => function($q) use ($vendorProductIds) {
            $q->whereIn('product_id', $vendorProductIds)->with('product');
        }])->latest()->take(5)->get();

        // Top products
        $topProducts = Product::where('vendor_id', $vendor->id)
            ->withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->take(5)
            ->get();

        // Sales chart data (last 7 days)
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $sales = OrderItem::whereIn('product_id', $vendorProductIds)
                ->whereHas('order', function($q) use ($date) {
                    $q->whereDate('created_at', $date)
                      ->where('payment_status', 'paid');
                })->sum('total');
            
            $chartData[] = [
                'date' => $date->format('M d'),
                'sales' => $sales
            ];
        }

        // Pending orders count
        $pendingOrders = Order::whereHas('items', function($q) use ($vendorProductIds) {
            $q->whereIn('product_id', $vendorProductIds);
        })->where('status', 'pending')->count();

        // Calculate real-time earnings for display
        $vendor->total_sales = $vendor->calculated_total_sales;
        $vendor->total_earnings = $vendor->calculated_total_earnings;
        $vendor->balance = $vendor->calculated_balance;

        return Inertia::render('Vendor/Dashboard', [
            'vendor' => ['status' => $vendor->status, 'store_name' => $vendor->store_name],
            'stats' => [
                'month_sales' => format_currency($thisMonthSales), 'growth' => round((float) $salesGrowth, 1),
                'balance' => format_currency($vendor->balance), 'earnings' => format_currency($vendor->total_earnings),
                'total_products' => $totalProducts, 'active_products' => $activeProducts, 'pending_orders' => $pendingOrders,
            ],
            'chart' => collect($chartData)->map(fn ($d) => ['date' => $d['date'], 'value' => (float) $d['sales'], 'label' => format_currency($d['sales'])])->values(),
            'recentOrders' => $recentOrders->map(fn ($o) => [
                'id' => $o->id, 'number' => $o->order_number, 'customer' => $o->user?->name ?? $o->shipping_name ?? 'Guest',
                'items' => $o->items->sum('quantity'), 'total' => format_currency($o->items->sum('total')),
                'status' => $o->status, 'date' => $o->created_at?->format('M d, Y'),
            ])->values(),
            'topProducts' => $topProducts->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'image' => $p->image_url, 'sold' => (int) $p->order_items_count, 'is_active' => (bool) $p->is_active,
            ])->values(),
        ]);
    }

    public function register()
    {
        if (Auth::user()->vendor) {
            return redirect()->route('vendor.dashboard');
        }
        return Inertia::render('Vendor/Register', [
            'commission' => (float) setting('vendor.default_commission', 10),
            'email' => Auth::user()->email,
        ]);
    }

    public function storeRegistration(Request $request)
    {
        // One store per account (asked of the database, not a possibly cached relation)
        if (\App\Models\Vendor::where('user_id', Auth::id())->exists()) {
            return redirect()->route('vendor.dashboard');
        }

        $request->validate([
            'store_name' => 'required|string|max:255|unique:vendors,store_name',
            'description' => 'nullable|string',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'nullable|string',
            'country' => 'required|string',
            'zip_code' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:4096',
        ]);

        $data = $request->only([
            'store_name', 'description', 'phone', 'address', 
            'city', 'state', 'country', 'zip_code'
        ]);
        
        $data['user_id'] = Auth::id();
        // Store names are unique but their slugs may not be ("A & B" / "A B"), so add a suffix when taken.
        $base = \Str::slug($request->store_name) ?: 'store';
        $slug = $base;
        for ($i = 2; \App\Models\Vendor::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }
        $data['slug'] = $slug;
        $data['email'] = Auth::user()->email;
        $data['status'] = 'pending';
        $data['commission_rate'] = setting('vendor.default_commission', 10);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('vendors/logos', 'public');
        }

        if ($request->hasFile('banner')) {
            $data['banner'] = $request->file('banner')->store('vendors/banners', 'public');
        }

        \App\Models\Vendor::create($data);

        return redirect()->route('vendor.dashboard')
            ->with('success', 'Your vendor application has been submitted. We will review it shortly.');
    }
}

