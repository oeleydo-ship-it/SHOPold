<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistics
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total');
        $totalOrders = Order::count();
        $totalProducts = Product::count();
        $totalCustomers = User::where('is_admin', false)->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $lowStockProducts = Product::where('quantity', '<=', 5)->count();

        // Recent orders
        $recentOrders = Order::with('user')->latest()->take(10)->get();

        // Monthly revenue chart data
        $monthlyRevenue = Order::where('payment_status', 'paid')
            ->whereYear('created_at', now()->year)
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('month')
            ->pluck('revenue', 'month')
            ->toArray();

        // Fill missing months with 0
        $revenueData = [];
        for ($i = 1; $i <= 12; $i++) {
            $revenueData[] = $monthlyRevenue[$i] ?? 0;
        }

        // Order status distribution
        $orderStatusData = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Top selling products
        $topProducts = Product::withCount('orderItems')
            ->orderByDesc('order_items_count')
            ->take(5)
            ->get();

        // Recent reviews
        $recentReviews = Review::with(['user', 'product'])->latest()->take(5)->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'revenue' => format_currency($totalRevenue),
                'orders' => $totalOrders,
                'products' => $totalProducts,
                'customers' => $totalCustomers,
                'pendingOrders' => $pendingOrders,
                'lowStock' => $lowStockProducts,
            ],
            'revenueData' => $revenueData,
            'orderStatusData' => $orderStatusData,
            'recentOrders' => $recentOrders->map(fn ($o) => [
                'id' => $o->id,
                'number' => $o->order_number,
                'customer' => $o->user?->name,
                'status' => $o->status,
                'total' => format_currency($o->total),
                'date' => $o->created_at->format('M d, Y'),
                'url' => route('admin.orders.show', $o),
            ]),
            'topProducts' => $topProducts->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sold' => $p->order_items_count,
                'price' => format_currency($p->current_price),
            ]),
            'recentReviews' => $recentReviews->map(fn ($r) => [
                'id' => $r->id,
                'user' => $r->user?->name,
                'product' => $r->product?->name,
                'rating' => $r->rating,
                'comment' => \Illuminate\Support\Str::limit((string) ($r->comment ?? ''), 90),
            ]),
        ]);
    }
}
