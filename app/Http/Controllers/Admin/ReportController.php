<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Sales overview dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // days
        $startDate = Carbon::now()->subDays($period);
        
        // Revenue stats
        $totalRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->sum('total');
            
        $previousRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate->copy()->subDays($period), $startDate])
            ->sum('total');
            
        $revenueChange = $previousRevenue > 0 
            ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1) 
            : 100;

        // Order stats
        $totalOrders = Order::where('created_at', '>=', $startDate)->count();
        $previousOrders = Order::whereBetween('created_at', [$startDate->copy()->subDays($period), $startDate])->count();
        $ordersChange = $previousOrders > 0 
            ? round((($totalOrders - $previousOrders) / $previousOrders) * 100, 1) 
            : 100;

        // Average order value
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // New customers
        $newCustomers = User::where('is_admin', false)
            ->where('created_at', '>=', $startDate)
            ->count();

        // Daily revenue chart data
        $dailyRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top selling products
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', '!=', 'cancelled')
            ->select('products.id', 'products.name', 'products.image',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.quantity * order_items.price) as revenue'))
            ->groupBy('products.id', 'products.name', 'products.image')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        // Orders by status
        $ordersByStatus = Order::where('created_at', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Top categories
        $topCategories = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.created_at', '>=', $startDate)
            ->where('orders.status', '!=', 'cancelled')
            ->select('categories.name',
                DB::raw('SUM(order_items.quantity * order_items.price) as revenue'))
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Recent orders
        $recentOrders = Order::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Reports/Index', [
            'period' => (string) $period,
            'stats' => [
                'revenue' => format_currency($totalRevenue),
                'revenueChange' => $revenueChange,
                'orders' => $totalOrders,
                'ordersChange' => $ordersChange,
                'avgOrderValue' => format_currency($avgOrderValue),
                'newCustomers' => $newCustomers,
            ],
            'daily' => $dailyRevenue->map(fn ($d) => [
                'date' => Carbon::parse($d->date)->format('M d'),
                'revenue' => (float) $d->revenue,
                'orders' => (int) $d->orders,
            ])->values(),
            'topProducts' => $topProducts->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'image' => $p->image ? asset('storage/' . $p->image) : null,
                'sold' => (int) $p->total_sold,
                'revenue' => format_currency($p->revenue),
            ])->values(),
            'ordersByStatus' => (object) $ordersByStatus->toArray(),
            'topCategories' => $topCategories->map(fn ($c) => [
                'name' => $c->name,
                'revenue' => format_currency($c->revenue),
                'raw' => (float) $c->revenue,
            ])->values(),
            'recentOrders' => $recentOrders->map(fn ($o) => [
                'id' => $o->id,
                'number' => $o->order_number,
                'customer' => $o->user?->name,
                'status' => $o->status,
                'payment_status' => $o->payment_status,
                'total' => format_currency($o->total),
                'date' => $o->created_at->format('M d, Y'),
            ])->values(),
        ]);
    }

    /**
     * Sales report
     */
    public function sales(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        $orders = Order::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->where('status', '!=', 'cancelled')
            ->with('user')
            ->latest()
            ->paginate(50);

        $summary = [
            'total_orders' => Order::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                ->where('status', '!=', 'cancelled')->count(),
            'total_revenue' => Order::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                ->where('payment_status', 'paid')->sum('total'),
            'total_tax' => Order::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                ->where('payment_status', 'paid')->sum('tax'),
            'total_shipping' => Order::whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
                ->where('payment_status', 'paid')->sum('shipping_cost'),
        ];

        return Inertia::render('Admin/Reports/Sales', [
            'orders' => $orders->withQueryString()->through(fn ($o) => [
                'id' => $o->id,
                'number' => $o->order_number,
                'customer' => $o->user?->name,
                'status' => $o->status,
                'payment_status' => $o->payment_status,
                'subtotal' => format_currency($o->subtotal),
                'tax' => format_currency($o->tax),
                'total' => format_currency($o->total),
                'date' => $o->created_at->format('M d, Y'),
            ]),
            'summary' => [
                'orders' => number_format($summary['total_orders']),
                'revenue' => format_currency($summary['total_revenue']),
                'tax' => format_currency($summary['total_tax']),
                'shipping' => format_currency($summary['total_shipping']),
            ],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Products report
     */
    public function products(Request $request)
    {
        $products = Product::withCount(['orderItems as total_sold' => function ($query) {
                $query->select(DB::raw('COALESCE(SUM(quantity), 0)'));
            }])
            ->withSum(['orderItems as total_revenue' => function ($query) {
                $query->select(DB::raw('COALESCE(SUM(quantity * price), 0)'));
            }], 'price')
            ->orderByDesc('total_sold')
            ->paginate(50);

        $lowStock = Product::where('quantity', '<=', 5)
            ->where('quantity', '>', 0)
            ->orderBy('quantity')
            ->get();

        $outOfStock = Product::where('quantity', 0)->get();

        $item = fn ($p) => ['id' => $p->id, 'name' => $p->name, 'quantity' => $p->quantity];

        return Inertia::render('Admin/Reports/Products', [
            'products' => $products->through(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'image' => $p->image ? asset('storage/' . $p->image) : null,
                'quantity' => $p->quantity,
                'sold' => $p->total_sold ?? 0,
                'revenue' => format_currency($p->total_revenue ?? 0),
            ]),
            'lowStock' => $lowStock->map($item)->values(),
            'outOfStock' => $outOfStock->map($item)->values(),
        ]);
    }

    /**
     * Customers report
     */
    public function customers(Request $request)
    {
        $customers = User::where('is_admin', false)
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total')
            ->orderByDesc('total_spent')
            ->paginate(50);

        $newCustomers = User::where('is_admin', false)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        $returningCustomers = User::where('is_admin', false)
            ->has('orders', '>', 1)
            ->count();

        return Inertia::render('Admin/Reports/Customers', [
            'customers' => $customers->through(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'orders_count' => $c->orders_count,
                'total_spent' => format_currency($c->total_spent ?? 0),
                'joined' => $c->created_at->format('M d, Y'),
            ]),
            'total' => number_format($customers->total()),
            'newCustomers' => number_format($newCustomers),
            'returningCustomers' => number_format($returningCustomers),
        ]);
    }
}
